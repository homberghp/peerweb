--
-- PostgreSQL database dump
--

-- Dumped from database version 10.6 (Ubuntu 10.6-0ubuntu0.18.04.1)
-- Dumped by pg_dump version 10.6 (Ubuntu 10.6-0ubuntu0.18.04.1)

begin work;

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: svn_progress; Type: VIEW; Schema: public; Owner: hom
--
drop view if exists public.svn_progress;
CREATE VIEW public.svn_progress AS
 SELECT prj_grp.snummer,
    student.achternaam,
    student.roepnaam,
    cohort,
    prj_tutor.grp_name,
    r.milestone,
    r.repospath,
    r.description,
    r.isroot,
    r.id,
    r.url_tail,
    r.owner,
    r.grp_num,
    r.prjm_id,
    r.prjtg_id,
    r.youngest,
    r.last_commit
   FROM (((public.prj_grp
     JOIN public.prj_tutor USING (prjtg_id))
     JOIN public.repositories r USING (prjtg_id))
     JOIN public.student USING (snummer))
  ORDER BY r.grp_num;


ALTER TABLE public.svn_progress OWNER TO hom;
grant select,references on TABLE public.svn_progress  to public ;
--
-- PostgreSQL database dump complete
--

commit;
