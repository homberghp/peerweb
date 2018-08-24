select *,pcn_nummer||'@'||'student.fontys.nl' as fontys_login from importer.sv05_aanmelders order by course_grp,achternaam;
