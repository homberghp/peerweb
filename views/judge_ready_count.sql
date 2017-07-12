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
-- Name: judge_ready_count; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW judge_ready_count AS
 SELECT gs2.prjtg_id,
    gs2.prj_id,
    gs2.milestone,
    gs2.prjm_id,
    gs2.grp_num,
    gs2.size,
    COALESCE(rc.ready_count, (0)::bigint) AS ready_count
   FROM (grp_size2 gs2
     LEFT JOIN ( SELECT count(*) AS ready_count,
            prj_grp.prjtg_id
           FROM prj_grp
          WHERE (prj_grp.written = true)
          GROUP BY prj_grp.prjtg_id) rc USING (prjtg_id));


ALTER TABLE judge_ready_count OWNER TO hom;

--
-- Name: judge_ready_count; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE judge_ready_count TO peerweb;


--
-- PostgreSQL database dump complete
--

