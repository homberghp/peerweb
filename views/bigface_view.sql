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
-- Name: bigface_view; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW bigface_view AS
 SELECT s.snummer AS userid,
    s.achternaam,
    s.roepnaam,
    s.tussenvoegsel,
    t.tutor AS nickname,
    t.office_phone,
    t.office,
    s.email1 AS email,
    t.team,
    t.building,
    t.room,
    t.display_name,
    (((('<img src="mfotos/'::text || COALESCE(r.snummer, 0)) || '.jpg" alt="'::text) || s.snummer) || '"/>'::text) AS image,
    s.faculty_id,
    fac.faculty_short,
    COALESCE(r.snummer, 0) AS photo_id
   FROM (((tutor t
     JOIN student s ON ((t.userid = s.snummer)))
     LEFT JOIN registered_mphotos r USING (snummer))
     JOIN faculty fac ON ((t.faculty_id = fac.faculty_id)))
  WHERE (s.active = true);


ALTER TABLE bigface_view OWNER TO hom;

--
-- Name: bigface_view; Type: ACL; Schema: public; Owner: hom
--

GRANT ALL ON TABLE bigface_view TO peerweb;


--
-- PostgreSQL database dump complete
--

