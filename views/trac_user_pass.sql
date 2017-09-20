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
-- Name: trac_user_pass; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW trac_user_pass AS
 SELECT (passwd.userid)::text AS username,
    passwd.password
   FROM passwd;


ALTER TABLE trac_user_pass OWNER TO hom;

--
-- Name: trac_user_pass; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE trac_user_pass TO peerweb;
GRANT SELECT ON TABLE trac_user_pass TO wwwrun;


--
-- PostgreSQL database dump complete
--

