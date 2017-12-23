begin work;
drop view if exists dualklasses;
create view dualklasses as
   select distinct (regexp_split_to_array(lesgroep,'\s*;\s*'))[0] sclass
   from importer.sv09_ingeschrevenen where lesgroep notnull ;
         
commit;
