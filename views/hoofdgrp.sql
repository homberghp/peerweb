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
-- Name: hoofdgrp; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW hoofdgrp AS
 SELECT DISTINCT s.hoofdgrp,
    s.faculty_id,
    f.full_name,
    f.faculty_short,
    fc.course_short
   FROM ((student s
     JOIN faculty f ON ((s.faculty_id = f.faculty_id)))
     JOIN fontys_course fc ON (((s.opl = fc.course) AND (fc.faculty_id = f.faculty_id))));


ALTER TABLE hoofdgrp OWNER TO hom;

--
-- Name: hoofdgrp; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE hoofdgrp TO peerweb;


--
-- PostgreSQL database dump complete
--

