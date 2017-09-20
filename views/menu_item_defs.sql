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
-- Name: menu_item_defs; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW menu_item_defs AS
 SELECT m.menu_name,
    mi.column_name,
    atc.data_type,
    mi.item_length,
    mi.edit_type,
    mi.capability,
    moq.query,
    mid.length,
    mid."precision",
    mi.placeholder,
    mi.regex_name
   FROM ((((menu m
     JOIN menu_item mi USING (menu_name))
     JOIN all_tab_columns atc ON ((((m.relation_name)::text = atc.table_name) AND ((mi.column_name)::text = atc.column_name))))
     LEFT JOIN menu_option_queries moq ON ((((m.menu_name)::text = (moq.menu_name)::text) AND ((mi.column_name)::text = (moq.column_name)::text))))
     LEFT JOIN menu_item_display mid ON ((((mid.menu_name)::text = (mi.menu_name)::text) AND ((mid.column_name)::text = (mi.column_name)::text))));


ALTER TABLE menu_item_defs OWNER TO hom;

--
-- Name: menu_item_defs; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE menu_item_defs TO peerweb;


--
-- PostgreSQL database dump complete
--

