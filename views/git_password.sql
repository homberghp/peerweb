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
-- Name: git_password; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW git_password AS
 SELECT s.snummer,
    s.email1 AS username,
    p.password
   FROM (( SELECT passwd.userid AS snummer,
            passwd.password
           FROM passwd
          WHERE ((passwd.capabilities & 262144) <> 0)) p
     JOIN student s USING (snummer))
UNION
 SELECT s.snummer,
    (s.snummer)::text AS username,
    p.password
   FROM (( SELECT passwd.userid AS snummer,
            passwd.password
           FROM passwd
          WHERE ((passwd.capabilities & 262144) <> 0)) p
     JOIN student s USING (snummer));


ALTER TABLE git_password OWNER TO hom;

--
-- Name: git_password; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,REFERENCES ON TABLE git_password TO wwwrun;


--
-- PostgreSQL database dump complete
--

