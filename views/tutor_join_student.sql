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
-- Name: tutor_join_student; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW tutor_join_student AS
 SELECT t.tutor,
    t.userid AS snummer,
    t.userid,
    s.achternaam,
    s.roepnaam,
    s.tussenvoegsel,
    t.faculty_id,
    t.team,
    t.office AS function,
    t.building,
    t.city,
    t.room,
    t.office_phone,
    t.schedule_id,
    t.display_name,
    s.opl
   FROM (tutor t
     JOIN student s ON ((t.userid = s.snummer)));


ALTER TABLE tutor_join_student OWNER TO hom;

--
-- Name: VIEW tutor_join_student; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW tutor_join_student IS 'Tutor view used to repesent tutor';


--
-- Name: tutor_join_student tutor_join_student_delete; Type: RULE; Schema: public; Owner: hom
--

CREATE RULE tutor_join_student_delete AS
    ON DELETE TO tutor_join_student DO INSTEAD  DELETE FROM tutor
  WHERE ((tutor.tutor)::text = (old.tutor)::text);


--
-- Name: tutor_join_student tutor_join_student_insert; Type: RULE; Schema: public; Owner: hom
--

CREATE RULE tutor_join_student_insert AS
    ON INSERT TO tutor_join_student DO INSTEAD  INSERT INTO tutor (tutor, userid, faculty_id, team, office, building, city, room, office_phone, schedule_id, display_name)
  VALUES (new.tutor, new.userid, new.faculty_id, new.team, new.function, new.building, new.city, new.room, new.office_phone, new.schedule_id, new.display_name);


--
-- Name: tutor_join_student tutor_join_student_update; Type: RULE; Schema: public; Owner: hom
--

CREATE RULE tutor_join_student_update AS
    ON UPDATE TO tutor_join_student DO INSTEAD  UPDATE tutor SET tutor = new.tutor, userid = new.userid, faculty_id = new.faculty_id, team = new.team, office = new.function, building = new.building, city = new.city, room = new.room, office_phone = new.office_phone, schedule_id = new.schedule_id, display_name = new.display_name
  WHERE (tutor.userid = old.userid);


--
-- Name: tutor_join_student; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE tutor_join_student TO peerweb;


--
-- PostgreSQL database dump complete
--

