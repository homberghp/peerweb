begin work;
drop view if exists dualklasses;
create view dualklasses as
   select distinct instituutcode faculty_id, (regexp_split_to_array(lesgroep,'\s*;\s*'))[1] sclass
   from importer.sv09_ingeschrevenen where lesgroep notnull ;
         
commit;
