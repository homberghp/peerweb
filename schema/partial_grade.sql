begin work;
create table if not exists partial_grade_event
       (
       partial_grade_event_id serial primary key,
       event_date date,
       event_exam_code text,
       description text,
       owner integer references tutor(userid)
       
       );

create table if not exists partial_grade (
       partial_grade_event_id integer references  partial_grade_event(partial_grade_event_id),
       partial_grade_id serial,
       committer integer references tutor(userid),
       candidate integer references student(snummer),
       grade numeric
);

commit;
