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
-- Name: grp_size2; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW grp_size2 AS
 SELECT pm.prj_id,
    pm.milestone,
    pm.prjm_id,
    pt.grp_num,
    pt.prjtg_id,
    gs.gsize AS size
   FROM ((( SELECT prj_grp.prjtg_id,
            count(*) AS gsize
           FROM prj_grp
          GROUP BY prj_grp.prjtg_id) gs
     JOIN prj_tutor pt USING (prjtg_id))
     JOIN prj_milestone pm USING (prjm_id));


ALTER TABLE grp_size2 OWNER TO hom;

--
-- Name: grp_size2; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE grp_size2 TO peerweb;


--
-- PostgreSQL database dump complete
--

