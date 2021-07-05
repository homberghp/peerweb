--
-- PostgreSQL database dump
--

-- Dumped from database version 13.1 (Ubuntu 13.1-1.pgdg16.04+1)
-- Dumped by pg_dump version 13.1 (Ubuntu 13.1-1.pgdg16.04+1)
begin work;
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
-- Name: mooc_progress; Type: VIEW; Schema: public; Owner: hom
--

CREATE OR REPLACE VIEW public.mooc_progress AS
 with kand as (select snummer from public.prj_grp where prjtg_id=12074)
 SELECT mooc_2020.snummer,
    student.achternaam,
    student.roepnaam,
    student.tussenvoegsel,
    student.email1,
    student_class.sclass,
    mooc_2020.part01,
    mooc_2020.part02,
    mooc_2020.part03,
    mooc_2020.part04,
    mooc_2020.part05,
    mooc_2020.part06,
    mooc_2020.part07,
    mooc_2020.part08,
    mooc_2020.part09,
    mooc_2020.part10,
    mooc_2020.part11,
    mooc_2020.part12,
    mooc_2020.part13,
    mooc_2020.part14,
    mooc_2020.total
   FROM public.mooc_2020  join kand using(snummer)
     JOIN public.student USING (snummer)
     JOIN public.student_class USING (faculty_id, class_id)
--     where snummer not in (select snummer from prj_grp where prjtg_id=11677)
  ORDER BY student_class.sclass, student.achternaam, student.roepnaam;


ALTER TABLE public.mooc_progress OWNER TO hom;

--
-- Name: TABLE mooc_progress; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT ON TABLE public.mooc_progress TO PUBLIC;
commit;
select * from public.mooc_progress order by 2; 
--
-- PostgreSQL database dump complete
--

