begin work;

drop view if exists exam_result_view;
create view exam_result_view as
       select snummer,achternaam,roepnaam, cohort,
       exam_date,module_part.progress_code,exam_event_id,
       grade
       from exam_grades join exam_event using (exam_event_id)
       join module_part using(module_part_id)
       join student using(snummer)
       ;

commit;
       
