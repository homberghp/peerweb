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
-- Name: transaction_operator; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW transaction_operator AS
 SELECT t.ts,
    t.trans_id,
    t.operator,
    t.from_ip,
    (((s.roepnaam)::text || COALESCE(((' '::text || (s.tussenvoegsel)::text) || ' '::text), ' '::text)) || (s.achternaam)::text) AS op_name
   FROM (transaction t
     JOIN student s ON ((t.operator = s.snummer)));


ALTER TABLE transaction_operator OWNER TO hom;

--
-- Name: transaction_operator; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE transaction_operator TO peerweb;


--
-- PostgreSQL database dump complete
--

