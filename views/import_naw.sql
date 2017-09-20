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
-- Name: import_naw; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW import_naw AS
 SELECT DISTINCT ig.studentnummer AS snummer,
    initcap((ig.achternaam)::text) AS achternaam,
    ig.tussenvoegsel AS tussenvoegsel,
    ig.voorletters,
    initcap((ig.roepnaam)::text) AS roepnaam,
    ig.straat,
    ig.huisnr,
    ig.postcode AS pcode,
    initcap((ig.woonplaats)::text) AS plaats,
    ig.e_mail_instelling AS email1,
    nm.nationaliteit,
    (date_part('year'::text, ig.datum_aankomst_opleiding))::smallint AS cohort,
    ig.geboortedatum AS gebdat,
        CASE
            WHEN (ig.geslacht = 'man'::bpchar) THEN 'M'::text
            ELSE 'F'::text
        END AS sex,
        CASE
            WHEN (ig.voorkeurstaal = 'Duits'::bpchar) THEN 'DE'::text
            WHEN (ig.voorkeurstaal = 'Engels'::bpchar) THEN 'EN'::text
            ELSE 'NL'::text
        END AS lang,
    ig.pcn_nummer AS pcn,
    COALESCE(((('+'::text || ig.land_nummer_vast_centrale_verificatie) || ' '::text) || ig.vast_nummer_centrale_verificatie), ((('+'::text || ig.land_nummer_vast_decentrale_verificatie) || ' '::text) || ig.vast_nummer_decentrale_verificatie)) AS phone_home,
    ((('+'::text || (ig.land_nummer_mobiel)::text) || ' '::text) || ig.mobiel_nummer) AS phone_gsm,
    NULL::text AS phone_postaddress,
    ig.instituutcode AS faculty_id,
    il.a3 AS land,
    initcap((ig.geboorteplaats)::text) AS geboorteplaats,
    ia.a3 AS geboorteland,
    initcap((ig.voornamen)::text) AS voornaam
   FROM (((ingeschrevenen ig
     LEFT JOIN map_land_nl_iso3166 ia ON (((ig.geboorteland)::text = (ia.land_nl)::text)))
     LEFT JOIN map_land_nl_iso3166 il ON (((ig.land)::text = (il.land_nl)::text)))
     LEFT JOIN nat_mapper nm ON (((ig.leidende_nationaliteit)::bpchar = nm.nation_omschr)));


ALTER TABLE import_naw OWNER TO hom;

--
-- PostgreSQL database dump complete
--

