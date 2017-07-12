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
-- Name: my_project_repositories; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW my_project_repositories AS
 SELECT pm.prj_id,
    (pm.milestone)::integer AS milestone,
    pg.snummer,
    r.grp_num,
    r.description,
    r.url_tail,
    r.id AS repo_id
   FROM (((repositories r
     JOIN prj_tutor pt ON (((r.prjm_id = pt.prjm_id) AND (r.grp_num = pt.grp_num))))
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
     JOIN prj_grp pg ON ((pt.prjtg_id = pg.prjtg_id)))
UNION
 SELECT pm.prj_id,
    pm.milestone,
    pg.snummer,
    0 AS grp_num,
    r.description,
    r.url_tail,
    r.id AS repo_id
   FROM (((repositories r
     JOIN prj_tutor pt ON (((r.prjm_id = pt.prjm_id) AND (r.grp_num = 0))))
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
     JOIN prj_grp pg ON ((pt.prjtg_id = pg.prjtg_id)));


ALTER TABLE my_project_repositories OWNER TO hom;

--
-- Name: my_project_repositories; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE my_project_repositories TO peerweb;


--
-- PostgreSQL database dump complete
--

