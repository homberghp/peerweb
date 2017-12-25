--
-- PostgreSQL database dump
--

-- Dumped from database version 10.0
-- Dumped by pg_dump version 10.0

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
-- Name: all_alumni_email; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW all_alumni_email AS
 SELECT s.snummer,
    s.class_id,
    s.achternaam,
    s.roepnaam,
    s.email1,
    ae.email2,
    ae.email3,
    a.email2 AS email4,
    a.email3 AS email5
   FROM ((student s
     LEFT JOIN alt_email ae ON ((s.snummer = ae.snummer)))
     LEFT JOIN alumni_email a ON ((s.snummer = a.snummer)));


ALTER TABLE all_alumni_email OWNER TO hom;

--
-- Name: all_alumni_email all_alumni_email_delete; Type: RULE; Schema: public; Owner: hom
--

CREATE RULE all_alumni_email_delete AS
    ON DELETE TO all_alumni_email DO INSTEAD NOTHING;


--
-- Name: all_alumni_email all_alumni_email_update; Type: RULE; Schema: public; Owner: hom
--

CREATE RULE all_alumni_email_update AS
    ON UPDATE TO all_alumni_email DO INSTEAD ( UPDATE student SET email1 = new.email1
  WHERE (student.snummer = new.snummer);
 INSERT INTO alt_email (snummer, email2, email3)  SELECT new.snummer,
            new.email2,
            new.email3 ON CONFLICT(snummer) DO UPDATE SET email2 = excluded.email2, email3 = excluded.email3;
 UPDATE alumni_email SET email2 = new.email4, email3 = new.email5
  WHERE (alumni_email.snummer = new.snummer);
);


--
-- PostgreSQL database dump complete
--

