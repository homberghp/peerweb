--
-- PostgreSQL database dump
--

-- Dumped from database version 12.5 (Ubuntu 12.5-1.pgdg16.04+1)
-- Dumped by pg_dump version 12.5 (Ubuntu 12.5-1.pgdg16.04+1)

begin work;
SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: svn_group; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE or replace VIEW public.svn_group AS
 SELECT pt.grp_name AS groupname,
    pg.snummer AS username,
    pm.prj_id,
    pm.milestone,
    s.achternaam,
    s.roepnaam,
    pt.prjm_id,
    pt.prjtg_id
   FROM
	public.prj_tutor pt 
     	JOIN public.prj_milestone pm ON (pt.prjm_id = pm.prjm_id)
	LEFT JOIN public.prj_grp pg ON (pg.prjtg_id = pt.prjtg_id)
     	LEFT JOIN public.grp_alias ga ON (pt.prjtg_id = ga.prjtg_id)
        LEFT JOIN public.student s USING (snummer);


ALTER TABLE public.svn_group OWNER TO rpadmin;

--
-- Name: TABLE svn_group; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.svn_group TO peerweb;
GRANT ALL ON TABLE public.svn_group TO wwwrun;


--
-- PostgreSQL database dump complete
--

commit;
