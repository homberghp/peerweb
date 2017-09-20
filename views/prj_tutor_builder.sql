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
-- Name: prj_tutor_builder; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW prj_tutor_builder AS
 SELECT pm.prj_id,
    t.tutor,
    pt.tutor_id,
    pm.milestone,
    pt.grp_num,
    pm.prjm_id,
    pt.prjtg_id,
    pt.grp_name
   FROM ((prj_milestone pm
     JOIN prj_tutor pt USING (prjm_id))
     JOIN tutor t ON ((t.userid = pt.tutor_id)));


ALTER TABLE prj_tutor_builder OWNER TO hom;

--
-- Name: prj_tutor_builder; Type: ACL; Schema: public; Owner: hom
--

GRANT ALL ON TABLE prj_tutor_builder TO peerweb;


--
-- PostgreSQL database dump complete
--

