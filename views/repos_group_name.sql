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
-- Name: repos_group_name; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW repos_group_name AS
 SELECT pm.prj_id,
    pt.tutor_id AS owner,
    pm.milestone,
    pt.grp_num,
    pt.prjm_id,
    pt.prjtg_id,
    btrim((COALESCE(pt.grp_name, (('g'::text || pt.grp_num))::character varying))::text) AS group_name
   FROM (prj_tutor pt
     JOIN prj_milestone pm USING (prjm_id));


ALTER TABLE repos_group_name OWNER TO hom;

--
-- Name: VIEW repos_group_name; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW repos_group_name IS 'used to create repository entries';


--
-- Name: repos_group_name; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE repos_group_name TO peerweb;
GRANT ALL ON TABLE repos_group_name TO wwwrun;


--
-- PostgreSQL database dump complete
--

