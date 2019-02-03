begin work;
--
-- Name: sclass_selector(integer); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE or replace FUNCTION sclass_selector(vuserid integer) RETURNS SETOF sclass_selector
    LANGUAGE plpgsql security definer
    AS $class_selector$
begin return query
select scl.faculty_id,
       case
       when me.class_cluster=scl.class_cluster and me.faculty_id=scl.faculty_id then 0::smallint
       when me.class_cluster<>scl.class_cluster and me.faculty_id=scl.faculty_id then 1::smallint
       else 2::smallint end as mine,
       
       sort1,sort2,scl.class_cluster,scl.owner,f.faculty_short||'-'||cluster_name as namegrp,
       sclass||'#'||class_id||' count '||coalesce(student_count,0)  as sclass ,class_id
       from student_class scl
       join faculty f on (f.faculty_id=scl.faculty_id)
       left join class_cluster cc using(class_cluster)
       left join class_size using(class_id)
       cross join (select c.class_cluster,c.faculty_id
       from student s join student_class c using(class_id) where snummer=vuserid) me
;
end
$class_selector$;

commit;
