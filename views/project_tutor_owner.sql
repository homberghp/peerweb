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
-- Name: project_tutor_owner; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW project_tutor_owner AS
 SELECT s.roepnaam,
    s.tussenvoegsel,
    s.achternaam,
    s.email1,
    s.snummer,
    p.prj_id
   FROM (project p
     JOIN student s ON ((p.owner_id = s.snummer)));


ALTER TABLE project_tutor_owner OWNER TO hom;

--
-- Name: project_tutor_owner; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE project_tutor_owner TO peerweb;


--
-- PostgreSQL database dump complete
--

