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
-- Name: prj_grp_email; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW prj_grp_email AS
 SELECT pt.prjm_id,
    p.afko,
    p.year,
    COALESCE(btrim((ga.alias)::text), ('g'::text || pt.grp_num)) AS alias,
    pt.grp_name,
    lower(((((((btrim((fontys_course.course_short)::text) || '.'::text) || btrim((p.afko)::text)) || '.'::text) || p.year) || '.'::text) || btrim((pt.grp_name)::text))) AS maillist,
    s.email1,
    pt.grp_num,
    pt.prjtg_id,
    s.achternaam,
    s.roepnaam
   FROM ((((((student s
     JOIN prj_grp pg USING (snummer))
     JOIN prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
     JOIN project p ON ((pm.prj_id = p.prj_id)))
     JOIN fontys_course USING (course))
     LEFT JOIN grp_alias ga ON ((pt.prjtg_id = ga.prjtg_id)))
  ORDER BY pt.grp_num;


ALTER TABLE prj_grp_email OWNER TO hom;

--
-- Name: VIEW prj_grp_email; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW prj_grp_email IS 'used to create maillist per project group';


--
-- Name: prj_grp_email; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE prj_grp_email TO peerweb;


--
-- PostgreSQL database dump complete
--

