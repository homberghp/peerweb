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
-- Name: author_grp_members; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW author_grp_members AS
 SELECT author_grp.prj_id,
    author_grp.milestone,
    author_grp.grp_num,
    author_grp.upload_id,
    prj_grp.snummer,
    prj_grp.prj_grp_open AS open,
    author_grp.rights,
    author_grp.author
   FROM (author_grp
     JOIN prj_grp USING (prjtg_id));


ALTER TABLE author_grp_members OWNER TO hom;

--
-- Name: author_grp_members; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE author_grp_members TO peerweb;


--
-- PostgreSQL database dump complete
--

