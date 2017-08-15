-- upsert  data from schema.table importer.sv09_as_student_email_v tio student_email_sync
-- securrity and prevent overwrite of fields slb and class_id
begin work;
insert into student_email_sync select * from importer.sv09_as_student_email_v ;
-- drop all records.
truncate importer.sv09_ingeschrevenen ;
commit;

