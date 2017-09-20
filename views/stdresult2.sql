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
-- Name: stdresult2; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW stdresult2 AS
 SELECT p.prjtg_id,
    a.contestant AS snummer,
    a.criterium,
    avg(a.grade) AS grade
   FROM (prj_grp p
     JOIN assessment a ON (((p.snummer = a.contestant) AND (p.prjtg_id = a.prjtg_id))))
  GROUP BY p.prjtg_id, a.criterium, a.contestant;


ALTER TABLE stdresult2 OWNER TO hom;

--
-- Name: stdresult2; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE stdresult2 TO peerweb;


--
-- PostgreSQL database dump complete
--

