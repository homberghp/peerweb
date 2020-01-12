--
-- PostgreSQL database dump
--

-- Dumped from database version 10.10 (Ubuntu 10.10-1.pgdg16.04+1)
-- Dumped by pg_dump version 10.10 (Ubuntu 10.10-1.pgdg16.04+1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: menu_item_defs; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.menu_item_defs AS
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
    mi.regex_name,
    atc.is_nullable as "nullable"
   FROM ((((public.menu m
     JOIN public.menu_item mi USING (menu_name))
     JOIN information_schema.columns atc ON ((((m.relation_name)::text = atc.table_name) AND ((mi.column_name)::text = atc.column_name))))
     LEFT JOIN public.menu_option_queries moq ON ((((m.menu_name)::text = (moq.menu_name)::text) AND ((mi.column_name)::text = (moq.column_name)::text))))
     LEFT JOIN public.menu_item_display mid ON ((((mid.menu_name)::text = (mi.menu_name)::text) AND ((mid.column_name)::text = (mi.column_name)::text))));


ALTER TABLE public.menu_item_defs OWNER TO rpadmin;

--
-- Name: TABLE menu_item_defs; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.menu_item_defs TO peerweb;


--
-- PostgreSQL database dump complete
--

