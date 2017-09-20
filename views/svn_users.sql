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
-- Name: svn_users; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW svn_users AS
 SELECT (''::text || (password.userid)::text) AS username,
    password.password
   FROM passwd password
  WHERE (password.disabled = false);


ALTER TABLE svn_users OWNER TO hom;

--
-- Name: svn_users; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE svn_users TO peerweb;
GRANT SELECT,REFERENCES ON TABLE svn_users TO wwwrun;


--
-- PostgreSQL database dump complete
--

