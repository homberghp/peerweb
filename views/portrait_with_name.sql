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
-- Name: portrait_with_name; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW portrait_with_name AS
 SELECT s.snummer,
    ((((s.roepnaam)::text || ' '::text) || COALESCE(((s.tussenvoegsel)::text || ' '::text), ''::text)) || (s.achternaam)::text) AS name,
    (('fotos/'::text || COALESCE((rp.snummer)::text, 'anonymous'::text)) || '.jpg'::text) AS image
   FROM (student s
     LEFT JOIN registered_photos rp USING (snummer));


ALTER TABLE portrait_with_name OWNER TO hom;

--
-- Name: VIEW portrait_with_name; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW portrait_with_name IS 'used for wsb password creation scripts';


--
-- Name: portrait_with_name; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE portrait_with_name TO peerweb;
GRANT SELECT ON TABLE portrait_with_name TO wwwrun;


--
-- PostgreSQL database dump complete
--

