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
-- Name: tutor_upload_count; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW tutor_upload_count AS
 SELECT pm.prj_id,
    pm.milestone,
    t.tutor,
    pt.tutor_id,
    count(u.upload_id) AS doc_count
   FROM ((((uploads u
     JOIN prj_grp pg ON (((pg.prjtg_id = u.prjtg_id) AND (pg.snummer = u.snummer))))
     JOIN prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN tutor t ON ((pt.tutor_id = t.userid)))
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
  GROUP BY pm.prj_id, pm.milestone, t.tutor, pt.tutor_id;


ALTER TABLE tutor_upload_count OWNER TO hom;

--
-- Name: tutor_upload_count; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE tutor_upload_count TO peerweb;


--
-- PostgreSQL database dump complete
--

