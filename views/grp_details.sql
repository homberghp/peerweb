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
-- Name: grp_details; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW grp_details AS
 SELECT p.prj_id,
    p.afko,
    p.description,
    p.year,
    p.comment,
    p.valid_until,
    p.course,
    p.owner_id,
    prj_milestone.prjm_id,
    prj_milestone.milestone,
    prj_milestone.prj_milestone_open,
    prj_milestone.assessment_due,
    prj_milestone.milestone_name,
    town.display_name AS owner,
    town.faculty_id,
    f.faculty_short,
    tu.display_name AS tutor,
    pt.grp_num,
    pt.prjtg_id,
    pt.tutor_grade,
    pt.tutor_id,
    pt.grp_name,
    ga.long_name,
    ga.alias,
    ga.website,
    ga.productname,
    ga.youtube_link,
    regexp_replace(btrim((ga.youtube_link)::text), '.*?v=((-?|\w)+)?&?.*$'::text, '\1'::text) AS yt_id
   FROM ((((((project p
     JOIN prj_milestone USING (prj_id))
     JOIN prj_tutor pt USING (prjm_id))
     JOIN grp_alias ga USING (prjtg_id))
     JOIN tutor town ON ((p.owner_id = town.userid)))
     JOIN tutor tu ON ((pt.tutor_id = tu.userid)))
     JOIN faculty f ON ((f.faculty_id = town.faculty_id)));


ALTER TABLE grp_details OWNER TO hom;

--
-- Name: VIEW grp_details; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW grp_details IS 'detail attributes for project group, updatable.';


--
-- Name: grp_details grp_details_delete; Type: RULE; Schema: public; Owner: hom
--

CREATE RULE grp_details_delete AS
    ON DELETE TO grp_details DO INSTEAD  DELETE FROM grp_alias g
  WHERE (g.prjtg_id = old.prjtg_id);


--
-- Name: grp_details grp_details_insert; Type: RULE; Schema: public; Owner: hom
--

CREATE RULE grp_details_insert AS
    ON INSERT TO grp_details DO INSTEAD  INSERT INTO grp_alias (prjtg_id, long_name, alias, website, productname, youtube_link)
  VALUES (new.prjtg_id, new.alias, new.long_name, new.website, new.productname, new.youtube_link);


--
-- Name: grp_details grp_details_update; Type: RULE; Schema: public; Owner: hom
--

CREATE RULE grp_details_update AS
    ON UPDATE TO grp_details DO INSTEAD  UPDATE grp_alias SET long_name = new.long_name, alias = new.alias, website = new.website, productname = new.productname, youtube_link = new.youtube_link
  WHERE (grp_alias.prjtg_id = new.prjtg_id);


--
-- Name: grp_details; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE grp_details TO peerweb;


--
-- PostgreSQL database dump complete
--

