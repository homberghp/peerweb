--
-- PostgreSQL database dump
--

-- Dumped from database version 9.6.3
-- Dumped by pg_dump version 9.6.3

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET row_security = off;

SET search_path = public, pg_catalog;

--
-- Name: exam_result_view; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW exam_result_view AS
 SELECT exam_grades.snummer,
    student.achternaam,
    student.roepnaam,
    student.cohort,
    exam_event.exam_date,
    module_part.progress_code,
    exam_grades.exam_event_id,
    exam_grades.grade
   FROM (((exam_grades
     JOIN exam_event USING (exam_event_id))
     JOIN module_part USING (module_part_id))
     JOIN student USING (snummer));


ALTER TABLE exam_result_view OWNER TO hom;

--
-- Name: exam_result_view; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,REFERENCES ON TABLE exam_result_view TO peerweb;


--
-- PostgreSQL database dump complete
--

