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
-- Name: tutor_data; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW tutor_data AS
 SELECT student.snummer,
    student.*::student AS student,
    student.snummer AS tutor_id,
    student.achternaam,
    student.roepnaam,
    student.tussenvoegsel,
    tutor.tutor,
    tutor.faculty_id,
    student.hoofdgrp,
    student.email1 AS tutor_email,
    faculty.faculty_short AS faculty,
    fontys_course.course_short AS opl
   FROM (((tutor
     JOIN student ON ((tutor.userid = student.snummer)))
     JOIN faculty ON ((tutor.faculty_id = faculty.faculty_id)))
     JOIN fontys_course ON ((student.opl = fontys_course.course)));


ALTER TABLE tutor_data OWNER TO hom;

--
-- Name: VIEW tutor_data; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW tutor_data IS 'data from tutor selectors in e.g. slb';


--
-- Name: tutor_data tutor_data_update; Type: RULE; Schema: public; Owner: hom
--

CREATE RULE tutor_data_update AS
    ON UPDATE TO tutor_data DO INSTEAD ( UPDATE student SET hoofdgrp = new.hoofdgrp
  WHERE (student.snummer = new.snummer);
 UPDATE tutor SET faculty_id = new.faculty_id
  WHERE (tutor.userid = new.snummer);
);


--
-- Name: tutor_data; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE tutor_data TO peerweb;


--
-- PostgreSQL database dump complete
--

