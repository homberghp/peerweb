begin work;
-- drop table if exists tutor_selector cascade;
-- create table tutor_selector(
--        opl bigint,faculty_id smallint,
--        mine smallint,
--        namegrp text,name text, tutor varchar(5),userid integer
-- );
-- comment on table tutor_selector is 'result type for tutor_selector(userid)';

create or replace function tutor_selector(vuserid integer) returns  setof tutor_selector  as
$tutor_selector$
begin return query
select s.opl,s.faculty_id,
       case
       when me.opl=s.opl and me.faculty_id=s.faculty_id then 0::smallint
       when me.opl<>s.opl and me.faculty_id=s.faculty_id then 1::smallint
       else 2::smallint end as mine,
       faculty_short||'-'||course_short as namegrp,
       achternaam||', '||roepnaam||coalesce(' '||tussenvoegsel,'')||' ['||tutor||': '||t.userid||']' as name,tutor,userid
       from tutor t
       join student s on(t.userid=s.snummer)
       join faculty f on (f.faculty_id=s.faculty_id)
       join fontys_course c on(s.opl=c.course)
       cross join (select opl,faculty_id
       from student where snummer=vuserid) me
;
end
$tutor_selector$ language 'plpgsql' security definer;

commit;
