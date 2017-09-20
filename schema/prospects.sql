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

SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: prospects; Type: TABLE; Schema: public; Owner: hom
--

CREATE TABLE prospects (
    snummer integer,
    achternaam text,
    tussenvoegsel text,
    voorletters text,
    roepnaam text,
    straat text,
    huisnr character(4),
    pcode text,
    plaats text,
    email1 text,
    nationaliteit character(2),
    cohort integer,
    gebdat date,
    sex character(1),
    lang text,
    pcn integer,
    opl integer,
    phone_home text,
    phone_gsm text,
    phone_postaddress text,
    faculty_id integer,
    hoofdgrp text,
    active boolean,
    slb integer,
    land character(3),
    studieplan integer,
    geboorteplaats text,
    geboorteland character(3),
    voornamen text,
    class_id integer,
    email2 text
);


ALTER TABLE prospects OWNER TO hom;

--
-- Name: prospects; Type: ACL; Schema: public; Owner: hom
--

GRANT ALL ON TABLE prospects TO PUBLIC;


--
-- PostgreSQL database dump complete
--

