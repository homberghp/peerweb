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
-- Name: validator_regex_slashed; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW validator_regex_slashed AS
 SELECT validator_regex.regex_name,
    replace(validator_regex.regex, '\'::text, '\\'::text) AS regex
   FROM validator_regex;


ALTER TABLE validator_regex_slashed OWNER TO hom;

--
-- Name: VIEW validator_regex_slashed; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW validator_regex_slashed IS ' regex_name join map for validation; used in regex editor';


--
-- Name: validator_regex_slashed validator_regex_slashed_r_delete; Type: RULE; Schema: public; Owner: hom
--

CREATE RULE validator_regex_slashed_r_delete AS
    ON DELETE TO validator_regex_slashed DO INSTEAD  DELETE FROM validator_regex
  WHERE (((validator_regex.regex_name)::text = (old.regex_name)::text) AND (validator_regex.regex = old.regex));


--
-- Name: validator_regex_slashed validator_regex_slashed_r_insert; Type: RULE; Schema: public; Owner: hom
--

CREATE RULE validator_regex_slashed_r_insert AS
    ON INSERT TO validator_regex_slashed DO INSTEAD  INSERT INTO validator_regex (regex_name, regex)
  VALUES (new.regex_name, new.regex);


--
-- Name: validator_regex_slashed validator_regex_slashed_r_update; Type: RULE; Schema: public; Owner: hom
--

CREATE RULE validator_regex_slashed_r_update AS
    ON UPDATE TO validator_regex_slashed DO INSTEAD  UPDATE validator_regex SET regex_name = new.regex_name, regex = new.regex
  WHERE (((validator_regex.regex_name)::text = (new.regex_name)::text) AND (validator_regex.regex = new.regex));


--
-- Name: validator_regex_slashed; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE validator_regex_slashed TO peerweb;


--
-- PostgreSQL database dump complete
--

