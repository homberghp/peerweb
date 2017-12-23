
begin work;

drop view if exists all_email;
drop view if exists alien_email;
drop view if exists alumnus;
drop view if exists alu_student_mail;
drop view if exists assessment_remarks_view;
drop view if exists barchart_view;
drop view if exists bigface_view;
drop view if exists birthdays;
drop view if exists class_selector;
drop view if exists contestant_assessment;
drop view if exists current_student_class;
drop view if exists dead_class;
drop view if exists document_data3;
drop view if exists double_email;
drop view if exists double_emails;
drop view if exists exam_account;
drop view if exists examlist;
drop view if exists exam_result_view;
drop view if exists foto;
drop view if exists git_password;
drop view if exists git_project_users;
drop view if exists hoofdgrp_size;
drop view if exists hoofdgrp_s;
drop view if exists hoofdgrp;
drop view if exists judge_assessment;
drop view if exists lime_token;
drop view if exists loggedin;
drop view if exists minifoto;
drop view if exists naw;
drop view if exists portrait;
drop view if exists portrait_with_name;
drop view if exists prj_grp_email;
drop view if exists prj_grp_email_g0;
drop view if exists prj_tutor_email;
drop view if exists project_group;
drop view if exists project_tutor_owner;
drop view if exists sebiassessment_student_view;
drop view if exists statsvn_names;
drop view if exists student_class_name;
drop view if exists student_class_size;
drop view if exists class_size;
drop view if exists student_class_v;
drop view if exists student_email;
drop view if exists student_latin1;
drop view if exists student_name_email;
drop view if exists student_plus;
drop view if exists student_project_attributes;
drop view if exists student_short;
drop view if exists svn_group;
drop view if exists tiny_portrait;
drop view if exists transaction_operator;
drop view if exists tutor_data;
drop view if exists tutor_join_student;
drop view if exists unknown_student;
drop view if exists upload_archive_names;
drop view if exists import_naw;
drop view if exists all_alumni_email;
-- alter table student
--       rename column voorvoegsel to tussenvoegsel;
-- alter table student
--       rename column voornaam to voornamen;
-- alter table ingeschrevenen
--       rename column voorvoegsels to tussenvoegsel;
      
alter table student
      alter column pcode type text using pcode::text,
      alter column tussenvoegsel type text using tussenvoegsel::text,
      alter column voorletters type text using voorletters::text,
      alter column straat type text using straat::text,
      alter column plaats type text using plaats::text,
      alter column email1 type email using email1::email,
      alter column achternaam type text using achternaam::text,
      alter column roepnaam type text using roepnaam::text,
      alter column hoofdgrp type text using hoofdgrp::text,
      alter column voornamen type text using voornamen::text,
      alter column phone_home type text using phone_home::text,
      alter column phone_gsm type text using phone_gsm::text,
      alter column phone_postaddress type text using phone_postaddress::text,
      alter column geboorteplaats type text using geboorteplaats::text
      ;
alter table alt_email
      alter column email2 type email using email2::email,
      alter column email3 type email using email3::email;

alter table student_class alter column sclass type text using sclass::text;
      
\i upload_archive_names.sql
\i student_email.sql 
\i import_naw.sql
\i unknown_student.sql
\i tutor_join_student.sql
\i tutor_data.sql
\i transaction_operator.sql
\i tiny_portrait.sql
\i svn_group.sql
\i student_short.sql
\i student_project_attributes.sql
\i student_plus.sql
\i student_name_email.sql
\i student_latin1.sql
\i student_class_v.sql
\i class_size.sql
\i student_class_size.sql
\i student_class_name.sql
\i statsvn_names.sql
\i sebiassessment_student_view.sql
\i project_tutor_owner.sql
\i project_group.sql
\i prj_tutor_email.sql
\i prj_grp_email_g0.sql
\i prj_grp_email.sql
\i portrait_with_name.sql
\i portrait.sql
\i naw.sql
\i minifoto.sql
\i loggedin.sql
\i lime_token.sql
\i judge_assessment.sql
\i hoofdgrp.sql
\i hoofdgrp_s.sql
\i hoofdgrp_size.sql
\i git_project_users.sql
\i git_password.sql
\i foto.sql
\i exam_result_view.sql
\i examlist.sql
\i exam_account.sql
\i double_emails.sql
\i double_email.sql
\i document_data3.sql
\i dead_class.sql
\i current_student_class.sql
\i contestant_assessment.sql
\i birthdays.sql
\i bigface_view.sql
\i barchart_view.sql
\i assessment_remarks_view.sql
\i alu_student_mail.sql
\i alumnus.sql
\i alien_email.sql
\i all_alumni_email.sql
\i all_email.sql
\i class_selector.sql
commit;
