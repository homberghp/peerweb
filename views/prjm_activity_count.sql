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
-- Name: prjm_activity_count; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW prjm_activity_count AS
 SELECT activity.prjm_id,
    count(*) AS act_count
   FROM activity
  GROUP BY activity.prjm_id;


ALTER TABLE prjm_activity_count OWNER TO hom;

--
-- Name: VIEW prjm_activity_count; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW prjm_activity_count IS 'used by peerpresenceoverview';


--
-- Name: prjm_activity_count; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE prjm_activity_count TO peerweb;


--
-- PostgreSQL database dump complete
--

