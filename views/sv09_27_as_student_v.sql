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
-- Name: sv09_27_as_student_v; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW sv09_27_as_student_v AS
 SELECT a.studentnummer AS snummer,
    substr((a.achternaam)::text, 1, 40) AS achternaam,
    a.tussenvoegsels AS tussenvoegsel,
    a.voorletters,
    a.roepnaam,
    substr((a.straat)::text, 1, 40) AS straat,
    substr((a.huisnummer || (a.huisnummertoevoeging)::text), 1, 4) AS huisnr,
    a.postcode AS pcode,
    a.woonplaats AS plaats,
    a.e_mail_instelling AS email1,
    nm.nationaliteit,
    (regexp_replace((a.instroom)::text, '.+?\s(\d{4})$'::text, '\1'::text))::integer AS cohort,
    a.geboortedatum AS gebdat,
        CASE
            WHEN (a.geslacht = 'vrouw'::bpchar) THEN 'F'::text
            ELSE 'M'::text
        END AS sex,
        CASE
            WHEN (a.voorkeurstaal = 'Engels'::bpchar) THEN 'EN'::text
            WHEN (a.voorkeurstaal = 'Duits'::bpchar) THEN 'DE'::text
            ELSE 'NL'::text
        END AS lang,
    a.pcn_nummer AS pcn,
    sp.studieprogr AS opl,
    ((('+'::text || a.land_nummer_vast_centrale_verificatie) || ' '::text) || a.vast_nummer_centrale_verificatie) AS phone_home,
    ((('+'::text || a.land_nummer_mobiel) || ' '::text) || a.mobiel_nummer) AS phone_gsm,
    NULL::character varying(40) AS phone_postaddress,
    27 AS faculty_id,
    a.groepcode AS hoofdgrp,
    true AS active,
    NULL::integer AS slb,
    iso.a3 AS land,
    a.studielinkvariantcode AS studieplan,
    substr((a.geboorteplaats)::text, 1, 40) AS geboorteplaats,
    iso2.a3 AS geboorteland,
    substr((a.voornamen)::text, 1, 40) AS voornaam,
    0 AS class_id
   FROM ((((sv09_27 a
     LEFT JOIN studieplan sp ON ((sp.studieplan = a.studielinkvariantcode)))
     LEFT JOIN nat_mapper nm ON (((a.leidende_nationaliteit)::bpchar = nm.nation_omschr)))
     LEFT JOIN iso3166 iso ON (((a.land)::text = (iso.land_nl)::text)))
     LEFT JOIN iso3166 iso2 ON (((a.geboorteland)::text = (iso2.land_nl)::text)));


ALTER TABLE sv09_27_as_student_v OWNER TO hom;

--
-- PostgreSQL database dump complete
--

