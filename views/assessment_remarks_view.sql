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
-- Name: assessment_remarks_view; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW assessment_remarks_view AS
 SELECT ar.prjtg_id,
    ar.contestant,
    ar.judge,
    j.achternaam AS jachternaam,
    j.roepnaam AS jroepnaam,
    c.achternaam AS cachternaam,
    c.roepnaam AS croepnaam,
    ((((j.roepnaam)::text || COALESCE((' '::text || (j.tussenvoegsel)::text), ''::text)) || ' '::text) || (j.achternaam)::text) AS jname,
    ((((c.roepnaam)::text || COALESCE((' '::text || (c.tussenvoegsel)::text), ''::text)) || ' '::text) || (c.achternaam)::text) AS cname,
    ar.remark
   FROM ((assessment_remarks ar
     JOIN student j ON ((j.snummer = ar.judge)))
     JOIN student c ON ((c.snummer = ar.contestant)));


ALTER TABLE assessment_remarks_view OWNER TO hom;

--
-- Name: assessment_remarks_view; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE assessment_remarks_view TO peerweb;


--
-- PostgreSQL database dump complete
--

