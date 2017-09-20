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
-- Name: all_tab_columns; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW all_tab_columns AS
 SELECT lower((pg_get_userbyid(tab.relowner))::text) AS owner,
    lower((tab.relname)::text) AS table_name,
    lower((col.attname)::text) AS column_name,
    lower((typ.typname)::text) AS data_type,
    col.attlen AS data_length,
    col.attnum AS column_id,
        CASE
            WHEN col.attnotnull THEN 'N'::bpchar
            ELSE 'Y'::bpchar
        END AS nullable,
    dflt.adsrc AS data_default
   FROM pg_class tab,
    pg_type typ,
    (pg_attribute col
     LEFT JOIN pg_attrdef dflt ON (((dflt.adrelid = col.attrelid) AND (dflt.adnum = col.attnum))))
  WHERE ((tab.oid = col.attrelid) AND (typ.oid = col.atttypid) AND (col.attnum > 0));


ALTER TABLE all_tab_columns OWNER TO hom;

--
-- Name: VIEW all_tab_columns; Type: COMMENT; Schema: public; Owner: hom
--

COMMENT ON VIEW all_tab_columns IS 'describes details for relations; is used in ste (simple table editor)';


--
-- Name: all_tab_columns; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE all_tab_columns TO peerweb;


--
-- PostgreSQL database dump complete
--

