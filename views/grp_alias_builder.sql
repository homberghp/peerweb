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
-- Name: grp_alias_builder; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW grp_alias_builder AS
 SELECT pm.prj_id,
    ga.long_name,
    pt.grp_num,
    pm.milestone,
    ga.alias,
    ga.website,
    ga.productname,
    pm.prjm_id,
    pt.prjtg_id
   FROM ((prj_milestone pm
     JOIN prj_tutor pt ON ((pm.prjm_id = pt.prjm_id)))
     LEFT JOIN grp_alias ga ON ((ga.prjtg_id = pt.prjtg_id)));


ALTER TABLE grp_alias_builder OWNER TO hom;

--
-- Name: VIEW grp_alias_builder; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW grp_alias_builder IS 'used for duplication of project groups -> grp_aliases';


--
-- Name: grp_alias_builder; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE grp_alias_builder TO peerweb;


--
-- PostgreSQL database dump complete
--

