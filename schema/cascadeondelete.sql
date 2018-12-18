begin work;
ALTER TABLE "activity_participant" DROP CONSTRAINT "act_part_fk1";
ALTER TABLE "student_class" DROP CONSTRAINT "classes_owner" ;
ALTER TABLE "document_critique" DROP CONSTRAINT "critique_student_fk1";
ALTER TABLE "diploma_dates" DROP CONSTRAINT "diploma_dates_fk1";
ALTER TABLE "exam_grades" DROP CONSTRAINT "exam_grades_snummer_fkey";
ALTER TABLE "sebi_stick" DROP CONSTRAINT "exam_stick_snummer_fkey";
ALTER TABLE "github_id" DROP CONSTRAINT "github_id_snummer_fkey" ;
ALTER TABLE "meeloopmail" DROP CONSTRAINT "meeloopmail_owner_fkey";
ALTER TABLE "personal_repos" DROP CONSTRAINT "personal_repos_fk1" ;
ALTER TABLE "project_attributes_def" DROP CONSTRAINT "project_attributes_def_author_fkey" ;

ALTER TABLE "any_query" DROP CONSTRAINT "any_query_owner_fkey" ;
ALTER TABLE "exam_event" DROP CONSTRAINT "exam_event_examiner_fkey" ;
ALTER TABLE "prj_tutor" DROP CONSTRAINT "prj_tutor_tutor_id_fkey" ;
ALTER TABLE "project" DROP CONSTRAINT "project_owner_id_fkey" ;
ALTER TABLE "student" DROP CONSTRAINT "student_slb_fk" ;
ALTER TABLE "tutor_class_cluster" DROP CONSTRAINT "tutor_class_cluster_userid_fkey" ;
alter TABLE "critique_history" DROP CONSTRAINT "critique_history_fk1";

ALTER TABLE "activity_participant" ADD CONSTRAINT "act_part_fk1" FOREIGN KEY (snummer) REFERENCES student(snummer) ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE "student_class" ADD CONSTRAINT "classes_owner" FOREIGN KEY (owner) REFERENCES student(snummer) ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE "document_critique" ADD CONSTRAINT "critique_student_fk1" FOREIGN KEY (critiquer) REFERENCES student(snummer) ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE "diploma_dates" ADD CONSTRAINT "diploma_dates_fk1" FOREIGN KEY (snummer) REFERENCES student(snummer) ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE "exam_grades" ADD CONSTRAINT "exam_grades_snummer_fkey" FOREIGN KEY (snummer) REFERENCES student(snummer) ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE "sebi_stick" ADD CONSTRAINT "exam_stick_snummer_fkey" FOREIGN KEY (snummer) REFERENCES student(snummer) ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE "github_id" ADD CONSTRAINT "github_id_snummer_fkey" FOREIGN KEY (snummer) REFERENCES student(snummer)ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE "meeloopmail" ADD CONSTRAINT "meeloopmail_owner_fkey" FOREIGN KEY (owner) REFERENCES student(snummer)ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE "personal_repos" ADD CONSTRAINT "personal_repos_fk1" FOREIGN KEY (owner) REFERENCES student(snummer) ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE "project_attributes_def" ADD CONSTRAINT "project_attributes_def_author_fkey" FOREIGN KEY (author) REFERENCES student(snummer) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE "any_query" ADD CONSTRAINT "any_query_owner_fkey" FOREIGN KEY (owner) REFERENCES tutor(userid) ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE "exam_event" ADD CONSTRAINT "exam_event_examiner_fkey" FOREIGN KEY (examiner) REFERENCES tutor(userid) ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE "prj_tutor" ADD CONSTRAINT "prj_tutor_tutor_id_fkey" FOREIGN KEY (tutor_id) REFERENCES tutor(userid) ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE "project" ADD CONSTRAINT "project_owner_id_fkey" FOREIGN KEY (owner_id) REFERENCES tutor(userid) ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE "student" ADD CONSTRAINT "student_slb_fk" FOREIGN KEY (slb) REFERENCES tutor(userid) ON UPDATE CASCADE ON DELETE CASCADE ;
ALTER TABLE "tutor_class_cluster" ADD CONSTRAINT "tutor_class_cluster_userid_fkey" FOREIGN KEY (userid) REFERENCES tutor(userid) ON UPDATE CASCADE ON DELETE CASCADE;
alter TABLE "critique_history" ADD CONSTRAINT "critique_history_fk1" FOREIGN KEY (critique_id) REFERENCES document_critique(critique_id) ON UPDATE CASCADE ON DELETE CASCADE;

commit;


