begin work;

drop view if exists all_prj_tutor;
drop view if exists all_prj_tutor_y;
drop view if exists grp_detail;
drop view if exists grp_details;

alter table prj_milestone alter column milstone_name type varchar(40);

CREATE VIEW all_prj_tutor AS
 SELECT prj_tutor.prjtg_id,
    prj_milestone.prj_id,
    prj_milestone.prjm_id,
    t.tutor,
    prj_tutor.tutor_id,
    prj_tutor.grp_num,
    prj_tutor.grp_name,
    prj_tutor.prj_tutor_open,
    prj_tutor.assessment_complete,
    prj_milestone.milestone,
    prj_milestone.prj_milestone_open,
    prj_milestone.assessment_due,
    prj_milestone.weight,
    prj_milestone.milestone_name,
    prj_milestone.has_assessment,
    project.afko,
    project.description,
    project.year,
    tt.tutor AS tutor_owner,
    project.comment,
    project.valid_until,
    project.termendyear,
    project.course,
    project.owner_id,
    grp_alias.long_name,
    grp_alias.alias,
    grp_alias.website,
    grp_alias.productname,
    prj_tutor.tutor_grade
   FROM (((((prj_tutor
     JOIN prj_milestone USING (prjm_id))
     JOIN project USING (prj_id))
     JOIN tutor t ON ((t.userid = prj_tutor.tutor_id)))
     JOIN tutor tt ON ((tt.userid = project.owner_id)))
     LEFT JOIN grp_alias USING (prjtg_id));


ALTER TABLE all_prj_tutor OWNER TO hom;


REVOKE ALL ON TABLE all_prj_tutor FROM PUBLIC;
REVOKE ALL ON TABLE all_prj_tutor FROM hom;
GRANT ALL ON TABLE all_prj_tutor TO hom;
GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE all_prj_tutor TO peerweb;
GRANT SELECT,REFERENCES ON TABLE all_prj_tutor TO wwwrun;


--
-- PostgreSQL database dump complete
--


CREATE VIEW all_prj_tutor_y AS
 SELECT prj_tutor.prjtg_id,
    prj_milestone.prj_id,
    prj_tutor.prjm_id,
    t.tutor,
    prj_tutor.grp_num,
    prj_tutor.grp_name,
    prj_tutor.prj_tutor_open,
    prj_tutor.assessment_complete,
    prj_milestone.milestone,
    prj_milestone.prj_milestone_open,
    prj_milestone.assessment_due,
    prj_milestone.weight,
    prj_milestone.milestone_name,
    prj_milestone.has_assessment,
    project.afko,
    project.description,
    project.year,
    tt.tutor AS tutor_owner,
    project.comment,
    project.valid_until,
    project.termendyear,
    project.course,
    project.owner_id,
    tt.faculty_id
   FROM ((((prj_tutor
     JOIN tutor t ON ((prj_tutor.tutor_id = t.userid)))
     JOIN prj_milestone USING (prjm_id))
     JOIN project USING (prj_id))
     JOIN tutor tt ON ((project.owner_id = tt.userid)));


ALTER TABLE all_prj_tutor_y OWNER TO hom;

--
-- Name: all_prj_tutor_y; Type: ACL; Schema: public; Owner: hom
--

REVOKE ALL ON TABLE all_prj_tutor_y FROM PUBLIC;
REVOKE ALL ON TABLE all_prj_tutor_y FROM hom;
GRANT ALL ON TABLE all_prj_tutor_y TO hom;
GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE all_prj_tutor_y TO peerweb;
GRANT SELECT,REFERENCES ON TABLE all_prj_tutor_y TO wwwrun;

CREATE VIEW grp_detail AS
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


ALTER TABLE grp_detail OWNER TO hom;

--
-- Name: VIEW grp_detail; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW grp_detail IS 'detail attributes for project group, updatable.';


--
-- Name: grp_detail_delete; Type: RULE; Schema: public; Owner: hom
--

CREATE RULE grp_detail_delete AS
    ON DELETE TO grp_detail DO INSTEAD  DELETE FROM grp_alias g
  WHERE (g.prjtg_id = old.prjtg_id);


--
-- Name: grp_detail_insert; Type: RULE; Schema: public; Owner: hom
--

CREATE RULE grp_detail_insert AS
    ON INSERT TO grp_detail DO INSTEAD  INSERT INTO grp_alias (prjtg_id, long_name, alias, website, productname, youtube_link)
  VALUES (new.prjtg_id, new.alias, new.long_name, new.website, new.productname, new.youtube_link);


--
-- Name: grp_detail_update; Type: RULE; Schema: public; Owner: hom
--

CREATE RULE grp_detail_update AS
    ON UPDATE TO grp_detail DO INSTEAD  UPDATE grp_alias SET long_name = new.long_name, alias = new.alias, website = new.website, productname = new.productname, youtube_link = new.youtube_link
  WHERE (grp_alias.prjtg_id = new.prjtg_id);


--
-- Name: grp_detail; Type: ACL; Schema: public; Owner: hom
--

REVOKE ALL ON TABLE grp_detail FROM PUBLIC;
REVOKE ALL ON TABLE grp_detail FROM hom;
GRANT ALL ON TABLE grp_detail TO hom;
GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE grp_detail TO peerweb;




--
-- PostgreSQL database dump complete
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
-- Name: grp_details_delete; Type: RULE; Schema: public; Owner: hom
--

CREATE RULE grp_details_delete AS
    ON DELETE TO grp_details DO INSTEAD  DELETE FROM grp_alias g
  WHERE (g.prjtg_id = old.prjtg_id);


--
-- Name: grp_details_insert; Type: RULE; Schema: public; Owner: hom
--

CREATE RULE grp_details_insert AS
    ON INSERT TO grp_details DO INSTEAD  INSERT INTO grp_alias (prjtg_id, long_name, alias, website, productname, youtube_link)
  VALUES (new.prjtg_id, new.alias, new.long_name, new.website, new.productname, new.youtube_link);


--
-- Name: grp_details_update; Type: RULE; Schema: public; Owner: hom
--

CREATE RULE grp_details_update AS
    ON UPDATE TO grp_details DO INSTEAD  UPDATE grp_alias SET long_name = new.long_name, alias = new.alias, website = new.website, productname = new.productname, youtube_link = new.youtube_link
  WHERE (grp_alias.prjtg_id = new.prjtg_id);


--
-- Name: grp_details; Type: ACL; Schema: public; Owner: hom
--

REVOKE ALL ON TABLE grp_details FROM PUBLIC;
REVOKE ALL ON TABLE grp_details FROM hom;
GRANT ALL ON TABLE grp_details TO hom;
GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE grp_details TO peerweb;



commit;
