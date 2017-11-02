begin work;
--
-- PostgreSQL database dump
--

-- Dumped from database version 9.6.5
-- Dumped by pg_dump version 9.6.5

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
drop view if exists all_alumni_email;
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
     LEFT JOIN alt_email ae on(s.snummer=ae.snummer))
     LEFT JOIN alumni_email a on(s.snummer=a.snummer));


ALTER TABLE all_alumni_email OWNER TO hom;

CREATE RULE all_alumni_email_delete AS
    ON DELETE TO all_alumni_email  DO INSTEAD NOTHING;

create rule all_alumni_email_update as
    on update to all_alumni_email do instead
     (update student set email1=new.email1 where student.snummer=new.snummer;
      insert into alt_email (snummer,email2,email3) select new.snummer,new.email2,new.email3
      on conflict(snummer) do update set (email2,email3) =(excluded.email2,excluded.email3);
      update alumni_email set (email2,email3) =(new.email4,new.email5) where alumni_email.snummer=new.snummer;
     );
--
-- PostgreSQL database dump complete
--

commit;
