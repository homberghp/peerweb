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
-- Name: project_deliverables_tr; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW project_deliverables_tr AS
 SELECT project_deliverables.prjm_id,
    project_deliverables.doctype,
    project_deliverables.version_limit,
    project_deliverables.due,
    project_deliverables.publish_early,
    project_deliverables.rights
   FROM project_deliverables;


ALTER TABLE project_deliverables_tr OWNER TO hom;

--
-- Name: VIEW project_deliverables_tr; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW project_deliverables_tr IS 'project_deliverables minus prj_id and milestone';


--
-- Name: project_deliverables_tr; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE project_deliverables_tr TO peerweb;


--
-- PostgreSQL database dump complete
--

