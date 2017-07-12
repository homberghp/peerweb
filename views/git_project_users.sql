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
-- Name: git_project_users; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW git_project_users AS
 SELECT a.prjm_id,
    0 AS gid,
    s1.snummer,
    (((((('@'::text || lower(btrim((fontys_course.course_short)::text))) || a.year) || lower(btrim((a.afko)::text))) || 'm'::text) || a.milestone) || '_tutor'::text) AS git_grp,
    s1.email1 AS git_grp_member,
    ''::text AS repo
   FROM ((all_prj_tutor a
     JOIN student s1 ON ((a.tutor_id = s1.snummer)))
     JOIN fontys_course USING (course))
UNION
 SELECT a.prjm_id,
    a.grp_num AS gid,
    prj_grp.snummer,
    ((((((('@'::text || lower(btrim((fontys_course.course_short)::text))) || a.year) || lower(btrim((a.afko)::text))) || 'm'::text) || a.milestone) || '_g'::text) || a.grp_num) AS git_grp,
    s2.email1 AS git_grp_member,
    ((((((a.year || '/'::text) || lower(btrim((a.afko)::text))) || 'm'::text) || a.milestone) || '/g'::text) || a.grp_num) AS repo
   FROM (((all_prj_tutor a
     JOIN prj_grp USING (prjtg_id))
     JOIN student s2 USING (snummer))
     JOIN fontys_course USING (course))
  ORDER BY 1, 2;


ALTER TABLE git_project_users OWNER TO hom;

--
-- Name: git_project_users; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,REFERENCES ON TABLE git_project_users TO wwwrun;


--
-- PostgreSQL database dump complete
--

