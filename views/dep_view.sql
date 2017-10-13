WITH RECURSIVE dep_recursive AS (

    -- Recursion: Initial Query
    SELECT
        0 AS "level",
        'enter_object_name' AS "dep_name",   --  <- define dependent object HERE
        '' AS "dep_table",
        '' AS "dep_type",
        '' AS "ref_name",
        '' AS "ref_type"

    UNION ALL

    -- Recursive Query
    SELECT
        level + 1 AS "level",
        depedencies.dep_name,
        depedencies.dep_table,
        depedencies.dep_type,
        depedencies.ref_name,
        depedencies.ref_type
    FROM (

        -- This function defines the type of any pg_class object
        WITH classType AS (
            SELECT
                oid,
                CASE relkind
                    WHEN 'r' THEN 'TABLE'::text
                    WHEN 'i' THEN 'INDEX'::text
                    WHEN 'S' THEN 'SEQUENCE'::text
                    WHEN 'v' THEN 'VIEW'::text
                    WHEN 'c' THEN 'TYPE'::text      -- note: COMPOSITE type
                    WHEN 'm' THEN 'MATERIALIZED VIEW'::text
                    WHEN 't' THEN 'TABLE'::text     -- note: TOAST table
                END AS "type"
            FROM pg_class
        )

        -- Note: In pg_depend, the triple (classid,objid,objsubid) describes some object that depends
        -- on the object described by the tuple (refclassid,refobjid).
        -- So to drop the depending object, the referenced object (refclassid,refobjid) must be dropped first
        SELECT DISTINCT
            -- dep_name: Name of dependent object
            CASE classid
                WHEN 'pg_class'::regclass THEN objid::regclass::text
                WHEN 'pg_type'::regclass THEN objid::regtype::text
                WHEN 'pg_proc'::regclass THEN objid::regprocedure::text
                WHEN 'pg_constraint'::regclass THEN (SELECT conname FROM pg_constraint WHERE OID = objid)
                WHEN 'pg_attrdef'::regclass THEN 'default'
                WHEN 'pg_rewrite'::regclass THEN (SELECT ev_class::regclass::text FROM pg_rewrite WHERE OID = objid)
                WHEN 'pg_trigger'::regclass THEN (SELECT tgname FROM pg_trigger WHERE OID = objid)
                ELSE objid::text 
            END AS "dep_name",
            -- dep_table: Name of the table that is associated with the dependent object (for default values, triggers, rewrite rules)
            CASE classid
                WHEN 'pg_constraint'::regclass THEN (SELECT conrelid::regclass::text FROM pg_constraint WHERE OID = objid)
                WHEN 'pg_attrdef'::regclass THEN (SELECT adrelid::regclass::text FROM pg_attrdef WHERE OID = objid)
                WHEN 'pg_trigger'::regclass THEN (SELECT tgrelid::regclass::text FROM pg_trigger WHERE OID = objid)
                ELSE ''
            END AS "dep_table",
            -- dep_type: Type of the dependent object (TABLE, FUNCTION, VIEW, TYPE, TRIGGER, ...)
            CASE classid
                WHEN 'pg_class'::regclass THEN (SELECT TYPE FROM classType WHERE OID = objid)
                WHEN 'pg_type'::regclass THEN 'TYPE'
                WHEN 'pg_proc'::regclass THEN 'FUNCTION'
                WHEN 'pg_constraint'::regclass THEN 'TABLE CONSTRAINT'
                WHEN 'pg_attrdef'::regclass THEN 'TABLE DEFAULT'
                WHEN 'pg_rewrite'::regclass THEN (SELECT TYPE FROM classType WHERE OID = (SELECT ev_class FROM pg_rewrite WHERE OID = objid))
                WHEN 'pg_trigger'::regclass THEN 'TRIGGER'
                ELSE objid::text
            END AS "dep_type",
            -- ref_name: Name of referenced object (the object that depends on the dependent object)
            CASE refclassid
                WHEN 'pg_class'::regclass THEN refobjid::regclass::text
                WHEN 'pg_type'::regclass THEN refobjid::regtype::text
                WHEN 'pg_proc'::regclass THEN refobjid::regprocedure::text
                ELSE refobjid::text
            END AS "ref_name",
            -- ref_type: Type of the referenced object (TABLE, FUNCTION, VIEW, TYPE, TRIGGER, ...)
            CASE refclassid
                WHEN 'pg_class'::regclass THEN (SELECT TYPE FROM classType WHERE OID = refobjid)
                WHEN 'pg_type'::regclass THEN 'TYPE'
                WHEN 'pg_proc'::regclass THEN 'FUNCTION'
                ELSE refobjid::text
            END AS "ref_type",
            -- dependency type: Only 'normal' dependencies are relevant for DROP statements
            CASE deptype
                WHEN 'n' THEN 'normal'
                WHEN 'a' THEN 'automatic'
                WHEN 'i' THEN 'internal'
                WHEN 'e' THEN 'extension'
                WHEN 'p' THEN 'pinned'
            END AS "dependency type"
        FROM pg_catalog.pg_depend
        WHERE deptype = 'n'                 -- look at normal dependencies only
        AND refclassid NOT IN (2615, 2612)  -- schema and language are ignored as dependencies

    ) depedencies
    -- Recursion: Join with results of last query, search for dependencies recursively
    JOIN dep_recursive ON (dep_recursive.dep_name = depedencies.ref_name)
    WHERE depedencies.ref_name NOT IN(depedencies.dep_name, depedencies.dep_table) -- no self-references

)

-- Select and filter the results of the recursive query
SELECT
    MAX(level) AS "level",          -- drop highest level first, so no other objects depend on it
    dep_name,                       -- the object to drop
    MIN(dep_table) AS "dep_table",  -- the table that is associated with this object (constraints, triggers)
    MIN(dep_type) AS "dep_type",    -- the type of this object
    string_agg(ref_name, ', ') AS "ref_names",   -- list of objects that depend on this (just FYI)
    string_agg(ref_type, ', ') AS "ref_types"    -- list of their respective types (just FYI)
FROM dep_recursive
WHERE level > 0                  -- ignore the initial object (level 0)
GROUP BY dep_name                -- ignore multiple references to dependent objects, dropping them once is enough
ORDER BY level desc, dep_name;   -- level descending: deepest dependency firstWITH RECURSIVE dep_recursive AS (
