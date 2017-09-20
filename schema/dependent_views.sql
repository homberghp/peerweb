/**** Usage Examples ****
-- Examine the entire object hierarchy
SELECT report.dependency_tree('');

-- Dependencies for any relations with names containing match (in regular expression)
SELECT report.dependency_tree('match');

-- Dependencies for relations person & address
SELECT report.dependency_tree('{person,address}'::text[]);

-- Dependencies for function slice
SELECT report.dependency_tree(ARRAY['slice'::regproc]);

-- Dependencies for type hstore
SELECT report.dependency_tree(ARRAY['hstore'::regtype]);

-- Dependencies for triggers by the name updated
SELECT report.dependency_tree(ARRAY(
  SELECT oid FROM pg_trigger WHERE tgname ~ 'updated'
  ));

-- Dependencies for foreign key constraint names starting with product
SELECT report.dependency_tree(ARRAY(
  SELECT oid FROM pg_constraint
  WHERE conname ~ '^product.*_fk'
  ));
*/
create schema if not exists report;
DROP VIEW IF EXISTS report.dependency;
CREATE OR REPLACE VIEW report.dependency AS
WITH RECURSIVE preference AS (
  SELECT 10 AS max_depth
    , 16384 AS min_oid -- user objects only
    , '^(londiste|pgq|pg_toast)'::text AS schema_exclusion
    , '^pg_(conversion|language|ts_(dict|template))'::text AS class_exclusion
    , '{"SCHEMA":"00", "TABLE":"01", "TABLE CONSTRAINT":"02", "DEFAULT VALUE":"03",
        "INDEX":"05", "SEQUENCE":"06", "TRIGGER":"07", "FUNCTION":"08",
        "VIEW":"10", "MATERIALIZED VIEW":"11", "FOREIGN TABLE":"12"}'::json AS type_sort_orders
)
, dependency_pair AS (
    SELECT objid
      , array_agg(objsubid ORDER BY objsubid) AS objsubids
      , upper(obj.type) AS object_type
      , coalesce(obj.schema, substring(obj.identity, E'(\\w+?)\\.'), '') AS object_schema
      , obj.name AS object_name
      , obj.identity AS object_identity
      , refobjid
      , array_agg(refobjsubid ORDER BY refobjsubid) AS refobjsubids
      , upper(refobj.type) AS refobj_type
      , coalesce(CASE WHEN refobj.type='schema' THEN refobj.identity
                                                ELSE refobj.schema END
          , substring(refobj.identity, E'(\\w+?)\\.'), '') AS refobj_schema
      , refobj.name AS refobj_name
      , refobj.identity AS refobj_identity
      , CASE deptype
            WHEN 'n' THEN 'normal'
            WHEN 'a' THEN 'automatic'
            WHEN 'i' THEN 'internal'
            WHEN 'e' THEN 'extension'
            WHEN 'p' THEN 'pinned'
        END AS dependency_type
    FROM pg_depend dep
      , LATERAL pg_identify_object(classid, objid, 0) AS obj
      , LATERAL pg_identify_object(refclassid, refobjid, 0) AS refobj
      , preference
    WHERE deptype = ANY('{n,a}')
    AND objid >= preference.min_oid
    AND (refobjid >= preference.min_oid OR refobjid = 2200) -- need public schema as root node
    AND coalesce(obj.schema, substring(obj.identity, E'(\\w+?)\\.'), '') !~ preference.schema_exclusion
    AND coalesce(CASE WHEN refobj.type='schema' THEN refobj.identity
                                                ELSE refobj.schema END
          , substring(refobj.identity, E'(\\w+?)\\.'), '') !~ preference.schema_exclusion
    GROUP BY objid, obj.type, obj.schema, obj.name, obj.identity
      , refobjid, refobj.type, refobj.schema, refobj.name, refobj.identity, deptype
)
, dependency_hierarchy AS (
    SELECT DISTINCT
        0 AS level,
        refobjid AS objid,
        refobj_type AS object_type,
        refobj_identity AS object_identity,
        --refobjsubids AS objsubids,
        NULL::text AS dependency_type,
        ARRAY[refobjid] AS dependency_chain,
        ARRAY[concat(preference.type_sort_orders->>refobj_type,refobj_type,':',refobj_identity)] AS dependency_sort_chain
    FROM dependency_pair root
    , preference
    WHERE NOT EXISTS
       (SELECT 'x' FROM dependency_pair branch WHERE branch.objid = root.refobjid)
    AND refobj_schema !~ preference.schema_exclusion
    UNION ALL
    SELECT
        level + 1 AS level,
        child.objid,
        child.object_type,
        child.object_identity,
        --child.objsubids,
        child.dependency_type,
        parent.dependency_chain || child.objid,
        parent.dependency_sort_chain || concat(preference.type_sort_orders->>child.object_type,child.object_type,':',child.object_identity)
    FROM dependency_pair child
    JOIN dependency_hierarchy parent ON (parent.objid = child.refobjid)
    , preference
    WHERE level < preference.max_depth
    AND child.object_schema !~ preference.schema_exclusion
    AND child.refobj_schema !~ preference.schema_exclusion
    AND NOT (child.objid = ANY(parent.dependency_chain)) -- prevent circular referencing
)
SELECT * FROM dependency_hierarchy
ORDER BY dependency_chain ;

