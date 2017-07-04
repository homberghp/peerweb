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
-- Name: prj_tutor_email; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW prj_tutor_email AS
 SELECT DISTINCT pt.prjm_id,
    pt.afko,
    pt.year,
    'tutors'::text AS alias,
    (lower((((((btrim((fontys_course.course_short)::text) || '.'::text) || btrim((pt.afko)::text)) || '.'::text) || pt.year) || '.'::text)) || 'tutors'::text) AS maillist,
    s.email1,
    '-1'::integer AS grp_num,
    0 AS prjtg_id,
    s.achternaam,
    s.roepnaam
   FROM (((all_prj_tutor pt
     JOIN tutor t ON ((pt.tutor_id = t.userid)))
     JOIN student s ON ((t.userid = s.snummer)))
     JOIN fontys_course USING (course));


ALTER TABLE prj_tutor_email OWNER TO hom;

--
-- Name: VIEW prj_tutor_email; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW prj_tutor_email IS 'used to create maillist per project group';


--
-- Name: prj_tutor_email; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE prj_tutor_email TO peerweb;


--
-- PostgreSQL database dump complete
--

