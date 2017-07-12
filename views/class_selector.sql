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
-- Name: class_selector; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW class_selector AS
 SELECT faculty.faculty_short,
    classes.sclass,
    classes.class_id AS value,
    (((((faculty.faculty_short)::text || ' .'::text) || btrim((classes.sclass)::text)) || '#'::text) || classes.class_id) AS name
   FROM (student_class classes
     JOIN faculty ON ((faculty.faculty_id = classes.faculty_id)));


ALTER TABLE class_selector OWNER TO hom;

--
-- Name: VIEW class_selector; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW class_selector IS 'used through menu_option_query for student_admin';


--
-- Name: class_selector; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE class_selector TO peerweb;


--
-- PostgreSQL database dump complete
--