-- Procedure to report depedency tree using regexp search pattern (relation-only)
CREATE OR REPLACE FUNCTION report.dependency_tree(search_pattern text)
  RETURNS TABLE(dependency_tree text)
  SECURITY DEFINER LANGUAGE SQL
  AS $function$
WITH target AS (
  SELECT objid, dependency_chain
  FROM report.dependency
  WHERE object_identity ~ search_pattern
)
, list AS (
  SELECT
    format('%*s%s %s', -4*level
          , CASE WHEN object_identity ~ search_pattern THEN '*' END
          , object_type, object_identity
    ) AS dependency_tree
  , dependency_sort_chain
  FROM target
  JOIN report.dependency report
    ON report.objid = ANY(target.dependency_chain) -- root-bound chain
    OR target.objid = ANY(report.dependency_chain) -- leaf-bound chain
  WHERE length(search_pattern) > 0
  -- Do NOT waste search time on blank/null search_pattern.
  UNION
  -- Query the entire dependencies instead.
  SELECT
    format('%*s%s %s', 4*level, '', object_type, object_identity) AS depedency_tree
  , dependency_sort_chain
  FROM report.dependency
  WHERE length(coalesce(search_pattern,'')) = 0
)
SELECT dependency_tree FROM list
ORDER BY dependency_sort_chain;
$function$ ;

-- Procedure to report depedency tree by specific relation name(s) (in text array)
CREATE OR REPLACE FUNCTION report.dependency_tree(object_names text[])
  RETURNS TABLE(dependency_tree text)
  SECURITY DEFINER LANGUAGE SQL
  AS $function$
WITH target AS (
  SELECT objid, dependency_chain
  FROM report.dependency
  JOIN unnest(object_names) AS target(objname) ON objid = objname::regclass
)
, list AS (
  SELECT DISTINCT
    format('%*s%s %s', -4*level
          , CASE WHEN object_identity = ANY(object_names) THEN '*' END
          , object_type, object_identity
    ) AS dependency_tree
  , dependency_sort_chain
  FROM target
  JOIN report.dependency report
    ON report.objid = ANY(target.dependency_chain) -- root-bound chain
    OR target.objid = ANY(report.dependency_chain) -- leaf-bound chain
)
SELECT dependency_tree FROM list
ORDER BY dependency_sort_chain;
$function$ ;

-- Procedure to report depedency tree by oid
CREATE OR REPLACE FUNCTION report.dependency_tree(object_ids oid[])
  RETURNS TABLE(dependency_tree text)
  SECURITY DEFINER LANGUAGE SQL
  AS $function$
WITH target AS (
  SELECT objid, dependency_chain
  FROM report.dependency
  JOIN unnest(object_ids) AS target(objid) USING (objid)
)
, list AS (
  SELECT DISTINCT
    format('%*s%s %s', -4*level
          , CASE WHEN report.objid = ANY(object_ids) THEN '*' END
          , object_type, object_identity
    ) AS dependency_tree
  , dependency_sort_chain
  FROM target
  JOIN report.dependency report
    ON report.objid = ANY(target.dependency_chain) -- root-bound chain
    OR target.objid = ANY(report.dependency_chain) -- leaf-bound chain
)
SELECT dependency_tree FROM list
ORDER BY dependency_sort_chain;
$function$ ;
