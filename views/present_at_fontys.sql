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
-- Name: present_at_fontys; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW present_at_fontys AS
 SELECT present_anywhere.userid,
    present_anywhere.since,
    present_anywhere.id,
    present_anywhere.from_ip,
    present_anywhere.day,
    present_anywhere.hourcode,
    present_anywhere.start_time,
    present_anywhere.stop_time,
    present_anywhere.dayname,
    present_anywhere.day_lang
   FROM present_anywhere
  WHERE (present_anywhere.from_ip <<= '145.85.0.0/16'::inet);


ALTER TABLE present_at_fontys OWNER TO hom;

--
-- Name: present_at_fontys; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE present_at_fontys TO peerweb;


--
-- PostgreSQL database dump complete
--

