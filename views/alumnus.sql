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
-- Name: alumnus; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW alumnus AS
 SELECT s.snummer
   FROM (student s
     JOIN student_class c USING (class_id))
  WHERE (c.sclass ~~ ('ALUMN%'::bpchar)::text);


ALTER TABLE alumnus OWNER TO hom;

--
-- Name: VIEW alumnus; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW alumnus IS 'alumni are in student_class ALUMNI';


--
-- Name: alumnus; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE alumnus TO peerweb;


--
-- PostgreSQL database dump complete
--

