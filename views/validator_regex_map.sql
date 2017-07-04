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
-- Name: validator_regex_map; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW validator_regex_map AS
 SELECT validator_map.input_name,
    validator_regex.regex,
    validator_regex.regex_name,
    validator_map.starred
   FROM (validator_map
     JOIN validator_regex USING (regex_name));


ALTER TABLE validator_regex_map OWNER TO hom;

--
-- Name: VIEW validator_regex_map; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW validator_regex_map IS ' regex_name join map for validation';


--
-- Name: validator_regex_map; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE validator_regex_map TO peerweb;


--
-- PostgreSQL database dump complete
--

