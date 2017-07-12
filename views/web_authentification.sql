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
-- Name: web_authentification; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW web_authentification AS
 SELECT (''::text || (password.userid)::text) AS username,
    password.password
   FROM passwd password
UNION
 SELECT guest_users.username,
    guest_users.password
   FROM guest_users;


ALTER TABLE web_authentification OWNER TO hom;

--
-- Name: VIEW web_authentification; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW web_authentification IS 'used by generic web authentification for private sites ';


--
-- Name: web_authentification; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE web_authentification TO peerweb;
GRANT SELECT,REFERENCES ON TABLE web_authentification TO jenkins;
GRANT SELECT ON TABLE web_authentification TO wwwrun;


--
-- PostgreSQL database dump complete
--

