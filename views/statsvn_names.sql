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
-- Name: statsvn_names; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW statsvn_names AS
 SELECT ((((('user.'::text || prj_grp.snummer) || '.realName='::text) || (student.achternaam)::text) || ','::text) || (student.roepnaam)::text) AS member,
    prj_tutor.prjm_id,
    prj_tutor.grp_num
   FROM (((prj_grp
     JOIN student USING (snummer))
     JOIN prj_tutor USING (prjtg_id))
     JOIN prj_milestone USING (prjm_id));


ALTER TABLE statsvn_names OWNER TO hom;

--
-- Name: statsvn_names; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE statsvn_names TO peerweb;


--
-- PostgreSQL database dump complete
--

