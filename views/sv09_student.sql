
begin work;
drop view if exists sv09_student;

create view sv09_student as
select s.*,sc.sclass,sp.studieplan_short,sp.studieplan_omschrijving,sclass as course_class from student s
       natural left join student_class sc
       left join faculty f on (s.faculty_id=f.faculty_id)  
       left join fontys_course fc on(opl= course and s.faculty_id=fc.faculty_id)
       left join studieplan sp using(studieplan)
;
commit;
