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
-- Name: act_part_count; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW act_part_count AS
 SELECT activity_participant.act_id,
    count(*) AS count
   FROM activity_participant
  GROUP BY activity_participant.act_id;


ALTER TABLE act_part_count OWNER TO hom;

--
-- Name: VIEW act_part_count; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW act_part_count IS 'count participants in activity. For reporting.';


--
-- Name: act_part_count; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE act_part_count TO peerweb;


--
-- PostgreSQL database dump complete
--

