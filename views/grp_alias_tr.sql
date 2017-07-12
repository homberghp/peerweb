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
-- Name: grp_alias_tr; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW grp_alias_tr AS
 SELECT grp_alias.prjtg_id,
    grp_alias.long_name,
    grp_alias.alias,
    grp_alias.website,
    grp_alias.productname
   FROM grp_alias;


ALTER TABLE grp_alias_tr OWNER TO hom;

--
-- Name: VIEW grp_alias_tr; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW grp_alias_tr IS 'grp_alias with redeundant columns removed';


--
-- Name: grp_alias_tr; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE grp_alias_tr TO peerweb;


--
-- PostgreSQL database dump complete
--

