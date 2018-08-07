begin work;
--
-- PostgreSQL database dump
--

-- Dumped from database version 9.6.4
-- Dumped by pg_dump version 9.6.4

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET row_security = off;

SET search_path = importer, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: sv05_aanmelders; Type: TABLE; Schema: importer; Owner: importer
--
drop table sv05_aanmelders cascade;
CREATE TABLE IF NOT EXISTS sv05_aanmelders  (
    peildatum date,
    aanmelddatum date,
    instroom text,
    datum_van date,
    studiejaar integer,
    instituutcode integer,
    instituutnaam text,
    studentnummer integer,
    achternaam text,
    voorvoegsels text,
    voorletters text,
    voornamen text,
    roepnaam text,
    volledige_naam text,
    geslacht character(1),
    geboortedatum date,
    geboorteplaats text,
    geboorteland text,
    "e_mail_privé" text,
    e_mail_instelling text,
    land_nummer_mobiel integer,
    mobiel_nummer bigint,
    land_nummer_vast integer,
    vast_nummer bigint,
    pcn_nummer integer,
    studielinknummer integer,
    volledig_adres text,
    postcode_en_plaats text,
    land text,
    nationaliteit_1 text,
    nationaliteit_2 text,
    leidende_nationaliteit text,
    eer text,
    inschrijvingid integer,
    isatcode integer,
    opleiding text,
    opleidingnaamvoluit text,
    studielinkvariantcode integer,
    variant_omschrijving text,
    lesplaats text,
    vorm text,
    fase text,
    soort text,
    aanmeldingstatus text,
    datum_definitief_ingeschreven date,
    datum_annulering date,
    start_in_1e_jaar text,
    bijvakker text,
    datum_aankomst_fontys date,
    datum_aankomst_instituut date,
    datum_aankomst_opleiding date,
    indicatie_collegegeld text,
    pasfoto_uploaddatum date,
    voorkeurstaal text,
    exchange_kenmerk character(4),
    course_grp text,
    lang character(2),
    postcode text,
    woonplaats text,
    -- huisnr character(4),
    -- straat text
    seri integer
);


ALTER TABLE sv05_aanmelders OWNER TO importer;

--
-- Name: sv05_aanmelders; Type: ACL; Schema: importer; Owner: importer
--

GRANT ALL ON TABLE sv05_aanmelders TO PUBLIC;
GRANT ALL ON TABLE sv05_aanmelders TO peerweb;

truncate sv05_aanmelders restart identity cascade;
alter table sv05_aanmelders drop constraint if exists sv05_aanmelders_pk;
commit;
