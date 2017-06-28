--
-- PostgreSQL database dump
--

-- Dumped from database version 9.6.3
-- Dumped by pg_dump version 9.6.3
begin work;
drop table if exists aanmelders;
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
-- Name: aanmelders; Type: TABLE; Schema: public; Owner: hom
--

CREATE TABLE aanmelders (
    snummer integer primary key,
    achternaam character varying(40) not null,
    voorvoegsel character varying(10),
    voorletters character varying(10) not null,
    roepnaam character varying(20) not null,
    straat character varying(40) not null,
    huisnr character(4) not null,
    pcode character(7) not null,
    plaats character varying(40) not null,
    email1 character varying(50) not null,
    nationaliteit character(2) not null,
    hoofdgrp character(10) not null,
    active boolean default true,
    cohort smallint not null default date_part('year'::text, (now())::date) ,
    gebdat date not null check(age(gebdat) >= '15 year'::interval),
    sex character(1) check (sex in ('M','F')),
    lang character(2) check (lang in ('NL','DE','EN')),
    pcn integer not null,
    opl bigint,
    phone_home character varying(40),
    phone_gsm character varying(40),
    phone_postaddress character varying(40),
    faculty_id smallint,
    email2 character varying(50) not null,
    slb integer,
    image text,
    class_id integer default 0,
    studieplan integer,
    geboorteplaats character varying(40) not null,
    geboorteland character(3) not null,
    voornaam character varying(40) not null
);


ALTER TABLE aanmelders OWNER TO hom;
GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE aanmelders TO peerweb;

--
-- PostgreSQL database dump complete
--

commit;
