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
-- Name: importer; Type: SCHEMA; Schema: -; Owner: rpadmin
--

CREATE SCHEMA importer;


ALTER SCHEMA importer OWNER TO rpadmin;

--
-- Name: SCHEMA importer; Type: COMMENT; Schema: -; Owner: rpadmin
--

COMMENT ON SCHEMA importer IS 'schema from excel import via jmerge';


--
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: 
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


--
-- Name: citext; Type: EXTENSION; Schema: -; Owner: 
--

CREATE EXTENSION IF NOT EXISTS citext WITH SCHEMA public;


--
-- Name: EXTENSION citext; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION citext IS 'data type for case-insensitive character strings';


--
-- Name: email; Type: DOMAIN; Schema: public; Owner: rpadmin
--

CREATE DOMAIN public.email AS public.citext
	CONSTRAINT email_check CHECK ((VALUE OPERATOR(public.~) '^[a-zA-Z0-9.!#$%&''*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$'::public.citext));


ALTER DOMAIN public.email OWNER TO rpadmin;

--
-- Name: armor(bytea); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.armor(bytea) RETURNS text
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pg_armor';


ALTER FUNCTION public.armor(bytea) OWNER TO rpadmin;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: grade_summer_result; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.grade_summer_result (
    prjtg_id integer NOT NULL,
    snummer integer NOT NULL,
    criterium integer[] NOT NULL,
    multiplier numeric[],
    grade numeric[]
);


ALTER TABLE public.grade_summer_result OWNER TO rpadmin;

--
-- Name: TABLE grade_summer_result; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.grade_summer_result IS 'result type for function assessment_grade_set';


--
-- Name: assessment_grade_set(integer, numeric); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.assessment_grade_set(grp integer, prod numeric) RETURNS SETOF public.grade_summer_result
    LANGUAGE plpgsql
    AS $$
begin 
return query 
select prjtg_id,snummer,array_agg(criterium) as criterium, array_agg(multiplier) as multiplier,array_agg(grade) as grade from (
select g2.prjtg_id,
       snummer,
       criterium,
       gsize,
       grade_sum_i,
       grade_sum_i::real/(gsize-1) as iav,
       round((grade_sum_g::real/(gsize*(gsize-1)))::numeric,2) as gav,
       round(((grade_sum_i*gsize)/(case when grade_sum_g <>0 then grade_sum_g else 1 end ))::numeric,2) as multiplier,
       round(prod*((grade_sum_i*gsize)/(case when grade_sum_g <>0 then grade_sum_g else 1 end ))::numeric,2) as grade
       from 
       (select * from (
       	       	      select prjtg_id,contestant as snummer,criterium,sum(grade)::real as grade_sum_i 
       	       	       	       from assessment 
       		       	       where prjtg_id=grp
       			       group by prjtg_id,contestant,criterium 
       			       order by prjtg_id,contestant,criterium
       			) i
		 join ( 
		      select prjtg_id,criterium,sum(grade)::real as grade_sum_g 
       	     	      	       from assessment 
       	     	      	       where prjtg_id=grp
       	     	      	       group by prjtg_id,criterium 
       	     	      	       order by prjtg_id,criterium
       			) g using(prjtg_id,criterium)
		 join (
		      select prjtg_id,count(*) as gsize from prj_grp group by prjtg_id) gs using(prjtg_id)
       		      	     order by snummer, criterium
		      ) g2 
union 
select prjtg_id,
       snummer, 
       99 as criterium,
       gsize,grade_sum_sum_i,
       grade_sum_sum_i::real/(gsize-1) as iavg,
       round((grade_sum_sum_g::real/(gsize*(gsize-1)))::numeric,2) as gavg,
       round(((grade_sum_sum_i*gsize)/(case when grade_sum_sum_g<>0 then grade_sum_sum_g else 1 end))::numeric,2) as multiplier, 
       round(prod*((grade_sum_sum_i*gsize)/(case when grade_sum_sum_g<>0 then grade_sum_sum_g else 1 end))::numeric,2) as grade
from (select * from (
select prjtg_id,contestant as snummer,sum(grade)::real as grade_sum_sum_i from assessment where prjtg_id=grp group by prjtg_id,snummer) g4
join (select prjtg_id,sum(grade) as grade_sum_sum_g from assessment where prjtg_id=grp group by prjtg_id) g5 using(prjtg_id)
join (select prjtg_id,count(*) as gsize from prj_grp group by prjtg_id ) gs2 using(prjtg_id) 

) g6
order by prjtg_id,snummer,criterium
) agg group by prjtg_id,snummer;
end
$$;


ALTER FUNCTION public.assessment_grade_set(grp integer, prod numeric) OWNER TO rpadmin;

--
-- Name: assessment; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.assessment (
    contestant integer NOT NULL,
    judge integer NOT NULL,
    criterium smallint NOT NULL,
    grade smallint,
    prjtg_id integer NOT NULL
);


ALTER TABLE public.assessment OWNER TO rpadmin;

--
-- Name: TABLE assessment; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.assessment IS 'core table. assessement grade raw data';


--
-- Name: COLUMN assessment.contestant; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON COLUMN public.assessment.contestant IS 'the one being graded.';


--
-- Name: COLUMN assessment.judge; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON COLUMN public.assessment.judge IS 'the one giving grades.';


--
-- Name: assessmentbuild(integer, smallint); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.assessmentbuild(integer, smallint) RETURNS public.assessment
    LANGUAGE sql
    AS $_$ select $1 as prj_id, $2 as milestone, contestant,judge,criterium,grade ,grp_num from build_assessment$_$;


ALTER FUNCTION public.assessmentbuild(integer, smallint) OWNER TO rpadmin;

--
-- Name: snummer; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.snummer (
    snummer integer NOT NULL
);


ALTER TABLE public.snummer OWNER TO rpadmin;

--
-- Name: TABLE snummer; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.snummer IS 'result type for authorized_document.';


--
-- Name: authorized_document(integer); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.authorized_document(doc_id_in integer) RETURNS SETOF public.snummer
    LANGUAGE plpgsql
    AS $$
begin 
return query 
      select snummer as snummer from uploads where upload_id =doc_id_in
      union 
      select pg.snummer from prj_grp pg join uploads u using (prjtg_id)  where upload_id =doc_id_in and rights[1] =true 
      union 
      select pg.snummer as snummer from prj_grp pg join prj_tutor pt using(prjtg_id) 
            join uploads u on (pt.prjm_id=u.prjm_id) where rights[2]=true and upload_id=doc_id_in
      union 
      select da.snummer from document_author da where upload_id=doc_id_in
      union 
      select prj_tutor.tutor_id as snummer from uploads join prj_tutor using(prjm_id) 
            where upload_id=doc_id_in
      union 
            select pa.snummer from uploads u join prj_tutor pt using(prjtg_id) 
            join project_auditor pa on(u.prjtg_id=pt.prjtg_id and (pt.grp_num=pa.gid or pa.gid=0))
            where upload_id=doc_id_in;
end
$$;


ALTER FUNCTION public.authorized_document(doc_id_in integer) OWNER TO rpadmin;

--
-- Name: FUNCTION authorized_document(doc_id_in integer); Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON FUNCTION public.authorized_document(doc_id_in integer) IS 'set of users allowed to read document';


--
-- Name: crypt(text, text); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.crypt(text, text) RETURNS text
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pg_crypt';


ALTER FUNCTION public.crypt(text, text) OWNER TO rpadmin;

--
-- Name: dearmor(text); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.dearmor(text) RETURNS bytea
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pg_dearmor';


ALTER FUNCTION public.dearmor(text) OWNER TO rpadmin;

--
-- Name: decrypt(bytea, bytea, text); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.decrypt(bytea, bytea, text) RETURNS bytea
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pg_decrypt';


ALTER FUNCTION public.decrypt(bytea, bytea, text) OWNER TO rpadmin;

--
-- Name: decrypt_iv(bytea, bytea, bytea, text); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.decrypt_iv(bytea, bytea, bytea, text) RETURNS bytea
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pg_decrypt_iv';


ALTER FUNCTION public.decrypt_iv(bytea, bytea, bytea, text) OWNER TO rpadmin;

--
-- Name: digest(bytea, text); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.digest(bytea, text) RETURNS bytea
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pg_digest';


ALTER FUNCTION public.digest(bytea, text) OWNER TO rpadmin;

--
-- Name: digest(text, text); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.digest(text, text) RETURNS bytea
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pg_digest';


ALTER FUNCTION public.digest(text, text) OWNER TO rpadmin;

--
-- Name: email_to_href(text); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.email_to_href(inp text) RETURNS text
    LANGUAGE plpgsql
    AS $$ 
       begin
          return '<a href="mailto:'||trim(inp)||'">'||trim(inp)||'</a>'; 
      end;
$$;


ALTER FUNCTION public.email_to_href(inp text) OWNER TO rpadmin;

--
-- Name: encrypt(bytea, bytea, text); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.encrypt(bytea, bytea, text) RETURNS bytea
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pg_encrypt';


ALTER FUNCTION public.encrypt(bytea, bytea, text) OWNER TO rpadmin;

--
-- Name: encrypt_iv(bytea, bytea, bytea, text); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.encrypt_iv(bytea, bytea, bytea, text) RETURNS bytea
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pg_encrypt_iv';


ALTER FUNCTION public.encrypt_iv(bytea, bytea, bytea, text) OWNER TO rpadmin;

--
-- Name: gen_random_bytes(integer); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.gen_random_bytes(integer) RETURNS bytea
    LANGUAGE c STRICT
    AS '$libdir/pgcrypto', 'pg_random_bytes';


ALTER FUNCTION public.gen_random_bytes(integer) OWNER TO rpadmin;

--
-- Name: gen_salt(text); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.gen_salt(text) RETURNS text
    LANGUAGE c STRICT
    AS '$libdir/pgcrypto', 'pg_gen_salt';


ALTER FUNCTION public.gen_salt(text) OWNER TO rpadmin;

--
-- Name: gen_salt(text, integer); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.gen_salt(text, integer) RETURNS text
    LANGUAGE c STRICT
    AS '$libdir/pgcrypto', 'pg_gen_salt_rounds';


ALTER FUNCTION public.gen_salt(text, integer) OWNER TO rpadmin;

--
-- Name: getdocauthors(integer); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.getdocauthors(up_id integer) RETURNS text
    LANGUAGE plpgsql
    AS $$
    DECLARE
    auth text;
    reslt text;
    sep text;
    BEGIN
    reslt := '';
    sep   := '';
    for auth in  select roepnaam||coalesce(' '||tussenvoegsel||' ',' ')||achternaam||' ('||snummer||')' as coauthor
        from student join document_author using(snummer) where upload_id = up_id order by achternaam loop
    	reslt := reslt||sep||auth;
    	sep :=', ';
    end loop;
    return reslt;
END;
$$;


ALTER FUNCTION public.getdocauthors(up_id integer) OWNER TO rpadmin;

--
-- Name: hmac(bytea, bytea, text); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.hmac(bytea, bytea, text) RETURNS bytea
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pg_hmac';


ALTER FUNCTION public.hmac(bytea, bytea, text) OWNER TO rpadmin;

--
-- Name: hmac(text, text, text); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.hmac(text, text, text) RETURNS bytea
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pg_hmac';


ALTER FUNCTION public.hmac(text, text, text) OWNER TO rpadmin;

--
-- Name: insert_student_email(); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.insert_student_email() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
  DECLARE
     cohort integer;
  BEGIN
    IF new.cohort is null THEN
      cohort := date_part('year'::text, (now())::date);
    ELSE
      cohort := new.cohort;
    END IF;
    
    INSERT INTO student ( snummer,achternaam,tussenvoegsel,voorletters,roepnaam,straat,huisnr,
    	   		  pcode,plaats,email1,nationaliteit,cohort,gebdat,sex,lang,pcn,opl,phone_home,
			  phone_gsm,phone_postaddress,faculty_id,hoofdgrp,active,slb,land,studieplan,
			  geboorteplaats,geboorteland,voornamen,class_id)
    VALUES ( new.snummer,new.achternaam,new.tussenvoegsel,new.voorletters,new.roepnaam,new.straat,new.huisnr,
	     new.pcode,new.plaats,new.email1,new.nationaliteit,new.cohort,new.gebdat,new.sex,new.lang,new.pcn,new.opl,new.phone_home,
	     new.phone_gsm,new.phone_postaddress,new.faculty_id,new.hoofdgrp,new.active,new.slb,new.land,new.studieplan,
	     new.geboorteplaats,new.geboorteland,new.voornamen,new.class_id)
	on conflict(snummer)
	do update set ( snummer,achternaam,tussenvoegsel,voorletters,roepnaam,straat,huisnr,
	   		pcode,plaats,email1,nationaliteit,cohort,gebdat,sex,lang,pcn,opl,phone_home,
			phone_gsm,phone_postaddress,faculty_id,hoofdgrp,active,slb,land,studieplan,
			geboorteplaats,geboorteland,voornamen,class_id)=
		      ( EXCLUDED.snummer,EXCLUDED.achternaam,EXCLUDED.tussenvoegsel,EXCLUDED.voorletters,EXCLUDED.roepnaam,EXCLUDED.straat,
		        EXCLUDED.huisnr,EXCLUDED.pcode,EXCLUDED.plaats,EXCLUDED.email1,EXCLUDED.nationaliteit,EXCLUDED.cohort,EXCLUDED.gebdat,EXCLUDED.sex,EXCLUDED.lang,EXCLUDED.pcn,EXCLUDED.opl,EXCLUDED.phone_home,
			EXCLUDED.phone_gsm,EXCLUDED.phone_postaddress,EXCLUDED.faculty_id,EXCLUDED.hoofdgrp,EXCLUDED.active,EXCLUDED.slb,EXCLUDED.land,EXCLUDED.studieplan,
			EXCLUDED.geboorteplaats,EXCLUDED.geboorteland,EXCLUDED.voornamen,EXCLUDED.class_id);
  INSERT INTO alt_email (snummer, email2)
  SELECT new.snummer,new.email2
  WHERE (new.email2 IS NOT NULL) on conflict on constraint alt_email_pkey  do nothing;
  update alt_email set email2=new.email2 where snummer=new.snummer;
  insert into passwd (userid) select new.snummer where not exists (select 1 from passwd where userid=new.snummer);
  return NEW; -- TO SIGNAL SUCCESS
  END;
  $$;


ALTER FUNCTION public.insert_student_email() OWNER TO rpadmin;

--
-- Name: interval_to_hms(interval); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.interval_to_hms(interval) RETURNS text
    LANGUAGE plpgsql
    AS $_$
-- returns interval string in format h{2,}:mm:ss format with as many hours
-- needed to expres the complete duration
-- to be used in task_timer sums
DECLARE 
  iduration alias for $1;
  day_to_hours integer;
  hours integer;
  ms_string text;
  result text;
  lduration interval;
BEGIN
  lduration := date_trunc('second',iduration);
  day_to_hours := extract(day from lduration::interval)*24;
  hours := extract(hour from lduration::interval);
  ms_string := substring(lduration from '.....$');
  result = (day_to_hours+hours)||':'||ms_string;
  RETURN result;
END
$_$;


ALTER FUNCTION public.interval_to_hms(interval) OWNER TO rpadmin;

--
-- Name: iscribe(integer); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.iscribe(peerid integer) RETURNS TABLE(prjm_idv integer)
    LANGUAGE plpgsql
    AS $$
begin 
      return query
      select prjm_id from prj_milestone join project using (prj_id) where peerid=owner_id
      union
      select distinct prjm_id from prj_tutor where peerid=tutor_id
      union
      select distinct prjm_id from project_scribe join prj_milestone using(prj_id) where peerid=scribe;
end
  $$;


ALTER FUNCTION public.iscribe(peerid integer) OWNER TO rpadmin;

--
-- Name: peer_password(text); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.peer_password(text) RETURNS text
    LANGUAGE c STRICT
    AS 'peer_password', 'peer_password';


ALTER FUNCTION public.peer_password(text) OWNER TO rpadmin;

--
-- Name: pgp_key_id(bytea); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.pgp_key_id(bytea) RETURNS text
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pgp_key_id_w';


ALTER FUNCTION public.pgp_key_id(bytea) OWNER TO rpadmin;

--
-- Name: pgp_pub_decrypt(bytea, bytea); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.pgp_pub_decrypt(bytea, bytea) RETURNS text
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pgp_pub_decrypt_text';


ALTER FUNCTION public.pgp_pub_decrypt(bytea, bytea) OWNER TO rpadmin;

--
-- Name: pgp_pub_decrypt(bytea, bytea, text); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.pgp_pub_decrypt(bytea, bytea, text) RETURNS text
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pgp_pub_decrypt_text';


ALTER FUNCTION public.pgp_pub_decrypt(bytea, bytea, text) OWNER TO rpadmin;

--
-- Name: pgp_pub_decrypt(bytea, bytea, text, text); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.pgp_pub_decrypt(bytea, bytea, text, text) RETURNS text
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pgp_pub_decrypt_text';


ALTER FUNCTION public.pgp_pub_decrypt(bytea, bytea, text, text) OWNER TO rpadmin;

--
-- Name: pgp_pub_decrypt_bytea(bytea, bytea); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.pgp_pub_decrypt_bytea(bytea, bytea) RETURNS bytea
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pgp_pub_decrypt_bytea';


ALTER FUNCTION public.pgp_pub_decrypt_bytea(bytea, bytea) OWNER TO rpadmin;

--
-- Name: pgp_pub_decrypt_bytea(bytea, bytea, text); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.pgp_pub_decrypt_bytea(bytea, bytea, text) RETURNS bytea
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pgp_pub_decrypt_bytea';


ALTER FUNCTION public.pgp_pub_decrypt_bytea(bytea, bytea, text) OWNER TO rpadmin;

--
-- Name: pgp_pub_decrypt_bytea(bytea, bytea, text, text); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.pgp_pub_decrypt_bytea(bytea, bytea, text, text) RETURNS bytea
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pgp_pub_decrypt_bytea';


ALTER FUNCTION public.pgp_pub_decrypt_bytea(bytea, bytea, text, text) OWNER TO rpadmin;

--
-- Name: pgp_pub_encrypt(text, bytea); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.pgp_pub_encrypt(text, bytea) RETURNS bytea
    LANGUAGE c STRICT
    AS '$libdir/pgcrypto', 'pgp_pub_encrypt_text';


ALTER FUNCTION public.pgp_pub_encrypt(text, bytea) OWNER TO rpadmin;

--
-- Name: pgp_pub_encrypt(text, bytea, text); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.pgp_pub_encrypt(text, bytea, text) RETURNS bytea
    LANGUAGE c STRICT
    AS '$libdir/pgcrypto', 'pgp_pub_encrypt_text';


ALTER FUNCTION public.pgp_pub_encrypt(text, bytea, text) OWNER TO rpadmin;

--
-- Name: pgp_pub_encrypt_bytea(bytea, bytea); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.pgp_pub_encrypt_bytea(bytea, bytea) RETURNS bytea
    LANGUAGE c STRICT
    AS '$libdir/pgcrypto', 'pgp_pub_encrypt_bytea';


ALTER FUNCTION public.pgp_pub_encrypt_bytea(bytea, bytea) OWNER TO rpadmin;

--
-- Name: pgp_pub_encrypt_bytea(bytea, bytea, text); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.pgp_pub_encrypt_bytea(bytea, bytea, text) RETURNS bytea
    LANGUAGE c STRICT
    AS '$libdir/pgcrypto', 'pgp_pub_encrypt_bytea';


ALTER FUNCTION public.pgp_pub_encrypt_bytea(bytea, bytea, text) OWNER TO rpadmin;

--
-- Name: pgp_sym_decrypt(bytea, text); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.pgp_sym_decrypt(bytea, text) RETURNS text
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pgp_sym_decrypt_text';


ALTER FUNCTION public.pgp_sym_decrypt(bytea, text) OWNER TO rpadmin;

--
-- Name: pgp_sym_decrypt(bytea, text, text); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.pgp_sym_decrypt(bytea, text, text) RETURNS text
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pgp_sym_decrypt_text';


ALTER FUNCTION public.pgp_sym_decrypt(bytea, text, text) OWNER TO rpadmin;

--
-- Name: pgp_sym_decrypt_bytea(bytea, text); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.pgp_sym_decrypt_bytea(bytea, text) RETURNS bytea
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pgp_sym_decrypt_bytea';


ALTER FUNCTION public.pgp_sym_decrypt_bytea(bytea, text) OWNER TO rpadmin;

--
-- Name: pgp_sym_decrypt_bytea(bytea, text, text); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.pgp_sym_decrypt_bytea(bytea, text, text) RETURNS bytea
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pgp_sym_decrypt_bytea';


ALTER FUNCTION public.pgp_sym_decrypt_bytea(bytea, text, text) OWNER TO rpadmin;

--
-- Name: pgp_sym_encrypt(text, text); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.pgp_sym_encrypt(text, text) RETURNS bytea
    LANGUAGE c STRICT
    AS '$libdir/pgcrypto', 'pgp_sym_encrypt_text';


ALTER FUNCTION public.pgp_sym_encrypt(text, text) OWNER TO rpadmin;

--
-- Name: pgp_sym_encrypt(text, text, text); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.pgp_sym_encrypt(text, text, text) RETURNS bytea
    LANGUAGE c STRICT
    AS '$libdir/pgcrypto', 'pgp_sym_encrypt_text';


ALTER FUNCTION public.pgp_sym_encrypt(text, text, text) OWNER TO rpadmin;

--
-- Name: pgp_sym_encrypt_bytea(bytea, text); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.pgp_sym_encrypt_bytea(bytea, text) RETURNS bytea
    LANGUAGE c STRICT
    AS '$libdir/pgcrypto', 'pgp_sym_encrypt_bytea';


ALTER FUNCTION public.pgp_sym_encrypt_bytea(bytea, text) OWNER TO rpadmin;

--
-- Name: pgp_sym_encrypt_bytea(bytea, text, text); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.pgp_sym_encrypt_bytea(bytea, text, text) RETURNS bytea
    LANGUAGE c STRICT
    AS '$libdir/pgcrypto', 'pgp_sym_encrypt_bytea';


ALTER FUNCTION public.pgp_sym_encrypt_bytea(bytea, text, text) OWNER TO rpadmin;

--
-- Name: plpgsql_call_handler(); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.plpgsql_call_handler() RETURNS language_handler
    LANGUAGE c
    AS '$libdir/plpgsql', 'plpgsql_call_handler';


ALTER FUNCTION public.plpgsql_call_handler() OWNER TO rpadmin;

--
-- Name: sclass_selector; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.sclass_selector (
    faculty_id smallint,
    mine smallint,
    sort1 integer,
    sort2 integer,
    class_cluster integer,
    owner integer,
    namegrp text,
    sclass text,
    class_id integer NOT NULL
);


ALTER TABLE public.sclass_selector OWNER TO rpadmin;

--
-- Name: TABLE sclass_selector; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.sclass_selector IS 'result type for sclass_selector(userid)';


--
-- Name: sclass_selector(integer); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.sclass_selector(vuserid integer) RETURNS SETOF public.sclass_selector
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
begin return query
select scl.faculty_id,
       case
       when me.class_cluster=scl.class_cluster and me.faculty_id=scl.faculty_id then 0::smallint
       when me.class_cluster<>scl.class_cluster and me.faculty_id=scl.faculty_id then 1::smallint
       else 2::smallint end as mine,
       
       sort1,sort2,scl.class_cluster,scl.owner,f.faculty_short||'-'||cluster_name as namegrp,
       sclass||'#'||class_id||' count '||coalesce(student_count,0)  as sclass ,class_id
       from student_class scl
       join faculty f on (f.faculty_id=scl.faculty_id)
       left join class_cluster cc using(class_cluster)
       left join class_size using(class_id)
       cross join (select c.class_cluster,c.faculty_id
       from student s join student_class c using(class_id) where snummer=vuserid) me
;
end
$$;


ALTER FUNCTION public.sclass_selector(vuserid integer) OWNER TO rpadmin;

--
-- Name: swap_email12(integer); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.swap_email12(snuma integer) RETURNS integer
    LANGUAGE plpgsql
    AS $$
declare email_dummy alt_email%rowtype;
begin
	select * from alt_email
      	       where snummer=snuma
       	       for update into email_dummy;
	if not found then
	     raise exception 'cannot find alt email';
	end if;
	update alt_email a set email2 = (select email1 from student where snummer =snuma) where snummer=snuma;
	update student set email1= email_dummy.email2
	       where snummer=snuma and exists (select 1 from alt_email where snummer=snuma);
	return snuma;
end;
$$;


ALTER FUNCTION public.swap_email12(snuma integer) OWNER TO rpadmin;

--
-- Name: swap_email_project(integer); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.swap_email_project(prjm_ida integer) RETURNS integer
    LANGUAGE plpgsql
    AS $$
declare sr alt_email%rowtype;
declare i integer;
begin
	i := 0;
	for sr in select snummer,email2,email3 from alt_email join prj_grp using(snummer) join prj_tutor using (prjtg_id)   where prjm_id =prjm_ida loop
	    execute format('select swap_email12(%s::integer)', sr.snummer);
	    i:= i+1;
	end loop;
	return i;
end;
$$;


ALTER FUNCTION public.swap_email_project(prjm_ida integer) OWNER TO rpadmin;

--
-- Name: try_close(integer, integer); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.try_close(gid integer, stid integer) RETURNS boolean
    LANGUAGE plpgsql
    AS $$
   DECLARE allwritten BOOLEAN;
   DECLARE any_open BOOLEAN;
   DECLARE prj_tutor_open BOOLEAN;
   DECLARE assessment_complete BOOLEAN;
BEGIN
   -- close for this judge
   UPDATE prj_grp SET written=true,prj_grp_open=false WHERE prjtg_id=gid AND snummer=stid;
   SELECT bool_and(written) AS allwritten,
          bool_or(prj_grp_open) AS any_open
   INTO allwritten,any_open
   FROM public.prj_grp 
   JOIN prj_tutor USING(prjtg_id)
   WHERE prjtg_id=gid 
   GROUP by prjtg_id,prj_tutor.prj_tutor_open;
   
   -- if nothing to be done, return false
   IF (NOT allwritten OR any_open) THEN
   -- nothing to do
      RETURN FALSE; 
   ELSE 
   -- else, lose
      UPDATE prj_tutor SET prj_tutor_open=FALSE, assessment_complete=TRUE 
      WHERE prjtg_id=gid;
      RETURN TRUE;
   END IF;


end $$;


ALTER FUNCTION public.try_close(gid integer, stid integer) OWNER TO rpadmin;

--
-- Name: FUNCTION try_close(gid integer, stid integer); Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON FUNCTION public.try_close(gid integer, stid integer) IS 'Close prj_grp assessment when all other 
judges in group have written and closed';


--
-- Name: tutor_my_project_milestones(integer); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.tutor_my_project_milestones(peer_id integer) RETURNS TABLE(prjm_id integer, role_def text)
    LANGUAGE sql
    AS $$
	select distinct prjm_id, case when peer_id=owner_id then 'owner' else 'tutor' end as role from prj_milestone pm join project p using(prj_id) join prj_tutor pt using(prjm_id) where peer_id=p.owner_id or peer_id=pt.tutor_id 
$$;


ALTER FUNCTION public.tutor_my_project_milestones(peer_id integer) OWNER TO rpadmin;

--
-- Name: tutor_selector; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.tutor_selector (
    opl bigint,
    faculty_id smallint,
    mine smallint,
    namegrp text,
    name text,
    tutor character varying(5),
    userid integer
);


ALTER TABLE public.tutor_selector OWNER TO rpadmin;

--
-- Name: TABLE tutor_selector; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.tutor_selector IS 'result type for tutor_selector(userid)';


--
-- Name: tutor_selector(integer); Type: FUNCTION; Schema: public; Owner: rpadmin
--

CREATE FUNCTION public.tutor_selector(vuserid integer) RETURNS SETOF public.tutor_selector
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
begin return query
select s.opl,s.faculty_id,
       case
       when me.opl=s.opl and me.faculty_id=s.faculty_id then 0::smallint
       when me.opl<>s.opl and me.faculty_id=s.faculty_id then 1::smallint
       else 2::smallint end as mine,
       faculty_short||'-'||course_short as namegrp,
       achternaam||', '||roepnaam||coalesce(' '||tussenvoegsel,'')||' ['||tutor||': '||t.userid||']' as name,tutor,userid
       from tutor t
       join student s on(t.userid=s.snummer)
       join faculty f on (f.faculty_id=s.faculty_id)
       join fontys_course c on(s.opl=c.course)
       cross join (select opl,faculty_id
       from student where snummer=vuserid) me
;
end
$$;


ALTER FUNCTION public.tutor_selector(vuserid integer) OWNER TO rpadmin;

--
-- Name: blad1; Type: TABLE; Schema: importer; Owner: rpadmin
--

CREATE TABLE importer.blad1 (
    snummer integer,
    name text,
    grp_num integer
);


ALTER TABLE importer.blad1 OWNER TO rpadmin;

--
-- Name: sv05_20190829; Type: TABLE; Schema: importer; Owner: importer
--

CREATE TABLE importer.sv05_20190829 (
    draaidatum text,
    aanmelddatum text,
    instroom text,
    datum_van text,
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
    geslacht text,
    geboortedatum text,
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
    datum_definitief_ingeschreven text,
    datum_annulering date,
    start_in_1e_jaar text,
    bijvakker text,
    datum_aankomst_fontys text,
    datum_aankomst_instituut text,
    datum_aankomst_opleiding text,
    indicatie_collegegeld text,
    pasfoto_uploaddatum text,
    voorkeurstaal text,
    exchange_kenmerk character(4)
);


ALTER TABLE importer.sv05_20190829 OWNER TO importer;

--
-- Name: sv05_aanmelders; Type: TABLE; Schema: importer; Owner: importer
--

CREATE TABLE importer.sv05_aanmelders (
    sv05_aanmelders_id integer NOT NULL,
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
    geslacht text,
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
    datum_annulering text,
    start_in_1e_jaar text,
    bijvakker text,
    datum_aankomst_fontys date,
    datum_aankomst_instituut date,
    datum_aankomst_opleiding date,
    indicatie_collegegeld text,
    pasfoto_uploaddatum date,
    voorkeurstaal text,
    exchange_kenmerk text,
    postcode text,
    woonplaats text,
    huisnr character(4),
    straat text,
    course_grp text,
    lang character(2)
);


ALTER TABLE importer.sv05_aanmelders OWNER TO importer;

--
-- Name: sv05_aanmelders_sv05_aanmelders_id_seq; Type: SEQUENCE; Schema: importer; Owner: importer
--

CREATE SEQUENCE importer.sv05_aanmelders_sv05_aanmelders_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE importer.sv05_aanmelders_sv05_aanmelders_id_seq OWNER TO importer;

--
-- Name: sv05_aanmelders_sv05_aanmelders_id_seq; Type: SEQUENCE OWNED BY; Schema: importer; Owner: importer
--

ALTER SEQUENCE importer.sv05_aanmelders_sv05_aanmelders_id_seq OWNED BY importer.sv05_aanmelders.sv05_aanmelders_id;


--
-- Name: sv09_import_summary; Type: TABLE; Schema: importer; Owner: rpadmin
--

CREATE TABLE importer.sv09_import_summary (
    x integer,
    comment character varying(40),
    "row" integer
);


ALTER TABLE importer.sv09_import_summary OWNER TO rpadmin;

--
-- Name: sv09_ingeschrevenen; Type: TABLE; Schema: importer; Owner: importer
--

CREATE TABLE importer.sv09_ingeschrevenen (
    draaidatum date,
    studiejaar integer,
    instituutcode integer,
    instituutnaam text,
    directeur text,
    studentnummer integer,
    achternaam text,
    voorvoegsels text,
    voorletters text,
    voornamen text,
    roepnaam text,
    volledig_naam text,
    geslacht text,
    geboortedatum date,
    geboorteplaats text,
    geboorteland text,
    "e_mail_privé" text,
    e_mail_instelling text,
    land_nummer_mobiel integer,
    mobiel_nummer text,
    land_nummer_vast_centrale_verificatie integer,
    vast_nummer_centrale_verificatie text,
    land_nummer_vast_decentrale_verificatie integer,
    vast_nummer_decentrale_verificatie text,
    pcn_nummer integer,
    onderwijsnummer text,
    straat text,
    huisnummer text,
    huisnummertoevoeging text,
    postcode text,
    woonplaats text,
    buitenlandse_adresregel_1 character(2),
    buitenlandse_adresregel_2 character(2),
    buitenlandse_adresregel_3 character(2),
    land text,
    nationaliteit_1 text,
    nationaliteit_2 text,
    leidende_nationaliteit text,
    eer text,
    inschrijvingid integer,
    isatcode integer,
    opleiding text,
    opleidingsnaam_voluit text,
    opleidingsnaam_voluit_engels text,
    studielinkvariantcode integer,
    variant_omschrijving text,
    lesplaats text,
    vorm text,
    fase text,
    bijvakker character(4),
    datum_van date,
    datum_tot date,
    datum_aankomst_fontys date,
    datum_aankomst_instituut date,
    datum_aankomst_opleiding date,
    examen_datum character(2),
    judicium_datum character(2),
    examen_behaald text,
    aanmelddatum date,
    instroom text,
    dipl_vooropl_behaald character(4),
    datum_dipl_vooropl date,
    detail_toelaatbare_vooropleiding text,
    cluster_vooropl text,
    toeleverende_school text,
    plaats_toeleverende_school text,
    soort_verzoek text,
    datum_definitief_ingeschreven date,
    lesgroep text,
    slb_groep text,
    indicatie_collegegeld text,
    pasfoto_uploaddatum text,
    voorkeurstaal text,
    kop_opleiding character(2)
);


ALTER TABLE importer.sv09_ingeschrevenen OWNER TO importer;

--
-- Name: worksheet; Type: TABLE; Schema: importer; Owner: rpadmin
--

CREATE TABLE importer.worksheet (
    snummer integer,
    achternaam text,
    roepnaam text,
    java1 double precision,
    java2 double precision,
    mod1 double precision,
    sen1 double precision,
    q double precision,
    grp_num integer,
    fakecolumn1 integer,
    fakecolumn2 character(4)
);


ALTER TABLE importer.worksheet OWNER TO rpadmin;

--
-- Name: absence_reason; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.absence_reason (
    act_id integer NOT NULL,
    snummer integer NOT NULL,
    reason text NOT NULL
);


ALTER TABLE public.absence_reason OWNER TO rpadmin;

--
-- Name: TABLE absence_reason; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.absence_reason IS 'absence in activity, if any reason, like sickness.';


SET default_with_oids = true;

--
-- Name: activity_participant; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.activity_participant (
    act_id integer NOT NULL,
    snummer integer NOT NULL,
    presence character(1) DEFAULT 'P'::bpchar
);


ALTER TABLE public.activity_participant OWNER TO rpadmin;

--
-- Name: TABLE activity_participant; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.activity_participant IS 'Participants to activity or event (e.g. collo).
';


--
-- Name: act_part_count; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.act_part_count AS
 SELECT activity_participant.act_id,
    count(*) AS count
   FROM public.activity_participant
  GROUP BY activity_participant.act_id;


ALTER TABLE public.act_part_count OWNER TO rpadmin;

--
-- Name: VIEW act_part_count; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.act_part_count IS 'count participants in activity. For reporting.';


--
-- Name: activity; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.activity (
    act_id integer NOT NULL,
    datum date DEFAULT now(),
    short character varying(30) DEFAULT 'Unnamed activity'::character varying,
    description text DEFAULT 'description will follow'::text,
    act_type smallint DEFAULT 0,
    part smallint DEFAULT 1 NOT NULL,
    start_time time without time zone DEFAULT '08:45:00'::time without time zone,
    prjm_id integer NOT NULL
);


ALTER TABLE public.activity OWNER TO rpadmin;

--
-- Name: TABLE activity; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.activity IS 'Presence registration for colloquium, practicum etc.';


--
-- Name: grp_alias; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.grp_alias (
    long_name character varying(40),
    alias character(15),
    website character varying(128),
    productname character varying(128) DEFAULT ''::character varying,
    prjtg_id integer NOT NULL,
    youtube_link character varying(128),
    youtube_icon_url character varying(128)
);


ALTER TABLE public.grp_alias OWNER TO rpadmin;

--
-- Name: TABLE grp_alias; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.grp_alias IS 'aliases for groups that have one';


SET default_with_oids = false;

--
-- Name: prj_grp; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.prj_grp (
    snummer integer NOT NULL,
    prj_grp_open boolean DEFAULT false,
    written boolean DEFAULT false,
    prjtg_id integer NOT NULL
);


ALTER TABLE public.prj_grp OWNER TO rpadmin;

--
-- Name: TABLE prj_grp; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.prj_grp IS 'group is student in project-milestone and tutor.';


--
-- Name: prjm_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.prjm_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.prjm_id_seq OWNER TO rpadmin;

--
-- Name: prj_milestone; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.prj_milestone (
    prj_id integer NOT NULL,
    milestone smallint DEFAULT 1 NOT NULL,
    prj_milestone_open boolean DEFAULT false,
    assessment_due date DEFAULT (('now'::text)::date + 28),
    prjm_id integer DEFAULT nextval('public.prjm_id_seq'::regclass) NOT NULL,
    weight integer DEFAULT 1,
    milestone_name character varying(20) DEFAULT 'Milestone'::character varying,
    has_assessment boolean DEFAULT false,
    public boolean DEFAULT false
);


ALTER TABLE public.prj_milestone OWNER TO rpadmin;

--
-- Name: TABLE prj_milestone; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.prj_milestone IS 'project milstone with due date';


--
-- Name: prj_tutor; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.prj_tutor (
    grp_num smallint DEFAULT 1 NOT NULL,
    prjm_id integer NOT NULL,
    prjtg_id integer NOT NULL,
    prj_tutor_open boolean DEFAULT false NOT NULL,
    assessment_complete boolean DEFAULT false NOT NULL,
    tutor_grade numeric(3,1) DEFAULT 7.0,
    tutor_id integer,
    grp_name character varying(15)
);


ALTER TABLE public.prj_tutor OWNER TO rpadmin;

--
-- Name: TABLE prj_tutor; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.prj_tutor IS 'group tutor, defines group name, group number.';


--
-- Name: act_presence_list2; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.act_presence_list2 AS
 SELECT act.act_id,
    act.datum,
    act.short,
    act.description,
    act.act_type,
    act.part,
    act.start_time,
    act.prjm_id,
    cand.snummer,
    COALESCE(ga.alias, (('g'::text || pt.grp_num))::bpchar) AS agroup,
    ap.presence AS present,
    ar.reason AS note
   FROM ((((((public.prj_grp cand
     JOIN public.prj_tutor pt ON ((cand.prjtg_id = pt.prjtg_id)))
     JOIN public.prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
     LEFT JOIN public.grp_alias ga ON ((ga.prjtg_id = pt.prjtg_id)))
     JOIN public.activity act ON ((pm.prjm_id = act.prjm_id)))
     LEFT JOIN public.activity_participant ap ON (((cand.snummer = ap.snummer) AND (act.act_id = ap.act_id))))
     LEFT JOIN public.absence_reason ar ON (((ap.snummer = ar.snummer) AND (ar.act_id = ap.act_id))));


ALTER TABLE public.act_presence_list2 OWNER TO rpadmin;

--
-- Name: VIEW act_presence_list2; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.act_presence_list2 IS 'creates activity presence list';


SET default_with_oids = true;

--
-- Name: fontys_course; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.fontys_course (
    course bigint NOT NULL,
    course_description character varying(64),
    faculty_id smallint,
    course_short character(4)
);


ALTER TABLE public.fontys_course OWNER TO rpadmin;

--
-- Name: TABLE fontys_course; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.fontys_course IS 'Fontys courses known by peerweb. A course is same to dutch  ''curriculum'' .
';


SET default_with_oids = false;

--
-- Name: student; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.student (
    snummer integer NOT NULL,
    achternaam text,
    tussenvoegsel text,
    voorletters text,
    roepnaam text,
    straat text,
    huisnr character(4),
    pcode text,
    plaats text,
    email1 public.email,
    nationaliteit character(2) DEFAULT 'NL'::bpchar,
    cohort smallint DEFAULT date_part('year'::text, (now())::date) NOT NULL,
    gebdat date,
    sex character(1) DEFAULT 'M'::bpchar,
    lang character(2) DEFAULT 'NL'::bpchar,
    pcn integer,
    opl bigint DEFAULT 0,
    phone_home text,
    phone_gsm text,
    phone_postaddress text,
    faculty_id smallint DEFAULT 0,
    hoofdgrp text DEFAULT 'NEW'::bpchar,
    active boolean DEFAULT false,
    slb integer,
    land character(3) DEFAULT 'NLD'::bpchar,
    studieplan integer,
    geboorteplaats text,
    geboorteland character(3),
    voornamen text,
    class_id integer DEFAULT 0,
    CONSTRAINT student_age CHECK ((gebdat < ((now())::date - '15 years'::interval)))
);


ALTER TABLE public.student OWNER TO rpadmin;

--
-- Name: TABLE student; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.student IS 'user data e.g. n.a.r.';


--
-- Name: COLUMN student.snummer; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON COLUMN public.student.snummer IS 'Student nummer';


--
-- Name: COLUMN student.achternaam; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON COLUMN public.student.achternaam IS 'naam';


--
-- Name: COLUMN student.tussenvoegsel; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON COLUMN public.student.tussenvoegsel IS 'naam zoals van den';


--
-- Name: COLUMN student.voorletters; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON COLUMN public.student.voorletters IS 'naam';


--
-- Name: COLUMN student.roepnaam; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON COLUMN public.student.roepnaam IS 'naam';


--
-- Name: COLUMN student.email1; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON COLUMN public.student.email1 IS 'fontys email adres';


--
-- Name: COLUMN student.nationaliteit; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON COLUMN public.student.nationaliteit IS 'nationaliteit, NL, DE of bijv PL BE etc';


--
-- Name: COLUMN student.cohort; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON COLUMN public.student.cohort IS 'jaar van binnenkomst';


--
-- Name: COLUMN student.gebdat; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON COLUMN public.student.gebdat IS 'geboortedatum, voor toegang';


--
-- Name: COLUMN student.lang; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON COLUMN public.student.lang IS 'taal, NL,DE of EN';


--
-- Name: COLUMN student.pcn; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON COLUMN public.student.pcn IS 'fontys pcn';


SET default_with_oids = true;

--
-- Name: student_class; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.student_class (
    sort1 integer DEFAULT 1,
    sort2 integer DEFAULT 1,
    comment text DEFAULT 'XXX'::text,
    faculty_id smallint DEFAULT 0,
    class_id integer NOT NULL,
    sclass text,
    class_cluster integer DEFAULT 0,
    owner integer DEFAULT 0
);


ALTER TABLE public.student_class OWNER TO rpadmin;

--
-- Name: TABLE student_class; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.student_class IS 'classes used in student selection';


SET default_with_oids = false;

--
-- Name: studieplan; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.studieplan (
    studieplan integer NOT NULL,
    studieplan_omschrijving character(64),
    studieplan_short character(10),
    studieprogr bigint,
    variant_omschrijving text
);


ALTER TABLE public.studieplan OWNER TO rpadmin;

--
-- Name: TABLE studieplan; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.studieplan IS 'descrition of study plan. Lang and course.';


--
-- Name: tutor; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.tutor (
    tutor character varying(5) NOT NULL,
    userid integer NOT NULL,
    faculty_id smallint DEFAULT 0,
    team character varying(30),
    office character varying(30) DEFAULT 'Lecturer'::character varying,
    building character varying(30) DEFAULT 'W1'::character varying,
    city character varying(30) DEFAULT 'Venlo'::character varying,
    room character varying(10),
    office_phone character varying(15),
    schedule_id character varying(20),
    display_name character varying(80),
    teaches boolean DEFAULT false
);


ALTER TABLE public.tutor OWNER TO rpadmin;

--
-- Name: TABLE tutor; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.tutor IS 'users with tutor role and some attributes';


--
-- Name: active_47; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.active_47 AS
 SELECT s.snummer,
    s.achternaam,
    s.tussenvoegsel,
    s.voorletters,
    s.roepnaam,
    s.straat,
    s.huisnr,
    s.pcode,
    s.plaats,
    s.email1,
    s.nationaliteit,
    s.cohort,
    s.gebdat,
    s.sex,
    s.lang,
    s.pcn,
    s.opl,
    s.phone_home,
    s.phone_gsm,
    s.phone_postaddress,
    s.faculty_id,
    s.hoofdgrp,
    s.active,
    s.slb,
    s.land,
    s.studieplan,
    s.geboorteplaats,
    s.geboorteland,
    s.voornamen,
    s.class_id,
    sc.sclass AS groep,
    fc.course_short AS opleiding,
    sp.variant_omschrijving
   FROM (((public.student s
     JOIN public.student_class sc USING (class_id))
     JOIN public.fontys_course fc ON ((s.opl = fc.course)))
     JOIN public.studieplan sp USING (studieplan))
  WHERE ((s.faculty_id = 47) AND s.active AND (NOT (s.snummer IN ( SELECT tutor.userid
           FROM public.tutor))))
  ORDER BY s.studieplan, s.cohort, s.achternaam, s.roepnaam;


ALTER TABLE public.active_47 OWNER TO rpadmin;

--
-- Name: activity_act_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.activity_act_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.activity_act_id_seq OWNER TO rpadmin;

--
-- Name: activity_act_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.activity_act_id_seq OWNED BY public.activity.act_id;


SET default_with_oids = true;

--
-- Name: activity_project; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.activity_project (
    prj_id integer NOT NULL
);


ALTER TABLE public.activity_project OWNER TO rpadmin;

--
-- Name: TABLE activity_project; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.activity_project IS 'List the projects that have activities for which precense must be recorded.';


--
-- Name: activity_type; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.activity_type (
    act_type smallint NOT NULL,
    act_type_descr character varying(30)
);


ALTER TABLE public.activity_type OWNER TO rpadmin;

--
-- Name: TABLE activity_type; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.activity_type IS 'type of recorded activtiy, like collo, excursion, practicum. ';


--
-- Name: additional_course; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.additional_course (
    snummer integer NOT NULL,
    course_code bigint NOT NULL
);


ALTER TABLE public.additional_course OWNER TO rpadmin;

--
-- Name: TABLE additional_course; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.additional_course IS 'For students that follow more than  one course (eg ipo+wtb or LenE and TV).';


--
-- Name: additional_course_descr; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.additional_course_descr AS
 SELECT additional_course.snummer,
    additional_course.course_code,
    fontys_course.course,
    fontys_course.course_description,
    fontys_course.faculty_id AS institute,
    fontys_course.course_short AS abre
   FROM (public.additional_course
     JOIN public.fontys_course ON ((additional_course.course_code = fontys_course.course)));


ALTER TABLE public.additional_course_descr OWNER TO rpadmin;

--
-- Name: VIEW additional_course_descr; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.additional_course_descr IS 'describes additional course student is registered to';


SET default_with_oids = false;

--
-- Name: aden; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.aden (
    studieplan integer,
    studieplan_omschrijving character(64),
    studieplan_short character(10),
    studieprogr integer,
    variant_omschrijving text
);


ALTER TABLE public.aden OWNER TO rpadmin;

--
-- Name: aldaview; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.aldaview AS
 SELECT 1 AS milestone,
    ('/home/svn/2013/alda/g'::text || prj_tutor.grp_num) AS repospath,
    ('alda g'::text || prj_tutor.grp_num) AS description,
    false AS isroot,
    ('/svn/2013/alda/g'::text || prj_tutor.grp_num) AS url_tail,
    prj_tutor.tutor_id AS owner,
    prj_tutor.grp_num,
    prj_tutor.prjm_id,
    prj_tutor.prjtg_id
   FROM public.prj_tutor
  WHERE (prj_tutor.prjm_id = 535);


ALTER TABLE public.aldaview OWNER TO rpadmin;

--
-- Name: alien_email; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.alien_email AS
 SELECT student.snummer
   FROM public.student
  WHERE (((student.email1)::text !~~ '%fontys.nl'::text) AND (student.hoofdgrp !~~ 'ALU%'::text));


ALTER TABLE public.alien_email OWNER TO rpadmin;

--
-- Name: VIEW alien_email; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.alien_email IS 'email not fitting the fontys mold, except alumni';


SET default_with_oids = true;

--
-- Name: alt_email; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.alt_email (
    snummer integer NOT NULL,
    email2 public.email,
    email3 public.email,
    CONSTRAINT emails_distinct CHECK (((email2)::text <> (email3)::text))
);


ALTER TABLE public.alt_email OWNER TO rpadmin;

--
-- Name: TABLE alt_email; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.alt_email IS 'alternate email addresses';


SET default_with_oids = false;

--
-- Name: alumni_email; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.alumni_email (
    snummer integer,
    roepnaam text,
    achternaam text,
    email2 public.email,
    email3 text,
    geslaagd text,
    opleiding text,
    lang character(4),
    adres text,
    telefoon text,
    huidige_werkgever text
);


ALTER TABLE public.alumni_email OWNER TO rpadmin;

--
-- Name: all_alumni_email; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.all_alumni_email AS
 SELECT s.snummer,
    s.class_id,
    s.achternaam,
    s.roepnaam,
    s.email1,
    ae.email2,
    ae.email3,
    a.email2 AS email4,
    a.email3 AS email5
   FROM ((public.student s
     LEFT JOIN public.alt_email ae ON ((s.snummer = ae.snummer)))
     LEFT JOIN public.alumni_email a ON ((s.snummer = a.snummer)));


ALTER TABLE public.all_alumni_email OWNER TO rpadmin;

--
-- Name: all_email; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.all_email AS
 SELECT student.snummer,
    student.achternaam,
    student.roepnaam,
    student.email1,
    alt_email.email2,
    alt_email.email3,
    student.class_id
   FROM (public.student
     JOIN public.alt_email USING (snummer));


ALTER TABLE public.all_email OWNER TO rpadmin;

--
-- Name: project; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.project (
    prj_id integer NOT NULL,
    afko character(10),
    description character varying(30),
    year smallint DEFAULT date_part('year'::text, now()),
    comment text,
    valid_until date DEFAULT (now() + '6 mons'::interval),
    termendyear smallint,
    course bigint DEFAULT 112,
    owner_id integer NOT NULL
);


ALTER TABLE public.project OWNER TO rpadmin;

--
-- Name: TABLE project; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.project IS 'project data';


--
-- Name: all_prj_tutor; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.all_prj_tutor AS
 SELECT prj_tutor.prjtg_id,
    prj_milestone.prj_id,
    prj_milestone.prjm_id,
    t.tutor,
    prj_tutor.tutor_id,
    prj_tutor.grp_num,
    prj_tutor.grp_name,
    prj_tutor.prj_tutor_open,
    prj_tutor.assessment_complete,
    prj_milestone.milestone,
    prj_milestone.prj_milestone_open,
    prj_milestone.assessment_due,
    prj_milestone.weight,
    prj_milestone.milestone_name,
    prj_milestone.has_assessment,
    project.afko,
    project.description,
    project.year,
    tt.tutor AS tutor_owner,
    project.comment,
    project.valid_until,
    project.termendyear,
    project.course,
    project.owner_id,
    grp_alias.long_name,
    grp_alias.alias,
    grp_alias.website,
    grp_alias.productname,
    prj_tutor.tutor_grade
   FROM (((((public.prj_tutor
     JOIN public.prj_milestone USING (prjm_id))
     JOIN public.project USING (prj_id))
     JOIN public.tutor t ON ((t.userid = prj_tutor.tutor_id)))
     JOIN public.tutor tt ON ((tt.userid = project.owner_id)))
     LEFT JOIN public.grp_alias USING (prjtg_id));


ALTER TABLE public.all_prj_tutor OWNER TO rpadmin;

--
-- Name: VIEW all_prj_tutor; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.all_prj_tutor IS 'all from prj_tutor (by prjtg_id) up to project';


--
-- Name: all_prj_tutor_y; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.all_prj_tutor_y AS
 SELECT prj_tutor.prjtg_id,
    prj_milestone.prj_id,
    prj_tutor.prjm_id,
    t.tutor,
    prj_tutor.grp_num,
    prj_tutor.grp_name,
    prj_tutor.prj_tutor_open,
    prj_tutor.assessment_complete,
    prj_milestone.milestone,
    prj_milestone.prj_milestone_open,
    prj_milestone.assessment_due,
    prj_milestone.weight,
    prj_milestone.milestone_name,
    prj_milestone.has_assessment,
    project.afko,
    project.description,
    project.year,
    tt.tutor AS tutor_owner,
    project.comment,
    project.valid_until,
    project.termendyear,
    project.course,
    project.owner_id,
    tt.faculty_id
   FROM ((((public.prj_tutor
     JOIN public.tutor t ON ((prj_tutor.tutor_id = t.userid)))
     JOIN public.prj_milestone USING (prjm_id))
     JOIN public.project USING (prj_id))
     JOIN public.tutor tt ON ((project.owner_id = tt.userid)));


ALTER TABLE public.all_prj_tutor_y OWNER TO rpadmin;

--
-- Name: all_project_milestone; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.all_project_milestone AS
 SELECT project.prj_id,
    project.afko AS project,
    project.description AS project_description,
    project.year AS project_year,
    project.comment AS project_comment,
    project.valid_until,
    project.termendyear,
    project.course,
    project.owner_id,
    prj_milestone.milestone,
    prj_milestone.prj_milestone_open,
    prj_milestone.assessment_due,
    prj_milestone.prjm_id,
    prj_milestone.weight,
    prj_milestone.milestone_name,
    prj_milestone.has_assessment
   FROM (public.project
     JOIN public.prj_milestone USING (prj_id));


ALTER TABLE public.all_project_milestone OWNER TO rpadmin;

--
-- Name: project_scribe; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.project_scribe (
    prj_id integer NOT NULL,
    scribe integer NOT NULL,
    project_scribe_id integer NOT NULL
);


ALTER TABLE public.project_scribe OWNER TO rpadmin;

--
-- Name: TABLE project_scribe; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.project_scribe IS 'records per project who may record task grades and presence list entries.';


--
-- Name: all_project_scribe; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.all_project_scribe AS
 SELECT ps.prj_id,
    ps.scribe
   FROM public.project_scribe ps
UNION
 SELECT pm.prj_id,
    pt.tutor_id AS scribe
   FROM (public.prj_milestone pm
     JOIN public.prj_tutor pt ON ((pm.prjm_id = pt.prjm_id)));


ALTER TABLE public.all_project_scribe OWNER TO rpadmin;

--
-- Name: VIEW all_project_scribe; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.all_project_scribe IS 'tutors and scribes can update presence and tasks';


--
-- Name: all_tab_columns; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.all_tab_columns AS
 SELECT lower((pg_get_userbyid(tab.relowner))::text) AS owner,
    lower((tab.relname)::text) AS table_name,
    lower((col.attname)::text) AS column_name,
    lower((typ.typname)::text) AS data_type,
    col.attlen AS data_length,
    col.attnum AS column_id,
        CASE
            WHEN col.attnotnull THEN 'N'::bpchar
            ELSE 'Y'::bpchar
        END AS nullable,
    dflt.adsrc AS data_default
   FROM pg_class tab,
    pg_type typ,
    (pg_attribute col
     LEFT JOIN pg_attrdef dflt ON (((dflt.adrelid = col.attrelid) AND (dflt.adnum = col.attnum))))
  WHERE ((tab.oid = col.attrelid) AND (typ.oid = col.atttypid) AND (col.attnum > 0));


ALTER TABLE public.all_tab_columns OWNER TO rpadmin;

--
-- Name: VIEW all_tab_columns; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.all_tab_columns IS 'describes details for relations; is used in ste (simple table editor)';


--
-- Name: alu_student_mail; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.alu_student_mail AS
 SELECT student.snummer
   FROM (public.student
     JOIN public.alt_email USING (snummer))
  WHERE (((student.email1)::text ~~ '%student.fontys.nl%'::text) AND ((alt_email.email2)::text !~~ '%student.fontys.nl%'::text) AND (student.class_id = 363));


ALTER TABLE public.alu_student_mail OWNER TO rpadmin;

--
-- Name: alumnus; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.alumnus AS
 SELECT s.snummer
   FROM (public.student s
     JOIN public.student_class c USING (class_id))
  WHERE (c.sclass ~~ ('ALUMN%'::bpchar)::text);


ALTER TABLE public.alumnus OWNER TO rpadmin;

--
-- Name: VIEW alumnus; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.alumnus IS 'alumni are in student_class ALUMNI';


--
-- Name: any_query; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.any_query (
    any_query_id integer NOT NULL,
    owner integer,
    query_name character varying(30),
    query_comment text,
    query text,
    active boolean DEFAULT true
);


ALTER TABLE public.any_query OWNER TO rpadmin;

--
-- Name: TABLE any_query; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.any_query IS 'Saved queries. Not operational on 20130712.';


--
-- Name: any_query_any_query_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.any_query_any_query_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.any_query_any_query_id_seq OWNER TO rpadmin;

--
-- Name: any_query_any_query_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.any_query_any_query_id_seq OWNED BY public.any_query.any_query_id;


--
-- Name: arbeitsaemterberatungstellen; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.arbeitsaemterberatungstellen (
    _id integer NOT NULL,
    naam_amt character varying(39),
    aan character varying(25),
    adres character varying(26),
    woonplaats character varying(36),
    telefon character varying(17),
    telefon_alt character varying(16),
    telefax character varying(15),
    email character varying(36)
);


ALTER TABLE public.arbeitsaemterberatungstellen OWNER TO rpadmin;

--
-- Name: TABLE arbeitsaemterberatungstellen; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.arbeitsaemterberatungstellen IS 'For recruitement.';


--
-- Name: arbeitsaemterberatungstellen__id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.arbeitsaemterberatungstellen__id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.arbeitsaemterberatungstellen__id_seq OWNER TO rpadmin;

--
-- Name: arbeitsaemterberatungstellen__id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.arbeitsaemterberatungstellen__id_seq OWNED BY public.arbeitsaemterberatungstellen._id;


--
-- Name: assessment_remarks; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.assessment_remarks (
    contestant integer NOT NULL,
    judge integer NOT NULL,
    prjtg_id integer NOT NULL,
    remark text NOT NULL,
    id integer NOT NULL
);


ALTER TABLE public.assessment_remarks OWNER TO rpadmin;

--
-- Name: assessement_remark_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.assessement_remark_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.assessement_remark_id_seq OWNER TO rpadmin;

--
-- Name: assessement_remark_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.assessement_remark_id_seq OWNED BY public.assessment_remarks.id;


--
-- Name: base_criteria; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.base_criteria (
    criterium_id smallint DEFAULT nextval(('criterium_id_seq'::text)::regclass) NOT NULL,
    author integer,
    nl_short character varying(80),
    de_short character varying(80),
    en_short character varying(80),
    nl character varying(200),
    de character varying(200),
    en character varying(200)
);


ALTER TABLE public.base_criteria OWNER TO rpadmin;

--
-- Name: TABLE base_criteria; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.base_criteria IS 'Reusable set of criteria in 3 languages.';


--
-- Name: prjm_criterium; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.prjm_criterium (
    prjm_id integer NOT NULL,
    criterium_id smallint NOT NULL
);


ALTER TABLE public.prjm_criterium OWNER TO rpadmin;

--
-- Name: TABLE prjm_criterium; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.prjm_criterium IS 'selected criterium for assessment in project/mil.';


--
-- Name: criteria_v; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.criteria_v AS
 SELECT prjm_criterium.prjm_id,
    prjm_criterium.criterium_id AS criterium,
    base_criteria.nl_short,
    base_criteria.de_short,
    base_criteria.en_short,
    base_criteria.nl,
    base_criteria.de,
    base_criteria.en
   FROM (public.prjm_criterium
     JOIN public.base_criteria USING (criterium_id));


ALTER TABLE public.criteria_v OWNER TO rpadmin;

--
-- Name: assessment_builder3; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.assessment_builder3 AS
 SELECT c.snummer AS contestant,
    j.snummer AS judge,
    cr.criterium,
    0 AS grade,
    j.prjtg_id,
    pt.prjm_id
   FROM (((public.prj_grp j
     JOIN public.prj_grp c USING (prjtg_id))
     JOIN public.prj_tutor pt USING (prjtg_id))
     JOIN public.criteria_v cr ON ((pt.prjm_id = cr.prjm_id)))
  WHERE (j.snummer <> c.snummer);


ALTER TABLE public.assessment_builder3 OWNER TO rpadmin;

--
-- Name: assessment_commit; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.assessment_commit (
    snummer integer NOT NULL,
    commit_time timestamp without time zone NOT NULL,
    prjtg_id integer NOT NULL,
    assessment_commit_id integer NOT NULL
);


ALTER TABLE public.assessment_commit OWNER TO rpadmin;

--
-- Name: TABLE assessment_commit; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.assessment_commit IS 'to keep track of the commits by filling in the forms';


--
-- Name: assessment_commit_assessment_commit_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.assessment_commit_assessment_commit_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.assessment_commit_assessment_commit_id_seq OWNER TO rpadmin;

--
-- Name: assessment_commit_assessment_commit_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.assessment_commit_assessment_commit_id_seq OWNED BY public.assessment_commit.assessment_commit_id;


--
-- Name: assessment_group_notready; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.assessment_group_notready AS
 SELECT DISTINCT assessment.prjtg_id
   FROM public.assessment
  WHERE (assessment.grade = 0)
  GROUP BY assessment.prjtg_id;


ALTER TABLE public.assessment_group_notready OWNER TO rpadmin;

--
-- Name: VIEW assessment_group_notready; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.assessment_group_notready IS 'lists prjtg_id where exists grade =0 (not graded)';


--
-- Name: assessment_group_ready; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.assessment_group_ready AS
 SELECT pm.prj_id,
    pm.milestone,
    pt.grp_num
   FROM ((( SELECT DISTINCT assessment.prjtg_id
           FROM public.assessment
          WHERE (NOT (assessment.prjtg_id IN ( SELECT DISTINCT assessment.prjtg_id
                  WHERE (assessment.grade = 0)
                  ORDER BY assessment.prjtg_id)))
          GROUP BY assessment.prjtg_id
          ORDER BY assessment.prjtg_id) rdy
     JOIN public.prj_tutor pt ON ((rdy.prjtg_id = pt.prjtg_id)))
     JOIN public.prj_milestone pm ON ((pm.prjm_id = pt.prjtg_id)));


ALTER TABLE public.assessment_group_ready OWNER TO rpadmin;

--
-- Name: VIEW assessment_group_ready; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.assessment_group_ready IS 'select groups that are ready';


--
-- Name: assessment_groups; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.assessment_groups AS
 SELECT DISTINCT a.judge AS snummer,
    a.prjtg_id
   FROM public.assessment a;


ALTER TABLE public.assessment_groups OWNER TO rpadmin;

--
-- Name: VIEW assessment_groups; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.assessment_groups IS 'needed by student_project_attributes';


--
-- Name: assessment_groups2; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.assessment_groups2 AS
 SELECT DISTINCT assessment.judge AS snummer,
    assessment.prjtg_id
   FROM public.assessment;


ALTER TABLE public.assessment_groups2 OWNER TO rpadmin;

--
-- Name: VIEW assessment_groups2; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.assessment_groups2 IS 'needed by student_project_attributes and student milestone selector';


--
-- Name: assessment_grp_open; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.assessment_grp_open AS
 SELECT pm.prj_id,
    pm.milestone,
    pt.grp_num,
    pt.prjtg_id,
        CASE
            WHEN (sum(
            CASE
                WHEN pg.prj_grp_open THEN 1
                ELSE 0
            END) > 0) THEN true
            ELSE false
        END AS open
   FROM ((public.prj_grp pg
     JOIN public.prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN public.prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
  GROUP BY pt.grp_num, pm.prj_id, pt.prjtg_id, pm.milestone
  ORDER BY pt.grp_num;


ALTER TABLE public.assessment_grp_open OWNER TO rpadmin;

--
-- Name: VIEW assessment_grp_open; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.assessment_grp_open IS 'used for selector in groupresult';


--
-- Name: assessment_grp_open2; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.assessment_grp_open2 AS
 SELECT prj_grp.prjtg_id,
    bool_or(prj_grp.prj_grp_open) AS open
   FROM public.prj_grp
  GROUP BY prj_grp.prjtg_id;


ALTER TABLE public.assessment_grp_open2 OWNER TO rpadmin;

--
-- Name: assessment_milestones; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.assessment_milestones AS
 SELECT DISTINCT prj_tutor.prjm_id
   FROM (public.assessment_groups2
     JOIN public.prj_tutor USING (prjtg_id));


ALTER TABLE public.assessment_milestones OWNER TO rpadmin;

--
-- Name: assessment_projects; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.assessment_projects AS
 SELECT DISTINCT pm.prj_id,
    pm.milestone
   FROM ((public.assessment a
     JOIN public.prj_tutor pt ON ((a.prjtg_id = pt.prjtg_id)))
     JOIN public.prj_milestone pm ON ((pm.prjm_id = pt.prjtg_id)))
  ORDER BY pm.prj_id, pm.milestone;


ALTER TABLE public.assessment_projects OWNER TO rpadmin;

--
-- Name: VIEW assessment_projects; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.assessment_projects IS 'used by ipeer.php';


--
-- Name: assessment_remarks_view; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.assessment_remarks_view AS
 SELECT ar.prjtg_id,
    ar.contestant,
    ar.judge,
    j.achternaam AS jachternaam,
    j.roepnaam AS jroepnaam,
    c.achternaam AS cachternaam,
    c.roepnaam AS croepnaam,
    (((j.roepnaam || COALESCE((' '::text || j.tussenvoegsel), ''::text)) || ' '::text) || j.achternaam) AS jname,
    (((c.roepnaam || COALESCE((' '::text || c.tussenvoegsel), ''::text)) || ' '::text) || c.achternaam) AS cname,
    ar.remark
   FROM ((public.assessment_remarks ar
     JOIN public.student j ON ((j.snummer = ar.judge)))
     JOIN public.student c ON ((c.snummer = ar.contestant)));


ALTER TABLE public.assessment_remarks_view OWNER TO rpadmin;

--
-- Name: assessment_tr; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.assessment_tr AS
 SELECT assessment.prjtg_id,
    assessment.contestant,
    assessment.judge,
    assessment.criterium,
    assessment.grade
   FROM public.assessment;


ALTER TABLE public.assessment_tr OWNER TO rpadmin;

--
-- Name: VIEW assessment_tr; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.assessment_tr IS 'assessment with prj_id,milestone,grp_num and prjm_id dropped';


--
-- Name: assessment_zero_count; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.assessment_zero_count AS
 SELECT pm.prj_id,
    pm.milestone,
    pt.grp_num,
    gz.gcount AS count
   FROM ((( SELECT count(assessment.grade) AS gcount,
            assessment.prjtg_id
           FROM public.assessment
          WHERE (assessment.grade = 0)
          GROUP BY assessment.prjtg_id) gz
     JOIN public.prj_tutor pt ON ((gz.prjtg_id = pt.prjtg_id)))
     JOIN public.prj_milestone pm ON ((pm.prjm_id = pt.prjtg_id)));


ALTER TABLE public.assessment_zero_count OWNER TO rpadmin;

--
-- Name: VIEW assessment_zero_count; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.assessment_zero_count IS 'count zeros per group';


--
-- Name: auth_grp_members; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.auth_grp_members AS
 SELECT (pg.snummer)::text AS username,
    (((((((p.afko)::text || '_'::text) || p.year) || '_'::text) || pm.milestone) || '_'::text) || (COALESCE(ga.alias, (('group'::text || lpad((pt.grp_num)::text, 2, '00'::text)))::bpchar))::text) AS groupname
   FROM ((((public.prj_grp pg
     JOIN public.prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN public.prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
     JOIN public.project p ON ((pm.prj_id = p.prj_id)))
     LEFT JOIN public.grp_alias ga ON ((pt.prjtg_id = ga.prjtg_id)));


ALTER TABLE public.auth_grp_members OWNER TO rpadmin;

SET default_with_oids = true;

--
-- Name: uploads; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.uploads (
    snummer integer NOT NULL,
    doctype smallint NOT NULL,
    title character varying(80),
    vers smallint NOT NULL,
    uploadts timestamp without time zone DEFAULT now(),
    upload_id integer DEFAULT nextval(('public.upload_id_seq'::text)::regclass) NOT NULL,
    mime_type character varying(80) DEFAULT 'text/plain'::character varying,
    rights boolean[] DEFAULT '{f,f}'::boolean[],
    rel_file_path text NOT NULL,
    prjm_id integer NOT NULL,
    prjtg_id integer NOT NULL,
    mime_type_long character varying(128) DEFAULT 'text/plain'::character varying,
    filesize integer
);


ALTER TABLE public.uploads OWNER TO rpadmin;

--
-- Name: TABLE uploads; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.uploads IS 'uploads of student per project';


--
-- Name: COLUMN uploads.rights; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON COLUMN public.uploads.rights IS 'read rights for several; 0=groupshared,1=projectshared';


--
-- Name: author_grp; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.author_grp AS
 SELECT pm.prj_id,
    pm.milestone,
    pt.grp_num,
    u.upload_id,
    u.rights,
    u.snummer AS author,
    pt.prjtg_id
   FROM (((public.uploads u
     JOIN public.prj_grp pg ON (((u.snummer = pg.snummer) AND (u.prjtg_id = pg.prjtg_id))))
     JOIN public.prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN public.prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)));


ALTER TABLE public.author_grp OWNER TO rpadmin;

--
-- Name: author_grp_members; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.author_grp_members AS
 SELECT author_grp.prj_id,
    author_grp.milestone,
    author_grp.grp_num,
    author_grp.upload_id,
    prj_grp.snummer,
    prj_grp.prj_grp_open AS open,
    author_grp.rights,
    author_grp.author
   FROM (public.author_grp
     JOIN public.prj_grp USING (prjtg_id));


ALTER TABLE public.author_grp_members OWNER TO rpadmin;

--
-- Name: available_assessment; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.available_assessment AS
 SELECT DISTINCT pm.prjm_id
   FROM ((public.assessment a
     JOIN public.prj_tutor pt ON ((a.prjtg_id = pt.prjtg_id)))
     JOIN public.prj_milestone pm ON ((pm.prjm_id = pt.prjm_id)));


ALTER TABLE public.available_assessment OWNER TO rpadmin;

--
-- Name: VIEW available_assessment; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.available_assessment IS 'used by iresult.php; tutor/groupresult.php';


--
-- Name: available_assessment_grp_contestant; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.available_assessment_grp_contestant AS
 SELECT DISTINCT assessment_tr.prjtg_id,
    assessment_tr.contestant
   FROM public.assessment_tr;


ALTER TABLE public.available_assessment_grp_contestant OWNER TO rpadmin;

--
-- Name: VIEW available_assessment_grp_contestant; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.available_assessment_grp_contestant IS 'assessment enabled for this contestant';


--
-- Name: available_assessment_grp_judge; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.available_assessment_grp_judge AS
 SELECT DISTINCT assessment_tr.prjtg_id,
    assessment_tr.judge
   FROM public.assessment_tr;


ALTER TABLE public.available_assessment_grp_judge OWNER TO rpadmin;

--
-- Name: VIEW available_assessment_grp_judge; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.available_assessment_grp_judge IS 'assessment enabled for this judge';


SET default_with_oids = false;

--
-- Name: bader; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.bader (
    snummer integer,
    username text,
    password character varying(64),
    uid integer,
    gid integer,
    achternaam text,
    roepnaam text,
    tussenvoegsel text,
    opl bigint,
    cohort smallint,
    email1 public.email,
    pcn integer,
    sclass text,
    lang character(2),
    hoofdgrp text
);


ALTER TABLE public.bader OWNER TO rpadmin;

--
-- Name: grp_size2; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.grp_size2 AS
 SELECT pm.prj_id,
    pm.milestone,
    pm.prjm_id,
    pt.grp_num,
    pt.prjtg_id,
    gs.gsize AS size
   FROM ((( SELECT prj_grp.prjtg_id,
            count(*) AS gsize
           FROM public.prj_grp
          GROUP BY prj_grp.prjtg_id) gs
     JOIN public.prj_tutor pt USING (prjtg_id))
     JOIN public.prj_milestone pm USING (prjm_id));


ALTER TABLE public.grp_size2 OWNER TO rpadmin;

--
-- Name: judge_ready_count; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.judge_ready_count AS
 SELECT gs2.prjtg_id,
    gs2.prj_id,
    gs2.milestone,
    gs2.prjm_id,
    gs2.grp_num,
    gs2.size,
    COALESCE(rc.ready_count, (0)::bigint) AS ready_count
   FROM (public.grp_size2 gs2
     LEFT JOIN ( SELECT count(*) AS ready_count,
            prj_grp.prjtg_id
           FROM public.prj_grp
          WHERE (prj_grp.written = true)
          GROUP BY prj_grp.prjtg_id) rc USING (prjtg_id));


ALTER TABLE public.judge_ready_count OWNER TO rpadmin;

--
-- Name: barchart_view; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.barchart_view AS
 SELECT COALESCE(jrc.size, (0)::bigint) AS size,
    pt.prjtg_id,
    pm.prjm_id,
    pm.prj_id,
    pm.milestone,
    COALESCE(ga.alias, (('g'::text || pt.grp_num))::bpchar) AS alias,
    pt.grp_num,
    (((ts.roepnaam || ' '::text) || COALESCE((ts.tussenvoegsel || ' '::text), ''::text)) || ts.achternaam) AS tut_name,
    t.tutor,
    jrc.ready_count,
    pm.prj_milestone_open,
    pt.prj_tutor_open
   FROM (((((public.prj_milestone pm
     JOIN public.prj_tutor pt ON ((pm.prjm_id = pt.prjm_id)))
     JOIN public.tutor t ON ((pt.tutor_id = t.userid)))
     JOIN public.student ts ON ((t.userid = ts.snummer)))
     LEFT JOIN public.judge_ready_count jrc ON ((pt.prjtg_id = jrc.prjtg_id)))
     LEFT JOIN public.grp_alias ga ON ((ga.prjtg_id = pt.prjtg_id)));


ALTER TABLE public.barchart_view OWNER TO rpadmin;

--
-- Name: VIEW barchart_view; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.barchart_view IS 'used in openBarChart2.php';


--
-- Name: berufskollegs; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.berufskollegs (
    _id integer NOT NULL,
    naam_school character varying(71),
    aan character varying(17),
    adres character varying(25),
    postcode integer,
    woonplaats character varying(18),
    telefon character varying(23),
    telefax character varying(23),
    email character varying(50)
);


ALTER TABLE public.berufskollegs OWNER TO rpadmin;

--
-- Name: TABLE berufskollegs; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.berufskollegs IS 'For recruitement.';


--
-- Name: berufskollegs__id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.berufskollegs__id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.berufskollegs__id_seq OWNER TO rpadmin;

--
-- Name: berufskollegs__id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.berufskollegs__id_seq OWNED BY public.berufskollegs._id;


--
-- Name: bigface_settings; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.bigface_settings (
    bfkey character varying(30) NOT NULL,
    bfvalue text,
    comment text
);


ALTER TABLE public.bigface_settings OWNER TO rpadmin;

--
-- Name: TABLE bigface_settings; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.bigface_settings IS 'Serivce big faces for fibs. Controls and settings.';


--
-- Name: faculty; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.faculty (
    full_name character varying(64),
    faculty_id smallint NOT NULL,
    faculty_short character(6),
    schedule_url character varying(30) DEFAULT 'fihe'::character varying
);


ALTER TABLE public.faculty OWNER TO rpadmin;

--
-- Name: TABLE faculty; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.faculty IS 'faculties within fontys';


--
-- Name: registered_mphotos; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.registered_mphotos (
    snummer integer NOT NULL
);


ALTER TABLE public.registered_mphotos OWNER TO rpadmin;

--
-- Name: TABLE registered_mphotos; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.registered_mphotos IS 'Employee with big photo.';


--
-- Name: bigface_view; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.bigface_view AS
 SELECT s.snummer AS userid,
    s.achternaam,
    s.roepnaam,
    s.tussenvoegsel,
    t.tutor AS nickname,
    t.office_phone,
    t.office,
    s.email1 AS email,
    t.team,
    t.building,
    t.room,
    t.display_name,
    (((('<img src="mfotos/'::text || COALESCE(r.snummer, 0)) || '.jpg" alt="'::text) || s.snummer) || '"/>'::text) AS image,
    s.faculty_id,
    fac.faculty_short,
    COALESCE(r.snummer, 0) AS photo_id
   FROM (((public.tutor t
     JOIN public.student s ON ((t.userid = s.snummer)))
     LEFT JOIN public.registered_mphotos r USING (snummer))
     JOIN public.faculty fac ON ((t.faculty_id = fac.faculty_id)))
  WHERE (s.active = true);


ALTER TABLE public.bigface_view OWNER TO rpadmin;

--
-- Name: birthdays; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.birthdays AS
 SELECT student.snummer,
    student.achternaam,
    student.tussenvoegsel,
    student.voorletters,
    student.roepnaam,
    student.straat,
    student.huisnr,
    student.pcode,
    student.plaats,
    student.email1,
    student.nationaliteit,
    student.hoofdgrp,
    student.cohort,
    student.gebdat,
    student.sex,
    student.phone_home,
    student.phone_gsm,
    student.lang,
    student.class_id,
    faculty.faculty_short
   FROM ((public.student
     JOIN public.faculty ON ((student.faculty_id = faculty.faculty_id)))
     JOIN public.student_class classes USING (class_id))
  WHERE ((classes.sclass !~~ 'UITVAL%'::text) AND (to_char((student.gebdat)::timestamp with time zone, 'MM-DD'::text) = to_char(((now())::date)::timestamp with time zone, 'MM-DD'::text)));


ALTER TABLE public.birthdays OWNER TO rpadmin;

--
-- Name: VIEW birthdays; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.birthdays IS 'Who''s birthday is it today?';


--
-- Name: class_cluster; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.class_cluster (
    class_cluster integer NOT NULL,
    cluster_name character varying(20),
    cluster_description text,
    sort_order smallint DEFAULT 0
);


ALTER TABLE public.class_cluster OWNER TO rpadmin;

--
-- Name: TABLE class_cluster; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.class_cluster IS 'Grouping of classes that might transgress institute bouadaries, like Food and Flower.';


--
-- Name: class_cluster_class_cluster_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.class_cluster_class_cluster_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.class_cluster_class_cluster_seq OWNER TO rpadmin;

--
-- Name: class_cluster_class_cluster_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.class_cluster_class_cluster_seq OWNED BY public.class_cluster.class_cluster;


--
-- Name: class_selector; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.class_selector AS
 SELECT faculty.faculty_short,
    classes.sclass,
    classes.class_id AS value,
    (((((faculty.faculty_short)::text || ' .'::text) || btrim(classes.sclass)) || '#'::text) || classes.class_id) AS name
   FROM (public.student_class classes
     JOIN public.faculty ON ((faculty.faculty_id = classes.faculty_id)));


ALTER TABLE public.class_selector OWNER TO rpadmin;

--
-- Name: VIEW class_selector; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.class_selector IS 'used through menu_option_query for student_admin';


--
-- Name: class_size; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.class_size AS
 SELECT s.class_id,
    count(*) AS student_count
   FROM public.student s
  GROUP BY s.class_id;


ALTER TABLE public.class_size OWNER TO rpadmin;

--
-- Name: classes_class_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.classes_class_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.classes_class_id_seq OWNER TO rpadmin;

--
-- Name: classes_class_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.classes_class_id_seq OWNED BY public.student_class.class_id;


--
-- Name: colloquium_speakers; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.colloquium_speakers (
    colloquium_speaker_id integer NOT NULL,
    lastname character varying(20),
    firstname character varying(20),
    infix character varying(10),
    sex character(1),
    speaker_org character varying(40),
    weblink1 character varying(80),
    email character(80),
    phone character varying(20),
    last_talk date,
    weblink2 character varying(80)
);


ALTER TABLE public.colloquium_speakers OWNER TO rpadmin;

--
-- Name: TABLE colloquium_speakers; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.colloquium_speakers IS 'Colloquium speakers collection';


--
-- Name: COLUMN colloquium_speakers.colloquium_speaker_id; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON COLUMN public.colloquium_speakers.colloquium_speaker_id IS 'primary key';


--
-- Name: COLUMN colloquium_speakers.infix; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON COLUMN public.colloquium_speakers.infix IS 'Dutch tussenvoegsel';


--
-- Name: COLUMN colloquium_speakers.speaker_org; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON COLUMN public.colloquium_speakers.speaker_org IS 'Business name';


--
-- Name: colloquium_speakers_colloquium_speaker_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.colloquium_speakers_colloquium_speaker_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.colloquium_speakers_colloquium_speaker_id_seq OWNER TO rpadmin;

--
-- Name: colloquium_speakers_colloquium_speaker_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.colloquium_speakers_colloquium_speaker_id_seq OWNED BY public.colloquium_speakers.colloquium_speaker_id;


--
-- Name: contestant_assessment; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.contestant_assessment AS
 SELECT s.snummer,
    s.achternaam,
    s.tussenvoegsel,
    s.voorletters,
    s.roepnaam,
    s.straat,
    s.huisnr,
    s.pcode,
    s.plaats,
    s.email1,
    s.nationaliteit,
    s.hoofdgrp,
    s.cohort,
    pm.prj_id,
    pm.milestone,
    pm.prjm_id,
    a.contestant,
    a.judge,
    a.criterium,
    a.grade,
    pt.grp_num,
    pt.prjtg_id
   FROM ((((public.student s
     JOIN public.assessment a ON ((s.snummer = a.contestant)))
     JOIN public.prj_grp pg ON (((a.contestant = pg.snummer) AND (a.prjtg_id = pg.prjtg_id))))
     JOIN public.prj_tutor pt ON ((a.prjtg_id = pt.prjtg_id)))
     JOIN public.prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)));


ALTER TABLE public.contestant_assessment OWNER TO rpadmin;

--
-- Name: contestant_crit_avg; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.contestant_crit_avg AS
 SELECT assessment.prjtg_id,
    assessment.criterium,
    assessment.contestant AS snummer,
    sum(assessment.grade) AS contestant_crit_grade_sum
   FROM public.assessment
  GROUP BY assessment.prjtg_id, assessment.criterium, assessment.contestant;


ALTER TABLE public.contestant_crit_avg OWNER TO rpadmin;

--
-- Name: contestant_sum; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.contestant_sum AS
 SELECT pm.prj_id,
    pm.milestone,
    a.snummer,
    a.grade_sum
   FROM ((( SELECT assessment.contestant AS snummer,
            assessment.prjtg_id,
            sum(assessment.grade) AS grade_sum
           FROM public.assessment
          GROUP BY assessment.contestant, assessment.prjtg_id) a
     JOIN public.prj_tutor pt ON ((a.prjtg_id = pt.prjtg_id)))
     JOIN public.prj_milestone pm ON ((pm.prjm_id = pt.prjtg_id)));


ALTER TABLE public.contestant_sum OWNER TO rpadmin;

--
-- Name: course_week; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.course_week (
    start_date date,
    stop_date date,
    course_week_no smallint NOT NULL
);


ALTER TABLE public.course_week OWNER TO rpadmin;

--
-- Name: TABLE course_week; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.course_week IS 'Weeks for schedule schemas';


SET default_with_oids = true;

--
-- Name: timetableweek; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.timetableweek (
    day smallint NOT NULL,
    hourcode smallint NOT NULL,
    start_time time without time zone,
    stop_time time without time zone
);


ALTER TABLE public.timetableweek OWNER TO rpadmin;

--
-- Name: TABLE timetableweek; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.timetableweek IS 'Hour definitions in course or schedule week.';


--
-- Name: course_hours; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.course_hours AS
 SELECT course_week.start_date,
    course_week.stop_date,
    course_week.course_week_no,
    timetableweek.day,
    timetableweek.hourcode,
    timetableweek.start_time,
    timetableweek.stop_time
   FROM public.course_week,
    public.timetableweek;


ALTER TABLE public.course_hours OWNER TO rpadmin;

SET default_with_oids = false;

--
-- Name: crit_temp; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.crit_temp (
    criterium_id smallint,
    author integer,
    nl_short character varying(30),
    de_short character varying(30),
    en_short character varying(30),
    nl character varying(200),
    de character varying(200),
    en character varying(200)
);


ALTER TABLE public.crit_temp OWNER TO rpadmin;

--
-- Name: criteria_pm; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.criteria_pm AS
 SELECT base_criteria.criterium_id,
    base_criteria.author,
    base_criteria.nl_short,
    base_criteria.de_short,
    base_criteria.en_short,
    base_criteria.nl,
    base_criteria.de,
    base_criteria.en,
    prjm_criterium.prjm_id
   FROM (public.base_criteria
     JOIN public.prjm_criterium USING (criterium_id));


ALTER TABLE public.criteria_pm OWNER TO rpadmin;

--
-- Name: criterium_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.criterium_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    MAXVALUE 65535
    CACHE 1;


ALTER TABLE public.criterium_id_seq OWNER TO rpadmin;

--
-- Name: critique_history; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.critique_history (
    critique_id integer,
    id bigint NOT NULL,
    edit_time timestamp without time zone DEFAULT now(),
    critique_text text
);


ALTER TABLE public.critique_history OWNER TO rpadmin;

--
-- Name: TABLE critique_history; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.critique_history IS 'simple versioning for critiques.';


--
-- Name: critique_history_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.critique_history_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.critique_history_id_seq OWNER TO rpadmin;

--
-- Name: critique_history_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.critique_history_id_seq OWNED BY public.critique_history.id;


--
-- Name: current_student_class; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.current_student_class AS
 SELECT student.snummer,
    student.class_id,
    date_part('year'::text, now()) AS course_year
   FROM public.student;


ALTER TABLE public.current_student_class OWNER TO rpadmin;

--
-- Name: davinci_leden1; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.davinci_leden1 (
    jaar integer,
    email character varying(40),
    snummer integer,
    sinds smallint,
    actief boolean,
    laatste_lid_jaar smallint,
    iban character varying(30)
);


ALTER TABLE public.davinci_leden1 OWNER TO rpadmin;

--
-- Name: dead_class; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.dead_class AS
 SELECT DISTINCT s1.class_id
   FROM public.student s1
  WHERE (NOT (EXISTS ( SELECT 1
           FROM public.student
          WHERE ((student.class_id = s1.class_id) AND (student.active = true)))));


ALTER TABLE public.dead_class OWNER TO rpadmin;

--
-- Name: diploma_dates; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.diploma_dates (
    snummer integer NOT NULL,
    propedeuse date,
    bachelor date,
    stopped_non_diploma date
);


ALTER TABLE public.diploma_dates OWNER TO rpadmin;

--
-- Name: TABLE diploma_dates; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.diploma_dates IS 'Record diploma status of students.';


--
-- Name: doc_critique_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.doc_critique_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.doc_critique_seq OWNER TO rpadmin;

--
-- Name: doctype_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.doctype_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.doctype_id_seq OWNER TO rpadmin;

--
-- Name: doctype_upload_group_count; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.doctype_upload_group_count AS
 SELECT u.prjtg_id,
    u.doctype,
    count(u.upload_id) AS doc_count
   FROM public.uploads u
  GROUP BY u.prjtg_id, u.doctype;


ALTER TABLE public.doctype_upload_group_count OWNER TO rpadmin;

--
-- Name: document_audience; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.document_audience AS
 SELECT uploads.upload_id,
    uploads.rights,
    uploads.snummer AS reader,
    uploads.prjm_id,
    uploads.prjtg_id AS viewergrp,
    '0 author'::text AS reader_role
   FROM public.uploads
UNION
 SELECT uploads.upload_id,
    uploads.rights,
    prj_tutor.tutor_id AS reader,
    uploads.prjm_id,
    uploads.prjtg_id AS viewergrp,
    '1 project tutor'::text AS reader_role
   FROM (public.uploads
     JOIN public.prj_tutor USING (prjm_id))
UNION
 SELECT uploads.upload_id,
    uploads.rights,
    prj_grp.snummer AS reader,
    uploads.prjm_id,
    uploads.prjtg_id AS viewergrp,
    '2 group member'::text AS reader_role
   FROM (public.uploads
     JOIN public.prj_grp USING (prjtg_id))
  WHERE ((uploads.rights[1] = true) AND (prj_grp.snummer <> uploads.snummer))
UNION
 SELECT u.upload_id,
    u.rights,
    pg.snummer AS reader,
    u.prjm_id,
    pg.prjtg_id AS viewergrp,
    '3 project member'::text AS reader_role
   FROM ((public.uploads u
     JOIN public.prj_tutor pt ON ((u.prjm_id = pt.prjm_id)))
     JOIN public.prj_grp pg ON ((pt.prjtg_id = pg.prjtg_id)))
  WHERE ((u.rights[2] = true) AND (u.prjtg_id <> pg.prjtg_id));


ALTER TABLE public.document_audience OWNER TO rpadmin;

--
-- Name: document_author; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.document_author (
    upload_id integer NOT NULL,
    snummer integer NOT NULL,
    document_author_id integer NOT NULL
);


ALTER TABLE public.document_author OWNER TO rpadmin;

--
-- Name: TABLE document_author; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.document_author IS 'who is author for uploaded docs';


--
-- Name: document_author_document_author_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.document_author_document_author_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.document_author_document_author_id_seq OWNER TO rpadmin;

--
-- Name: document_author_document_author_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.document_author_document_author_id_seq OWNED BY public.document_author.document_author_id;


--
-- Name: document_critique; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.document_critique (
    critique_id integer DEFAULT nextval(('public.doc_critique_seq'::text)::regclass) NOT NULL,
    doc_id integer,
    critiquer integer,
    ts timestamp without time zone DEFAULT now(),
    critique_text text,
    edit_time timestamp without time zone DEFAULT now(),
    deleted boolean DEFAULT false
);


ALTER TABLE public.document_critique OWNER TO rpadmin;

--
-- Name: TABLE document_critique; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.document_critique IS 'critiques by students (and tutors) on uploaded documents';


--
-- Name: COLUMN document_critique.doc_id; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON COLUMN public.document_critique.doc_id IS 'link to critisized document';


--
-- Name: COLUMN document_critique.critiquer; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON COLUMN public.document_critique.critiquer IS 'snummer';


--
-- Name: COLUMN document_critique.critique_text; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON COLUMN public.document_critique.critique_text IS 'The critique text';


--
-- Name: document_critique_count; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.document_critique_count AS
 SELECT count(document_critique.critique_id) AS critique_count,
    document_critique.doc_id
   FROM public.document_critique
  WHERE (document_critique.deleted = false)
  GROUP BY document_critique.doc_id;


ALTER TABLE public.document_critique_count OWNER TO rpadmin;

--
-- Name: project_deliverables; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.project_deliverables (
    doctype smallint NOT NULL,
    version_limit smallint DEFAULT 2,
    due date DEFAULT (((now())::text)::date + 28),
    publish_early boolean DEFAULT true,
    rights boolean[] DEFAULT '{f,f,f}'::boolean[],
    prjm_id integer NOT NULL,
    pdeliverable_id integer NOT NULL
);


ALTER TABLE public.project_deliverables OWNER TO rpadmin;

--
-- Name: TABLE project_deliverables; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.project_deliverables IS 'deliverables of project';


--
-- Name: COLUMN project_deliverables.rights; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON COLUMN public.project_deliverables.rights IS 'read rights for several; 0=groupshared,1=projectshared';


--
-- Name: uploaddocumenttypes; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.uploaddocumenttypes (
    doctype smallint DEFAULT nextval(('public.doctype_id_seq'::text)::regclass) NOT NULL,
    description character varying(40),
    prj_id smallint NOT NULL,
    url text,
    warn_members boolean DEFAULT false,
    indiv_group character(1) DEFAULT 'I'::bpchar,
    CONSTRAINT uploaddocumenttypes_indiv_group_check CHECK ((indiv_group = ANY (ARRAY['I'::bpchar, 'G'::bpchar])))
);


ALTER TABLE public.uploaddocumenttypes OWNER TO rpadmin;

--
-- Name: TABLE uploaddocumenttypes; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.uploaddocumenttypes IS 'type description deliverables of project';


--
-- Name: document_data3; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.document_data3 AS
 SELECT rtrim((p.afko)::text) AS afko,
    rtrim((p.description)::text) AS description,
    pm.prj_id,
    p.year,
    pm.milestone,
    pm.prjm_id,
    pt.grp_num,
    pt.prjtg_id,
    ga.long_name,
    u.title,
    u.rel_file_path,
    u.uploadts,
    u.filesize,
    pd.due,
    rtrim((u.mime_type_long)::text) AS mime_type,
    u.vers,
    ut.doctype,
    ut.description AS dtdescr,
    u.upload_id,
    u.snummer,
    st.roepnaam,
    st.tussenvoegsel,
    st.achternaam,
    cl.sclass,
    document_critique_count.critique_count,
    u.rights
   FROM (((((((((public.uploads u
     JOIN public.prj_milestone pm ON ((u.prjm_id = pm.prjm_id)))
     JOIN public.uploaddocumenttypes ut ON (((pm.prj_id = ut.prj_id) AND (u.doctype = ut.doctype))))
     JOIN public.project_deliverables pd ON (((u.prjm_id = pd.prjm_id) AND (u.doctype = pd.doctype))))
     JOIN public.prj_tutor pt ON (((pm.prjm_id = pt.prjm_id) AND (u.prjtg_id = pt.prjtg_id))))
     JOIN public.student st ON ((u.snummer = st.snummer)))
     JOIN public.student_class cl ON ((st.class_id = cl.class_id)))
     LEFT JOIN public.grp_alias ga ON ((pt.prjtg_id = ga.prjtg_id)))
     JOIN public.project p ON ((p.prj_id = pm.prj_id)))
     LEFT JOIN public.document_critique_count ON ((u.upload_id = document_critique_count.doc_id)));


ALTER TABLE public.document_data3 OWNER TO rpadmin;

--
-- Name: VIEW document_data3; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.document_data3 IS 'all document data with prj_id and milestone removed';


--
-- Name: document_projects; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.document_projects AS
 SELECT uploaddocumenttypes.prj_id
   FROM public.uploaddocumenttypes;


ALTER TABLE public.document_projects OWNER TO rpadmin;

--
-- Name: dossier_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.dossier_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.dossier_id_seq OWNER TO rpadmin;

--
-- Name: SEQUENCE dossier_id_seq; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON SEQUENCE public.dossier_id_seq IS 'Seq for dossier table';


--
-- Name: double_email; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.double_email AS
 SELECT student.email1
   FROM public.student
  GROUP BY student.email1
 HAVING (count(1) > 1);


ALTER TABLE public.double_email OWNER TO rpadmin;

--
-- Name: double_emails; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.double_emails AS
 SELECT student.email1
   FROM public.student
  GROUP BY student.email1
 HAVING (count(1) > 1)
  ORDER BY student.email1;


ALTER TABLE public.double_emails OWNER TO rpadmin;

SET default_with_oids = true;

--
-- Name: downloaded; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.downloaded (
    snummer integer NOT NULL,
    upload_id integer NOT NULL,
    downloadts timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.downloaded OWNER TO rpadmin;

--
-- Name: TABLE downloaded; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.downloaded IS 'Record downloading of documents.';


SET default_with_oids = false;

--
-- Name: education_unit; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.education_unit (
    module_id integer NOT NULL,
    credits integer,
    weight integer,
    education_unit_id integer NOT NULL,
    CONSTRAINT weight_check CHECK ((weight >= 0))
);


ALTER TABLE public.education_unit OWNER TO rpadmin;

--
-- Name: TABLE education_unit; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.education_unit IS 'module parts, producing separate credits.';


--
-- Name: education_unit_description; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.education_unit_description (
    education_unit_id integer NOT NULL,
    language_id character(2) NOT NULL,
    module_id integer NOT NULL,
    description character varying(50)
);


ALTER TABLE public.education_unit_description OWNER TO rpadmin;

--
-- Name: TABLE education_unit_description; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.education_unit_description IS 'description of parts of modules.';


SET default_with_oids = true;

--
-- Name: email_signature; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.email_signature (
    snummer integer NOT NULL,
    signature text
);


ALTER TABLE public.email_signature OWNER TO rpadmin;

--
-- Name: TABLE email_signature; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.email_signature IS 'for email personalisation.';


--
-- Name: enumeraties; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.enumeraties (
    menu_name character varying(30) NOT NULL,
    column_name character varying(30) NOT NULL,
    name character varying(30) NOT NULL,
    value character varying(30) NOT NULL,
    sort_order smallint DEFAULT 0,
    is_default character(1) DEFAULT 'N'::bpchar,
    id integer NOT NULL
);


ALTER TABLE public.enumeraties OWNER TO rpadmin;

--
-- Name: TABLE enumeraties; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.enumeraties IS 'Lists for several drop down menus in menu and menu_item. ';


--
-- Name: enumeraties_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.enumeraties_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.enumeraties_id_seq OWNER TO rpadmin;

--
-- Name: enumeraties_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.enumeraties_id_seq OWNED BY public.enumeraties.id;


SET default_with_oids = false;

--
-- Name: exam; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.exam (
    exam_id integer NOT NULL,
    module_id integer,
    education_unit_id integer,
    weight integer,
    persistent_grade boolean,
    exam_type_id character varying(10),
    exam_grading_level_id character varying(10),
    exam_grading_type_id character varying(10),
    exam_grading_aspect_id character varying(10),
    CONSTRAINT exam_check CHECK ((weight >= 0))
);


ALTER TABLE public.exam OWNER TO rpadmin;

--
-- Name: TABLE exam; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.exam IS 'Module exam type.';


--
-- Name: exam_account; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.exam_account AS
 SELECT ('x'::text || (prj_grp.snummer)::text) AS uname,
    student.roepnaam AS password,
    1 AS uid,
    (pm.prj_id + 10000) AS gid,
    ((student.roepnaam || ' '::text) || student.achternaam) AS gecos,
    ('/exam/x'::text || (prj_grp.snummer)::text) AS homedir,
    '/bin/bash'::text AS shell,
    pm.prj_id,
    pm.milestone
   FROM (((public.prj_grp
     JOIN public.student USING (snummer))
     JOIN public.prj_tutor pt ON ((prj_grp.prjtg_id = pt.prjtg_id)))
     JOIN public.prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)));


ALTER TABLE public.exam_account OWNER TO rpadmin;

--
-- Name: exam_event; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.exam_event (
    exam_event_id integer NOT NULL,
    module_part_id integer,
    exam_date date,
    examiner integer
);


ALTER TABLE public.exam_event OWNER TO rpadmin;

--
-- Name: exam_event_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.exam_event_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.exam_event_id_seq OWNER TO rpadmin;

--
-- Name: exam_event_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.exam_event_id_seq OWNED BY public.exam_event.exam_event_id;


--
-- Name: exam_exam_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.exam_exam_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.exam_exam_id_seq OWNER TO rpadmin;

--
-- Name: exam_exam_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.exam_exam_id_seq OWNED BY public.exam.exam_id;


--
-- Name: exam_focus; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.exam_focus (
    exam_focus_id integer NOT NULL
);


ALTER TABLE public.exam_focus OWNER TO rpadmin;

--
-- Name: exam_focus_description; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.exam_focus_description (
    exam_focus_id integer NOT NULL,
    language_id character(2) NOT NULL,
    description character varying(50)
);


ALTER TABLE public.exam_focus_description OWNER TO rpadmin;

--
-- Name: exam_grades; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.exam_grades (
    snummer integer,
    exam_event_id integer,
    grade numeric,
    trans_id bigint,
    exam_grade_id bigint NOT NULL
);


ALTER TABLE public.exam_grades OWNER TO rpadmin;

--
-- Name: exam_grades_backup; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.exam_grades_backup (
    snummer integer,
    event text,
    grade numeric,
    trans_id bigint,
    exam_grade_id bigint
);


ALTER TABLE public.exam_grades_backup OWNER TO rpadmin;

--
-- Name: exam_grades_exam_grade_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.exam_grades_exam_grade_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.exam_grades_exam_grade_id_seq OWNER TO rpadmin;

--
-- Name: exam_grades_exam_grade_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.exam_grades_exam_grade_id_seq OWNED BY public.exam_grades.exam_grade_id;


--
-- Name: exam_grading_aspect; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.exam_grading_aspect (
    exam_grading_aspect_id character varying(10) NOT NULL
);


ALTER TABLE public.exam_grading_aspect OWNER TO rpadmin;

--
-- Name: exam_grading_aspect_description; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.exam_grading_aspect_description (
    exam_grading_aspect_id character varying(10) NOT NULL,
    language_id character(2) NOT NULL,
    description character varying(50)
);


ALTER TABLE public.exam_grading_aspect_description OWNER TO rpadmin;

--
-- Name: exam_grading_level; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.exam_grading_level (
    exam_grading_level_id character varying(10) NOT NULL
);


ALTER TABLE public.exam_grading_level OWNER TO rpadmin;

--
-- Name: exam_grading_level_description; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.exam_grading_level_description (
    exam_grading_level_id character varying(10) NOT NULL,
    language_id character(2) NOT NULL,
    description character varying(50)
);


ALTER TABLE public.exam_grading_level_description OWNER TO rpadmin;

--
-- Name: exam_grading_type; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.exam_grading_type (
    exam_grading_type_id character varying(10) NOT NULL
);


ALTER TABLE public.exam_grading_type OWNER TO rpadmin;

--
-- Name: exam_grading_type_description; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.exam_grading_type_description (
    exam_grading_type_id character varying(10) NOT NULL,
    language_id character(2) NOT NULL,
    description character varying(50)
);


ALTER TABLE public.exam_grading_type_description OWNER TO rpadmin;

--
-- Name: module_part; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.module_part (
    module_id integer,
    progress_code character(10) DEFAULT 'UNDE11'::bpchar,
    credits double precision DEFAULT 1,
    module_part_id integer DEFAULT nextval(('module_part_seq'::text)::regclass) NOT NULL,
    part_description text DEFAULT 'default description'::text
);


ALTER TABLE public.module_part OWNER TO rpadmin;

--
-- Name: COLUMN module_part.credits; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON COLUMN public.module_part.credits IS 'credits per part';


--
-- Name: exam_result_view; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.exam_result_view AS
 SELECT exam_grades.snummer,
    student.achternaam,
    student.roepnaam,
    student.cohort,
    exam_event.exam_date,
    module_part.progress_code,
    exam_grades.exam_event_id,
    exam_grades.grade
   FROM (((public.exam_grades
     JOIN public.exam_event USING (exam_event_id))
     JOIN public.module_part USING (module_part_id))
     JOIN public.student USING (snummer));


ALTER TABLE public.exam_result_view OWNER TO rpadmin;

--
-- Name: exam_type; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.exam_type (
    exam_type_id character varying(10) NOT NULL
);


ALTER TABLE public.exam_type OWNER TO rpadmin;

--
-- Name: exam_type_description; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.exam_type_description (
    exam_type_id character varying(10) NOT NULL,
    language_id character(2) NOT NULL,
    description character varying(50)
);


ALTER TABLE public.exam_type_description OWNER TO rpadmin;

--
-- Name: examlist; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.examlist AS
 SELECT s.snummer,
    s.achternaam,
    s.voorletters,
    s.roepnaam,
    s.tussenvoegsel,
    s.lang,
    pm.prj_id
   FROM (((public.student s
     JOIN public.prj_grp pg USING (snummer))
     JOIN public.prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN public.prj_milestone pm ON ((pm.prjm_id = pt.prjtg_id)));


ALTER TABLE public.examlist OWNER TO rpadmin;

--
-- Name: f20170922; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.f20170922 (
    snummer integer
);


ALTER TABLE public.f20170922 OWNER TO rpadmin;

SET default_with_oids = true;

--
-- Name: fake_mail_address; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.fake_mail_address (
    email1 text NOT NULL
);


ALTER TABLE public.fake_mail_address OWNER TO rpadmin;

--
-- Name: TABLE fake_mail_address; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.fake_mail_address IS 'Email address used during tests. ';


--
-- Name: fixed_contestant; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.fixed_contestant AS
 SELECT contestant_sum.prj_id,
    contestant_sum.milestone,
    contestant_sum.snummer
   FROM public.contestant_sum
  WHERE (contestant_sum.grade_sum > 0);


ALTER TABLE public.fixed_contestant OWNER TO rpadmin;

--
-- Name: judge_sum; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.judge_sum AS
 SELECT pm.prj_id,
    pm.milestone,
    a.snummer,
    a.grade_sum
   FROM ((( SELECT assessment.judge AS snummer,
            assessment.prjtg_id,
            sum(assessment.grade) AS grade_sum
           FROM public.assessment
          GROUP BY assessment.judge, assessment.prjtg_id) a
     JOIN public.prj_tutor pt ON ((a.prjtg_id = pt.prjtg_id)))
     JOIN public.prj_milestone pm ON ((pm.prjm_id = pt.prjtg_id)));


ALTER TABLE public.judge_sum OWNER TO rpadmin;

--
-- Name: fixed_judge; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.fixed_judge AS
 SELECT judge_sum.prj_id,
    judge_sum.milestone,
    judge_sum.snummer
   FROM public.judge_sum
  WHERE (judge_sum.grade_sum > 0);


ALTER TABLE public.fixed_judge OWNER TO rpadmin;

--
-- Name: fixed_student; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.fixed_student AS
 SELECT fixed_judge.prj_id,
    fixed_judge.milestone,
    fixed_judge.snummer
   FROM public.fixed_judge
UNION
 SELECT fixed_contestant.prj_id,
    fixed_contestant.milestone,
    fixed_contestant.snummer
   FROM public.fixed_contestant;


ALTER TABLE public.fixed_student OWNER TO rpadmin;

--
-- Name: fixed_student2; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.fixed_student2 AS
 SELECT DISTINCT pg.snummer,
    pm.prj_id,
    pm.milestone,
    pm.prjm_id,
    pt.prjtg_id,
    pt.grp_num
   FROM (((public.assessment a
     JOIN public.prj_grp pg ON (((a.prjtg_id = pg.prjtg_id) AND ((pg.snummer = a.judge) OR (pg.snummer = a.contestant)))))
     JOIN public.prj_tutor pt ON ((a.prjtg_id = pt.prjtg_id)))
     JOIN public.prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)));


ALTER TABLE public.fixed_student2 OWNER TO rpadmin;

--
-- Name: foto_prefix; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.foto_prefix (
    prefix character varying(64) NOT NULL
);


ALTER TABLE public.foto_prefix OWNER TO rpadmin;

--
-- Name: TABLE foto_prefix; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.foto_prefix IS 'used by images derived from snummers';


--
-- Name: foto; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.foto AS
 SELECT student.snummer,
    (((((('<img src="'::text || (foto_prefix.prefix)::text) || '/'::text) || (student.snummer)::text) || '.jpg" alt="'::text) || (student.snummer)::text) || '"/>'::text) AS image
   FROM public.student,
    public.foto_prefix;


ALTER TABLE public.foto OWNER TO rpadmin;

--
-- Name: VIEW foto; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.foto IS 'used by images derived from snummers';


SET default_with_oids = false;

--
-- Name: fotos_2019; Type: TABLE; Schema: public; Owner: hom
--

CREATE TABLE public.fotos_2019 (
    snummer integer
);


ALTER TABLE public.fotos_2019 OWNER TO hom;

SET default_with_oids = true;

--
-- Name: passwd; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.passwd (
    userid integer NOT NULL,
    capabilities integer DEFAULT 0 NOT NULL,
    password character varying(64) DEFAULT 'No password'::bpchar NOT NULL,
    disabled boolean DEFAULT false
);


ALTER TABLE public.passwd OWNER TO rpadmin;

--
-- Name: TABLE passwd; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.passwd IS 'authentication, capability; note tutor column is only as aid, not maintained';


--
-- Name: git_password; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.git_password AS
 SELECT s.snummer,
    s.email1 AS username,
    p.password
   FROM (( SELECT passwd.userid AS snummer,
            passwd.password
           FROM public.passwd
          WHERE ((passwd.capabilities & 262144) <> 0)) p
     JOIN public.student s USING (snummer))
UNION
 SELECT s.snummer,
    (s.snummer)::text AS username,
    p.password
   FROM (( SELECT passwd.userid AS snummer,
            passwd.password
           FROM public.passwd
          WHERE ((passwd.capabilities & 262144) <> 0)) p
     JOIN public.student s USING (snummer));


ALTER TABLE public.git_password OWNER TO rpadmin;

--
-- Name: git_project_users; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.git_project_users AS
 SELECT a.prjm_id,
    0 AS gid,
    s1.snummer,
    (((((('@'::text || lower(btrim((fontys_course.course_short)::text))) || a.year) || lower(btrim((a.afko)::text))) || 'm'::text) || a.milestone) || '_tutor'::text) AS git_grp,
    s1.email1 AS git_grp_member,
    ''::text AS repo
   FROM ((public.all_prj_tutor a
     JOIN public.student s1 ON ((a.tutor_id = s1.snummer)))
     JOIN public.fontys_course USING (course))
UNION
 SELECT a.prjm_id,
    a.grp_num AS gid,
    prj_grp.snummer,
    ((((((('@'::text || lower(btrim((fontys_course.course_short)::text))) || a.year) || lower(btrim((a.afko)::text))) || 'm'::text) || a.milestone) || '_g'::text) || a.grp_num) AS git_grp,
    s2.email1 AS git_grp_member,
    ((((((a.year || '/'::text) || lower(btrim((a.afko)::text))) || 'm'::text) || a.milestone) || '/g'::text) || a.grp_num) AS repo
   FROM (((public.all_prj_tutor a
     JOIN public.prj_grp USING (prjtg_id))
     JOIN public.student s2 USING (snummer))
     JOIN public.fontys_course USING (course))
  ORDER BY 1, 2;


ALTER TABLE public.git_project_users OWNER TO rpadmin;

SET default_with_oids = false;

--
-- Name: github_id; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.github_id (
    snummer integer,
    github_id text
);


ALTER TABLE public.github_id OWNER TO rpadmin;

--
-- Name: grp_alias_builder; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.grp_alias_builder AS
 SELECT pm.prj_id,
    ga.long_name,
    pt.grp_num,
    pm.milestone,
    ga.alias,
    ga.website,
    ga.productname,
    pm.prjm_id,
    pt.prjtg_id
   FROM ((public.prj_milestone pm
     JOIN public.prj_tutor pt ON ((pm.prjm_id = pt.prjm_id)))
     LEFT JOIN public.grp_alias ga ON ((ga.prjtg_id = pt.prjtg_id)));


ALTER TABLE public.grp_alias_builder OWNER TO rpadmin;

--
-- Name: VIEW grp_alias_builder; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.grp_alias_builder IS 'used for duplication of project groups -> grp_aliases';


--
-- Name: grp_alias_tr; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.grp_alias_tr AS
 SELECT grp_alias.prjtg_id,
    grp_alias.long_name,
    grp_alias.alias,
    grp_alias.website,
    grp_alias.productname
   FROM public.grp_alias;


ALTER TABLE public.grp_alias_tr OWNER TO rpadmin;

--
-- Name: VIEW grp_alias_tr; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.grp_alias_tr IS 'grp_alias with redeundant columns removed';


--
-- Name: grp_average; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.grp_average AS
 SELECT pm.prj_id,
    av.criterium,
    pm.milestone,
    pt.grp_num,
    av.grp_avg
   FROM ((( SELECT assessment.prjtg_id,
            assessment.criterium,
            avg(assessment.grade) AS grp_avg
           FROM public.assessment
          GROUP BY assessment.prjtg_id, assessment.criterium) av
     JOIN public.prj_tutor pt ON ((av.prjtg_id = pt.prjtg_id)))
     JOIN public.prj_milestone pm ON ((pm.prjm_id = pt.prjtg_id)));


ALTER TABLE public.grp_average OWNER TO rpadmin;

--
-- Name: VIEW grp_average; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.grp_average IS 'Used by tutor/groupresult.php';


--
-- Name: grp_average2; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.grp_average2 AS
 SELECT assessment.prjtg_id,
    assessment.criterium,
    avg(assessment.grade) AS grp_avg
   FROM public.assessment
  GROUP BY assessment.prjtg_id, assessment.criterium;


ALTER TABLE public.grp_average2 OWNER TO rpadmin;

--
-- Name: grp_crit_avg; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.grp_crit_avg AS
 SELECT assessment.prjtg_id,
    assessment.criterium,
    sum(assessment.grade) AS crit_grade_sum
   FROM public.assessment
  GROUP BY assessment.prjtg_id, assessment.criterium;


ALTER TABLE public.grp_crit_avg OWNER TO rpadmin;

--
-- Name: grp_detail; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.grp_detail AS
 SELECT p.prj_id,
    p.afko,
    p.description,
    p.year,
    p.comment,
    p.valid_until,
    p.course,
    p.owner_id,
    prj_milestone.prjm_id,
    prj_milestone.milestone,
    prj_milestone.prj_milestone_open,
    prj_milestone.assessment_due,
    prj_milestone.milestone_name,
    town.display_name AS owner,
    town.faculty_id,
    f.faculty_short,
    tu.display_name AS tutor,
    pt.grp_num,
    pt.prjtg_id,
    pt.tutor_grade,
    pt.tutor_id,
    pt.grp_name,
    ga.long_name,
    ga.alias,
    ga.website,
    ga.productname,
    ga.youtube_link,
    regexp_replace(btrim((ga.youtube_link)::text), '.*?v=((-?|\w)+)?&?.*$'::text, '\1'::text) AS yt_id
   FROM ((((((public.project p
     JOIN public.prj_milestone USING (prj_id))
     JOIN public.prj_tutor pt USING (prjm_id))
     JOIN public.grp_alias ga USING (prjtg_id))
     JOIN public.tutor town ON ((p.owner_id = town.userid)))
     JOIN public.tutor tu ON ((pt.tutor_id = tu.userid)))
     JOIN public.faculty f ON ((f.faculty_id = town.faculty_id)));


ALTER TABLE public.grp_detail OWNER TO rpadmin;

--
-- Name: VIEW grp_detail; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.grp_detail IS 'detail attributes for project group, updatable.';


--
-- Name: grp_details; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.grp_details AS
 SELECT p.prj_id,
    p.afko,
    p.description,
    p.year,
    p.comment,
    p.valid_until,
    p.course,
    p.owner_id,
    prj_milestone.prjm_id,
    prj_milestone.milestone,
    prj_milestone.prj_milestone_open,
    prj_milestone.assessment_due,
    prj_milestone.milestone_name,
    town.display_name AS owner,
    town.faculty_id,
    f.faculty_short,
    tu.display_name AS tutor,
    pt.grp_num,
    pt.prjtg_id,
    pt.tutor_grade,
    pt.tutor_id,
    pt.grp_name,
    ga.long_name,
    ga.alias,
    ga.website,
    ga.productname,
    ga.youtube_link,
    regexp_replace(btrim((ga.youtube_link)::text), '.*?v=((-?|\w)+)?&?.*$'::text, '\1'::text) AS yt_id
   FROM ((((((public.project p
     JOIN public.prj_milestone USING (prj_id))
     JOIN public.prj_tutor pt USING (prjm_id))
     JOIN public.grp_alias ga USING (prjtg_id))
     JOIN public.tutor town ON ((p.owner_id = town.userid)))
     JOIN public.tutor tu ON ((pt.tutor_id = tu.userid)))
     JOIN public.faculty f ON ((f.faculty_id = town.faculty_id)));


ALTER TABLE public.grp_details OWNER TO rpadmin;

--
-- Name: VIEW grp_details; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.grp_details IS 'detail attributes for project group, updatable.';


--
-- Name: grp_overall_average; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.grp_overall_average AS
 SELECT assessment.prjtg_id,
    avg(assessment.grade) AS grp_avg
   FROM public.assessment
  GROUP BY assessment.prjtg_id;


ALTER TABLE public.grp_overall_average OWNER TO rpadmin;

--
-- Name: VIEW grp_overall_average; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.grp_overall_average IS 'used by tutor/groupresult.php';


--
-- Name: grp_size; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.grp_size AS
 SELECT grp_size2.prjtg_id,
    grp_size2.prj_id,
    grp_size2.milestone,
    grp_size2.grp_num,
    grp_size2.size
   FROM public.grp_size2;


ALTER TABLE public.grp_size OWNER TO rpadmin;

--
-- Name: grp_tg_size; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.grp_tg_size AS
 SELECT prj_grp.prjtg_id,
    count(*) AS grp_size
   FROM public.prj_grp
  GROUP BY prj_grp.prjtg_id;


ALTER TABLE public.grp_tg_size OWNER TO rpadmin;

--
-- Name: grp_upload_count; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.grp_upload_count AS
 SELECT pm.prj_id,
    pm.milestone,
    pt.grp_num,
    count(u.upload_id) AS doc_count
   FROM (((public.uploads u
     JOIN public.prj_grp pg ON (((u.prjtg_id = pg.prjtg_id) AND (u.snummer = pg.snummer))))
     JOIN public.prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN public.prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
  GROUP BY pm.prj_id, pm.milestone, pt.grp_num
  ORDER BY pm.prj_id, pm.milestone, pt.grp_num;


ALTER TABLE public.grp_upload_count OWNER TO rpadmin;

--
-- Name: VIEW grp_upload_count; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.grp_upload_count IS 'used by folderview';


--
-- Name: grp_upload_count2; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.grp_upload_count2 AS
 SELECT u.prjtg_id,
    count(u.upload_id) AS doc_count
   FROM public.uploads u
  GROUP BY u.prjtg_id;


ALTER TABLE public.grp_upload_count2 OWNER TO rpadmin;

--
-- Name: guest_users; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.guest_users (
    username text NOT NULL,
    password character varying(64)
);


ALTER TABLE public.guest_users OWNER TO rpadmin;

--
-- Name: TABLE guest_users; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.guest_users IS 'Guests, e.g. for subversion.';


--
-- Name: has_uploads; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.has_uploads AS
 SELECT DISTINCT prj_milestone.prj_id,
    prj_milestone.milestone
   FROM (public.prj_milestone
     JOIN public.uploaddocumenttypes USING (prj_id))
  ORDER BY prj_milestone.prj_id, prj_milestone.milestone;


ALTER TABLE public.has_uploads OWNER TO rpadmin;

--
-- Name: hoofdgrp; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.hoofdgrp AS
 SELECT DISTINCT s.hoofdgrp,
    s.faculty_id,
    f.full_name,
    f.faculty_short,
    fc.course_short
   FROM ((public.student s
     JOIN public.faculty f ON ((s.faculty_id = f.faculty_id)))
     JOIN public.fontys_course fc ON (((s.opl = fc.course) AND (fc.faculty_id = f.faculty_id))));


ALTER TABLE public.hoofdgrp OWNER TO rpadmin;

--
-- Name: hoofdgrp_map; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.hoofdgrp_map (
    instituutcode integer,
    opleiding character varying(39),
    lang character(2),
    hoofdgrp character(10),
    _id integer NOT NULL,
    course bigint
);


ALTER TABLE public.hoofdgrp_map OWNER TO rpadmin;

--
-- Name: hoofdgrp_map__id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.hoofdgrp_map__id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.hoofdgrp_map__id_seq OWNER TO rpadmin;

--
-- Name: hoofdgrp_map__id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.hoofdgrp_map__id_seq OWNED BY public.hoofdgrp_map._id;


--
-- Name: hoofdgrp_s; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.hoofdgrp_s AS
 SELECT DISTINCT student.hoofdgrp,
    student.faculty_id,
    faculty.faculty_short,
    fontys_course.course,
    fontys_course.course_short
   FROM ((public.student
     JOIN public.faculty USING (faculty_id))
     JOIN public.fontys_course ON ((student.opl = fontys_course.course)));


ALTER TABLE public.hoofdgrp_s OWNER TO rpadmin;

--
-- Name: hoofdgrp_size; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.hoofdgrp_size AS
 SELECT student.hoofdgrp,
    student.faculty_id,
    count(*) AS grp_size
   FROM (public.student
     JOIN public.hoofdgrp USING (faculty_id, hoofdgrp))
  GROUP BY student.hoofdgrp, student.faculty_id;


ALTER TABLE public.hoofdgrp_size OWNER TO rpadmin;

--
-- Name: ingeschrevenen; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.ingeschrevenen (
    peildatum date,
    studiejaar integer,
    instituutcode integer,
    instituutnaam character varying(40),
    directeur character(15),
    studentnummer integer,
    achternaam character varying(30),
    tussenvoegsel character(10),
    voorletters character(10),
    voornamen character varying(40),
    roepnaam character varying(30),
    volledig_naam character varying(30),
    geslacht character(10),
    geboortedatum date,
    geboorteplaats character varying(30),
    geboorteland character varying(40),
    "e_mail_privé" character varying(50),
    e_mail_instelling character varying(50),
    land_nummer_mobiel integer,
    mobiel_nummer bigint,
    land_nummer_vast_centrale_verificatie integer,
    vast_nummer_centrale_verificatie bigint,
    land_nummer_vast_decentrale_verificatie integer,
    vast_nummer_decentrale_verificatie bigint,
    pcn_nummer integer,
    onderwijsnummer integer,
    straat character varying(40),
    huisnummer integer,
    huisnummertoevoeging character(15),
    postcode character(10),
    woonplaats character varying(30),
    buitenlandse_adresregel_1 text,
    buitenlandse_adresregel_2 text,
    buitenlandse_adresregel_3 text,
    land character varying(30),
    nationaliteit_1 character varying(50),
    nationaliteit_2 character varying(50),
    leidende_nationaliteit character varying(50),
    isatcode integer,
    opleiding character varying(50),
    opleidingsnaam_voluit character varying(80),
    opleidingsnaam_voluit_engels character varying(80),
    studielinkvariantcode integer,
    variant_omschrijving character varying(60),
    lesplaats character(10),
    vorm character(10),
    fase character varying(40),
    bijvakker character(4),
    datum_van date,
    datum_tot date,
    datum_aankomst_fontys date,
    datum_aankomst_instituut date,
    datum_aankomst_opleiding date,
    propedeuse_datum date,
    aanmelddatum date,
    instroom character(15),
    dipl_vooropl_behaald character(4),
    datum_dipl_vooropl date,
    detail_toelaatbare_vooropleiding character varying(60),
    cluster_vooropl character(10),
    toeleverende_school character varying(80),
    plaats_toeleverende_school character varying(30),
    soort_verzoek character varying(30),
    datum_definitief_ingeschreven date,
    groepcode character(10),
    indicatie_collegegeld character varying(30),
    pasfoto_uploaddatum date,
    voorkeurstaal character(15),
    kop_opleiding text,
    huisnr character(4)
);


ALTER TABLE public.ingeschrevenen OWNER TO rpadmin;

--
-- Name: map_land_nl_iso3166; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.map_land_nl_iso3166 (
    land_nl character varying(40),
    a3 character(3),
    id integer NOT NULL
);


ALTER TABLE public.map_land_nl_iso3166 OWNER TO rpadmin;

--
-- Name: nat_mapper; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.nat_mapper (
    nation_omschr character(40),
    nationaliteit character(2),
    id integer NOT NULL
);


ALTER TABLE public.nat_mapper OWNER TO rpadmin;

--
-- Name: TABLE nat_mapper; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.nat_mapper IS 'Map nationality description in dutch to iso3166 2 letter country code.';


--
-- Name: import_naw; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.import_naw AS
 SELECT DISTINCT ig.studentnummer AS snummer,
    initcap((ig.achternaam)::text) AS achternaam,
    ig.tussenvoegsel,
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
   FROM (((public.ingeschrevenen ig
     LEFT JOIN public.map_land_nl_iso3166 ia ON (((ig.geboorteland)::text = (ia.land_nl)::text)))
     LEFT JOIN public.map_land_nl_iso3166 il ON (((ig.land)::text = (il.land_nl)::text)))
     LEFT JOIN public.nat_mapper nm ON (((ig.leidende_nationaliteit)::bpchar = nm.nation_omschr)));


ALTER TABLE public.import_naw OWNER TO rpadmin;

--
-- Name: inchecked; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.inchecked (
    _id integer NOT NULL,
    snummer integer
);


ALTER TABLE public.inchecked OWNER TO rpadmin;

--
-- Name: inchecked__id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.inchecked__id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.inchecked__id_seq OWNER TO rpadmin;

--
-- Name: inchecked__id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.inchecked__id_seq OWNED BY public.inchecked._id;


--
-- Name: infcap; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.infcap (
    userid integer,
    capabilities integer,
    password character varying(64),
    disabled boolean
);


ALTER TABLE public.infcap OWNER TO rpadmin;

--
-- Name: iso3166; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.iso3166 (
    country character varying(64),
    a2 character(2),
    a3 character(3),
    number smallint NOT NULL,
    country_by_lang character varying(64),
    land_nl character varying(40)
);


ALTER TABLE public.iso3166 OWNER TO rpadmin;

--
-- Name: TABLE iso3166; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.iso3166 IS 'Country codes.';


--
-- Name: iso3166a; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.iso3166a (
    country character varying(44),
    a2 character varying(2),
    a3 character varying(3),
    number integer,
    country_by_lang character varying(11),
    land_nl character varying(28)
);


ALTER TABLE public.iso3166a OWNER TO rpadmin;

--
-- Name: jagers; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.jagers (
    _id integer NOT NULL,
    roepnaam character(20),
    tussenvoegsel character(20),
    achternaam character(20),
    pcn integer NOT NULL,
    sex character(1),
    gebdat character(1),
    voorletters character(20),
    email character(40),
    lang character(20)
);


ALTER TABLE public.jagers OWNER TO rpadmin;

--
-- Name: TABLE jagers; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.jagers IS 'employee with jager role. Not used in 2013.';


--
-- Name: jagers__id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.jagers__id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.jagers__id_seq OWNER TO rpadmin;

--
-- Name: jagers__id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.jagers__id_seq OWNED BY public.jagers._id;


--
-- Name: judge_assessment; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.judge_assessment AS
 SELECT s.snummer,
    s.achternaam,
    s.tussenvoegsel,
    s.voorletters,
    s.roepnaam,
    s.straat,
    s.huisnr,
    s.pcode,
    s.plaats,
    s.email1,
    s.nationaliteit,
    s.hoofdgrp,
    s.cohort,
    prj_milestone.prj_id,
    prj_milestone.milestone,
    prj_tutor.prjm_id,
    a.contestant,
    a.judge,
    a.criterium,
    a.grade,
    prj_tutor.grp_num,
    a.prjtg_id
   FROM (((public.student s
     JOIN public.assessment a ON ((s.snummer = a.judge)))
     JOIN public.prj_tutor USING (prjtg_id))
     JOIN public.prj_milestone USING (prjm_id));


ALTER TABLE public.judge_assessment OWNER TO rpadmin;

--
-- Name: judge_crit_avg; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.judge_crit_avg AS
 SELECT av.snummer,
    pm.prj_id,
    pm.milestone,
    pt.grp_num,
    av.criterium,
    av.grade
   FROM ((( SELECT assessment.judge AS snummer,
            assessment.prjtg_id,
            assessment.criterium,
            avg(assessment.grade) AS grade
           FROM public.assessment
          GROUP BY assessment.prjtg_id, assessment.judge, assessment.criterium) av
     JOIN public.prj_tutor pt ON ((av.prjtg_id = pt.prjtg_id)))
     JOIN public.prj_milestone pm ON ((pm.prjm_id = pt.prjtg_id)));


ALTER TABLE public.judge_crit_avg OWNER TO rpadmin;

--
-- Name: judge_grade_count; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.judge_grade_count AS
 SELECT pm.prj_id,
    pm.milestone,
    pt.grp_num,
    rdy.ready_judge
   FROM ((( SELECT assessment.prjtg_id,
            count(*) AS ready_judge
           FROM public.assessment
          WHERE (assessment.grade <> 0)
          GROUP BY assessment.prjtg_id) rdy
     JOIN public.prj_tutor pt ON ((rdy.prjtg_id = pt.prjtg_id)))
     JOIN public.prj_milestone pm ON ((pm.prjm_id = pt.prjtg_id)));


ALTER TABLE public.judge_grade_count OWNER TO rpadmin;

--
-- Name: judge_grade_count2; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.judge_grade_count2 AS
 SELECT pm.prj_id,
    pm.milestone,
    pm.prjm_id,
    pt.grp_num,
    pt.prjtg_id,
    rdy.ready_judge
   FROM ((( SELECT assessment.prjtg_id,
            count(assessment.judge) AS ready_judge
           FROM public.assessment
          WHERE (assessment.grade <> 0)
          GROUP BY assessment.prjtg_id) rdy
     JOIN public.prj_tutor pt ON ((rdy.prjtg_id = pt.prjtg_id)))
     JOIN public.prj_milestone pm ON ((pm.prjm_id = pt.prjtg_id)));


ALTER TABLE public.judge_grade_count2 OWNER TO rpadmin;

--
-- Name: judge_grp_avg; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.judge_grp_avg AS
 SELECT pm.prj_id,
    pm.milestone,
    pt.grp_num,
    av.criterium,
    av.grade
   FROM ((( SELECT assessment.prjtg_id,
            assessment.criterium,
            avg(assessment.grade) AS grade
           FROM public.assessment
          GROUP BY assessment.prjtg_id, assessment.criterium) av
     JOIN public.prj_tutor pt ON ((av.prjtg_id = pt.prjtg_id)))
     JOIN public.prj_milestone pm ON ((pm.prjm_id = pt.prjtg_id)));


ALTER TABLE public.judge_grp_avg OWNER TO rpadmin;

--
-- Name: judge_notready; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.judge_notready AS
 SELECT DISTINCT assessment.judge AS snummer,
    assessment.prjtg_id
   FROM public.assessment
  WHERE (assessment.grade = 0);


ALTER TABLE public.judge_notready OWNER TO rpadmin;

--
-- Name: language; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.language (
    language_id character(2) NOT NULL,
    language character varying(30)
);


ALTER TABLE public.language OWNER TO rpadmin;

--
-- Name: TABLE language; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.language IS 'Choices between dutch,  german and english.';


--
-- Name: last_assessment_commit; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.last_assessment_commit AS
 SELECT ac.snummer,
    pm.prj_id,
    pm.milestone,
    pt.prjtg_id,
    max(ac.commit_time) AS commit_time
   FROM ((public.assessment_commit ac
     JOIN public.prj_tutor pt ON ((pt.prjtg_id = ac.prjtg_id)))
     JOIN public.prj_milestone pm ON ((pm.prjm_id = pt.prjm_id)))
  GROUP BY ac.snummer, pm.prj_id, pm.milestone, pt.prjtg_id;


ALTER TABLE public.last_assessment_commit OWNER TO rpadmin;

--
-- Name: VIEW last_assessment_commit; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.last_assessment_commit IS 'used by ipeer.php tutor/groupresult.php tutor/moduleresults.php';


--
-- Name: last_upload; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.last_upload AS
 SELECT uploads.snummer,
    uploads.doctype,
    uploads.title,
    uploads.vers,
    uploads.uploadts,
    uploads.upload_id,
    uploads.mime_type,
    uploads.rights,
    uploads.rel_file_path,
    uploads.prjm_id,
    uploads.prjtg_id,
    uploads.mime_type_long,
    uploads.filesize
   FROM public.uploads
  WHERE (uploads.upload_id = ( SELECT max(uploads_1.upload_id) AS max_id
           FROM public.uploads uploads_1));


ALTER TABLE public.last_upload OWNER TO rpadmin;

--
-- Name: learning_goal; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.learning_goal (
    module_id integer NOT NULL,
    learning_goal_id integer NOT NULL
);


ALTER TABLE public.learning_goal OWNER TO rpadmin;

--
-- Name: learning_goal_description; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.learning_goal_description (
    module_id integer NOT NULL,
    language_id character(2) NOT NULL,
    learning_goal_id integer NOT NULL,
    description character varying(250)
);


ALTER TABLE public.learning_goal_description OWNER TO rpadmin;

--
-- Name: learning_goal_exam_focus; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.learning_goal_exam_focus (
    module_id integer NOT NULL,
    learning_goal_id integer NOT NULL,
    exam_focus_id integer NOT NULL,
    weight integer,
    CONSTRAINT learning_goal_exam_focus_check CHECK ((weight >= 0))
);


ALTER TABLE public.learning_goal_exam_focus OWNER TO rpadmin;

--
-- Name: lime_token; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.lime_token AS
 SELECT s.roepnaam AS firstname,
    (COALESCE((s.tussenvoegsel || ' '::text), ''::text) || s.achternaam) AS lastname,
    s.email1 AS email,
    'OK'::text AS emailstatus,
    md5(((s.snummer)::text || now())) AS token,
    s.lang AS language_code,
    s.snummer AS attribute_1,
    pm.prjm_id AS attribute_2,
    pm.prj_id,
    pm.milestone
   FROM (((public.prj_grp pg
     JOIN public.student s USING (snummer))
     JOIN public.prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN public.prj_milestone pm ON ((pm.prjm_id = pt.prjm_id)));


ALTER TABLE public.lime_token OWNER TO rpadmin;

--
-- Name: list; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.list (
    snummer integer
);


ALTER TABLE public.list OWNER TO rpadmin;

SET default_with_oids = true;

--
-- Name: logon; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.logon (
    userid integer NOT NULL,
    since timestamp without time zone DEFAULT now(),
    id bigint NOT NULL,
    from_ip inet
);


ALTER TABLE public.logon OWNER TO rpadmin;

--
-- Name: TABLE logon; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.logon IS 'time of logon.';


--
-- Name: logged_in_today; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.logged_in_today AS
 SELECT logon.userid,
    logon.since,
    logon.id,
    logon.from_ip
   FROM public.logon
  WHERE (logon.since > (now())::date);


ALTER TABLE public.logged_in_today OWNER TO rpadmin;

--
-- Name: loggedin; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.loggedin AS
 SELECT s.achternaam,
    s.roepnaam,
    l.userid,
    l.since,
    l.id,
    l.from_ip
   FROM (public.logged_in_today l
     JOIN public.student s ON ((s.snummer = l.userid)));


ALTER TABLE public.loggedin OWNER TO rpadmin;

--
-- Name: logoff; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.logoff (
    userid integer,
    since timestamp without time zone DEFAULT now(),
    id bigint NOT NULL
);


ALTER TABLE public.logoff OWNER TO rpadmin;

--
-- Name: TABLE logoff; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.logoff IS 'time of log off.';


--
-- Name: logoff_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.logoff_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.logoff_id_seq OWNER TO rpadmin;

--
-- Name: logoff_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.logoff_id_seq OWNED BY public.logoff.id;


--
-- Name: logon_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.logon_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.logon_id_seq OWNER TO rpadmin;

--
-- Name: logon_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.logon_id_seq OWNED BY public.logon.id;


--
-- Name: logon_map_on_timetable; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.logon_map_on_timetable AS
 SELECT logon.userid AS snummer,
    logon.since,
    logon.id,
    logon.from_ip,
    timetableweek.day,
    timetableweek.hourcode,
    timetableweek.start_time,
    timetableweek.stop_time,
    course_week.start_date,
    course_week.stop_date,
    course_week.course_week_no
   FROM ((public.logon
     JOIN public.timetableweek ON ((((substr(to_char(logon.since, 'HH24:MI:SS'::text), 1, 8))::time without time zone >= timetableweek.start_time) AND ((substr(to_char(logon.since, 'HH24:MI:SS'::text), 1, 8))::time without time zone <= timetableweek.stop_time) AND (date_part('dow'::text, logon.since) = (timetableweek.day)::double precision))))
     JOIN public.course_week ON (((logon.since >= (course_week.start_date)::timestamp without time zone) AND (logon.since <= (course_week.stop_date)::timestamp without time zone))));


ALTER TABLE public.logon_map_on_timetable OWNER TO rpadmin;

SET default_with_oids = false;

--
-- Name: lpi_id; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.lpi_id (
    snummer integer NOT NULL,
    lpi_id character(12)
);


ALTER TABLE public.lpi_id OWNER TO rpadmin;

--
-- Name: TABLE lpi_id; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.lpi_id IS 'lpi membership for ICT students. For LPI exams.';


--
-- Name: map201901001; Type: TABLE; Schema: public; Owner: hom
--

CREATE TABLE public.map201901001 (
    map201901001_id integer NOT NULL,
    img integer,
    snummer integer
);


ALTER TABLE public.map201901001 OWNER TO hom;

--
-- Name: map201901001_map201901001_id_seq; Type: SEQUENCE; Schema: public; Owner: hom
--

CREATE SEQUENCE public.map201901001_map201901001_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.map201901001_map201901001_id_seq OWNER TO hom;

--
-- Name: map201901001_map201901001_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: hom
--

ALTER SEQUENCE public.map201901001_map201901001_id_seq OWNED BY public.map201901001.map201901001_id;


--
-- Name: map_land_nl_iso3166_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.map_land_nl_iso3166_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.map_land_nl_iso3166_id_seq OWNER TO rpadmin;

--
-- Name: map_land_nl_iso3166_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.map_land_nl_iso3166_id_seq OWNED BY public.map_land_nl_iso3166.id;


SET default_with_oids = true;

--
-- Name: menu; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.menu (
    menu_name character varying(40) NOT NULL,
    relation_name character varying(40) NOT NULL,
    menu_top smallint DEFAULT 1 NOT NULL,
    menu_left smallint DEFAULT 1 NOT NULL,
    capability integer DEFAULT 32767 NOT NULL,
    id integer NOT NULL
);


ALTER TABLE public.menu OWNER TO rpadmin;

--
-- Name: TABLE menu; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.menu IS 'simple editor definition table.';


--
-- Name: menu_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.menu_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.menu_id_seq OWNER TO rpadmin;

--
-- Name: menu_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.menu_id_seq OWNED BY public.menu.id;


--
-- Name: menu_item; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.menu_item (
    menu_name character varying(40) NOT NULL,
    column_name character varying(40) NOT NULL,
    edit_type character(1) DEFAULT 'T'::bpchar NOT NULL,
    capability smallint DEFAULT 32767 NOT NULL,
    id integer NOT NULL,
    item_length smallint DEFAULT 10,
    placeholder text,
    regex_name character varying(30) DEFAULT 'anything'::character varying,
    validator character varying(30)
);


ALTER TABLE public.menu_item OWNER TO rpadmin;

--
-- Name: TABLE menu_item; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.menu_item IS 'simple editor definition table. define items';


--
-- Name: menu_item_display; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.menu_item_display (
    menu_name character varying(40) NOT NULL,
    column_name character varying(40),
    length smallint,
    "precision" smallint,
    id integer NOT NULL
);


ALTER TABLE public.menu_item_display OWNER TO rpadmin;

--
-- Name: TABLE menu_item_display; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.menu_item_display IS 'used for menu item formatting.';


--
-- Name: menu_option_queries; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.menu_option_queries (
    menu_name character varying(40) NOT NULL,
    column_name character varying(40) NOT NULL,
    query text,
    id integer NOT NULL
);


ALTER TABLE public.menu_option_queries OWNER TO rpadmin;

--
-- Name: TABLE menu_option_queries; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.menu_option_queries IS 'produces html select lists or sequences for menu_items.';


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
    atc.nullable
   FROM ((((public.menu m
     JOIN public.menu_item mi USING (menu_name))
     JOIN public.all_tab_columns atc ON ((((m.relation_name)::text = atc.table_name) AND ((mi.column_name)::text = atc.column_name))))
     LEFT JOIN public.menu_option_queries moq ON ((((m.menu_name)::text = (moq.menu_name)::text) AND ((mi.column_name)::text = (moq.column_name)::text))))
     LEFT JOIN public.menu_item_display mid ON ((((mid.menu_name)::text = (mi.menu_name)::text) AND ((mid.column_name)::text = (mi.column_name)::text))));


ALTER TABLE public.menu_item_defs OWNER TO rpadmin;

--
-- Name: menu_item_display_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.menu_item_display_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.menu_item_display_id_seq OWNER TO rpadmin;

--
-- Name: menu_item_display_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.menu_item_display_id_seq OWNED BY public.menu_item_display.id;


--
-- Name: menu_item_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.menu_item_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.menu_item_id_seq OWNER TO rpadmin;

--
-- Name: menu_item_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.menu_item_id_seq OWNED BY public.menu_item.id;


--
-- Name: menu_option_queries_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.menu_option_queries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.menu_option_queries_id_seq OWNER TO rpadmin;

--
-- Name: menu_option_queries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.menu_option_queries_id_seq OWNED BY public.menu_option_queries.id;


SET default_with_oids = false;

--
-- Name: milestone_grade; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.milestone_grade (
    milestone_grade_id bigint NOT NULL,
    snummer integer,
    prjm_id integer,
    grade numeric(3,1),
    multiplier double precision DEFAULT 1.0,
    trans_id bigint,
    CONSTRAINT milestone_grade_grade_check CHECK ((grade > (0)::numeric)),
    CONSTRAINT milestone_grade_grade_check1 CHECK ((grade <= (10)::numeric))
);


ALTER TABLE public.milestone_grade OWNER TO rpadmin;

--
-- Name: TABLE milestone_grade; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.milestone_grade IS 'Use to persist tutor grading through peer assessment results table.';


--
-- Name: milestone_grade_milestone_grade_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.milestone_grade_milestone_grade_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.milestone_grade_milestone_grade_id_seq OWNER TO rpadmin;

--
-- Name: milestone_grade_milestone_grade_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.milestone_grade_milestone_grade_id_seq OWNED BY public.milestone_grade.milestone_grade_id;


--
-- Name: milestone_grp; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.milestone_grp AS
 SELECT DISTINCT pm.prj_id,
    pm.milestone
   FROM (public.prj_tutor pt
     JOIN public.prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
  ORDER BY pm.prj_id, pm.milestone;


ALTER TABLE public.milestone_grp OWNER TO rpadmin;

--
-- Name: milestone_open_past_due; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.milestone_open_past_due AS
 SELECT pm.prj_id,
    pm.milestone,
    pg.snummer,
    pt.prjtg_id,
    pm.assessment_due
   FROM ((public.prj_grp pg
     JOIN public.prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN public.prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
  WHERE ((pm.assessment_due < now()) AND (pg.prj_grp_open = true) AND (pm.prj_milestone_open = true));


ALTER TABLE public.milestone_open_past_due OWNER TO rpadmin;

--
-- Name: registered_photos; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.registered_photos (
    snummer integer NOT NULL
);


ALTER TABLE public.registered_photos OWNER TO rpadmin;

--
-- Name: TABLE registered_photos; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.registered_photos IS 'students with photo.';


--
-- Name: portrait; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.portrait AS
 SELECT st.snummer,
    (('fotos/'::text || COALESCE((rf.snummer)::text, '0'::text)) || '.jpg'::text) AS photo
   FROM (public.student st
     LEFT JOIN public.registered_photos rf USING (snummer));


ALTER TABLE public.portrait OWNER TO rpadmin;

--
-- Name: minifoto; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.minifoto AS
 SELECT student.snummer,
    (((('<img src="'::text || portrait.photo) || '" alt="'::text) || (student.snummer)::text) || '" style="width:24px;height:auto"/>'::text) AS minifoto
   FROM (public.student
     JOIN public.portrait USING (snummer));


ALTER TABLE public.minifoto OWNER TO rpadmin;

--
-- Name: minikiosk_visits; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.minikiosk_visits (
    counter integer NOT NULL
);


ALTER TABLE public.minikiosk_visits OWNER TO rpadmin;

--
-- Name: TABLE minikiosk_visits; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.minikiosk_visits IS 'visit count for mini kiosk.';


--
-- Name: missing_nats; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.missing_nats (
    leidende_nationaliteit text
);


ALTER TABLE public.missing_nats OWNER TO rpadmin;

--
-- Name: module; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.module (
    module_id integer DEFAULT nextval(('module_id_seq'::text)::regclass) NOT NULL,
    progress_code character varying(10),
    semester integer DEFAULT 1 NOT NULL,
    duration integer,
    author character varying(50),
    version integer,
    status integer,
    last_change_date date,
    last_change_by integer,
    repository_uri character varying(80),
    module_url character varying(200),
    author_id integer,
    module_description text
);


ALTER TABLE public.module OWNER TO rpadmin;

--
-- Name: COLUMN module.module_id; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON COLUMN public.module.module_id IS 'Generated sequence number';


--
-- Name: COLUMN module.duration; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON COLUMN public.module.duration IS 'something like 1 semester of 7 weeks';


--
-- Name: module_activity; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.module_activity (
    module_activity_id character varying(10) NOT NULL
);


ALTER TABLE public.module_activity OWNER TO rpadmin;

--
-- Name: module_activity_description; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.module_activity_description (
    module_activity_id character varying(10) NOT NULL,
    language_id character(2) NOT NULL,
    description character varying(50)
);


ALTER TABLE public.module_activity_description OWNER TO rpadmin;

--
-- Name: module_desciption_long; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.module_desciption_long (
    module_id integer NOT NULL,
    language_id character(2) NOT NULL,
    description text
);


ALTER TABLE public.module_desciption_long OWNER TO rpadmin;

--
-- Name: module_description_short; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.module_description_short (
    module_id integer NOT NULL,
    language_id character(2) NOT NULL,
    description text
);


ALTER TABLE public.module_description_short OWNER TO rpadmin;

--
-- Name: module_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.module_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.module_id_seq OWNER TO rpadmin;

--
-- Name: SEQUENCE module_id_seq; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON SEQUENCE public.module_id_seq IS 'modules in curriculum';


--
-- Name: module_language; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.module_language (
    module_id integer NOT NULL,
    language_id character(2) NOT NULL
);


ALTER TABLE public.module_language OWNER TO rpadmin;

--
-- Name: module_part_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.module_part_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.module_part_seq OWNER TO rpadmin;

--
-- Name: module_participant; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.module_participant AS
 SELECT DISTINCT pm.prj_id,
    pg.snummer
   FROM ((public.prj_grp pg
     JOIN public.prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN public.prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
  ORDER BY pm.prj_id, pg.snummer;


ALTER TABLE public.module_participant OWNER TO rpadmin;

--
-- Name: module_participant_hours; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.module_participant_hours AS
 SELECT module_participant.prj_id,
    module_participant.snummer,
    course_hours.start_date,
    course_hours.stop_date,
    course_hours.course_week_no,
    course_hours.day,
    course_hours.hourcode,
    course_hours.start_time,
    course_hours.stop_time
   FROM public.module_participant,
    public.course_hours;


ALTER TABLE public.module_participant_hours OWNER TO rpadmin;

--
-- Name: module_prerequisite; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.module_prerequisite (
    module_id integer NOT NULL,
    prerequisite integer NOT NULL
);


ALTER TABLE public.module_prerequisite OWNER TO rpadmin;

--
-- Name: module_resource; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.module_resource (
    module_id integer NOT NULL,
    module_resource_id integer NOT NULL,
    module_resource_type_id character varying(10),
    description character varying(10)
);


ALTER TABLE public.module_resource OWNER TO rpadmin;

--
-- Name: module_resource_type; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.module_resource_type (
    module_resource_type_id character varying(10) NOT NULL
);


ALTER TABLE public.module_resource_type OWNER TO rpadmin;

--
-- Name: module_resource_type_description; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.module_resource_type_description (
    module_resource_type_id character varying(10) NOT NULL,
    language_id character(2) NOT NULL,
    description character varying(50)
);


ALTER TABLE public.module_resource_type_description OWNER TO rpadmin;

--
-- Name: module_topic; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.module_topic (
    module_id integer NOT NULL,
    module_topic_id integer NOT NULL,
    week_id integer NOT NULL,
    hour_id integer NOT NULL
);


ALTER TABLE public.module_topic OWNER TO rpadmin;

--
-- Name: module_topic_description; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.module_topic_description (
    module_id integer NOT NULL,
    module_topic_id integer NOT NULL,
    language_id character(2) NOT NULL,
    description character varying(50)
);


ALTER TABLE public.module_topic_description OWNER TO rpadmin;

--
-- Name: module_week_schedule; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.module_week_schedule (
    module_id integer NOT NULL,
    week_id integer NOT NULL,
    module_activity_id character varying(10) NOT NULL,
    hours_planned integer
);


ALTER TABLE public.module_week_schedule OWNER TO rpadmin;

--
-- Name: mooc; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.mooc (
    mooc_id integer NOT NULL,
    snummer integer,
    username character varying(30),
    first_name character varying(20),
    last_name character varying(20),
    organizational_identifier_e_g_student_number integer,
    week1 integer,
    week2 integer,
    week3 integer,
    week4 integer,
    week5 integer,
    week6 integer,
    total integer
);


ALTER TABLE public.mooc OWNER TO rpadmin;

--
-- Name: mooc_mooc_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.mooc_mooc_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.mooc_mooc_id_seq OWNER TO rpadmin;

--
-- Name: mooc_mooc_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.mooc_mooc_id_seq OWNED BY public.mooc.mooc_id;


--
-- Name: movable_student; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.movable_student AS
 SELECT judge_sum.prj_id,
    judge_sum.milestone,
    judge_sum.snummer
   FROM (public.judge_sum
     JOIN public.contestant_sum USING (prj_id, milestone, snummer, grade_sum))
  WHERE (judge_sum.grade_sum = 0);


ALTER TABLE public.movable_student OWNER TO rpadmin;

--
-- Name: my_peer_results_2; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.my_peer_results_2 AS
 SELECT gca.criterium,
    pm.prj_id,
    gca.prjtg_id,
    gca.crit_grade_sum,
    ((gca.crit_grade_sum)::numeric / ((gts.grp_size * (gts.grp_size - 1)))::numeric) AS grp_avg,
    cca.snummer,
    cca.contestant_crit_grade_sum,
    ((cca.contestant_crit_grade_sum)::numeric / ((gts.grp_size - 1))::numeric) AS grade,
        CASE
            WHEN ((gca.crit_grade_sum)::numeric <> (0)::numeric) THEN (((((gts.grp_size - 1) * gts.grp_size))::numeric * (cca.contestant_crit_grade_sum)::numeric) / ((gca.crit_grade_sum * (gts.grp_size - 1)))::numeric)
            ELSE (0)::numeric
        END AS multiplier,
    gts.grp_size,
    t.tutor,
    pm.milestone,
    pt.grp_num,
    pt.prjm_id,
    pt.prj_tutor_open,
    pt.assessment_complete,
    c.nl_short,
    c.de_short,
    c.nl,
    c.de,
    c.en_short,
    c.en
   FROM ((((((public.grp_crit_avg gca
     JOIN public.contestant_crit_avg cca USING (prjtg_id, criterium))
     JOIN public.grp_tg_size gts USING (prjtg_id))
     JOIN public.prj_tutor pt USING (prjtg_id))
     JOIN public.tutor t ON ((pt.tutor_id = t.userid)))
     JOIN public.prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
     JOIN public.criteria_v c ON (((pm.prjm_id = c.prjm_id) AND (c.criterium = gca.criterium))));


ALTER TABLE public.my_peer_results_2 OWNER TO rpadmin;

--
-- Name: repositories; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.repositories (
    milestone integer NOT NULL,
    repospath character varying(128) NOT NULL,
    description text,
    isroot boolean DEFAULT true,
    id integer NOT NULL,
    url_tail character varying(120),
    owner integer,
    grp_num smallint DEFAULT 0,
    prjm_id integer,
    prjtg_id integer,
    youngest integer DEFAULT 0,
    last_commit timestamp without time zone
);


ALTER TABLE public.repositories OWNER TO rpadmin;

--
-- Name: TABLE repositories; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.repositories IS 'Repositories used in peerweb.';


--
-- Name: my_project_repositories; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.my_project_repositories AS
 SELECT pm.prj_id,
    (pm.milestone)::integer AS milestone,
    pg.snummer,
    r.grp_num,
    r.description,
    r.url_tail,
    r.id AS repo_id
   FROM (((public.repositories r
     JOIN public.prj_tutor pt ON (((r.prjm_id = pt.prjm_id) AND (r.grp_num = pt.grp_num))))
     JOIN public.prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
     JOIN public.prj_grp pg ON ((pt.prjtg_id = pg.prjtg_id)))
UNION
 SELECT pm.prj_id,
    pm.milestone,
    pg.snummer,
    0 AS grp_num,
    r.description,
    r.url_tail,
    r.id AS repo_id
   FROM (((public.repositories r
     JOIN public.prj_tutor pt ON (((r.prjm_id = pt.prjm_id) AND (r.grp_num = 0))))
     JOIN public.prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
     JOIN public.prj_grp pg ON ((pt.prjtg_id = pg.prjtg_id)));


ALTER TABLE public.my_project_repositories OWNER TO rpadmin;

--
-- Name: nat_mapper_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.nat_mapper_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.nat_mapper_id_seq OWNER TO rpadmin;

--
-- Name: nat_mapper_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.nat_mapper_id_seq OWNED BY public.nat_mapper.id;


--
-- Name: nationality; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.nationality AS
 SELECT iso3166.a2 AS value,
    initcap((iso3166.country)::text) AS name
   FROM public.iso3166;


ALTER TABLE public.nationality OWNER TO rpadmin;

--
-- Name: VIEW nationality; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.nationality IS 'used in student_admin';


--
-- Name: naw; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.naw AS
 SELECT student.snummer,
    student.achternaam,
    student.roepnaam,
    student.tussenvoegsel,
    student.straat,
    student.plaats,
    student.huisnr,
    student.pcode
   FROM public.student;


ALTER TABLE public.naw OWNER TO rpadmin;

--
-- Name: newreg; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.newreg (
    snummer integer
);


ALTER TABLE public.newreg OWNER TO rpadmin;

--
-- Name: news; Type: TABLE; Schema: public; Owner: hom
--

CREATE TABLE public.news (
    snummer integer,
    achternaam text,
    tussenvoegsel text,
    voorletters text,
    roepnaam text,
    straat text,
    huisnr character(4),
    pcode text,
    plaats text,
    email1 public.email,
    nationaliteit character(2),
    cohort smallint,
    gebdat date,
    sex character(1),
    lang character(2),
    pcn integer,
    opl bigint,
    phone_home text,
    phone_gsm text,
    phone_postaddress text,
    faculty_id smallint,
    hoofdgrp text,
    active boolean,
    slb integer,
    land character(3),
    studieplan integer,
    geboorteplaats text,
    geboorteland character(3),
    voornamen text,
    class_id integer
);


ALTER TABLE public.news OWNER TO hom;

--
-- Name: nums; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.nums (
    snummer integer
);


ALTER TABLE public.nums OWNER TO rpadmin;

--
-- Name: osco_svn; Type: TABLE; Schema: public; Owner: hom
--

CREATE TABLE public.osco_svn (
    snummer integer,
    prj_grp_open boolean,
    written boolean,
    prjtg_id integer
);


ALTER TABLE public.osco_svn OWNER TO hom;

--
-- Name: oslb; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.oslb (
    snummer integer,
    slb integer
);


ALTER TABLE public.oslb OWNER TO rpadmin;

--
-- Name: pa20181030; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.pa20181030 AS
 SELECT ar.snummer,
    ar.reason,
    sc.sclass,
    s.achternaam,
    s.roepnaam
   FROM (((public.activity_participant ap
     JOIN public.absence_reason ar USING (act_id, snummer))
     JOIN public.student s ON ((ap.snummer = s.snummer)))
     JOIN public.student_class sc USING (faculty_id, class_id))
  WHERE (ap.act_id = 1931);


ALTER TABLE public.pa20181030 OWNER TO rpadmin;

--
-- Name: page_help; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.page_help (
    help_id integer NOT NULL,
    page character varying(40),
    author integer,
    helptext text
);


ALTER TABLE public.page_help OWNER TO rpadmin;

--
-- Name: TABLE page_help; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.page_help IS 'diy help texts';


--
-- Name: page_help_help_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.page_help_help_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.page_help_help_id_seq OWNER TO rpadmin;

--
-- Name: page_help_help_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.page_help_help_id_seq OWNED BY public.page_help.help_id;


--
-- Name: participant_present_list; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.participant_present_list AS
 SELECT mh.snummer,
    mh.prj_id,
    mh.course_week_no,
    mh.day,
    mh.hourcode,
    lt.from_ip,
    lt.since
   FROM (public.module_participant_hours mh
     LEFT JOIN public.logon_map_on_timetable lt ON ((((mh.course_week_no = lt.course_week_no) AND (mh.day = lt.day) AND (mh.hourcode = lt.hourcode) AND (mh.snummer = lt.snummer)) OR (lt.snummer IS NULL))));


ALTER TABLE public.participant_present_list OWNER TO rpadmin;

SET default_with_oids = true;

--
-- Name: password_request; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.password_request (
    userid integer NOT NULL,
    request_time timestamp without time zone DEFAULT now() NOT NULL,
    id bigint NOT NULL
);


ALTER TABLE public.password_request OWNER TO rpadmin;

--
-- Name: TABLE password_request; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.password_request IS 'log password requests.';


--
-- Name: password_request_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.password_request_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.password_request_id_seq OWNER TO rpadmin;

--
-- Name: password_request_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.password_request_id_seq OWNED BY public.password_request.id;


SET default_with_oids = false;

--
-- Name: peer_settings; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.peer_settings (
    key character varying(30) NOT NULL,
    value text,
    comment text
);


ALTER TABLE public.peer_settings OWNER TO rpadmin;

--
-- Name: TABLE peer_settings; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.peer_settings IS 'several settings which would otherwise be hardcode. 
in php read on each request.';


--
-- Name: personal_repos; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.personal_repos (
    owner integer,
    repospath text,
    url_tail text,
    isroot boolean DEFAULT false,
    id integer NOT NULL,
    description text,
    youngest integer DEFAULT 0,
    last_commit timestamp without time zone
);


ALTER TABLE public.personal_repos OWNER TO rpadmin;

--
-- Name: TABLE personal_repos; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.personal_repos IS 'personal repositories, created through peerweb.';


--
-- Name: personal_repos_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.personal_repos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.personal_repos_id_seq OWNER TO rpadmin;

--
-- Name: personal_repos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.personal_repos_id_seq OWNED BY public.personal_repos.id;


--
-- Name: portrait_with_name; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.portrait_with_name AS
 SELECT s.snummer,
    (((s.roepnaam || ' '::text) || COALESCE((s.tussenvoegsel || ' '::text), ''::text)) || s.achternaam) AS name,
    (('fotos/'::text || COALESCE((rp.snummer)::text, 'anonymous'::text)) || '.jpg'::text) AS image
   FROM (public.student s
     LEFT JOIN public.registered_photos rp USING (snummer));


ALTER TABLE public.portrait_with_name OWNER TO rpadmin;

--
-- Name: VIEW portrait_with_name; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.portrait_with_name IS 'used for wsb password creation scripts';


SET default_with_oids = true;

--
-- Name: weekdays; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.weekdays (
    day smallint NOT NULL,
    dayname character varying(12),
    day_lang character(2),
    shortname character(2)
);


ALTER TABLE public.weekdays OWNER TO rpadmin;

--
-- Name: TABLE weekdays; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.weekdays IS 'Days of the weeks.';


--
-- Name: present_anywhere; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.present_anywhere AS
 SELECT logon.userid,
    logon.since,
    logon.id,
    logon.from_ip,
    timetableweek.day,
    timetableweek.hourcode,
    timetableweek.start_time,
    timetableweek.stop_time,
    weekdays.dayname,
    weekdays.day_lang
   FROM public.logon,
    (public.timetableweek
     JOIN public.weekdays USING (day))
  WHERE ((to_char(logon.since, 'HH24:MI:SS'::text) >= (timetableweek.start_time)::text) AND (to_char(logon.since, 'HH24:MI:SS'::text) <= (timetableweek.stop_time)::text) AND (date_part('dow'::text, logon.since) = (timetableweek.day)::double precision));


ALTER TABLE public.present_anywhere OWNER TO rpadmin;

--
-- Name: present_at_fontys; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.present_at_fontys AS
 SELECT present_anywhere.userid,
    present_anywhere.since,
    present_anywhere.id,
    present_anywhere.from_ip,
    present_anywhere.day,
    present_anywhere.hourcode,
    present_anywhere.start_time,
    present_anywhere.stop_time,
    present_anywhere.dayname,
    present_anywhere.day_lang
   FROM public.present_anywhere
  WHERE (present_anywhere.from_ip <<= '145.85.0.0/16'::inet);


ALTER TABLE public.present_at_fontys OWNER TO rpadmin;

--
-- Name: present_in_coursehours; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.present_in_coursehours AS
 SELECT ch.start_date,
    ch.stop_date,
    ch.course_week_no,
    ch.day,
    ch.hourcode,
    ch.start_time,
    ch.stop_time,
    lo.userid,
    lo.since,
    lo.id,
    lo.from_ip
   FROM (public.course_hours ch
     LEFT JOIN public.logon lo ON ((((lo.since)::text >= (ch.start_time)::text) AND ((lo.since)::text <= (ch.stop_time)::text))));


ALTER TABLE public.present_in_coursehours OWNER TO rpadmin;

--
-- Name: present_in_courseweek; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.present_in_courseweek AS
 SELECT course_week.start_date,
    course_week.stop_date,
    course_week.course_week_no,
    present_anywhere.userid,
    present_anywhere.since,
    present_anywhere.id,
    present_anywhere.from_ip,
    present_anywhere.day,
    present_anywhere.hourcode,
    present_anywhere.start_time,
    present_anywhere.stop_time,
    present_anywhere.dayname,
    present_anywhere.day_lang
   FROM (public.course_week
     LEFT JOIN public.present_anywhere ON (((present_anywhere.since >= (course_week.start_date)::timestamp without time zone) AND (present_anywhere.since <= (course_week.stop_date)::timestamp without time zone))));


ALTER TABLE public.present_in_courseweek OWNER TO rpadmin;

SET default_with_oids = false;

--
-- Name: prj3_2018; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.prj3_2018 (
    prj3_2018_id integer NOT NULL,
    snummer integer,
    gn integer,
    g integer
);


ALTER TABLE public.prj3_2018 OWNER TO rpadmin;

--
-- Name: prj3_2018_prj3_2018_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.prj3_2018_prj3_2018_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.prj3_2018_prj3_2018_id_seq OWNER TO rpadmin;

--
-- Name: prj3_2018_prj3_2018_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.prj3_2018_prj3_2018_id_seq OWNED BY public.prj3_2018.prj3_2018_id;


--
-- Name: prj3_assessment; Type: TABLE; Schema: public; Owner: hom
--

CREATE TABLE public.prj3_assessment (
    contestant integer,
    judge integer,
    criterium smallint,
    grade smallint,
    prjtg_id integer
);


ALTER TABLE public.prj3_assessment OWNER TO hom;

--
-- Name: prj3_remarks; Type: TABLE; Schema: public; Owner: hom
--

CREATE TABLE public.prj3_remarks (
    contestant integer,
    judge integer,
    prjtg_id integer,
    remark text,
    id integer
);


ALTER TABLE public.prj3_remarks OWNER TO hom;

--
-- Name: prj3puk; Type: TABLE; Schema: public; Owner: hom
--

CREATE TABLE public.prj3puk (
    milestone integer,
    repospath character varying(128),
    description text,
    isroot boolean,
    id integer,
    url_tail character varying(120),
    owner integer,
    grp_num smallint,
    prjm_id integer,
    prjtg_id integer,
    youngest integer,
    last_commit timestamp without time zone
);


ALTER TABLE public.prj3puk OWNER TO hom;

SET default_with_oids = true;

--
-- Name: prj_contact; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.prj_contact (
    snummer integer,
    prjtg_id integer NOT NULL
);


ALTER TABLE public.prj_contact OWNER TO rpadmin;

--
-- Name: TABLE prj_contact; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.prj_contact IS 'Contact is central person in a group that is primary contact for tutor and group.';


--
-- Name: prj_grp_builder2; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.prj_grp_builder2 AS
 SELECT pmt.prj_id,
    pgo.snummer,
    pmt.milestone,
    false AS prj_grp_open,
    ptt.grp_num,
    false AS written,
    pmt.prjm_id,
    ptt.prjtg_id,
    pto.prjm_id AS orig_prjm_id
   FROM ((((public.prj_grp pgo
     JOIN public.prj_tutor pto ON ((pgo.prjtg_id = pto.prjtg_id)))
     JOIN public.prj_milestone pmo ON ((pto.prjm_id = pmo.prjm_id)))
     JOIN public.prj_tutor ptt ON ((pto.grp_num = ptt.grp_num)))
     JOIN public.prj_milestone pmt ON ((ptt.prjm_id = pmt.prjm_id)));


ALTER TABLE public.prj_grp_builder2 OWNER TO rpadmin;

--
-- Name: VIEW prj_grp_builder2; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.prj_grp_builder2 IS 'used in copying project groups';


--
-- Name: prj_grp_email; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.prj_grp_email AS
 SELECT pt.prjm_id,
    p.afko,
    p.year,
    COALESCE(btrim((ga.alias)::text), ('g'::text || pt.grp_num)) AS alias,
    pt.grp_name,
    lower(((((((btrim((fontys_course.course_short)::text) || '.'::text) || btrim((p.afko)::text)) || '.'::text) || p.year) || '.'::text) || btrim((pt.grp_name)::text))) AS maillist,
    s.email1,
    pt.grp_num,
    pt.prjtg_id,
    s.achternaam,
    s.roepnaam
   FROM ((((((public.student s
     JOIN public.prj_grp pg USING (snummer))
     JOIN public.prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN public.prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
     JOIN public.project p ON ((pm.prj_id = p.prj_id)))
     JOIN public.fontys_course USING (course))
     LEFT JOIN public.grp_alias ga ON ((pt.prjtg_id = ga.prjtg_id)))
  ORDER BY pt.grp_num;


ALTER TABLE public.prj_grp_email OWNER TO rpadmin;

--
-- Name: VIEW prj_grp_email; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.prj_grp_email IS 'used to create maillist per project group';


--
-- Name: prj_grp_email_g0; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.prj_grp_email_g0 AS
 SELECT pt.prjm_id,
    p.afko,
    p.year,
    'all'::text AS alias,
    'g0'::text AS grp_name,
    lower(((((btrim((fontys_course.course_short)::text) || '.'::text) || btrim((p.afko)::text)) || '.'::text) || p.year)) AS maillist,
    s.email1,
    0 AS grp_num,
    pt.prjtg_id,
    s.achternaam,
    s.roepnaam
   FROM (((((public.student s
     JOIN public.prj_grp pg USING (snummer))
     JOIN public.prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN public.prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
     JOIN public.project p ON ((pm.prj_id = p.prj_id)))
     JOIN public.fontys_course USING (course))
  ORDER BY s.achternaam, s.roepnaam;


ALTER TABLE public.prj_grp_email_g0 OWNER TO rpadmin;

--
-- Name: VIEW prj_grp_email_g0; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.prj_grp_email_g0 IS 'used to create maillist for all members of project';


--
-- Name: prj_grp_open; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.prj_grp_open AS
 SELECT bool_and(pg.prj_grp_open) AS bool_and,
    pm.prj_id,
    pm.milestone,
    pt.grp_num,
    pt.prjtg_id
   FROM ((public.prj_grp pg
     JOIN public.prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN public.prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
  GROUP BY pm.prj_id, pm.milestone, pt.grp_num, pt.prjtg_id;


ALTER TABLE public.prj_grp_open OWNER TO rpadmin;

--
-- Name: prj_grp_ready; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.prj_grp_ready AS
 SELECT prj_grp.prjtg_id,
    (bool_and(prj_grp.written) AND (NOT bool_and(prj_grp.prj_grp_open))) AS ready
   FROM public.prj_grp
  GROUP BY prj_grp.prjtg_id;


ALTER TABLE public.prj_grp_ready OWNER TO rpadmin;

--
-- Name: prj_grp_tr; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.prj_grp_tr AS
 SELECT prj_grp.prjtg_id,
    prj_grp.snummer,
    prj_grp.prj_grp_open,
    prj_grp.written
   FROM public.prj_grp;


ALTER TABLE public.prj_grp_tr OWNER TO rpadmin;

--
-- Name: VIEW prj_grp_tr; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.prj_grp_tr IS 'prj_grp with prj_id, milestone, prjm_id and grp_num dropped';


--
-- Name: prj_tutor_builder; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.prj_tutor_builder AS
 SELECT pm.prj_id,
    t.tutor,
    pt.tutor_id,
    pm.milestone,
    pt.grp_num,
    pm.prjm_id,
    pt.prjtg_id,
    pt.grp_name
   FROM ((public.prj_milestone pm
     JOIN public.prj_tutor pt USING (prjm_id))
     JOIN public.tutor t ON ((t.userid = pt.tutor_id)));


ALTER TABLE public.prj_tutor_builder OWNER TO rpadmin;

--
-- Name: prj_tutor_email; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.prj_tutor_email AS
 SELECT DISTINCT pt.prjm_id,
    pt.afko,
    pt.year,
    'tutors'::text AS alias,
    (lower((((((btrim((fontys_course.course_short)::text) || '.'::text) || btrim((pt.afko)::text)) || '.'::text) || pt.year) || '.'::text)) || 'tutors'::text) AS maillist,
    s.email1,
    '-1'::integer AS grp_num,
    0 AS prjtg_id,
    s.achternaam,
    s.roepnaam
   FROM (((public.all_prj_tutor pt
     JOIN public.tutor t ON ((pt.tutor_id = t.userid)))
     JOIN public.student s ON ((t.userid = s.snummer)))
     JOIN public.fontys_course USING (course));


ALTER TABLE public.prj_tutor_email OWNER TO rpadmin;

--
-- Name: VIEW prj_tutor_email; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.prj_tutor_email IS 'used to create maillist per project group';


--
-- Name: prj_tutor_prjtg_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.prj_tutor_prjtg_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.prj_tutor_prjtg_id_seq OWNER TO rpadmin;

--
-- Name: prj_tutor_prjtg_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.prj_tutor_prjtg_id_seq OWNED BY public.prj_tutor.prjtg_id;


--
-- Name: prj_tutor_tr; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.prj_tutor_tr AS
 SELECT pt.prjm_id,
    pt.grp_num,
    t.tutor,
    pt.tutor_id,
    pt.prjtg_id,
    pt.prj_tutor_open,
    pt.assessment_complete
   FROM (public.prj_tutor pt
     JOIN public.tutor t ON ((pt.tutor_id = t.userid)));


ALTER TABLE public.prj_tutor_tr OWNER TO rpadmin;

--
-- Name: VIEW prj_tutor_tr; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.prj_tutor_tr IS 'prj_tutor with prj_id and milestone dropped';


--
-- Name: prjm_activity_count; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.prjm_activity_count AS
 SELECT activity.prjm_id,
    count(*) AS act_count
   FROM public.activity
  GROUP BY activity.prjm_id;


ALTER TABLE public.prjm_activity_count OWNER TO rpadmin;

--
-- Name: VIEW prjm_activity_count; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.prjm_activity_count IS 'used by peerpresenceoverview';


--
-- Name: prjm_size; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.prjm_size AS
 SELECT pm.prjm_id,
    count(*) AS size
   FROM ((public.prj_grp pg
     JOIN public.prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN public.prj_milestone pm ON ((pm.prjm_id = pt.prjm_id)))
  GROUP BY pm.prjm_id;


ALTER TABLE public.prjm_size OWNER TO rpadmin;

--
-- Name: prjtg_size; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.prjtg_size AS
 SELECT count(*) AS size,
    prj_grp.prjtg_id
   FROM public.prj_grp
  GROUP BY prj_grp.prjtg_id;


ALTER TABLE public.prjtg_size OWNER TO rpadmin;

SET default_with_oids = false;

--
-- Name: project_attributes_def; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.project_attributes_def (
    project_attributes_def integer NOT NULL,
    author integer,
    prj_id integer,
    pi_name character varying(40),
    pi_description text,
    interpretation character(1) DEFAULT 'N'::bpchar,
    due_date date
);


ALTER TABLE public.project_attributes_def OWNER TO rpadmin;

--
-- Name: TABLE project_attributes_def; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.project_attributes_def IS 'Define project (group) attributes, simple key value pairs like appraisal, performance number like profit of defect percentage etc. Freely defined by project owner.';


--
-- Name: project_attributes_def_project_attributes_def_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.project_attributes_def_project_attributes_def_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.project_attributes_def_project_attributes_def_seq OWNER TO rpadmin;

--
-- Name: project_attributes_def_project_attributes_def_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.project_attributes_def_project_attributes_def_seq OWNED BY public.project_attributes_def.project_attributes_def;


--
-- Name: project_attributes_values; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.project_attributes_values (
    project_attributes_def integer,
    pi_value text,
    prjtg_id integer,
    trans_id bigint,
    id integer NOT NULL
);


ALTER TABLE public.project_attributes_values OWNER TO rpadmin;

--
-- Name: TABLE project_attributes_values; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.project_attributes_values IS 'Values of project attributes per group and milestone.';


--
-- Name: COLUMN project_attributes_values.id; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON COLUMN public.project_attributes_values.id IS 'pk';


--
-- Name: project_attributes_values_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.project_attributes_values_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.project_attributes_values_id_seq OWNER TO rpadmin;

--
-- Name: project_attributes_values_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.project_attributes_values_id_seq OWNED BY public.project_attributes_values.id;


--
-- Name: project_auditor; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.project_auditor (
    snummer integer,
    prjm_id integer,
    gid integer,
    id integer NOT NULL
);


ALTER TABLE public.project_auditor OWNER TO rpadmin;

--
-- Name: TABLE project_auditor; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.project_auditor IS 'Someone with an interest in the group, like a scribe (for presence recording).
';


--
-- Name: project_auditor_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.project_auditor_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.project_auditor_id_seq OWNER TO rpadmin;

--
-- Name: project_auditor_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.project_auditor_id_seq OWNED BY public.project_auditor.id;


--
-- Name: project_deliverables_pdeliverable_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.project_deliverables_pdeliverable_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.project_deliverables_pdeliverable_id_seq OWNER TO rpadmin;

--
-- Name: project_deliverables_pdeliverable_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.project_deliverables_pdeliverable_id_seq OWNED BY public.project_deliverables.pdeliverable_id;


--
-- Name: project_deliverables_tr; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.project_deliverables_tr AS
 SELECT project_deliverables.prjm_id,
    project_deliverables.doctype,
    project_deliverables.version_limit,
    project_deliverables.due,
    project_deliverables.publish_early,
    project_deliverables.rights
   FROM public.project_deliverables;


ALTER TABLE public.project_deliverables_tr OWNER TO rpadmin;

--
-- Name: VIEW project_deliverables_tr; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.project_deliverables_tr IS 'project_deliverables minus prj_id and milestone';


--
-- Name: project_grade_weight_sum_product; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.project_grade_weight_sum_product AS
 SELECT prj_milestone.prj_id,
    milestone_grade.snummer,
    sum((milestone_grade.grade * (prj_milestone.weight)::numeric)) AS grade_weight_sum,
    sum(prj_milestone.weight) AS weight_sum
   FROM (public.prj_milestone
     LEFT JOIN public.milestone_grade USING (prjm_id))
  GROUP BY prj_milestone.prj_id, milestone_grade.snummer;


ALTER TABLE public.project_grade_weight_sum_product OWNER TO rpadmin;

--
-- Name: project_group; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.project_group AS
 SELECT (student.snummer)::text AS username,
    p.password,
    pt.prjm_id,
    pm.prj_id,
    pm.milestone,
    pt.grp_num AS gid
   FROM ((((public.student
     JOIN public.prj_grp pg USING (snummer))
     JOIN public.prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN public.prj_milestone pm ON ((pm.prjm_id = pt.prjm_id)))
     JOIN public.passwd p ON ((student.snummer = p.userid)))
UNION
 SELECT (pt.tutor_id)::text AS username,
    p.password,
    pt.prjm_id,
    pm.prj_id,
    pm.milestone,
    0 AS gid
   FROM ((public.prj_tutor pt
     JOIN public.prj_milestone pm ON ((pm.prjm_id = pt.prjm_id)))
     JOIN public.passwd p ON ((p.userid = pt.tutor_id)))
UNION
 SELECT (project_auditor.snummer)::text AS username,
    p.password,
    project_auditor.prjm_id,
    prj_milestone.prj_id,
    prj_milestone.milestone,
    project_auditor.gid
   FROM ((public.project_auditor
     JOIN public.prj_milestone USING (prjm_id))
     JOIN public.passwd p ON ((project_auditor.snummer = p.userid)));


ALTER TABLE public.project_group OWNER TO rpadmin;

--
-- Name: project_grp_stakeholders; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.project_grp_stakeholders AS
 SELECT pg.snummer,
    pg.prjtg_id
   FROM public.prj_grp pg
UNION
 SELECT prj_tutor.tutor_id AS snummer,
    prj_tutor.prjtg_id
   FROM public.prj_tutor;


ALTER TABLE public.project_grp_stakeholders OWNER TO rpadmin;

--
-- Name: project_member; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.project_member AS
 SELECT DISTINCT pm.prj_id,
    pg.snummer
   FROM ((public.prj_milestone pm
     JOIN public.prj_tutor pt USING (prjm_id))
     JOIN public.prj_grp pg USING (prjtg_id));


ALTER TABLE public.project_member OWNER TO rpadmin;

--
-- Name: VIEW project_member; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.project_member IS 'project member without milstone';


--
-- Name: project_prj_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.project_prj_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.project_prj_id_seq OWNER TO rpadmin;

--
-- Name: project_prj_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.project_prj_id_seq OWNED BY public.project.prj_id;


--
-- Name: project_roles; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.project_roles (
    prj_id smallint NOT NULL,
    role character varying(30) DEFAULT 'Project manager'::character varying NOT NULL,
    rolenum smallint NOT NULL,
    capabilities integer DEFAULT 0,
    short character(4) NOT NULL
);


ALTER TABLE public.project_roles OWNER TO rpadmin;

--
-- Name: TABLE project_roles; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.project_roles IS 'Roles defined in a project';


--
-- Name: project_scribe_project_scribe_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.project_scribe_project_scribe_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.project_scribe_project_scribe_id_seq OWNER TO rpadmin;

--
-- Name: project_scribe_project_scribe_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.project_scribe_project_scribe_id_seq OWNED BY public.project_scribe.project_scribe_id;


--
-- Name: project_task; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.project_task (
    prj_id integer NOT NULL,
    task_id integer NOT NULL,
    name character varying(20) DEFAULT 'undefined task'::character varying NOT NULL,
    description text DEFAULT 'description will follow'::text,
    task_number integer DEFAULT 1
);


ALTER TABLE public.project_task OWNER TO rpadmin;

--
-- Name: TABLE project_task; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.project_task IS 'tasks to be checked by tutors or assistants';


--
-- Name: project_task_completed; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.project_task_completed (
    task_id integer NOT NULL,
    snummer integer NOT NULL,
    mark character(1),
    comment text,
    trans_id bigint NOT NULL,
    grade numeric(3,1)
);


ALTER TABLE public.project_task_completed OWNER TO rpadmin;

--
-- Name: TABLE project_task_completed; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.project_task_completed IS 'Tasks completed for project with grade and comment field.';


--
-- Name: project_task_completed_max_trans; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.project_task_completed_max_trans AS
 SELECT max(project_task_completed.trans_id) AS trans_id,
    project_task_completed.task_id,
    project_task_completed.snummer
   FROM public.project_task_completed
  GROUP BY project_task_completed.task_id, project_task_completed.snummer;


ALTER TABLE public.project_task_completed_max_trans OWNER TO rpadmin;

--
-- Name: project_task_completed_latest; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.project_task_completed_latest AS
 SELECT ptc.task_id,
    ptc.snummer,
    ptc.mark,
    ptc.grade,
    ptc.comment,
    ptc.trans_id
   FROM public.project_task_completed ptc
  WHERE (ptc.trans_id = ( SELECT project_task_completed_max_trans.trans_id
           FROM public.project_task_completed_max_trans
          WHERE ((ptc.snummer = project_task_completed_max_trans.snummer) AND (ptc.task_id = project_task_completed_max_trans.task_id))));


ALTER TABLE public.project_task_completed_latest OWNER TO rpadmin;

--
-- Name: project_task_task_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.project_task_task_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.project_task_task_id_seq OWNER TO rpadmin;

--
-- Name: project_task_task_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.project_task_task_id_seq OWNED BY public.project_task.task_id;


--
-- Name: project_tasks; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.project_tasks (
    prj_id smallint NOT NULL,
    task_id smallint DEFAULT 0 NOT NULL,
    task_description character varying(40) DEFAULT 'idle'::character varying NOT NULL,
    snummer integer NOT NULL
);


ALTER TABLE public.project_tasks OWNER TO rpadmin;

--
-- Name: TABLE project_tasks; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.project_tasks IS 'tasks in project, individual per user';


--
-- Name: project_tutor_owner; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.project_tutor_owner AS
 SELECT s.roepnaam,
    s.tussenvoegsel,
    s.achternaam,
    s.email1,
    s.snummer,
    p.prj_id
   FROM (public.project p
     JOIN public.student s ON ((p.owner_id = s.snummer)));


ALTER TABLE public.project_tutor_owner OWNER TO rpadmin;

--
-- Name: project_weight_sum; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.project_weight_sum AS
 SELECT prj_milestone.prj_id,
    sum(prj_milestone.weight) AS weight_sum
   FROM public.prj_milestone
  GROUP BY prj_milestone.prj_id;


ALTER TABLE public.project_weight_sum OWNER TO rpadmin;

--
-- Name: prospects; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.prospects (
    snummer integer NOT NULL,
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
    cohort double precision,
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


ALTER TABLE public.prospects OWNER TO rpadmin;

--
-- Name: prospect_portrait; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.prospect_portrait AS
 SELECT st.snummer,
    (('fotos/'::text || COALESCE((rf.snummer)::text, 'anonymous'::text)) || '.jpg'::text) AS photo
   FROM (public.prospects st
     LEFT JOIN public.registered_photos rf USING (snummer));


ALTER TABLE public.prospect_portrait OWNER TO rpadmin;

--
-- Name: puk; Type: TABLE; Schema: public; Owner: hom
--

CREATE TABLE public.puk (
    grp_name text
);


ALTER TABLE public.puk OWNER TO hom;

--
-- Name: ready_judge_count; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.ready_judge_count AS
 SELECT prj_grp.prjtg_id,
    count(*) AS count
   FROM public.prj_grp
  WHERE (prj_grp.written = true)
  GROUP BY prj_grp.prjtg_id;


ALTER TABLE public.ready_judge_count OWNER TO rpadmin;

--
-- Name: recruiters_note; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.recruiters_note (
    recruiters_note_id integer NOT NULL,
    followup integer,
    trans_id bigint,
    note_text text
);


ALTER TABLE public.recruiters_note OWNER TO rpadmin;

--
-- Name: TABLE recruiters_note; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.recruiters_note IS 'For recruiting. Take notes.';


--
-- Name: recruiters_note_recruiters_note_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.recruiters_note_recruiters_note_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.recruiters_note_recruiters_note_id_seq OWNER TO rpadmin;

--
-- Name: recruiters_note_recruiters_note_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.recruiters_note_recruiters_note_id_seq OWNED BY public.recruiters_note.recruiters_note_id;


--
-- Name: register; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.register (
    snummer integer
);


ALTER TABLE public.register OWNER TO rpadmin;

--
-- Name: registerl; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.registerl (
    snummer integer
);


ALTER TABLE public.registerl OWNER TO rpadmin;

--
-- Name: repos_group_name; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.repos_group_name AS
 SELECT pm.prj_id,
    pt.tutor_id AS owner,
    pm.milestone,
    pt.grp_num,
    pt.prjm_id,
    pt.prjtg_id,
    btrim((COALESCE(pt.grp_name, (('g'::text || pt.grp_num))::character varying))::text) AS group_name
   FROM (public.prj_tutor pt
     JOIN public.prj_milestone pm USING (prjm_id));


ALTER TABLE public.repos_group_name OWNER TO rpadmin;

--
-- Name: VIEW repos_group_name; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.repos_group_name IS 'used to create repository entries';


--
-- Name: repositories_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.repositories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.repositories_id_seq OWNER TO rpadmin;

--
-- Name: repositories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.repositories_id_seq OWNED BY public.repositories.id;


--
-- Name: resitexpected; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.resitexpected (
    resitexpected_id integer NOT NULL,
    snummer integer
);


ALTER TABLE public.resitexpected OWNER TO rpadmin;

--
-- Name: resitexpected_resitexpected_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.resitexpected_resitexpected_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.resitexpected_resitexpected_id_seq OWNER TO rpadmin;

--
-- Name: resitexpected_resitexpected_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.resitexpected_resitexpected_id_seq OWNED BY public.resitexpected.resitexpected_id;


--
-- Name: schedule_hours; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.schedule_hours (
    day smallint,
    hourcode smallint,
    start_time time without time zone,
    stop_time time without time zone
);


ALTER TABLE public.schedule_hours OWNER TO rpadmin;

--
-- Name: sebi_stick; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.sebi_stick (
    snummer integer NOT NULL,
    stick integer
);


ALTER TABLE public.sebi_stick OWNER TO rpadmin;

--
-- Name: sebiassessment_student; Type: TABLE; Schema: public; Owner: hom
--

CREATE TABLE public.sebiassessment_student (
    snummer integer,
    username text,
    password character varying(64),
    uid integer,
    gid integer,
    achternaam text,
    roepnaam text,
    tussenvoegsel text,
    opl bigint,
    cohort smallint,
    email1 public.email,
    pcn integer,
    sclass text,
    lang character(2),
    hoofdgrp text
);


ALTER TABLE public.sebiassessment_student OWNER TO hom;

--
-- Name: sebiassessment_student_uid_seq; Type: SEQUENCE; Schema: public; Owner: hom
--

CREATE SEQUENCE public.sebiassessment_student_uid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.sebiassessment_student_uid_seq OWNER TO hom;

--
-- Name: sebiassessment_student_uid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: hom
--

ALTER SEQUENCE public.sebiassessment_student_uid_seq OWNED BY public.sebiassessment_student.uid;


--
-- Name: unix_uid_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.unix_uid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.unix_uid_seq OWNER TO rpadmin;

--
-- Name: unix_uid; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.unix_uid (
    snummer integer,
    uid integer DEFAULT nextval('public.unix_uid_seq'::regclass) NOT NULL,
    gid integer DEFAULT 10001,
    username text NOT NULL
);


ALTER TABLE public.unix_uid OWNER TO rpadmin;

--
-- Name: TABLE unix_uid; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.unix_uid IS 'Unix uid for linux etc. Not actively used on 20130712.';


--
-- Name: sebiassessment_student_view; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.sebiassessment_student_view AS
 SELECT s.snummer,
    ('x'::text || s.snummer) AS username,
    pw.password,
    unix_uid.uid,
    unix_uid.gid,
    s.achternaam,
    s.roepnaam,
    s.tussenvoegsel,
    s.opl,
    s.cohort,
    s.email1,
    s.pcn,
    sc.sclass,
    s.lang,
    s.hoofdgrp
   FROM (((public.student s
     JOIN public.passwd pw ON ((s.snummer = pw.userid)))
     JOIN public.unix_uid USING (snummer))
     JOIN public.student_class sc ON ((s.class_id = sc.class_id)));


ALTER TABLE public.sebiassessment_student_view OWNER TO rpadmin;

--
-- Name: semester; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.semester (
    id smallint DEFAULT nextval(('semester_seq'::text)::regclass) NOT NULL,
    semnr smallint DEFAULT 1,
    cohort smallint,
    theme text
);


ALTER TABLE public.semester OWNER TO rpadmin;

--
-- Name: TABLE semester; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.semester IS 'semester with themes for cohort';


--
-- Name: COLUMN semester.semnr; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON COLUMN public.semester.semnr IS 'sem nr 1..8';


--
-- Name: COLUMN semester.cohort; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON COLUMN public.semester.cohort IS 'cohort year for theme of semester';


--
-- Name: semester_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.semester_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.semester_seq OWNER TO rpadmin;

SET default_with_oids = true;

--
-- Name: session_data; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.session_data (
    snummer integer NOT NULL,
    session text
);


ALTER TABLE public.session_data OWNER TO rpadmin;

--
-- Name: TABLE session_data; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.session_data IS 'Persists most of session, so loging out has some benefits.';


SET default_with_oids = false;

--
-- Name: set1; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.set1 (
    set1_id integer NOT NULL,
    img integer,
    snummer integer
);


ALTER TABLE public.set1 OWNER TO rpadmin;

--
-- Name: set1_set1_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.set1_set1_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.set1_set1_id_seq OWNER TO rpadmin;

--
-- Name: set1_set1_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.set1_set1_id_seq OWNED BY public.set1.set1_id;


--
-- Name: shoot; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.shoot (
    f integer,
    snummer integer
);


ALTER TABLE public.shoot OWNER TO rpadmin;

--
-- Name: should_close_group_tutor; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.should_close_group_tutor AS
 SELECT ((pt.prj_tutor_open = true) AND (pt.prj_tutor_open <> algo.any_group_open)) AS should_close,
    pt.prj_tutor_open,
    algo.any_group_open,
    pt.prjtg_id
   FROM (public.prj_tutor pt
     JOIN ( SELECT bool_or(prj_grp.prj_grp_open) AS any_group_open,
            prj_grp.prjtg_id
           FROM public.prj_grp
          GROUP BY prj_grp.prjtg_id) algo USING (prjtg_id));


ALTER TABLE public.should_close_group_tutor OWNER TO rpadmin;

--
-- Name: should_close_prj_milestone; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.should_close_prj_milestone AS
 SELECT ((pm.prj_milestone_open = true) AND (pm.prj_milestone_open <> alpt.any_tutor_open)) AS should_close,
    pm.prj_milestone_open,
    alpt.any_tutor_open,
    pm.prjm_id
   FROM (public.prj_milestone pm
     JOIN ( SELECT bool_or(prj_tutor.prj_tutor_open) AS any_tutor_open,
            prj_tutor.prjm_id
           FROM public.prj_tutor
          GROUP BY prj_tutor.prjm_id) alpt USING (prjm_id));


ALTER TABLE public.should_close_prj_milestone OWNER TO rpadmin;

--
-- Name: simple_group_member; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.simple_group_member AS
 SELECT prj_grp.prjtg_id,
    prj_grp.snummer
   FROM public.prj_grp
UNION
 SELECT prj_tutor.prjtg_id,
    prj_tutor.tutor_id AS snummer
   FROM public.prj_tutor;


ALTER TABLE public.simple_group_member OWNER TO rpadmin;

--
-- Name: slb_projects; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.slb_projects AS
 SELECT project.prj_id
   FROM public.project
  WHERE (project.afko = 'SLB'::bpchar);


ALTER TABLE public.slb_projects OWNER TO rpadmin;

--
-- Name: sofa2019grps; Type: TABLE; Schema: public; Owner: hom
--

CREATE TABLE public.sofa2019grps (
    snummer integer,
    prj_grp_open boolean,
    written boolean,
    prjtg_id integer
);


ALTER TABLE public.sofa2019grps OWNER TO hom;

--
-- Name: sofa2019m1; Type: TABLE; Schema: public; Owner: hom
--

CREATE TABLE public.sofa2019m1 (
    grp_num smallint,
    prjm_id integer,
    prjtg_id integer,
    prj_tutor_open boolean,
    assessment_complete boolean,
    tutor_grade numeric(3,1),
    tutor_id integer,
    grp_name character varying(15)
);


ALTER TABLE public.sofa2019m1 OWNER TO hom;

--
-- Name: sp; Type: TABLE; Schema: public; Owner: hom
--

CREATE TABLE public.sp (
    studieplan integer,
    studieplan_omschrijving character(64),
    studieplan_short character(10),
    studieprogr bigint,
    variant_omschrijving text
);


ALTER TABLE public.sp OWNER TO hom;

--
-- Name: statsvn_names; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.statsvn_names AS
 SELECT ((((('user.'::text || prj_grp.snummer) || '.realName='::text) || student.achternaam) || ','::text) || student.roepnaam) AS member,
    prj_tutor.prjm_id,
    prj_tutor.grp_num
   FROM (((public.prj_grp
     JOIN public.student USING (snummer))
     JOIN public.prj_tutor USING (prjtg_id))
     JOIN public.prj_milestone USING (prjm_id));


ALTER TABLE public.statsvn_names OWNER TO rpadmin;

--
-- Name: stdresult; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.stdresult AS
 SELECT a.snummer,
    pm.prj_id,
    pt.grp_num,
    a.criterium,
    pm.milestone,
    a.grade
   FROM ((( SELECT assessment.contestant AS snummer,
            assessment.prjtg_id,
            assessment.criterium,
            avg(assessment.grade) AS grade
           FROM public.assessment
          GROUP BY assessment.contestant, assessment.prjtg_id, assessment.criterium) a
     JOIN public.prj_tutor pt ON ((a.prjtg_id = pt.prjtg_id)))
     JOIN public.prj_milestone pm ON ((pm.prjm_id = pt.prjtg_id)));


ALTER TABLE public.stdresult OWNER TO rpadmin;

--
-- Name: VIEW stdresult; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.stdresult IS 'used by ~/_include/test/peerutils,tutor/groupresult.php';


--
-- Name: stdresult2; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.stdresult2 AS
 SELECT p.prjtg_id,
    a.contestant AS snummer,
    a.criterium,
    avg(a.grade) AS grade
   FROM (public.prj_grp p
     JOIN public.assessment a ON (((p.snummer = a.contestant) AND (p.prjtg_id = a.prjtg_id))))
  GROUP BY p.prjtg_id, a.criterium, a.contestant;


ALTER TABLE public.stdresult2 OWNER TO rpadmin;

--
-- Name: stdresult_overall; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.stdresult_overall AS
 SELECT assessment.prjtg_id,
    assessment.contestant AS snummer,
    avg(assessment.grade) AS grade
   FROM public.assessment
  GROUP BY assessment.prjtg_id, assessment.contestant;


ALTER TABLE public.stdresult_overall OWNER TO rpadmin;

--
-- Name: VIEW stdresult_overall; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.stdresult_overall IS 'used by ~/_include/test/peerutils,tutor/groupresult.php';


--
-- Name: stickies; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.stickies AS
 SELECT absence_reason.snummer,
    regexp_matches(absence_reason.reason, '\d{3}'::text) AS stick
   FROM public.absence_reason
  WHERE (absence_reason.act_id = ANY (ARRAY[1658, 1672, 1874]));


ALTER TABLE public.stickies OWNER TO rpadmin;

--
-- Name: sticks2019; Type: TABLE; Schema: public; Owner: hom
--

CREATE TABLE public.sticks2019 (
    sticks2019_id integer NOT NULL,
    snummer integer,
    stick integer
);


ALTER TABLE public.sticks2019 OWNER TO hom;

--
-- Name: sticks2019_sticks2019_id_seq; Type: SEQUENCE; Schema: public; Owner: hom
--

CREATE SEQUENCE public.sticks2019_sticks2019_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.sticks2019_sticks2019_id_seq OWNER TO hom;

--
-- Name: sticks2019_sticks2019_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: hom
--

ALTER SEQUENCE public.sticks2019_sticks2019_id_seq OWNED BY public.sticks2019.sticks2019_id;


--
-- Name: sticks_2018; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.sticks_2018 AS
 SELECT absence_reason.snummer,
    (absence_reason.reason)::integer AS reason
   FROM public.absence_reason
  WHERE (absence_reason.act_id = 1874);


ALTER TABLE public.sticks_2018 OWNER TO rpadmin;

--
-- Name: sticks_20190916; Type: TABLE; Schema: public; Owner: hom
--

CREATE TABLE public.sticks_20190916 (
    sticks_20190916_id integer NOT NULL,
    snummer integer,
    stick integer
);


ALTER TABLE public.sticks_20190916 OWNER TO hom;

--
-- Name: sticks_20190916_sticks_20190916_id_seq; Type: SEQUENCE; Schema: public; Owner: hom
--

CREATE SEQUENCE public.sticks_20190916_sticks_20190916_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.sticks_20190916_sticks_20190916_id_seq OWNER TO hom;

--
-- Name: sticks_20190916_sticks_20190916_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: hom
--

ALTER SEQUENCE public.sticks_20190916_sticks_20190916_id_seq OWNED BY public.sticks_20190916.sticks_20190916_id;


--
-- Name: stp_inf; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.stp_inf (
    snummer integer,
    studieplan integer
);


ALTER TABLE public.stp_inf OWNER TO rpadmin;

--
-- Name: student_class2; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.student_class2 (
    sort1 integer,
    sort2 integer,
    comment text,
    faculty_id smallint,
    class_id integer,
    sclass character(10),
    class_cluster integer,
    owner integer
);


ALTER TABLE public.student_class2 OWNER TO rpadmin;

--
-- Name: student_class_name; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.student_class_name AS
 SELECT student.snummer,
    classes.sclass
   FROM (public.student
     JOIN public.student_class classes USING (class_id));


ALTER TABLE public.student_class_name OWNER TO rpadmin;

--
-- Name: student_class_size; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.student_class_size AS
 SELECT student.class_id,
    student.snummer,
    cs.student_count
   FROM ((public.student
     JOIN public.student_class classes USING (class_id))
     JOIN public.class_size cs USING (class_id));


ALTER TABLE public.student_class_size OWNER TO rpadmin;

--
-- Name: student_class_v; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.student_class_v AS
 SELECT student.snummer,
    student.class_id
   FROM public.student;


ALTER TABLE public.student_class_v OWNER TO rpadmin;

--
-- Name: student_email; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.student_email AS
 SELECT s.snummer,
    s.achternaam,
    s.tussenvoegsel,
    s.voorletters,
    s.roepnaam,
    s.straat,
    s.huisnr,
    s.pcode,
    s.plaats,
    s.email1,
    s.nationaliteit,
    s.cohort,
    s.gebdat,
    s.sex,
    s.lang,
    s.pcn,
    s.opl,
    s.phone_home,
    s.phone_gsm,
    s.phone_postaddress,
    s.faculty_id,
    s.hoofdgrp,
    s.active,
    s.slb,
    s.land,
    s.studieplan,
    s.geboorteplaats,
    s.geboorteland,
    s.voornamen,
    s.class_id,
    am.email2,
    COALESCE((rp.snummer || '.jpg'::text), '0.jpg'::text) AS image
   FROM ((public.student s
     LEFT JOIN public.alt_email am USING (snummer))
     LEFT JOIN public.registered_photos rp USING (snummer));


ALTER TABLE public.student_email OWNER TO rpadmin;

--
-- Name: student_latin1; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.student_latin1 AS
 SELECT student.snummer,
    convert_to(student.achternaam, 'iso_8859_1'::name) AS achternaam,
    convert_to(student.tussenvoegsel, 'iso_8859_1'::name) AS tussenvoegsel,
    student.voorletters,
    convert_to(student.roepnaam, 'iso_8859_1'::name) AS roepnaam,
    convert_to(student.straat, 'iso_8859_1'::name) AS straat,
    student.huisnr,
    student.pcode,
    convert_to(student.plaats, 'iso_8859_1'::name) AS plaats,
    student.email1,
    student.nationaliteit,
    student.cohort,
    student.gebdat,
    student.sex,
    student.lang,
    student.pcn,
    student.opl,
    student.phone_home,
    student.phone_gsm,
    student.phone_postaddress,
    student.faculty_id,
    student.hoofdgrp,
    student.active,
    student.slb,
    student.land,
    student.studieplan
   FROM public.student;


ALTER TABLE public.student_latin1 OWNER TO rpadmin;

--
-- Name: VIEW student_latin1; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.student_latin1 IS 'win/excel do not grasp utf-8 encoding header';


--
-- Name: student_name_email; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.student_name_email AS
 SELECT student.snummer,
    convert_to(rtrim(student.roepnaam), 'latin1'::name) AS roepnaam,
    student.tussenvoegsel,
    convert_to(rtrim(student.achternaam), 'latin1'::name) AS achternaam
   FROM public.student;


ALTER TABLE public.student_name_email OWNER TO rpadmin;

--
-- Name: student_plus; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.student_plus AS
 SELECT student.snummer,
    student.achternaam,
    student.tussenvoegsel,
    student.voorletters,
    student.roepnaam,
    student.straat,
    student.huisnr,
    student.pcode,
    student.plaats,
    student.email1,
    student.nationaliteit,
    student.hoofdgrp,
    student.cohort,
    student.gebdat,
    student.sex,
    student.phone_home,
    student.phone_gsm,
    student.lang,
    alt_email.email2
   FROM (public.student
     LEFT JOIN public.alt_email USING (snummer));


ALTER TABLE public.student_plus OWNER TO rpadmin;

--
-- Name: student_project_attributes; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.student_project_attributes AS
 SELECT DISTINCT s.snummer,
    pg.snummer AS has_project,
    p.afko,
    p.year,
    p.description,
    pm.milestone,
    pm.milestone_name,
    pt.grp_num,
    pt.prjtg_id,
    p.valid_until,
    ag.snummer AS has_assessment,
    hd.prjm_id AS has_doc
   FROM ((((((public.student s
     JOIN public.prj_grp pg USING (snummer))
     JOIN public.prj_tutor pt USING (prjtg_id))
     JOIN public.prj_milestone pm USING (prjm_id))
     JOIN public.project p USING (prj_id))
     LEFT JOIN public.assessment_groups ag USING (snummer, prjtg_id))
     LEFT JOIN public.project_deliverables hd USING (prjm_id))
  ORDER BY s.snummer, p.year DESC, p.afko;


ALTER TABLE public.student_project_attributes OWNER TO rpadmin;

--
-- Name: student_role; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.student_role (
    snummer integer NOT NULL,
    rolenum smallint NOT NULL,
    capabilities integer DEFAULT 0,
    prjm_id integer NOT NULL
);


ALTER TABLE public.student_role OWNER TO rpadmin;

--
-- Name: TABLE student_role; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.student_role IS 'role of a student in a project. Multiple roles per project/student are allowed.';


--
-- Name: student_short; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.student_short AS
 SELECT student.snummer,
    student.achternaam,
    student.roepnaam,
    student.pcn
   FROM public.student;


ALTER TABLE public.student_short OWNER TO rpadmin;

--
-- Name: student_upload_count; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.student_upload_count AS
 SELECT pm.prj_id,
    pm.milestone,
    u.snummer,
    count(u.upload_id) AS doc_count
   FROM ((public.uploads u
     JOIN public.prj_tutor pt ON ((u.prjtg_id = pt.prjtg_id)))
     JOIN public.prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
  GROUP BY pm.prj_id, pm.milestone, u.snummer;


ALTER TABLE public.student_upload_count OWNER TO rpadmin;

--
-- Name: VIEW student_upload_count; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.student_upload_count IS 'used by folderview';


--
-- Name: studie_prog; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.studie_prog (
    studieprogr integer NOT NULL,
    stud_prog_omsch character varying(50)
);


ALTER TABLE public.studie_prog OWNER TO rpadmin;

--
-- Name: TABLE studie_prog; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.studie_prog IS 'Description of study program';


--
-- Name: sv05_as_student_email_v; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW public.sv05_as_student_email_v AS
 SELECT DISTINCT ON (a.studentnummer) a.studentnummer AS snummer,
    a.achternaam,
    a.voorvoegsels AS tussenvoegsel,
    a.voorletters,
    a.roepnaam,
    a.straat,
    a.huisnr,
    a.postcode AS pcode,
    a.woonplaats AS plaats,
    a.e_mail_instelling AS email1,
    nm.nationaliteit,
    COALESCE(date_part('year'::text, a.datum_aankomst_opleiding), (a.studiejaar)::double precision, date_part('year'::text, (now())::date)) AS cohort,
    a.geboortedatum AS gebdat,
        CASE
            WHEN (a.geslacht = 'Man'::text) THEN 'M'::text
            ELSE 'F'::text
        END AS sex,
        CASE
            WHEN (a.voorkeurstaal = 'Engels'::text) THEN 'EN'::text
            WHEN (a.voorkeurstaal = 'Duits'::text) THEN 'DE'::text
            ELSE 'NL'::text
        END AS lang,
    a.pcn_nummer AS pcn,
    sp.studieprogr AS opl,
    ((('+'::text || a.land_nummer_vast) || ' '::text) || a.vast_nummer) AS phone_home,
    ((('+'::text || a.land_nummer_mobiel) || ' '::text) || a.mobiel_nummer) AS phone_gsm,
    NULL::text AS phone_postaddress,
    a.instituutcode AS faculty_id,
    a.course_grp AS hoofdgrp,
    true AS active,
    NULL::integer AS slb,
    iso.a3 AS land,
    a.studielinkvariantcode AS studieplan,
    a.geboorteplaats,
    iso2.a3 AS geboorteland,
    a.voornamen,
    0 AS class_id,
    a."e_mail_privé" AS email2
   FROM ((((importer.sv05_aanmelders a
     JOIN public.studieplan sp ON ((sp.studieplan = a.studielinkvariantcode)))
     LEFT JOIN public.nat_mapper nm ON ((a.leidende_nationaliteit = (nm.nation_omschr)::text)))
     LEFT JOIN public.iso3166 iso ON ((a.land = (iso.land_nl)::text)))
     LEFT JOIN public.iso3166 iso2 ON ((a.geboorteland = (iso2.land_nl)::text)))
  ORDER BY a.studentnummer, a.course_grp, sp.studieprogr, a.studielinkvariantcode;


ALTER TABLE public.sv05_as_student_email_v OWNER TO hom;

--
-- Name: sv09_import_summary; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.sv09_import_summary (
    x integer,
    comment character varying(40),
    "row" integer
);


ALTER TABLE public.sv09_import_summary OWNER TO rpadmin;

--
-- Name: TABLE sv09_import_summary; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.sv09_import_summary IS 'summarizing inport from progress sv09 view to peerweb';


--
-- Name: svn_auditor; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.svn_auditor AS
 SELECT DISTINCT project_scribe.scribe AS auditor,
    project_scribe.prj_id,
    prj_milestone.milestone,
    prj_milestone.prjm_id
   FROM (public.project_scribe
     JOIN public.prj_milestone USING (prj_id))
  WHERE (NOT (project_scribe.scribe IN ( SELECT tutor.userid
           FROM public.tutor)));


ALTER TABLE public.svn_auditor OWNER TO rpadmin;

--
-- Name: svn_groep; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.svn_groep AS
 SELECT prj_grp.prjtg_id AS "group",
    (prj_grp.snummer)::text AS username
   FROM public.prj_grp
  WHERE (prj_grp.snummer = 879417)
UNION
 SELECT prj_tutor.prjtg_id AS "group",
    (prj_tutor.tutor_id)::text AS username
   FROM public.prj_tutor;


ALTER TABLE public.svn_groep OWNER TO rpadmin;

--
-- Name: svn_group; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.svn_group AS
 SELECT pt.grp_name AS groupname,
    pg.snummer AS username,
    pm.prj_id,
    pm.milestone,
    s.achternaam,
    s.roepnaam,
    pt.prjm_id,
    pt.prjtg_id
   FROM ((((public.prj_grp pg
     JOIN public.student s USING (snummer))
     JOIN public.prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN public.prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
     LEFT JOIN public.grp_alias ga ON ((pt.prjtg_id = ga.prjtg_id)));


ALTER TABLE public.svn_group OWNER TO rpadmin;

--
-- Name: svn_grp; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.svn_grp AS
 SELECT prj_grp.prjtg_id,
    (prj_grp.snummer)::text AS username
   FROM public.prj_grp
  WHERE (prj_grp.snummer = 879417)
UNION
 SELECT prj_tutor.prjtg_id,
    (prj_tutor.tutor_id)::text AS username
   FROM public.prj_tutor;


ALTER TABLE public.svn_grp OWNER TO rpadmin;

--
-- Name: svn_guests; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.svn_guests (
    username character varying(128) NOT NULL,
    password character varying(64)
);


ALTER TABLE public.svn_guests OWNER TO rpadmin;

--
-- Name: TABLE svn_guests; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.svn_guests IS 'allow externals access to svn.';


--
-- Name: svn_progress; Type: VIEW; Schema: public; Owner: hom
--

CREATE VIEW public.svn_progress AS
 SELECT prj_grp.snummer,
    student.achternaam,
    student.roepnaam,
    student.cohort,
    prj_tutor.grp_name,
    r.milestone,
    r.repospath,
    r.description,
    r.isroot,
    r.id,
    r.url_tail,
    r.owner,
    r.grp_num,
    r.prjm_id,
    r.prjtg_id,
    r.youngest,
    r.last_commit
   FROM (((public.prj_grp
     JOIN public.prj_tutor USING (prjtg_id))
     JOIN public.repositories r USING (prjtg_id))
     JOIN public.student USING (snummer))
  ORDER BY r.grp_num;


ALTER TABLE public.svn_progress OWNER TO hom;

--
-- Name: svn_tutor; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.svn_tutor AS
 SELECT DISTINCT t.userid AS username,
    t.tutor,
    pm.prj_id,
    pm.milestone,
    pm.prjm_id
   FROM ((public.prj_tutor pt
     JOIN public.tutor t ON ((pt.tutor_id = t.userid)))
     JOIN public.prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
  ORDER BY t.userid, t.tutor, pm.prj_id, pm.milestone;


ALTER TABLE public.svn_tutor OWNER TO rpadmin;

--
-- Name: svn_tutor_snummer; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.svn_tutor_snummer AS
 SELECT pt.tutor_id AS snummer,
    pm.prj_id
   FROM (public.prj_tutor pt
     JOIN public.prj_milestone pm USING (prjm_id))
UNION
 SELECT project_scribe.scribe AS snummer,
    project_scribe.prj_id
   FROM public.project_scribe;


ALTER TABLE public.svn_tutor_snummer OWNER TO rpadmin;

--
-- Name: VIEW svn_tutor_snummer; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.svn_tutor_snummer IS 'get snummer from repo authz file in svn admin page';


--
-- Name: svn_users; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.svn_users AS
 SELECT (''::text || (password.userid)::text) AS username,
    password.password
   FROM public.passwd password
  WHERE (password.disabled = false);


ALTER TABLE public.svn_users OWNER TO rpadmin;

--
-- Name: task_timer; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.task_timer (
    snummer integer NOT NULL,
    prj_id smallint NOT NULL,
    milestone smallint NOT NULL,
    task_id smallint DEFAULT 0 NOT NULL,
    start_time timestamp without time zone DEFAULT (date_trunc('seconds'::text, now()))::timestamp without time zone NOT NULL,
    stop_time timestamp without time zone DEFAULT (date_trunc('seconds'::text, now()))::timestamp without time zone NOT NULL,
    id bigint NOT NULL,
    from_ip inet,
    time_tag timestamp without time zone DEFAULT (date_trunc('seconds'::text, now()))::timestamp without time zone NOT NULL,
    prjm_id integer NOT NULL,
    CONSTRAINT time_spans CHECK ((start_time <= stop_time))
);


ALTER TABLE public.task_timer OWNER TO rpadmin;

--
-- Name: TABLE task_timer; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.task_timer IS 'time the various tasks';


--
-- Name: task_timer_anywhere; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.task_timer_anywhere AS
 SELECT t.snummer AS userid,
    t.prj_id,
    t.milestone,
    t.task_id,
    t.time_tag,
    w.start_time,
    w.stop_time,
    t.from_ip,
    w.hourcode,
    w.day,
    weekdays.dayname,
    weekdays.day_lang
   FROM public.task_timer t,
    (public.timetableweek w
     JOIN public.weekdays USING (day))
  WHERE ((to_char(t.time_tag, 'HH24:MI:SS'::text) >= (w.start_time)::text) AND (to_char(t.time_tag, 'HH24:MI:SS'::text) <= (w.stop_time)::text) AND (date_part('dow'::text, t.time_tag) = (w.day)::double precision));


ALTER TABLE public.task_timer_anywhere OWNER TO rpadmin;

--
-- Name: task_timer_at_fontys; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.task_timer_at_fontys AS
 SELECT task_timer_anywhere.userid,
    task_timer_anywhere.prj_id,
    task_timer_anywhere.milestone,
    task_timer_anywhere.task_id,
    task_timer_anywhere.time_tag,
    task_timer_anywhere.start_time,
    task_timer_anywhere.stop_time,
    task_timer_anywhere.from_ip,
    task_timer_anywhere.hourcode,
    task_timer_anywhere.day,
    task_timer_anywhere.dayname,
    task_timer_anywhere.day_lang
   FROM public.task_timer_anywhere
  WHERE (task_timer_anywhere.from_ip <<= '145.85.0.0/16'::inet);


ALTER TABLE public.task_timer_at_fontys OWNER TO rpadmin;

--
-- Name: task_timer_group_sum; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.task_timer_group_sum AS
 SELECT pt.grp_num,
    pm.prj_id,
    pm.milestone,
    sum((tt.stop_time - tt.start_time)) AS project_time,
    pt.prjtg_id
   FROM (((public.task_timer tt
     JOIN public.prj_milestone pm ON (((tt.prj_id = pm.prj_id) AND (tt.milestone = pm.milestone))))
     JOIN public.prj_tutor pt ON ((pm.prjm_id = pt.prjm_id)))
     JOIN public.prj_grp pg ON (((pg.prjtg_id = pt.prjtg_id) AND (tt.snummer = pg.snummer))))
  GROUP BY pt.prjtg_id, pt.grp_num, pm.prj_id, pm.milestone;


ALTER TABLE public.task_timer_group_sum OWNER TO rpadmin;

--
-- Name: task_timer_grp_total; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.task_timer_grp_total AS
 SELECT pm.prj_id,
    pm.milestone,
    pt.grp_num,
    sum((tt.stop_time - tt.start_time)) AS project_total
   FROM (((public.task_timer tt
     JOIN public.prj_milestone pm ON (((tt.prj_id = pm.prj_id) AND (tt.milestone = pm.milestone))))
     JOIN public.prj_tutor pt ON ((pm.prjm_id = pt.prjm_id)))
     JOIN public.prj_grp pg ON (((pg.prjtg_id = pt.prjtg_id) AND (tt.snummer = pg.snummer))))
  GROUP BY pm.prj_id, pm.milestone, pt.grp_num;


ALTER TABLE public.task_timer_grp_total OWNER TO rpadmin;

--
-- Name: task_timer_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.task_timer_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.task_timer_id_seq OWNER TO rpadmin;

--
-- Name: task_timer_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.task_timer_id_seq OWNED BY public.task_timer.id;


--
-- Name: task_timer_project_sum; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.task_timer_project_sum AS
 SELECT task_timer.snummer,
    task_timer.prj_id,
    task_timer.milestone,
    sum((task_timer.stop_time - task_timer.start_time)) AS project_time
   FROM public.task_timer
  GROUP BY task_timer.snummer, task_timer.prj_id, task_timer.milestone;


ALTER TABLE public.task_timer_project_sum OWNER TO rpadmin;

--
-- Name: task_timer_sum; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.task_timer_sum AS
 SELECT task_timer.snummer,
    task_timer.prj_id,
    task_timer.milestone,
    task_timer.task_id,
    sum((task_timer.stop_time - task_timer.start_time)) AS task_time
   FROM public.task_timer
  GROUP BY task_timer.snummer, task_timer.prj_id, task_timer.milestone, task_timer.task_id;


ALTER TABLE public.task_timer_sum OWNER TO rpadmin;

--
-- Name: task_timer_week; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.task_timer_week AS
 SELECT DISTINCT task_timer.snummer,
    date_part('year'::text, task_timer.start_time) AS year,
    date_part('week'::text, task_timer.start_time) AS week
   FROM public.task_timer
  ORDER BY task_timer.snummer, (date_part('year'::text, task_timer.start_time)), (date_part('week'::text, task_timer.start_time));


ALTER TABLE public.task_timer_week OWNER TO rpadmin;

--
-- Name: task_timer_year_month; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.task_timer_year_month AS
 SELECT DISTINCT date_part('year'::text, task_timer.start_time) AS year,
    date_part('month'::text, task_timer.start_time) AS month,
    to_char(task_timer.start_time, 'IYYY Mon'::text) AS year_month,
    date_trunc('month'::text, task_timer.start_time) AS first_second,
    (date_trunc('month'::text, (task_timer.start_time + '31 days'::interval)) - '00:00:01'::interval) AS last_second
   FROM public.task_timer
  ORDER BY (date_part('year'::text, task_timer.start_time)), (date_part('month'::text, task_timer.start_time)), (to_char(task_timer.start_time, 'IYYY Mon'::text)), (date_trunc('month'::text, task_timer.start_time)), (date_trunc('month'::text, (task_timer.start_time + '31 days'::interval)) - '00:00:01'::interval);


ALTER TABLE public.task_timer_year_month OWNER TO rpadmin;

--
-- Name: teller; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.teller
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.teller OWNER TO rpadmin;

--
-- Name: this_week_schedule; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.this_week_schedule AS
 SELECT s.day,
    s.hourcode,
    s.start_time,
    s.stop_time,
    ((date_trunc('week'::text, ((now())::date)::timestamp with time zone))::date + (s.day - 1)) AS datum,
    (((date_trunc('week'::text, ((now())::date)::timestamp with time zone))::date + (s.day - 1)) + s.start_time) AS start_ts,
    (((now())::date + (s.day - 1)) + s.stop_time) AS stop_ts
   FROM public.schedule_hours s;


ALTER TABLE public.this_week_schedule OWNER TO rpadmin;

--
-- Name: tiny_portrait; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.tiny_portrait AS
 SELECT st.snummer,
    (('<img src=''fotos/'::text || COALESCE((rf.snummer)::text, 'anonymous'::text)) || '.jpg'' border=''0'' width=''18'' height=''27''/>'::text) AS portrait
   FROM (public.student st
     LEFT JOIN public.registered_photos rf USING (snummer));


ALTER TABLE public.tiny_portrait OWNER TO rpadmin;

--
-- Name: trac_init_data; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.trac_init_data AS
 SELECT apt.prj_id,
    apt.year,
    apt.milestone,
    apt.prjm_id,
    btrim((COALESCE(apt.alias, (('g'::text || apt.grp_num))::bpchar))::text) AS alias,
    apt.grp_num,
    (((((apt.afko)::text || '_'::text) || apt.year) || '_'::text) || (COALESCE(apt.alias, (('g'::text || apt.grp_num))::bpchar))::text) AS project_name,
    ((((((('trac_'::text || apt.year) || '_'::text) || (apt.afko)::text) || '_m'::text) || apt.milestone) || '_'::text) || replace((COALESCE(apt.alias, (('g'::text || apt.grp_num))::bpchar))::text, '-'::text, '_'::text)) AS dbname,
    r.repospath,
    regexp_replace((r.repospath)::text, '^/home/svn/'::text, '/home/trac/'::text) AS trac_path
   FROM (public.all_prj_tutor apt
     JOIN public.repositories r USING (prjtg_id));


ALTER TABLE public.trac_init_data OWNER TO rpadmin;

--
-- Name: trac_user_pass; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.trac_user_pass AS
 SELECT (passwd.userid)::text AS username,
    passwd.password
   FROM public.passwd;


ALTER TABLE public.trac_user_pass OWNER TO rpadmin;

--
-- Name: tracusers; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.tracusers (
    username text NOT NULL,
    password text
);


ALTER TABLE public.tracusers OWNER TO rpadmin;

--
-- Name: TABLE tracusers; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.tracusers IS 'access to trac. Must verify if still used 20130712.';


--
-- Name: transaction; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.transaction (
    ts timestamp without time zone DEFAULT now(),
    trans_id bigint NOT NULL,
    operator integer NOT NULL,
    from_ip inet NOT NULL
);


ALTER TABLE public.transaction OWNER TO rpadmin;

--
-- Name: TABLE transaction; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.transaction IS 'simple transaction log, to save on column history data.';


--
-- Name: transaction_operator; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.transaction_operator AS
 SELECT t.ts,
    t.trans_id,
    t.operator,
    t.from_ip,
    ((s.roepnaam || COALESCE(((' '::text || s.tussenvoegsel) || ' '::text), ' '::text)) || s.achternaam) AS op_name
   FROM (public.transaction t
     JOIN public.student s ON ((t.operator = s.snummer)));


ALTER TABLE public.transaction_operator OWNER TO rpadmin;

--
-- Name: transaction_trans_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.transaction_trans_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.transaction_trans_id_seq OWNER TO rpadmin;

--
-- Name: transaction_trans_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.transaction_trans_id_seq OWNED BY public.transaction.trans_id;


--
-- Name: tutor_class_cluster; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.tutor_class_cluster (
    userid integer NOT NULL,
    class_cluster integer NOT NULL,
    cluster_order smallint DEFAULT 1
);


ALTER TABLE public.tutor_class_cluster OWNER TO rpadmin;

--
-- Name: tutor_data; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.tutor_data AS
 SELECT student.snummer,
    student.*::public.student AS student,
    student.snummer AS tutor_id,
    student.achternaam,
    student.roepnaam,
    student.tussenvoegsel,
    tutor.tutor,
    tutor.faculty_id,
    student.hoofdgrp,
    student.email1 AS tutor_email,
    faculty.faculty_short AS faculty,
    fontys_course.course_short AS opl
   FROM (((public.tutor
     JOIN public.student ON ((tutor.userid = student.snummer)))
     JOIN public.faculty ON ((tutor.faculty_id = faculty.faculty_id)))
     JOIN public.fontys_course ON ((student.opl = fontys_course.course)));


ALTER TABLE public.tutor_data OWNER TO rpadmin;

--
-- Name: VIEW tutor_data; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.tutor_data IS 'data from tutor selectors in e.g. slb';


--
-- Name: tutor_join_student; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.tutor_join_student AS
 SELECT t.tutor,
    t.userid AS snummer,
    t.userid,
    s.achternaam,
    s.roepnaam,
    s.tussenvoegsel,
    t.faculty_id,
    t.team,
    t.office AS function,
    t.building,
    t.city,
    t.room,
    t.office_phone,
    t.schedule_id,
    t.display_name,
    s.opl
   FROM (public.tutor t
     JOIN public.student s ON ((t.userid = s.snummer)));


ALTER TABLE public.tutor_join_student OWNER TO rpadmin;

--
-- Name: VIEW tutor_join_student; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.tutor_join_student IS 'Tutor view used to repesent tutor';


--
-- Name: tutor_snummer; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.tutor_snummer AS
 SELECT tutor.userid AS snummer,
    tutor.tutor AS tutor_code
   FROM public.tutor;


ALTER TABLE public.tutor_snummer OWNER TO rpadmin;

--
-- Name: tutor_upload_count; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.tutor_upload_count AS
 SELECT pm.prj_id,
    pm.milestone,
    t.tutor,
    pt.tutor_id,
    count(u.upload_id) AS doc_count
   FROM ((((public.uploads u
     JOIN public.prj_grp pg ON (((pg.prjtg_id = u.prjtg_id) AND (pg.snummer = u.snummer))))
     JOIN public.prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN public.tutor t ON ((pt.tutor_id = t.userid)))
     JOIN public.prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
  GROUP BY pm.prj_id, pm.milestone, t.tutor, pt.tutor_id;


ALTER TABLE public.tutor_upload_count OWNER TO rpadmin;

SET default_with_oids = true;

--
-- Name: uilang; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.uilang (
    lang_code character(2) NOT NULL,
    language character varying(30)
);


ALTER TABLE public.uilang OWNER TO rpadmin;

--
-- Name: TABLE uilang; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.uilang IS 'Language used in UI. Still valid? 20130712.';


SET default_with_oids = false;

--
-- Name: uncollected_sticks; Type: TABLE; Schema: public; Owner: hom
--

CREATE TABLE public.uncollected_sticks (
    snummer integer,
    achternaam text,
    stick integer
);


ALTER TABLE public.uncollected_sticks OWNER TO hom;

--
-- Name: unknown_student; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.unknown_student AS
 SELECT import_naw.snummer,
    import_naw.achternaam,
    import_naw.tussenvoegsel,
    import_naw.voorletters,
    import_naw.roepnaam,
    import_naw.straat,
    import_naw.huisnr,
    import_naw.pcode,
    import_naw.plaats,
    import_naw.email1,
    import_naw.nationaliteit,
    import_naw.cohort,
    import_naw.gebdat,
    import_naw.sex,
    import_naw.lang,
    import_naw.pcn,
    import_naw.phone_home,
    import_naw.phone_gsm,
    import_naw.phone_postaddress,
    import_naw.faculty_id,
    import_naw.land,
    import_naw.geboorteplaats,
    import_naw.geboorteland,
    import_naw.voornaam
   FROM public.import_naw
  WHERE (NOT (import_naw.snummer IN ( SELECT student.snummer
           FROM public.student)));


ALTER TABLE public.unknown_student OWNER TO rpadmin;

--
-- Name: upload_archive_names; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.upload_archive_names AS
 SELECT btrim((apt.afko)::text) AS afko,
    apt.year,
    apt.milestone,
    apt.prjm_id,
    apt.tutor,
    apt.grp_num,
    up.rel_file_path,
    regexp_replace((udt.description)::text, '\s+'::text, '_'::text, 'g'::text) AS doc_type_desc,
    up.snummer AS author,
    up.doctype,
    ((up.snummer || '_'::text) || regexp_replace(student.achternaam, '\s+'::text, '_'::text, 'g'::text)) AS author_name,
    regexp_replace(((((((((((btrim((apt.afko)::text) || '_'::text) || apt.year) || 'M'::text) || apt.milestone) || '/'::text) || (apt.tutor)::text) || '_G'::text) || apt.grp_num) || '/'::text) || (udt.description)::text), '([({}]|\s)+'::text, '_'::text, 'g'::text) AS archfilename
   FROM (((public.uploads up
     JOIN ( SELECT prj_grp.prjtg_id,
            t.tutor,
            pt.prjm_id,
            prj_milestone.prj_id,
            prj_milestone.milestone,
            pt.grp_num,
            project.afko,
            project.year
           FROM ((((public.prj_grp
             JOIN public.prj_tutor pt USING (prjtg_id))
             JOIN public.tutor t ON ((pt.tutor_id = t.userid)))
             JOIN public.prj_milestone USING (prjm_id))
             JOIN public.project USING (prj_id))) apt USING (prjtg_id))
     JOIN public.uploaddocumenttypes udt USING (prj_id, doctype))
     JOIN public.student USING (snummer));


ALTER TABLE public.upload_archive_names OWNER TO rpadmin;

--
-- Name: VIEW upload_archive_names; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.upload_archive_names IS 'used to create zip archives from uploads';


--
-- Name: upload_count; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.upload_count AS
 SELECT uploads.snummer,
    count(uploads.snummer) AS document_count
   FROM public.uploads
  GROUP BY uploads.snummer;


ALTER TABLE public.upload_count OWNER TO rpadmin;

--
-- Name: VIEW upload_count; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.upload_count IS 'documents per user';


--
-- Name: upload_group_count; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.upload_group_count AS
 SELECT uploads.prjtg_id,
    count(uploads.upload_id) AS doc_count
   FROM public.uploads
  GROUP BY uploads.prjtg_id;


ALTER TABLE public.upload_group_count OWNER TO rpadmin;

--
-- Name: VIEW upload_group_count; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.upload_group_count IS 'count document per group disregarding type';


--
-- Name: upload_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.upload_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.upload_id_seq OWNER TO rpadmin;

--
-- Name: upload_mime_types; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.upload_mime_types AS
 SELECT DISTINCT (uploads.mime_type_long)::text AS mime_type
   FROM public.uploads;


ALTER TABLE public.upload_mime_types OWNER TO rpadmin;

--
-- Name: upload_rename; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.upload_rename AS
 SELECT uploads.upload_id,
    uploads.rel_file_path,
    regexp_replace(regexp_replace(uploads.rel_file_path, '\s+'::text, '_'::text, 'g'::text), '\.{2}'::text, '.'::text, 'g'::text) AS new_rel_file_path
   FROM public.uploads;


ALTER TABLE public.upload_rename OWNER TO rpadmin;

--
-- Name: uploads_tr; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.uploads_tr AS
 SELECT uploads.prjm_id,
    uploads.snummer,
    uploads.doctype,
    uploads.title,
    uploads.vers,
    uploads.uploadts,
    uploads.upload_id,
    uploads.mime_type,
    uploads.rights,
    uploads.rel_file_path
   FROM public.uploads;


ALTER TABLE public.uploads_tr OWNER TO rpadmin;

--
-- Name: used_criteria; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.used_criteria AS
 SELECT DISTINCT pm.prj_id,
    a.criterium AS used_criterium
   FROM ((public.assessment a
     JOIN public.prj_tutor pt ON ((a.prjtg_id = pt.prjtg_id)))
     JOIN public.prj_milestone pm ON ((pm.prjm_id = pt.prjtg_id)));


ALTER TABLE public.used_criteria OWNER TO rpadmin;

--
-- Name: VIEW used_criteria; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.used_criteria IS 'used in criteria3';


--
-- Name: validator_map_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.validator_map_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.validator_map_seq OWNER TO rpadmin;

--
-- Name: validator_map; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.validator_map (
    input_name character varying(120) NOT NULL,
    regex_name character varying(30) NOT NULL,
    starred character(1) DEFAULT 'Y'::bpchar NOT NULL,
    id integer DEFAULT nextval('public.validator_map_seq'::regclass) NOT NULL
);


ALTER TABLE public.validator_map OWNER TO rpadmin;

--
-- Name: TABLE validator_map; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.validator_map IS 'map regex to table x colums';


--
-- Name: validator_occurrences; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.validator_occurrences (
    page character varying(64),
    identifier character varying(30),
    data text,
    id integer NOT NULL
);


ALTER TABLE public.validator_occurrences OWNER TO rpadmin;

--
-- Name: TABLE validator_occurrences; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.validator_occurrences IS 'record missing validators for menu x columns.';


--
-- Name: validator_occurrences_id_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.validator_occurrences_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.validator_occurrences_id_seq OWNER TO rpadmin;

--
-- Name: validator_occurrences_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rpadmin
--

ALTER SEQUENCE public.validator_occurrences_id_seq OWNED BY public.validator_occurrences.id;


--
-- Name: validator_occurrences_seq; Type: SEQUENCE; Schema: public; Owner: rpadmin
--

CREATE SEQUENCE public.validator_occurrences_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.validator_occurrences_seq OWNER TO rpadmin;

--
-- Name: validator_regex; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.validator_regex (
    regex_name character varying(30) NOT NULL,
    regex text
);


ALTER TABLE public.validator_regex OWNER TO rpadmin;

--
-- Name: TABLE validator_regex; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON TABLE public.validator_regex IS 'validation per regex.';


--
-- Name: validator_regex_map; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.validator_regex_map AS
 SELECT validator_map.input_name,
    validator_regex.regex,
    validator_regex.regex_name,
    validator_map.starred
   FROM (public.validator_map
     JOIN public.validator_regex USING (regex_name));


ALTER TABLE public.validator_regex_map OWNER TO rpadmin;

--
-- Name: VIEW validator_regex_map; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.validator_regex_map IS ' regex_name join map for validation';


--
-- Name: validator_regex_slashed; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.validator_regex_slashed AS
 SELECT validator_regex.regex_name,
    replace(validator_regex.regex, '\'::text, '\\'::text) AS regex
   FROM public.validator_regex;


ALTER TABLE public.validator_regex_slashed OWNER TO rpadmin;

--
-- Name: VIEW validator_regex_slashed; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.validator_regex_slashed IS ' regex_name join map for validation; used in regex editor';


--
-- Name: variant; Type: TABLE; Schema: public; Owner: rpadmin
--

CREATE TABLE public.variant (
    studielinkvariantcode integer,
    variant_omschrijving text
);


ALTER TABLE public.variant OWNER TO rpadmin;

--
-- Name: viewabledocument; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.viewabledocument AS
 SELECT pga.snummer AS author,
    pgtv.snummer AS viewer,
    pta.grp_num AS author_grp,
    pgtv.grp_num AS viewer_grp,
    pta.prjtg_id,
    pgtv.prjtg_id AS viewer_prjtg_id,
    pma.prj_id,
    pma.milestone,
    up.upload_id AS doc_id,
    up.uploadts,
    up.title,
    ut.doctype,
    pd.due
   FROM ((((((public.uploads up
     JOIN public.prj_grp pga ON (((up.prjtg_id = pga.prjtg_id) AND (up.snummer = pga.snummer))))
     JOIN public.prj_tutor pta ON ((pta.prjtg_id = pga.prjtg_id)))
     JOIN public.prj_milestone pma ON ((pta.prjm_id = pma.prjm_id)))
     JOIN public.uploaddocumenttypes ut ON (((pma.prj_id = ut.prj_id) AND (ut.doctype = up.doctype))))
     JOIN ( SELECT ptv.prjtg_id,
            pgv.snummer,
            ptv.prjm_id,
            ptv.grp_num
           FROM (public.prj_grp pgv
             JOIN public.prj_tutor ptv ON ((pgv.prjtg_id = ptv.prjtg_id)))) pgtv ON ((pgtv.prjm_id = pta.prjm_id)))
     JOIN public.project_deliverables pd ON (((pd.prjm_id = pta.prjm_id) AND (up.doctype = pd.doctype))))
  WHERE ((pta.prjtg_id = pgtv.prjtg_id) OR ((pd.due)::timestamp with time zone < now()));


ALTER TABLE public.viewabledocument OWNER TO rpadmin;

--
-- Name: VIEW viewabledocument; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.viewabledocument IS 'group and project members that might view an upload document';


--
-- Name: web_access_by_group; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.web_access_by_group AS
 SELECT pg.snummer AS username,
    pt.prjm_id,
    (pt.prjtg_id)::text AS grp_name
   FROM (public.prj_grp pg
     JOIN public.prj_tutor pt USING (prjtg_id))
UNION
 SELECT DISTINCT pt.tutor_id AS username,
    pt.prjm_id,
    'tutor'::text AS grp_name
   FROM public.prj_tutor pt;


ALTER TABLE public.web_access_by_group OWNER TO rpadmin;

--
-- Name: web_access_by_group_mv; Type: MATERIALIZED VIEW; Schema: public; Owner: rpadmin
--

CREATE MATERIALIZED VIEW public.web_access_by_group_mv AS
 SELECT (''::text || pg.snummer) AS username,
    pw.password,
    pt.prjm_id,
    pt.grp_name
   FROM ((public.prj_grp pg
     JOIN public.prj_tutor pt USING (prjtg_id))
     JOIN public.passwd pw ON ((pg.snummer = pw.userid)))
UNION
 SELECT DISTINCT (''::text || pt.tutor_id) AS username,
    pw.password,
    pt.prjm_id,
    'tutor'::text AS grp_name
   FROM (public.prj_tutor pt
     JOIN public.passwd pw ON ((pt.tutor_id = pw.userid)))
  WITH NO DATA;


ALTER TABLE public.web_access_by_group_mv OWNER TO rpadmin;

--
-- Name: web_access_by_project; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.web_access_by_project AS
 SELECT DISTINCT prj_grp.snummer AS username,
    prj_milestone.prj_id
   FROM ((public.prj_grp
     JOIN public.prj_tutor USING (prjtg_id))
     JOIN public.prj_milestone USING (prjm_id))
UNION
 SELECT DISTINCT prj_tutor.tutor_id AS username,
    prj_milestone.prj_id
   FROM (public.prj_tutor
     JOIN public.prj_milestone USING (prjm_id));


ALTER TABLE public.web_access_by_project OWNER TO rpadmin;

--
-- Name: web_authentification; Type: VIEW; Schema: public; Owner: rpadmin
--

CREATE VIEW public.web_authentification AS
 SELECT (''::text || (password.userid)::text) AS username,
    password.password
   FROM public.passwd password
UNION
 SELECT guest_users.username,
    guest_users.password
   FROM public.guest_users;


ALTER TABLE public.web_authentification OWNER TO rpadmin;

--
-- Name: VIEW web_authentification; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON VIEW public.web_authentification IS 'used by generic web authentification for private sites ';


--
-- Name: sv05_aanmelders sv05_aanmelders_id; Type: DEFAULT; Schema: importer; Owner: importer
--

ALTER TABLE ONLY importer.sv05_aanmelders ALTER COLUMN sv05_aanmelders_id SET DEFAULT nextval('importer.sv05_aanmelders_sv05_aanmelders_id_seq'::regclass);


--
-- Name: activity act_id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.activity ALTER COLUMN act_id SET DEFAULT nextval('public.activity_act_id_seq'::regclass);


--
-- Name: any_query any_query_id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.any_query ALTER COLUMN any_query_id SET DEFAULT nextval('public.any_query_any_query_id_seq'::regclass);


--
-- Name: arbeitsaemterberatungstellen _id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.arbeitsaemterberatungstellen ALTER COLUMN _id SET DEFAULT nextval('public.arbeitsaemterberatungstellen__id_seq'::regclass);


--
-- Name: assessment_commit assessment_commit_id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.assessment_commit ALTER COLUMN assessment_commit_id SET DEFAULT nextval('public.assessment_commit_assessment_commit_id_seq'::regclass);


--
-- Name: assessment_remarks id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.assessment_remarks ALTER COLUMN id SET DEFAULT nextval('public.assessement_remark_id_seq'::regclass);


--
-- Name: berufskollegs _id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.berufskollegs ALTER COLUMN _id SET DEFAULT nextval('public.berufskollegs__id_seq'::regclass);


--
-- Name: class_cluster class_cluster; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.class_cluster ALTER COLUMN class_cluster SET DEFAULT nextval('public.class_cluster_class_cluster_seq'::regclass);


--
-- Name: colloquium_speakers colloquium_speaker_id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.colloquium_speakers ALTER COLUMN colloquium_speaker_id SET DEFAULT nextval('public.colloquium_speakers_colloquium_speaker_id_seq'::regclass);


--
-- Name: critique_history id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.critique_history ALTER COLUMN id SET DEFAULT nextval('public.critique_history_id_seq'::regclass);


--
-- Name: document_author document_author_id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.document_author ALTER COLUMN document_author_id SET DEFAULT nextval('public.document_author_document_author_id_seq'::regclass);


--
-- Name: enumeraties id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.enumeraties ALTER COLUMN id SET DEFAULT nextval('public.enumeraties_id_seq'::regclass);


--
-- Name: exam exam_id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam ALTER COLUMN exam_id SET DEFAULT nextval('public.exam_exam_id_seq'::regclass);


--
-- Name: exam_event exam_event_id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam_event ALTER COLUMN exam_event_id SET DEFAULT nextval('public.exam_event_id_seq'::regclass);


--
-- Name: exam_grades exam_grade_id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam_grades ALTER COLUMN exam_grade_id SET DEFAULT nextval('public.exam_grades_exam_grade_id_seq'::regclass);


--
-- Name: hoofdgrp_map _id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.hoofdgrp_map ALTER COLUMN _id SET DEFAULT nextval('public.hoofdgrp_map__id_seq'::regclass);


--
-- Name: inchecked _id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.inchecked ALTER COLUMN _id SET DEFAULT nextval('public.inchecked__id_seq'::regclass);


--
-- Name: jagers _id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.jagers ALTER COLUMN _id SET DEFAULT nextval('public.jagers__id_seq'::regclass);


--
-- Name: logoff id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.logoff ALTER COLUMN id SET DEFAULT nextval('public.logoff_id_seq'::regclass);


--
-- Name: logon id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.logon ALTER COLUMN id SET DEFAULT nextval('public.logon_id_seq'::regclass);


--
-- Name: map201901001 map201901001_id; Type: DEFAULT; Schema: public; Owner: hom
--

ALTER TABLE ONLY public.map201901001 ALTER COLUMN map201901001_id SET DEFAULT nextval('public.map201901001_map201901001_id_seq'::regclass);


--
-- Name: map_land_nl_iso3166 id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.map_land_nl_iso3166 ALTER COLUMN id SET DEFAULT nextval('public.map_land_nl_iso3166_id_seq'::regclass);


--
-- Name: menu id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.menu ALTER COLUMN id SET DEFAULT nextval('public.menu_id_seq'::regclass);


--
-- Name: menu_item id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.menu_item ALTER COLUMN id SET DEFAULT nextval('public.menu_item_id_seq'::regclass);


--
-- Name: menu_item_display id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.menu_item_display ALTER COLUMN id SET DEFAULT nextval('public.menu_item_display_id_seq'::regclass);


--
-- Name: menu_option_queries id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.menu_option_queries ALTER COLUMN id SET DEFAULT nextval('public.menu_option_queries_id_seq'::regclass);


--
-- Name: milestone_grade milestone_grade_id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.milestone_grade ALTER COLUMN milestone_grade_id SET DEFAULT nextval('public.milestone_grade_milestone_grade_id_seq'::regclass);


--
-- Name: mooc mooc_id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.mooc ALTER COLUMN mooc_id SET DEFAULT nextval('public.mooc_mooc_id_seq'::regclass);


--
-- Name: nat_mapper id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.nat_mapper ALTER COLUMN id SET DEFAULT nextval('public.nat_mapper_id_seq'::regclass);


--
-- Name: page_help help_id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.page_help ALTER COLUMN help_id SET DEFAULT nextval('public.page_help_help_id_seq'::regclass);


--
-- Name: password_request id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.password_request ALTER COLUMN id SET DEFAULT nextval('public.password_request_id_seq'::regclass);


--
-- Name: personal_repos id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.personal_repos ALTER COLUMN id SET DEFAULT nextval('public.personal_repos_id_seq'::regclass);


--
-- Name: prj3_2018 prj3_2018_id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.prj3_2018 ALTER COLUMN prj3_2018_id SET DEFAULT nextval('public.prj3_2018_prj3_2018_id_seq'::regclass);


--
-- Name: prj_tutor prjtg_id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.prj_tutor ALTER COLUMN prjtg_id SET DEFAULT nextval('public.prj_tutor_prjtg_id_seq'::regclass);


--
-- Name: project prj_id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project ALTER COLUMN prj_id SET DEFAULT nextval('public.project_prj_id_seq'::regclass);


--
-- Name: project_attributes_def project_attributes_def; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project_attributes_def ALTER COLUMN project_attributes_def SET DEFAULT nextval('public.project_attributes_def_project_attributes_def_seq'::regclass);


--
-- Name: project_attributes_values id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project_attributes_values ALTER COLUMN id SET DEFAULT nextval('public.project_attributes_values_id_seq'::regclass);


--
-- Name: project_auditor id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project_auditor ALTER COLUMN id SET DEFAULT nextval('public.project_auditor_id_seq'::regclass);


--
-- Name: project_deliverables pdeliverable_id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project_deliverables ALTER COLUMN pdeliverable_id SET DEFAULT nextval('public.project_deliverables_pdeliverable_id_seq'::regclass);


--
-- Name: project_scribe project_scribe_id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project_scribe ALTER COLUMN project_scribe_id SET DEFAULT nextval('public.project_scribe_project_scribe_id_seq'::regclass);


--
-- Name: project_task task_id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project_task ALTER COLUMN task_id SET DEFAULT nextval('public.project_task_task_id_seq'::regclass);


--
-- Name: recruiters_note recruiters_note_id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.recruiters_note ALTER COLUMN recruiters_note_id SET DEFAULT nextval('public.recruiters_note_recruiters_note_id_seq'::regclass);


--
-- Name: repositories id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.repositories ALTER COLUMN id SET DEFAULT nextval('public.repositories_id_seq'::regclass);


--
-- Name: resitexpected resitexpected_id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.resitexpected ALTER COLUMN resitexpected_id SET DEFAULT nextval('public.resitexpected_resitexpected_id_seq'::regclass);


--
-- Name: sebiassessment_student uid; Type: DEFAULT; Schema: public; Owner: hom
--

ALTER TABLE ONLY public.sebiassessment_student ALTER COLUMN uid SET DEFAULT nextval('public.sebiassessment_student_uid_seq'::regclass);


--
-- Name: set1 set1_id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.set1 ALTER COLUMN set1_id SET DEFAULT nextval('public.set1_set1_id_seq'::regclass);


--
-- Name: sticks2019 sticks2019_id; Type: DEFAULT; Schema: public; Owner: hom
--

ALTER TABLE ONLY public.sticks2019 ALTER COLUMN sticks2019_id SET DEFAULT nextval('public.sticks2019_sticks2019_id_seq'::regclass);


--
-- Name: sticks_20190916 sticks_20190916_id; Type: DEFAULT; Schema: public; Owner: hom
--

ALTER TABLE ONLY public.sticks_20190916 ALTER COLUMN sticks_20190916_id SET DEFAULT nextval('public.sticks_20190916_sticks_20190916_id_seq'::regclass);


--
-- Name: student_class class_id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.student_class ALTER COLUMN class_id SET DEFAULT nextval('public.classes_class_id_seq'::regclass);


--
-- Name: task_timer id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.task_timer ALTER COLUMN id SET DEFAULT nextval('public.task_timer_id_seq'::regclass);


--
-- Name: transaction trans_id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.transaction ALTER COLUMN trans_id SET DEFAULT nextval('public.transaction_trans_id_seq'::regclass);


--
-- Name: validator_occurrences id; Type: DEFAULT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.validator_occurrences ALTER COLUMN id SET DEFAULT nextval('public.validator_occurrences_id_seq'::regclass);


--
-- Name: sv05_aanmelders sv05_aanmelders_pkey; Type: CONSTRAINT; Schema: importer; Owner: importer
--

ALTER TABLE ONLY importer.sv05_aanmelders
    ADD CONSTRAINT sv05_aanmelders_pkey PRIMARY KEY (sv05_aanmelders_id);


--
-- Name: absence_reason absence_reason_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.absence_reason
    ADD CONSTRAINT absence_reason_pk PRIMARY KEY (act_id, snummer);


--
-- Name: absence_reason absence_reason_un; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.absence_reason
    ADD CONSTRAINT absence_reason_un UNIQUE (act_id, snummer);


--
-- Name: activity_participant act_part_un; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.activity_participant
    ADD CONSTRAINT act_part_un UNIQUE (act_id, snummer);


--
-- Name: activity_type act_type_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.activity_type
    ADD CONSTRAINT act_type_pk PRIMARY KEY (act_type);


--
-- Name: activity_participant activity_participant_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.activity_participant
    ADD CONSTRAINT activity_participant_pk PRIMARY KEY (act_id, snummer);


--
-- Name: activity activity_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.activity
    ADD CONSTRAINT activity_pk PRIMARY KEY (act_id);


--
-- Name: activity_project activity_project_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.activity_project
    ADD CONSTRAINT activity_project_pk PRIMARY KEY (prj_id);


--
-- Name: additional_course additional_course_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.additional_course
    ADD CONSTRAINT additional_course_pk PRIMARY KEY (snummer, course_code);


--
-- Name: alt_email alt_email_pkey; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.alt_email
    ADD CONSTRAINT alt_email_pkey PRIMARY KEY (snummer);


--
-- Name: any_query any_query_pkey; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.any_query
    ADD CONSTRAINT any_query_pkey PRIMARY KEY (any_query_id);


--
-- Name: arbeitsaemterberatungstellen arbeitsaemterberatungstellen_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.arbeitsaemterberatungstellen
    ADD CONSTRAINT arbeitsaemterberatungstellen_pk PRIMARY KEY (_id);


--
-- Name: assessment_remarks assessement_remark_un; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.assessment_remarks
    ADD CONSTRAINT assessement_remark_un UNIQUE (contestant, judge, prjtg_id);


--
-- Name: assessment_commit assessment_commit_pkey; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.assessment_commit
    ADD CONSTRAINT assessment_commit_pkey PRIMARY KEY (assessment_commit_id);


--
-- Name: assessment assessment_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.assessment
    ADD CONSTRAINT assessment_pk PRIMARY KEY (contestant, judge, criterium, prjtg_id);


--
-- Name: assessment assessment_un; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.assessment
    ADD CONSTRAINT assessment_un UNIQUE (prjtg_id, judge, contestant, criterium);


--
-- Name: base_criteria base_criteria_de_short_un; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.base_criteria
    ADD CONSTRAINT base_criteria_de_short_un UNIQUE (de_short);


--
-- Name: base_criteria base_criteria_en_short_un; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.base_criteria
    ADD CONSTRAINT base_criteria_en_short_un UNIQUE (en_short);


--
-- Name: base_criteria base_criteria_nl_short_un; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.base_criteria
    ADD CONSTRAINT base_criteria_nl_short_un UNIQUE (nl_short);


--
-- Name: base_criteria base_criteria_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.base_criteria
    ADD CONSTRAINT base_criteria_pk PRIMARY KEY (criterium_id);


--
-- Name: bigface_settings bigface_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.bigface_settings
    ADD CONSTRAINT bigface_settings_pkey PRIMARY KEY (bfkey);


--
-- Name: berufskollegs breufskollegs_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.berufskollegs
    ADD CONSTRAINT breufskollegs_pk PRIMARY KEY (_id);


--
-- Name: class_cluster class_cluster_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.class_cluster
    ADD CONSTRAINT class_cluster_pk PRIMARY KEY (class_cluster);


--
-- Name: student_class classes_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.student_class
    ADD CONSTRAINT classes_pk PRIMARY KEY (class_id);


--
-- Name: colloquium_speakers colloquium_speaker_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.colloquium_speakers
    ADD CONSTRAINT colloquium_speaker_pk PRIMARY KEY (colloquium_speaker_id);


--
-- Name: iso3166 country_by_lang_un; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.iso3166
    ADD CONSTRAINT country_by_lang_un UNIQUE (country_by_lang);


--
-- Name: iso3166 country_un; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.iso3166
    ADD CONSTRAINT country_un UNIQUE (country);


--
-- Name: course_week course_week_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.course_week
    ADD CONSTRAINT course_week_pk PRIMARY KEY (course_week_no);


--
-- Name: document_critique cr_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.document_critique
    ADD CONSTRAINT cr_pk PRIMARY KEY (critique_id);


--
-- Name: critique_history critique_history_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.critique_history
    ADD CONSTRAINT critique_history_pk PRIMARY KEY (id);


--
-- Name: diploma_dates diploma_dates_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.diploma_dates
    ADD CONSTRAINT diploma_dates_pk PRIMARY KEY (snummer);


--
-- Name: document_author document_author_pkey; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.document_author
    ADD CONSTRAINT document_author_pkey PRIMARY KEY (document_author_id);


--
-- Name: document_author document_author_un; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.document_author
    ADD CONSTRAINT document_author_un UNIQUE (upload_id, snummer);


--
-- Name: downloaded downloaded_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.downloaded
    ADD CONSTRAINT downloaded_pk PRIMARY KEY (snummer, upload_id, downloadts);


--
-- Name: education_unit_description education_unit_description_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.education_unit_description
    ADD CONSTRAINT education_unit_description_pk PRIMARY KEY (education_unit_id, language_id, module_id);


--
-- Name: education_unit education_unit_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.education_unit
    ADD CONSTRAINT education_unit_pk PRIMARY KEY (module_id, education_unit_id);


--
-- Name: email_signature email_signature_pkey; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.email_signature
    ADD CONSTRAINT email_signature_pkey PRIMARY KEY (snummer);


--
-- Name: enumeraties enumeraties_pkey; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.enumeraties
    ADD CONSTRAINT enumeraties_pkey PRIMARY KEY (menu_name, column_name, name);


--
-- Name: enumeraties enumeraties_un; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.enumeraties
    ADD CONSTRAINT enumeraties_un UNIQUE (id);


--
-- Name: exam_grades event_student_grade_unique; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam_grades
    ADD CONSTRAINT event_student_grade_unique UNIQUE (snummer, exam_event_id);


--
-- Name: exam_event exam_event_pkey; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam_event
    ADD CONSTRAINT exam_event_pkey PRIMARY KEY (exam_event_id);


--
-- Name: exam_event exam_event_un1; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam_event
    ADD CONSTRAINT exam_event_un1 UNIQUE (module_part_id, exam_date);


--
-- Name: exam_focus_description exam_focus_description_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam_focus_description
    ADD CONSTRAINT exam_focus_description_pk PRIMARY KEY (exam_focus_id, language_id);


--
-- Name: exam_focus exam_focus_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam_focus
    ADD CONSTRAINT exam_focus_pk PRIMARY KEY (exam_focus_id);


--
-- Name: exam_grading_aspect_description exam_grading_aspect_description_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam_grading_aspect_description
    ADD CONSTRAINT exam_grading_aspect_description_pk PRIMARY KEY (exam_grading_aspect_id, language_id);


--
-- Name: exam_grading_aspect exam_grading_aspect_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam_grading_aspect
    ADD CONSTRAINT exam_grading_aspect_pk PRIMARY KEY (exam_grading_aspect_id);


--
-- Name: exam_grading_level_description exam_grading_level_description_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam_grading_level_description
    ADD CONSTRAINT exam_grading_level_description_pk PRIMARY KEY (exam_grading_level_id, language_id);


--
-- Name: exam_grading_level exam_grading_level_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam_grading_level
    ADD CONSTRAINT exam_grading_level_pk PRIMARY KEY (exam_grading_level_id);


--
-- Name: exam_grading_type_description exam_grading_type_description_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam_grading_type_description
    ADD CONSTRAINT exam_grading_type_description_pk PRIMARY KEY (exam_grading_type_id, language_id);


--
-- Name: exam_grading_type exam_grading_type_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam_grading_type
    ADD CONSTRAINT exam_grading_type_pk PRIMARY KEY (exam_grading_type_id);


--
-- Name: exam exam_pkey; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam
    ADD CONSTRAINT exam_pkey PRIMARY KEY (exam_id);


--
-- Name: exam_type_description exam_type_description_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam_type_description
    ADD CONSTRAINT exam_type_description_pk PRIMARY KEY (exam_type_id, language_id);


--
-- Name: exam_type exam_type_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam_type
    ADD CONSTRAINT exam_type_pk PRIMARY KEY (exam_type_id);


--
-- Name: student_class fac_scl_cuslt_unique; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.student_class
    ADD CONSTRAINT fac_scl_cuslt_unique UNIQUE (faculty_id, sclass, class_cluster);


--
-- Name: student_class fac_sclass_un; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.student_class
    ADD CONSTRAINT fac_sclass_un UNIQUE (faculty_id, sclass);


--
-- Name: faculty faculty_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.faculty
    ADD CONSTRAINT faculty_pk PRIMARY KEY (faculty_id);


--
-- Name: fake_mail_address fake_email_address_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.fake_mail_address
    ADD CONSTRAINT fake_email_address_pk PRIMARY KEY (email1);


--
-- Name: fontys_course fontys_course_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.fontys_course
    ADD CONSTRAINT fontys_course_pk PRIMARY KEY (course);


--
-- Name: foto_prefix foto_prefix_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.foto_prefix
    ADD CONSTRAINT foto_prefix_pk PRIMARY KEY (prefix);


--
-- Name: github_id github_id_github_id_key; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.github_id
    ADD CONSTRAINT github_id_github_id_key UNIQUE (github_id);


--
-- Name: grade_summer_result grade_summer_result_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.grade_summer_result
    ADD CONSTRAINT grade_summer_result_pk PRIMARY KEY (prjtg_id, snummer, criterium);


--
-- Name: grp_alias grp_alias_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.grp_alias
    ADD CONSTRAINT grp_alias_pk PRIMARY KEY (prjtg_id);


--
-- Name: guest_users guest_users_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.guest_users
    ADD CONSTRAINT guest_users_pk PRIMARY KEY (username);


--
-- Name: hoofdgrp_map hoofdgrp_map_hoofdgrp_key; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.hoofdgrp_map
    ADD CONSTRAINT hoofdgrp_map_hoofdgrp_key UNIQUE (hoofdgrp);


--
-- Name: uploads id_u; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.uploads
    ADD CONSTRAINT id_u UNIQUE (upload_id);


--
-- Name: iso3166 iso3166_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.iso3166
    ADD CONSTRAINT iso3166_pk PRIMARY KEY (number);


--
-- Name: iso3166 iso_a2_un; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.iso3166
    ADD CONSTRAINT iso_a2_un UNIQUE (a2);


--
-- Name: iso3166 iso_a3_un; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.iso3166
    ADD CONSTRAINT iso_a3_un UNIQUE (a3);


--
-- Name: iso3166 iso_number_un; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.iso3166
    ADD CONSTRAINT iso_number_un UNIQUE (number);


--
-- Name: jagers jager_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.jagers
    ADD CONSTRAINT jager_pk PRIMARY KEY (pcn);


--
-- Name: iso3166 land_nl_un; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.iso3166
    ADD CONSTRAINT land_nl_un UNIQUE (land_nl);


--
-- Name: language language_pkey; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.language
    ADD CONSTRAINT language_pkey PRIMARY KEY (language_id);


--
-- Name: learning_goal_description learning_goal_description_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.learning_goal_description
    ADD CONSTRAINT learning_goal_description_pk PRIMARY KEY (module_id, language_id, learning_goal_id);


--
-- Name: learning_goal_exam_focus learning_goal_exam_focus_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.learning_goal_exam_focus
    ADD CONSTRAINT learning_goal_exam_focus_pk PRIMARY KEY (module_id, learning_goal_id, exam_focus_id);


--
-- Name: learning_goal learning_goal_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.learning_goal
    ADD CONSTRAINT learning_goal_pk PRIMARY KEY (module_id, learning_goal_id);


--
-- Name: logoff logoff_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.logoff
    ADD CONSTRAINT logoff_pk PRIMARY KEY (id);


--
-- Name: logon logon_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.logon
    ADD CONSTRAINT logon_pk PRIMARY KEY (id);


--
-- Name: lpi_id lpi_id_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.lpi_id
    ADD CONSTRAINT lpi_id_pk PRIMARY KEY (snummer);


--
-- Name: map201901001 map201901001_pkey; Type: CONSTRAINT; Schema: public; Owner: hom
--

ALTER TABLE ONLY public.map201901001
    ADD CONSTRAINT map201901001_pkey PRIMARY KEY (map201901001_id);


--
-- Name: map_land_nl_iso3166 map_land_nl_iso3166_land_nl_un; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.map_land_nl_iso3166
    ADD CONSTRAINT map_land_nl_iso3166_land_nl_un UNIQUE (land_nl);


--
-- Name: menu_item_display menu_item_diplay_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.menu_item_display
    ADD CONSTRAINT menu_item_diplay_pk PRIMARY KEY (id);


--
-- Name: menu_item menu_item_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.menu_item
    ADD CONSTRAINT menu_item_pk PRIMARY KEY (id);


--
-- Name: menu_item menu_item_un; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.menu_item
    ADD CONSTRAINT menu_item_un UNIQUE (menu_name, column_name);


--
-- Name: menu_option_queries menu_option_q_un; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.menu_option_queries
    ADD CONSTRAINT menu_option_q_un UNIQUE (menu_name, column_name);


--
-- Name: menu_option_queries menu_option_queries_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.menu_option_queries
    ADD CONSTRAINT menu_option_queries_pk PRIMARY KEY (id);


--
-- Name: menu menu_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.menu
    ADD CONSTRAINT menu_pk UNIQUE (menu_name, relation_name);


--
-- Name: menu menu_pk1; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.menu
    ADD CONSTRAINT menu_pk1 PRIMARY KEY (id);


--
-- Name: milestone_grade milestone_grade_pkey; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.milestone_grade
    ADD CONSTRAINT milestone_grade_pkey PRIMARY KEY (milestone_grade_id);


--
-- Name: milestone_grade milestone_grade_snummer_key; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.milestone_grade
    ADD CONSTRAINT milestone_grade_snummer_key UNIQUE (snummer, prjm_id);


--
-- Name: minikiosk_visits minikiosk_visits_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.minikiosk_visits
    ADD CONSTRAINT minikiosk_visits_pk PRIMARY KEY (counter);


--
-- Name: module_activity_description module_activity_description_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.module_activity_description
    ADD CONSTRAINT module_activity_description_pk PRIMARY KEY (module_activity_id, language_id);


--
-- Name: module_activity module_activity_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.module_activity
    ADD CONSTRAINT module_activity_pk PRIMARY KEY (module_activity_id);


--
-- Name: module_desciption_long module_description_long_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.module_desciption_long
    ADD CONSTRAINT module_description_long_pk PRIMARY KEY (module_id, language_id);


--
-- Name: module_description_short module_description_short_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.module_description_short
    ADD CONSTRAINT module_description_short_pk PRIMARY KEY (module_id, language_id);


--
-- Name: module_language module_language_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.module_language
    ADD CONSTRAINT module_language_pk PRIMARY KEY (module_id, language_id);


--
-- Name: module_part module_part_id; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.module_part
    ADD CONSTRAINT module_part_id PRIMARY KEY (module_part_id);


--
-- Name: module module_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.module
    ADD CONSTRAINT module_pk PRIMARY KEY (module_id);


--
-- Name: module_prerequisite module_prerequisite_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.module_prerequisite
    ADD CONSTRAINT module_prerequisite_pk PRIMARY KEY (module_id, prerequisite);


--
-- Name: module_resource module_resource_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.module_resource
    ADD CONSTRAINT module_resource_pk PRIMARY KEY (module_id, module_resource_id);


--
-- Name: module_resource_type_description module_resource_type_description_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.module_resource_type_description
    ADD CONSTRAINT module_resource_type_description_pk PRIMARY KEY (module_resource_type_id, language_id);


--
-- Name: module_resource_type module_resource_type_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.module_resource_type
    ADD CONSTRAINT module_resource_type_pk PRIMARY KEY (module_resource_type_id);


--
-- Name: module_topic_description module_topic_description_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.module_topic_description
    ADD CONSTRAINT module_topic_description_pk PRIMARY KEY (module_id, module_topic_id, language_id);


--
-- Name: module_topic module_topic_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.module_topic
    ADD CONSTRAINT module_topic_pk PRIMARY KEY (module_id, module_topic_id);


--
-- Name: module_week_schedule module_week_schedule_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.module_week_schedule
    ADD CONSTRAINT module_week_schedule_pk PRIMARY KEY (module_id, week_id, module_activity_id);


--
-- Name: mooc mooc_pkey; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.mooc
    ADD CONSTRAINT mooc_pkey PRIMARY KEY (mooc_id);


--
-- Name: nat_mapper nat_mapper_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.nat_mapper
    ADD CONSTRAINT nat_mapper_pk PRIMARY KEY (id);


--
-- Name: nat_mapper nation_omschr_un; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.nat_mapper
    ADD CONSTRAINT nation_omschr_un UNIQUE (nation_omschr);


--
-- Name: page_help page_help_page_key; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.page_help
    ADD CONSTRAINT page_help_page_key UNIQUE (page);


--
-- Name: page_help page_help_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.page_help
    ADD CONSTRAINT page_help_pk PRIMARY KEY (help_id);


--
-- Name: passwd password_pkey; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.passwd
    ADD CONSTRAINT password_pkey PRIMARY KEY (userid);


--
-- Name: password_request password_request_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.password_request
    ADD CONSTRAINT password_request_pk PRIMARY KEY (id);


--
-- Name: peer_settings peer_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.peer_settings
    ADD CONSTRAINT peer_settings_pkey PRIMARY KEY (key);


--
-- Name: personal_repos personal_repos_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.personal_repos
    ADD CONSTRAINT personal_repos_pk PRIMARY KEY (id);


--
-- Name: personal_repos personal_repos_un1; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.personal_repos
    ADD CONSTRAINT personal_repos_un1 UNIQUE (repospath);


--
-- Name: personal_repos personal_repos_un2; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.personal_repos
    ADD CONSTRAINT personal_repos_un2 UNIQUE (url_tail);


--
-- Name: prj_grp pgr_grp_un2; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.prj_grp
    ADD CONSTRAINT pgr_grp_un2 UNIQUE (prjtg_id, snummer);


--
-- Name: tutor pnc_un; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.tutor
    ADD CONSTRAINT pnc_un UNIQUE (userid);


--
-- Name: prj3_2018 prj3_2018_pkey; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.prj3_2018
    ADD CONSTRAINT prj3_2018_pkey PRIMARY KEY (prj3_2018_id);


--
-- Name: prj_contact prj_contact_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.prj_contact
    ADD CONSTRAINT prj_contact_pk PRIMARY KEY (prjtg_id);


--
-- Name: prj_grp prj_grp_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.prj_grp
    ADD CONSTRAINT prj_grp_pk PRIMARY KEY (snummer, prjtg_id);


--
-- Name: prj_milestone prj_m_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.prj_milestone
    ADD CONSTRAINT prj_m_pk PRIMARY KEY (prjm_id);


--
-- Name: prj_milestone prj_milstone_un; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.prj_milestone
    ADD CONSTRAINT prj_milstone_un UNIQUE (prjm_id);


--
-- Name: project_roles prj_role_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project_roles
    ADD CONSTRAINT prj_role_pk PRIMARY KEY (prj_id, rolenum);


--
-- Name: prj_tutor prj_tutor_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.prj_tutor
    ADD CONSTRAINT prj_tutor_pk PRIMARY KEY (prjtg_id);


--
-- Name: prj_tutor prj_tutor_prjtg_id_un; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.prj_tutor
    ADD CONSTRAINT prj_tutor_prjtg_id_un UNIQUE (prjtg_id);


--
-- Name: prj_tutor prj_tutor_u3; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.prj_tutor
    ADD CONSTRAINT prj_tutor_u3 UNIQUE (prjm_id, grp_num);


--
-- Name: prjm_criterium prjm_criterium_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.prjm_criterium
    ADD CONSTRAINT prjm_criterium_pk PRIMARY KEY (prjm_id, criterium_id);


--
-- Name: prjm_criterium prjm_criterium_un; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.prjm_criterium
    ADD CONSTRAINT prjm_criterium_un UNIQUE (prjm_id, criterium_id);


--
-- Name: project_attributes_def project_attributes_def_pkey; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project_attributes_def
    ADD CONSTRAINT project_attributes_def_pkey PRIMARY KEY (project_attributes_def);


--
-- Name: project_attributes_values project_attributes_value_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project_attributes_values
    ADD CONSTRAINT project_attributes_value_pk PRIMARY KEY (id);


--
-- Name: project_auditor project_auditor_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project_auditor
    ADD CONSTRAINT project_auditor_pk PRIMARY KEY (id);


--
-- Name: project_auditor project_auditor_un1; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project_auditor
    ADD CONSTRAINT project_auditor_un1 UNIQUE (snummer, prjm_id, gid);


--
-- Name: project_deliverables project_deliverables_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project_deliverables
    ADD CONSTRAINT project_deliverables_pk PRIMARY KEY (pdeliverable_id);


--
-- Name: project_deliverables project_deliverables_un; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project_deliverables
    ADD CONSTRAINT project_deliverables_un UNIQUE (prjm_id, doctype);


--
-- Name: project project_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project
    ADD CONSTRAINT project_pk PRIMARY KEY (prj_id);


--
-- Name: project_scribe project_scribe_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project_scribe
    ADD CONSTRAINT project_scribe_pk PRIMARY KEY (project_scribe_id);


--
-- Name: project_scribe project_scribe_un1; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project_scribe
    ADD CONSTRAINT project_scribe_un1 UNIQUE (prj_id, scribe);


--
-- Name: project_task_completed project_task_completed_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project_task_completed
    ADD CONSTRAINT project_task_completed_pk PRIMARY KEY (task_id, snummer, trans_id);


--
-- Name: project_task project_task_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project_task
    ADD CONSTRAINT project_task_pk PRIMARY KEY (task_id);


--
-- Name: project_tasks project_tasks_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project_tasks
    ADD CONSTRAINT project_tasks_pk PRIMARY KEY (prj_id, task_id, snummer);


--
-- Name: project project_un; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project
    ADD CONSTRAINT project_un UNIQUE (afko, year, course);


--
-- Name: prospects prospects_snummer_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.prospects
    ADD CONSTRAINT prospects_snummer_pk PRIMARY KEY (snummer);


--
-- Name: recruiters_note recruiters_note_pkey; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.recruiters_note
    ADD CONSTRAINT recruiters_note_pkey PRIMARY KEY (recruiters_note_id);


--
-- Name: registered_mphotos registered_mphotos_pkey; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.registered_mphotos
    ADD CONSTRAINT registered_mphotos_pkey PRIMARY KEY (snummer);


--
-- Name: registered_photos registered_photos_pkey; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.registered_photos
    ADD CONSTRAINT registered_photos_pkey PRIMARY KEY (snummer);


--
-- Name: repositories repositories_pkey; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.repositories
    ADD CONSTRAINT repositories_pkey PRIMARY KEY (repospath);


--
-- Name: repositories repositories_un1; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.repositories
    ADD CONSTRAINT repositories_un1 UNIQUE (repospath);


--
-- Name: repositories repositories_un2; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.repositories
    ADD CONSTRAINT repositories_un2 UNIQUE (url_tail);


--
-- Name: resitexpected resitexpected_pkey; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.resitexpected
    ADD CONSTRAINT resitexpected_pkey PRIMARY KEY (resitexpected_id);


--
-- Name: sclass_selector sclass_selector_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.sclass_selector
    ADD CONSTRAINT sclass_selector_pk PRIMARY KEY (class_id);


--
-- Name: sebi_stick sebi_stick_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.sebi_stick
    ADD CONSTRAINT sebi_stick_pk PRIMARY KEY (snummer);


--
-- Name: semester semester_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.semester
    ADD CONSTRAINT semester_pk PRIMARY KEY (id);


--
-- Name: session_data session_data_pkey; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.session_data
    ADD CONSTRAINT session_data_pkey PRIMARY KEY (snummer);


--
-- Name: set1 set1_pkey; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.set1
    ADD CONSTRAINT set1_pkey PRIMARY KEY (set1_id);


--
-- Name: snummer snummer_pkey; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.snummer
    ADD CONSTRAINT snummer_pkey PRIMARY KEY (snummer);


--
-- Name: sebi_stick stick_un; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.sebi_stick
    ADD CONSTRAINT stick_un UNIQUE (stick);


--
-- Name: sticks2019 sticks2019_pkey; Type: CONSTRAINT; Schema: public; Owner: hom
--

ALTER TABLE ONLY public.sticks2019
    ADD CONSTRAINT sticks2019_pkey PRIMARY KEY (sticks2019_id);


--
-- Name: sticks_20190916 sticks_20190916_pkey; Type: CONSTRAINT; Schema: public; Owner: hom
--

ALTER TABLE ONLY public.sticks_20190916
    ADD CONSTRAINT sticks_20190916_pkey PRIMARY KEY (sticks_20190916_id);


--
-- Name: student student_pcn_un; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.student
    ADD CONSTRAINT student_pcn_un UNIQUE (pcn);


--
-- Name: student student_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.student
    ADD CONSTRAINT student_pk PRIMARY KEY (snummer);


--
-- Name: student_role student_role_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.student_role
    ADD CONSTRAINT student_role_pk PRIMARY KEY (snummer, prjm_id);


--
-- Name: student_role student_role_un; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.student_role
    ADD CONSTRAINT student_role_un UNIQUE (snummer, prjm_id);


--
-- Name: CONSTRAINT student_role_un ON student_role; Type: COMMENT; Schema: public; Owner: rpadmin
--

COMMENT ON CONSTRAINT student_role_un ON public.student_role IS 'One role per project_milestone';


--
-- Name: studie_prog studie_prog_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.studie_prog
    ADD CONSTRAINT studie_prog_pk PRIMARY KEY (studieprogr);


--
-- Name: studieplan studieplan_pkey; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.studieplan
    ADD CONSTRAINT studieplan_pkey PRIMARY KEY (studieplan);


--
-- Name: svn_guests svn_guests_pkey; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.svn_guests
    ADD CONSTRAINT svn_guests_pkey PRIMARY KEY (username);


--
-- Name: task_timer task_timer_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.task_timer
    ADD CONSTRAINT task_timer_pk PRIMARY KEY (snummer, prj_id, milestone, task_id, start_time);


--
-- Name: timetableweek timetableweek_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.timetableweek
    ADD CONSTRAINT timetableweek_pk PRIMARY KEY (day, hourcode);


--
-- Name: tracusers tracusers_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.tracusers
    ADD CONSTRAINT tracusers_pk PRIMARY KEY (username);


--
-- Name: transaction transaction_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.transaction
    ADD CONSTRAINT transaction_pk PRIMARY KEY (trans_id);


--
-- Name: tutor_class_cluster tutor_class_cluster_pkey; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.tutor_class_cluster
    ADD CONSTRAINT tutor_class_cluster_pkey PRIMARY KEY (userid, class_cluster);


--
-- Name: tutor tutor_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.tutor
    ADD CONSTRAINT tutor_pk PRIMARY KEY (userid);


--
-- Name: tutor tutor_un; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.tutor
    ADD CONSTRAINT tutor_un UNIQUE (faculty_id, tutor);


--
-- Name: uilang uilang_pkey; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.uilang
    ADD CONSTRAINT uilang_pkey PRIMARY KEY (lang_code);


--
-- Name: unix_uid unix_uid_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.unix_uid
    ADD CONSTRAINT unix_uid_pk PRIMARY KEY (uid);


--
-- Name: unix_uid unix_uid_snummer_un; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.unix_uid
    ADD CONSTRAINT unix_uid_snummer_un UNIQUE (snummer);


--
-- Name: uploaddocumenttypes updt_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.uploaddocumenttypes
    ADD CONSTRAINT updt_pk PRIMARY KEY (doctype, prj_id);


--
-- Name: uploads uploads_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.uploads
    ADD CONSTRAINT uploads_pk PRIMARY KEY (upload_id);


--
-- Name: validator_map validator_map_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.validator_map
    ADD CONSTRAINT validator_map_pk PRIMARY KEY (id);


--
-- Name: validator_occurrences validator_occurences_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.validator_occurrences
    ADD CONSTRAINT validator_occurences_pk PRIMARY KEY (id);


--
-- Name: validator_occurrences validator_occurrences_un; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.validator_occurrences
    ADD CONSTRAINT validator_occurrences_un UNIQUE (page, identifier);


--
-- Name: validator_regex validator_regex_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.validator_regex
    ADD CONSTRAINT validator_regex_pk PRIMARY KEY (regex_name);


--
-- Name: validator_regex validator_regex_un; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.validator_regex
    ADD CONSTRAINT validator_regex_un UNIQUE (regex_name, regex);


--
-- Name: variant variant_un; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.variant
    ADD CONSTRAINT variant_un UNIQUE (studielinkvariantcode);


--
-- Name: weekdays weekdays_pk; Type: CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.weekdays
    ADD CONSTRAINT weekdays_pk PRIMARY KEY (day);


--
-- Name: assessment_grp_crit_idx; Type: INDEX; Schema: public; Owner: rpadmin
--

CREATE INDEX assessment_grp_crit_idx ON public.assessment USING btree (prjtg_id, criterium);


--
-- Name: assessment_idx1; Type: INDEX; Schema: public; Owner: rpadmin
--

CREATE INDEX assessment_idx1 ON public.assessment USING btree (prjtg_id, judge, contestant, criterium);


--
-- Name: exam_grades_exam_event_id_idx; Type: INDEX; Schema: public; Owner: rpadmin
--

CREATE INDEX exam_grades_exam_event_id_idx ON public.exam_grades USING brin (exam_event_id);


--
-- Name: fki_project_scribe_fk1; Type: INDEX; Schema: public; Owner: rpadmin
--

CREATE INDEX fki_project_scribe_fk1 ON public.project_scribe USING btree (scribe);


--
-- Name: idx_gebdat; Type: INDEX; Schema: public; Owner: rpadmin
--

CREATE INDEX idx_gebdat ON public.student USING btree (gebdat);


--
-- Name: prj_grp_idx; Type: INDEX; Schema: public; Owner: rpadmin
--

CREATE INDEX prj_grp_idx ON public.prj_grp USING btree (snummer);


--
-- Name: prj_grp_tg_idx; Type: INDEX; Schema: public; Owner: rpadmin
--

CREATE INDEX prj_grp_tg_idx ON public.prj_grp USING btree (prjtg_id);


--
-- Name: prj_grp_tg_stud_idx; Type: INDEX; Schema: public; Owner: rpadmin
--

CREATE INDEX prj_grp_tg_stud_idx ON public.prj_grp USING btree (prjtg_id, snummer);


--
-- Name: prj_milestone_idx; Type: INDEX; Schema: public; Owner: rpadmin
--

CREATE UNIQUE INDEX prj_milestone_idx ON public.prj_milestone USING btree (prj_id, milestone);


--
-- Name: prj_milestone_idx2; Type: INDEX; Schema: public; Owner: rpadmin
--

CREATE UNIQUE INDEX prj_milestone_idx2 ON public.prj_milestone USING btree (prj_id, milestone, prjm_id);


--
-- Name: prj_tutor_idx; Type: INDEX; Schema: public; Owner: rpadmin
--

CREATE INDEX prj_tutor_idx ON public.prj_tutor USING btree (tutor_id);


--
-- Name: project_task_completed_task_student; Type: INDEX; Schema: public; Owner: rpadmin
--

CREATE INDEX project_task_completed_task_student ON public.project_task_completed USING btree (task_id, snummer);


--
-- Name: student_email_idx; Type: INDEX; Schema: public; Owner: rpadmin
--

CREATE INDEX student_email_idx ON public.student USING btree (email1);


--
-- Name: student_hoofdgrp_idx; Type: INDEX; Schema: public; Owner: rpadmin
--

CREATE INDEX student_hoofdgrp_idx ON public.student USING btree (hoofdgrp);


--
-- Name: web_access_by_group_mv_idx; Type: INDEX; Schema: public; Owner: rpadmin
--

CREATE INDEX web_access_by_group_mv_idx ON public.web_access_by_group_mv USING btree (username, prjm_id, grp_name);


--
-- Name: all_alumni_email all_alumni_email_delete; Type: RULE; Schema: public; Owner: rpadmin
--

CREATE RULE all_alumni_email_delete AS
    ON DELETE TO public.all_alumni_email DO INSTEAD NOTHING;


--
-- Name: all_alumni_email all_alumni_email_update; Type: RULE; Schema: public; Owner: rpadmin
--

CREATE RULE all_alumni_email_update AS
    ON UPDATE TO public.all_alumni_email DO INSTEAD ( UPDATE public.student SET email1 = new.email1
  WHERE (student.snummer = new.snummer);
 INSERT INTO public.alt_email (snummer, email2, email3)  SELECT new.snummer,
            new.email2,
            new.email3 ON CONFLICT(snummer) DO UPDATE SET email2 = excluded.email2, email3 = excluded.email3;
 UPDATE public.alumni_email SET email2 = new.email4, email3 = new.email5
  WHERE (alumni_email.snummer = new.snummer);
);


--
-- Name: grp_detail grp_detail_delete; Type: RULE; Schema: public; Owner: rpadmin
--

CREATE RULE grp_detail_delete AS
    ON DELETE TO public.grp_detail DO INSTEAD  DELETE FROM public.grp_alias g
  WHERE (g.prjtg_id = old.prjtg_id);


--
-- Name: grp_detail grp_detail_insert; Type: RULE; Schema: public; Owner: rpadmin
--

CREATE RULE grp_detail_insert AS
    ON INSERT TO public.grp_detail DO INSTEAD  INSERT INTO public.grp_alias (prjtg_id, long_name, alias, website, productname, youtube_link)
  VALUES (new.prjtg_id, new.alias, new.long_name, new.website, new.productname, new.youtube_link);


--
-- Name: grp_detail grp_detail_update; Type: RULE; Schema: public; Owner: rpadmin
--

CREATE RULE grp_detail_update AS
    ON UPDATE TO public.grp_detail DO INSTEAD  UPDATE public.grp_alias SET long_name = new.long_name, alias = new.alias, website = new.website, productname = new.productname, youtube_link = new.youtube_link
  WHERE (grp_alias.prjtg_id = new.prjtg_id);


--
-- Name: grp_details grp_details_delete; Type: RULE; Schema: public; Owner: rpadmin
--

CREATE RULE grp_details_delete AS
    ON DELETE TO public.grp_details DO INSTEAD  DELETE FROM public.grp_alias g
  WHERE (g.prjtg_id = old.prjtg_id);


--
-- Name: grp_details grp_details_insert; Type: RULE; Schema: public; Owner: rpadmin
--

CREATE RULE grp_details_insert AS
    ON INSERT TO public.grp_details DO INSTEAD  INSERT INTO public.grp_alias (prjtg_id, long_name, alias, website, productname, youtube_link)
  VALUES (new.prjtg_id, new.alias, new.long_name, new.website, new.productname, new.youtube_link);


--
-- Name: grp_details grp_details_update; Type: RULE; Schema: public; Owner: rpadmin
--

CREATE RULE grp_details_update AS
    ON UPDATE TO public.grp_details DO INSTEAD  UPDATE public.grp_alias SET long_name = new.long_name, alias = new.alias, website = new.website, productname = new.productname, youtube_link = new.youtube_link
  WHERE (grp_alias.prjtg_id = new.prjtg_id);


--
-- Name: student_email student_email_delete; Type: RULE; Schema: public; Owner: rpadmin
--

CREATE RULE student_email_delete AS
    ON DELETE TO public.student_email DO INSTEAD NOTHING;


--
-- Name: student_email student_email_update; Type: RULE; Schema: public; Owner: rpadmin
--

CREATE RULE student_email_update AS
    ON UPDATE TO public.student_email DO INSTEAD ( UPDATE public.student SET achternaam = new.achternaam, tussenvoegsel = new.tussenvoegsel, voorletters = new.voorletters, roepnaam = new.roepnaam, straat = new.straat, huisnr = new.huisnr, pcode = new.pcode, plaats = new.plaats, email1 = new.email1, nationaliteit = new.nationaliteit, cohort = new.cohort, gebdat = new.gebdat, sex = new.sex, lang = new.lang, pcn = new.pcn, opl = new.opl, phone_home = new.phone_home, phone_gsm = new.phone_gsm, phone_postaddress = new.phone_postaddress, faculty_id = new.faculty_id, hoofdgrp = new.hoofdgrp, active = new.active, slb = new.slb, land = new.land, studieplan = new.studieplan, geboorteplaats = new.geboorteplaats, geboorteland = new.geboorteland, voornamen = new.voornamen, class_id = new.class_id
  WHERE (student.snummer = new.snummer);
 INSERT INTO public.alt_email (snummer, email2)  SELECT new.snummer,
            new.email2
          WHERE (new.email2 IS NOT NULL) ON CONFLICT ON CONSTRAINT alt_email_pkey DO NOTHING;
 UPDATE public.alt_email SET email2 = new.email2
  WHERE ((alt_email.snummer = new.snummer) AND (NOT (new.email2 IS NULL)));
);


--
-- Name: tutor_data tutor_data_update; Type: RULE; Schema: public; Owner: rpadmin
--

CREATE RULE tutor_data_update AS
    ON UPDATE TO public.tutor_data DO INSTEAD ( UPDATE public.student SET hoofdgrp = new.hoofdgrp
  WHERE (student.snummer = new.snummer);
 UPDATE public.tutor SET faculty_id = new.faculty_id
  WHERE (tutor.userid = new.snummer);
);


--
-- Name: tutor_join_student tutor_join_student_delete; Type: RULE; Schema: public; Owner: rpadmin
--

CREATE RULE tutor_join_student_delete AS
    ON DELETE TO public.tutor_join_student DO INSTEAD  DELETE FROM public.tutor
  WHERE ((tutor.tutor)::text = (old.tutor)::text);


--
-- Name: tutor_join_student tutor_join_student_insert; Type: RULE; Schema: public; Owner: rpadmin
--

CREATE RULE tutor_join_student_insert AS
    ON INSERT TO public.tutor_join_student DO INSTEAD  INSERT INTO public.tutor (tutor, userid, faculty_id, team, office, building, city, room, office_phone, schedule_id, display_name)
  VALUES (new.tutor, new.userid, new.faculty_id, new.team, new.function, new.building, new.city, new.room, new.office_phone, new.schedule_id, new.display_name);


--
-- Name: tutor_join_student tutor_join_student_update; Type: RULE; Schema: public; Owner: rpadmin
--

CREATE RULE tutor_join_student_update AS
    ON UPDATE TO public.tutor_join_student DO INSTEAD  UPDATE public.tutor SET tutor = new.tutor, userid = new.userid, faculty_id = new.faculty_id, team = new.team, office = new.function, building = new.building, city = new.city, room = new.room, office_phone = new.office_phone, schedule_id = new.schedule_id, display_name = new.display_name
  WHERE (tutor.userid = old.userid);


--
-- Name: validator_regex_slashed validator_regex_slashed_r_delete; Type: RULE; Schema: public; Owner: rpadmin
--

CREATE RULE validator_regex_slashed_r_delete AS
    ON DELETE TO public.validator_regex_slashed DO INSTEAD  DELETE FROM public.validator_regex
  WHERE (((validator_regex.regex_name)::text = (old.regex_name)::text) AND (validator_regex.regex = old.regex));


--
-- Name: validator_regex_slashed validator_regex_slashed_r_insert; Type: RULE; Schema: public; Owner: rpadmin
--

CREATE RULE validator_regex_slashed_r_insert AS
    ON INSERT TO public.validator_regex_slashed DO INSTEAD  INSERT INTO public.validator_regex (regex_name, regex)
  VALUES (new.regex_name, new.regex);


--
-- Name: validator_regex_slashed validator_regex_slashed_r_update; Type: RULE; Schema: public; Owner: rpadmin
--

CREATE RULE validator_regex_slashed_r_update AS
    ON UPDATE TO public.validator_regex_slashed DO INSTEAD  UPDATE public.validator_regex SET regex_name = new.regex_name, regex = new.regex
  WHERE (((validator_regex.regex_name)::text = (new.regex_name)::text) AND (validator_regex.regex = new.regex));


--
-- Name: student_email student_email_insert; Type: TRIGGER; Schema: public; Owner: rpadmin
--

CREATE TRIGGER student_email_insert INSTEAD OF INSERT ON public.student_email FOR EACH ROW EXECUTE PROCEDURE public.insert_student_email();


--
-- Name: sv09_ingeschrevenen sv09_iso3166_geboorteland_fk; Type: FK CONSTRAINT; Schema: importer; Owner: importer
--

ALTER TABLE ONLY importer.sv09_ingeschrevenen
    ADD CONSTRAINT sv09_iso3166_geboorteland_fk FOREIGN KEY (geboorteland) REFERENCES public.iso3166(land_nl);


--
-- Name: sv09_ingeschrevenen sv09_iso3166_land_nl_fk; Type: FK CONSTRAINT; Schema: importer; Owner: importer
--

ALTER TABLE ONLY importer.sv09_ingeschrevenen
    ADD CONSTRAINT sv09_iso3166_land_nl_fk FOREIGN KEY (land) REFERENCES public.iso3166(land_nl);


--
-- Name: sv09_ingeschrevenen sv09_nat_mapper_fk; Type: FK CONSTRAINT; Schema: importer; Owner: importer
--

ALTER TABLE ONLY importer.sv09_ingeschrevenen
    ADD CONSTRAINT sv09_nat_mapper_fk FOREIGN KEY (leidende_nationaliteit) REFERENCES public.nat_mapper(nation_omschr);


--
-- Name: sv09_ingeschrevenen sv09_studielinkvariantcode_fk; Type: FK CONSTRAINT; Schema: importer; Owner: importer
--

ALTER TABLE ONLY importer.sv09_ingeschrevenen
    ADD CONSTRAINT sv09_studielinkvariantcode_fk FOREIGN KEY (studielinkvariantcode) REFERENCES public.studieplan(studieplan);


--
-- Name: absence_reason absence_reason_fk1; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.absence_reason
    ADD CONSTRAINT absence_reason_fk1 FOREIGN KEY (act_id) REFERENCES public.activity(act_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: absence_reason absence_reason_fk2; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.absence_reason
    ADD CONSTRAINT absence_reason_fk2 FOREIGN KEY (snummer) REFERENCES public.student(snummer) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: assessment_commit ac_fk1; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.assessment_commit
    ADD CONSTRAINT ac_fk1 FOREIGN KEY (snummer) REFERENCES public.student(snummer) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: activity_participant act_part_fk1; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.activity_participant
    ADD CONSTRAINT act_part_fk1 FOREIGN KEY (snummer) REFERENCES public.student(snummer) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: activity_participant act_part_fk2; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.activity_participant
    ADD CONSTRAINT act_part_fk2 FOREIGN KEY (act_id) REFERENCES public.activity(act_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: activity activity_fk1; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.activity
    ADD CONSTRAINT activity_fk1 FOREIGN KEY (act_type) REFERENCES public.activity_type(act_type);


--
-- Name: activity activity_fk2; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.activity
    ADD CONSTRAINT activity_fk2 FOREIGN KEY (act_type) REFERENCES public.activity_type(act_type) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: activity activity_fk3; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.activity
    ADD CONSTRAINT activity_fk3 FOREIGN KEY (prjm_id) REFERENCES public.prj_milestone(prjm_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: activity_project activity_project_fk1; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.activity_project
    ADD CONSTRAINT activity_project_fk1 FOREIGN KEY (prj_id) REFERENCES public.project(prj_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: alt_email alt_email_fk1; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.alt_email
    ADD CONSTRAINT alt_email_fk1 FOREIGN KEY (snummer) REFERENCES public.student(snummer) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: any_query any_query_owner_fkey; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.any_query
    ADD CONSTRAINT any_query_owner_fkey FOREIGN KEY (owner) REFERENCES public.tutor(userid);


--
-- Name: assessment assess_crit; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.assessment
    ADD CONSTRAINT assess_crit FOREIGN KEY (criterium) REFERENCES public.base_criteria(criterium_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: assessment_commit assessment_commit_fk2; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.assessment_commit
    ADD CONSTRAINT assessment_commit_fk2 FOREIGN KEY (prjtg_id, snummer) REFERENCES public.prj_grp(prjtg_id, snummer) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: assessment assessment_prjtg_id_fk4; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.assessment
    ADD CONSTRAINT assessment_prjtg_id_fk4 FOREIGN KEY (prjtg_id) REFERENCES public.prj_tutor(prjtg_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: student_class classes_cluster_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.student_class
    ADD CONSTRAINT classes_cluster_fk FOREIGN KEY (class_cluster) REFERENCES public.class_cluster(class_cluster) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: student_class classes_owner; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.student_class
    ADD CONSTRAINT classes_owner FOREIGN KEY (owner) REFERENCES public.student(snummer);


--
-- Name: assessment contestant_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.assessment
    ADD CONSTRAINT contestant_fk FOREIGN KEY (contestant) REFERENCES public.student(snummer) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: critique_history critique_history_fk1; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.critique_history
    ADD CONSTRAINT critique_history_fk1 FOREIGN KEY (critique_id) REFERENCES public.document_critique(critique_id);


--
-- Name: document_critique critique_student_fk1; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.document_critique
    ADD CONSTRAINT critique_student_fk1 FOREIGN KEY (critiquer) REFERENCES public.student(snummer);


--
-- Name: diploma_dates diploma_dates_fk1; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.diploma_dates
    ADD CONSTRAINT diploma_dates_fk1 FOREIGN KEY (snummer) REFERENCES public.student(snummer);


--
-- Name: document_critique doc_dr_fk1; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.document_critique
    ADD CONSTRAINT doc_dr_fk1 FOREIGN KEY (doc_id) REFERENCES public.uploads(upload_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: document_author document_author_snummer_fkey; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.document_author
    ADD CONSTRAINT document_author_snummer_fkey FOREIGN KEY (snummer) REFERENCES public.student(snummer) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: document_author document_author_upload_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.document_author
    ADD CONSTRAINT document_author_upload_id_fkey FOREIGN KEY (upload_id) REFERENCES public.uploads(upload_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: downloaded downloaded_id_fk1; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.downloaded
    ADD CONSTRAINT downloaded_id_fk1 FOREIGN KEY (upload_id) REFERENCES public.uploads(upload_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: education_unit_description education_unit_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.education_unit_description
    ADD CONSTRAINT education_unit_fk FOREIGN KEY (education_unit_id, module_id) REFERENCES public.education_unit(education_unit_id, module_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: exam_event exam_event_examiner_fkey; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam_event
    ADD CONSTRAINT exam_event_examiner_fkey FOREIGN KEY (examiner) REFERENCES public.tutor(userid);


--
-- Name: exam_event exam_event_module_part_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam_event
    ADD CONSTRAINT exam_event_module_part_id_fkey FOREIGN KEY (module_part_id) REFERENCES public.module_part(module_part_id);


--
-- Name: exam exam_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam
    ADD CONSTRAINT exam_fk FOREIGN KEY (module_id, education_unit_id) REFERENCES public.education_unit(module_id, education_unit_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: learning_goal_exam_focus exam_focus_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.learning_goal_exam_focus
    ADD CONSTRAINT exam_focus_fk FOREIGN KEY (exam_focus_id) REFERENCES public.exam_focus(exam_focus_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: exam_focus_description exam_focus_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam_focus_description
    ADD CONSTRAINT exam_focus_id_fk FOREIGN KEY (exam_focus_id) REFERENCES public.exam_focus(exam_focus_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: exam_grades exam_grades_fk1; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam_grades
    ADD CONSTRAINT exam_grades_fk1 FOREIGN KEY (exam_event_id) REFERENCES public.exam_event(exam_event_id);


--
-- Name: exam_grades exam_grades_snummer_fkey; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam_grades
    ADD CONSTRAINT exam_grades_snummer_fkey FOREIGN KEY (snummer) REFERENCES public.student(snummer);


--
-- Name: exam_grades exam_grades_trans_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam_grades
    ADD CONSTRAINT exam_grades_trans_id_fkey FOREIGN KEY (trans_id) REFERENCES public.transaction(trans_id);


--
-- Name: exam_grading_aspect_description exam_grading_aspect_description_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam_grading_aspect_description
    ADD CONSTRAINT exam_grading_aspect_description_fk FOREIGN KEY (exam_grading_aspect_id) REFERENCES public.exam_grading_aspect(exam_grading_aspect_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: exam_grading_aspect_description exam_grading_aspect_description_fk2; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam_grading_aspect_description
    ADD CONSTRAINT exam_grading_aspect_description_fk2 FOREIGN KEY (language_id) REFERENCES public.language(language_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: exam exam_grading_aspect_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam
    ADD CONSTRAINT exam_grading_aspect_fk FOREIGN KEY (exam_grading_aspect_id) REFERENCES public.exam_grading_aspect(exam_grading_aspect_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: exam_grading_level_description exam_grading_level_description_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam_grading_level_description
    ADD CONSTRAINT exam_grading_level_description_fk FOREIGN KEY (exam_grading_level_id) REFERENCES public.exam_grading_level(exam_grading_level_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: exam_grading_level_description exam_grading_level_description_fk2; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam_grading_level_description
    ADD CONSTRAINT exam_grading_level_description_fk2 FOREIGN KEY (language_id) REFERENCES public.language(language_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: exam exam_grading_level_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam
    ADD CONSTRAINT exam_grading_level_fk FOREIGN KEY (exam_grading_level_id) REFERENCES public.exam_grading_level(exam_grading_level_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: exam_grading_type_description exam_grading_type_description_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam_grading_type_description
    ADD CONSTRAINT exam_grading_type_description_fk FOREIGN KEY (exam_grading_type_id) REFERENCES public.exam_grading_type(exam_grading_type_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: exam_grading_type_description exam_grading_type_description_fk2; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam_grading_type_description
    ADD CONSTRAINT exam_grading_type_description_fk2 FOREIGN KEY (language_id) REFERENCES public.language(language_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: exam exam_grading_type_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam
    ADD CONSTRAINT exam_grading_type_fk FOREIGN KEY (exam_grading_type_id) REFERENCES public.exam_grading_type(exam_grading_type_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: sebi_stick exam_stick_snummer_fkey; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.sebi_stick
    ADD CONSTRAINT exam_stick_snummer_fkey FOREIGN KEY (snummer) REFERENCES public.student(snummer);


--
-- Name: exam_type_description exam_type_description_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam_type_description
    ADD CONSTRAINT exam_type_description_fk FOREIGN KEY (exam_type_id) REFERENCES public.exam_type(exam_type_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: exam_type_description exam_type_description_fk2; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam_type_description
    ADD CONSTRAINT exam_type_description_fk2 FOREIGN KEY (language_id) REFERENCES public.language(language_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: exam exam_type_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam
    ADD CONSTRAINT exam_type_id_fk FOREIGN KEY (exam_type_id) REFERENCES public.exam_type(exam_type_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: github_id github_id_snummer_fkey; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.github_id
    ADD CONSTRAINT github_id_snummer_fkey FOREIGN KEY (snummer) REFERENCES public.student(snummer);


--
-- Name: grp_alias grp_alias_prjtg_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.grp_alias
    ADD CONSTRAINT grp_alias_prjtg_id_fk FOREIGN KEY (prjtg_id) REFERENCES public.prj_tutor(prjtg_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: hoofdgrp_map hoofdgrp_map_course_fkey; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.hoofdgrp_map
    ADD CONSTRAINT hoofdgrp_map_course_fkey FOREIGN KEY (course) REFERENCES public.fontys_course(course);


--
-- Name: assessment judge_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.assessment
    ADD CONSTRAINT judge_fk FOREIGN KEY (judge) REFERENCES public.student(snummer) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: student land_iso3166; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.student
    ADD CONSTRAINT land_iso3166 FOREIGN KEY (land) REFERENCES public.iso3166(a3) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: module_language language_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.module_language
    ADD CONSTRAINT language_id_fk FOREIGN KEY (language_id) REFERENCES public.language(language_id);


--
-- Name: exam_focus_description language_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.exam_focus_description
    ADD CONSTRAINT language_id_fk FOREIGN KEY (language_id) REFERENCES public.language(language_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: education_unit_description language_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.education_unit_description
    ADD CONSTRAINT language_id_fk FOREIGN KEY (module_id, language_id) REFERENCES public.module_language(module_id, language_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: learning_goal_exam_focus learning_goal_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.learning_goal_exam_focus
    ADD CONSTRAINT learning_goal_fk FOREIGN KEY (module_id, learning_goal_id) REFERENCES public.learning_goal(module_id, learning_goal_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: milestone_grade milestone_grade_prjm_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.milestone_grade
    ADD CONSTRAINT milestone_grade_prjm_id_fkey FOREIGN KEY (prjm_id) REFERENCES public.prj_milestone(prjm_id);


--
-- Name: milestone_grade milestone_grade_snummer_fkey; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.milestone_grade
    ADD CONSTRAINT milestone_grade_snummer_fkey FOREIGN KEY (snummer) REFERENCES public.student(snummer);


--
-- Name: module_activity_description module_activity_description_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.module_activity_description
    ADD CONSTRAINT module_activity_description_fk FOREIGN KEY (module_activity_id) REFERENCES public.module_activity(module_activity_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: module_activity_description module_activity_description_fk2; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.module_activity_description
    ADD CONSTRAINT module_activity_description_fk2 FOREIGN KEY (language_id) REFERENCES public.language(language_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: module_week_schedule module_activity_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.module_week_schedule
    ADD CONSTRAINT module_activity_id_fk FOREIGN KEY (module_activity_id) REFERENCES public.module_activity(module_activity_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: module_prerequisite module_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.module_prerequisite
    ADD CONSTRAINT module_id_fk FOREIGN KEY (module_id) REFERENCES public.module(module_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: module_language module_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.module_language
    ADD CONSTRAINT module_id_fk FOREIGN KEY (module_id) REFERENCES public.module(module_id);


--
-- Name: module_desciption_long module_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.module_desciption_long
    ADD CONSTRAINT module_id_fk FOREIGN KEY (module_id) REFERENCES public.module(module_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: learning_goal module_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.learning_goal
    ADD CONSTRAINT module_id_fk FOREIGN KEY (module_id) REFERENCES public.module(module_id);


--
-- Name: module_week_schedule module_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.module_week_schedule
    ADD CONSTRAINT module_id_fk FOREIGN KEY (module_id) REFERENCES public.module(module_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: module_resource module_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.module_resource
    ADD CONSTRAINT module_id_fk FOREIGN KEY (module_id) REFERENCES public.module(module_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: module_description_short module_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.module_description_short
    ADD CONSTRAINT module_id_fk FOREIGN KEY (module_id) REFERENCES public.module(module_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: education_unit module_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.education_unit
    ADD CONSTRAINT module_id_fk FOREIGN KEY (module_id) REFERENCES public.module(module_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: module_description_short module_language_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.module_description_short
    ADD CONSTRAINT module_language_fk FOREIGN KEY (module_id, language_id) REFERENCES public.module_language(module_id, language_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: learning_goal_description module_language_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.learning_goal_description
    ADD CONSTRAINT module_language_fk FOREIGN KEY (module_id, language_id) REFERENCES public.module_language(module_id, language_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: module_desciption_long module_language_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.module_desciption_long
    ADD CONSTRAINT module_language_id_fk FOREIGN KEY (module_id, language_id) REFERENCES public.module_language(module_id, language_id);


--
-- Name: module_part module_part_fk1; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.module_part
    ADD CONSTRAINT module_part_fk1 FOREIGN KEY (module_id) REFERENCES public.module(module_id);


--
-- Name: module_resource module_resource_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.module_resource
    ADD CONSTRAINT module_resource_id_fk FOREIGN KEY (module_resource_type_id) REFERENCES public.module_resource_type(module_resource_type_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: module_resource_type_description module_resource_type_description_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.module_resource_type_description
    ADD CONSTRAINT module_resource_type_description_fk FOREIGN KEY (module_resource_type_id) REFERENCES public.module_resource_type(module_resource_type_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: module_resource_type_description module_resource_type_description_fk2; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.module_resource_type_description
    ADD CONSTRAINT module_resource_type_description_fk2 FOREIGN KEY (language_id) REFERENCES public.language(language_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: module_topic_description module_topic_description_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.module_topic_description
    ADD CONSTRAINT module_topic_description_fk FOREIGN KEY (module_id, module_topic_id) REFERENCES public.module_topic(module_id, module_topic_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: module_topic_description module_topic_description_fk2; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.module_topic_description
    ADD CONSTRAINT module_topic_description_fk2 FOREIGN KEY (language_id) REFERENCES public.language(language_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: passwd password_fk1; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.passwd
    ADD CONSTRAINT password_fk1 FOREIGN KEY (userid) REFERENCES public.student(snummer) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: project_deliverables pd_fk3; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project_deliverables
    ADD CONSTRAINT pd_fk3 FOREIGN KEY (prjm_id) REFERENCES public.prj_milestone(prjm_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: personal_repos personal_repos_fk1; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.personal_repos
    ADD CONSTRAINT personal_repos_fk1 FOREIGN KEY (owner) REFERENCES public.student(snummer) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: project_tasks pr_tasks_fk2; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project_tasks
    ADD CONSTRAINT pr_tasks_fk2 FOREIGN KEY (snummer) REFERENCES public.student(snummer) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: module_prerequisite prerequisite_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.module_prerequisite
    ADD CONSTRAINT prerequisite_fk FOREIGN KEY (prerequisite) REFERENCES public.module(module_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: prj_contact prj_contact_fk1; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.prj_contact
    ADD CONSTRAINT prj_contact_fk1 FOREIGN KEY (prjtg_id) REFERENCES public.prj_tutor(prjtg_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: prj_contact prj_contact_prjtg_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.prj_contact
    ADD CONSTRAINT prj_contact_prjtg_id_fkey FOREIGN KEY (prjtg_id) REFERENCES public.prj_tutor(prjtg_id);


--
-- Name: prj_grp prj_grp_fk2; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.prj_grp
    ADD CONSTRAINT prj_grp_fk2 FOREIGN KEY (snummer) REFERENCES public.student(snummer) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: prj_grp prj_grp_fk3; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.prj_grp
    ADD CONSTRAINT prj_grp_fk3 FOREIGN KEY (prjtg_id) REFERENCES public.prj_tutor(prjtg_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: prj_milestone prj_m_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.prj_milestone
    ADD CONSTRAINT prj_m_fk FOREIGN KEY (prj_id) REFERENCES public.project(prj_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: project_roles prj_role_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project_roles
    ADD CONSTRAINT prj_role_fk FOREIGN KEY (prj_id) REFERENCES public.project(prj_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: prj_tutor prj_tutor_fk2; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.prj_tutor
    ADD CONSTRAINT prj_tutor_fk2 FOREIGN KEY (prjm_id) REFERENCES public.prj_milestone(prjm_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: prj_tutor prj_tutor_tutor_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.prj_tutor
    ADD CONSTRAINT prj_tutor_tutor_id_fkey FOREIGN KEY (tutor_id) REFERENCES public.tutor(userid) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: prjm_criterium prjm_criterium_fk1; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.prjm_criterium
    ADD CONSTRAINT prjm_criterium_fk1 FOREIGN KEY (prjm_id) REFERENCES public.prj_milestone(prjm_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: prjm_criterium prjm_criterium_fk2; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.prjm_criterium
    ADD CONSTRAINT prjm_criterium_fk2 FOREIGN KEY (criterium_id) REFERENCES public.base_criteria(criterium_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: project_attributes_def project_attributes_def_author_fkey; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project_attributes_def
    ADD CONSTRAINT project_attributes_def_author_fkey FOREIGN KEY (author) REFERENCES public.student(snummer);


--
-- Name: project_attributes_def project_attributes_def_prj_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project_attributes_def
    ADD CONSTRAINT project_attributes_def_prj_id_fkey FOREIGN KEY (prj_id) REFERENCES public.project(prj_id);


--
-- Name: project_attributes_values project_attributes_values_prjtg_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project_attributes_values
    ADD CONSTRAINT project_attributes_values_prjtg_id_fkey FOREIGN KEY (prjtg_id) REFERENCES public.prj_tutor(prjtg_id);


--
-- Name: project_attributes_values project_attributes_values_project_attributes_def_fkey; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project_attributes_values
    ADD CONSTRAINT project_attributes_values_project_attributes_def_fkey FOREIGN KEY (project_attributes_def) REFERENCES public.project_attributes_def(project_attributes_def);


--
-- Name: project_auditor project_auditor_fk1; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project_auditor
    ADD CONSTRAINT project_auditor_fk1 FOREIGN KEY (snummer) REFERENCES public.student(snummer);


--
-- Name: project_auditor project_auditor_fk2; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project_auditor
    ADD CONSTRAINT project_auditor_fk2 FOREIGN KEY (prjm_id) REFERENCES public.prj_milestone(prjm_id);


--
-- Name: project project_course_fkey; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project
    ADD CONSTRAINT project_course_fkey FOREIGN KEY (course) REFERENCES public.fontys_course(course);


--
-- Name: project project_owner_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project
    ADD CONSTRAINT project_owner_id_fkey FOREIGN KEY (owner_id) REFERENCES public.tutor(userid) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: project_scribe project_scribe_fk1; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project_scribe
    ADD CONSTRAINT project_scribe_fk1 FOREIGN KEY (scribe) REFERENCES public.student(snummer);


--
-- Name: project_scribe project_scribe_fk2; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project_scribe
    ADD CONSTRAINT project_scribe_fk2 FOREIGN KEY (prj_id) REFERENCES public.project(prj_id);


--
-- Name: project_task_completed project_task_completed_fk1; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project_task_completed
    ADD CONSTRAINT project_task_completed_fk1 FOREIGN KEY (snummer) REFERENCES public.student(snummer);


--
-- Name: project_task_completed project_task_completed_fk2; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project_task_completed
    ADD CONSTRAINT project_task_completed_fk2 FOREIGN KEY (task_id) REFERENCES public.project_task(task_id);


--
-- Name: project_task_completed project_task_completed_trans_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project_task_completed
    ADD CONSTRAINT project_task_completed_trans_id_fkey FOREIGN KEY (trans_id) REFERENCES public.transaction(trans_id);


--
-- Name: project_tasks project_task_fk1; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project_tasks
    ADD CONSTRAINT project_task_fk1 FOREIGN KEY (prj_id) REFERENCES public.project(prj_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: project_task project_task_fk1; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.project_task
    ADD CONSTRAINT project_task_fk1 FOREIGN KEY (prj_id) REFERENCES public.project(prj_id);


--
-- Name: recruiters_note recruiters_note_followup_fkey; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.recruiters_note
    ADD CONSTRAINT recruiters_note_followup_fkey FOREIGN KEY (followup) REFERENCES public.recruiters_note(recruiters_note_id);


--
-- Name: recruiters_note recruiters_note_trans_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.recruiters_note
    ADD CONSTRAINT recruiters_note_trans_id_fkey FOREIGN KEY (trans_id) REFERENCES public.transaction(trans_id);


--
-- Name: repositories repositories_prjtg_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.repositories
    ADD CONSTRAINT repositories_prjtg_id_fkey FOREIGN KEY (prjtg_id) REFERENCES public.prj_tutor(prjtg_id);


--
-- Name: student slb_student_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.student
    ADD CONSTRAINT slb_student_fk FOREIGN KEY (slb) REFERENCES public.student(snummer);


--
-- Name: student student_class_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.student
    ADD CONSTRAINT student_class_id_fkey FOREIGN KEY (class_id) REFERENCES public.student_class(class_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: student student_faculty_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.student
    ADD CONSTRAINT student_faculty_fk FOREIGN KEY (faculty_id) REFERENCES public.faculty(faculty_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: student student_language_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.student
    ADD CONSTRAINT student_language_fk FOREIGN KEY (lang) REFERENCES public.language(language_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: student student_opl_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.student
    ADD CONSTRAINT student_opl_fk FOREIGN KEY (opl) REFERENCES public.fontys_course(course);


--
-- Name: student_role student_role_fk2; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.student_role
    ADD CONSTRAINT student_role_fk2 FOREIGN KEY (prjm_id) REFERENCES public.prj_milestone(prjm_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: student student_slb_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.student
    ADD CONSTRAINT student_slb_fk FOREIGN KEY (slb) REFERENCES public.tutor(userid) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: student student_studieplan_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.student
    ADD CONSTRAINT student_studieplan_fk FOREIGN KEY (studieplan) REFERENCES public.studieplan(studieplan) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: studieplan studieplan_course_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.studieplan
    ADD CONSTRAINT studieplan_course_fk FOREIGN KEY (studieprogr) REFERENCES public.fontys_course(course);


--
-- Name: task_timer task_timer_fk1; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.task_timer
    ADD CONSTRAINT task_timer_fk1 FOREIGN KEY (prjm_id) REFERENCES public.prj_milestone(prjm_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: task_timer task_timer_fk2; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.task_timer
    ADD CONSTRAINT task_timer_fk2 FOREIGN KEY (snummer, prj_id, task_id) REFERENCES public.project_tasks(snummer, prj_id, task_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: task_timer task_timer_prjm_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.task_timer
    ADD CONSTRAINT task_timer_prjm_id_fkey FOREIGN KEY (prjm_id) REFERENCES public.prj_milestone(prjm_id);


--
-- Name: transaction transaction_operator_fkey; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.transaction
    ADD CONSTRAINT transaction_operator_fkey FOREIGN KEY (operator) REFERENCES public.student(snummer);


--
-- Name: tutor_class_cluster tutor_class_cluster_class_cluster_fkey; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.tutor_class_cluster
    ADD CONSTRAINT tutor_class_cluster_class_cluster_fkey FOREIGN KEY (class_cluster) REFERENCES public.class_cluster(class_cluster);


--
-- Name: tutor_class_cluster tutor_class_cluster_userid_fkey; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.tutor_class_cluster
    ADD CONSTRAINT tutor_class_cluster_userid_fkey FOREIGN KEY (userid) REFERENCES public.tutor(userid);


--
-- Name: tutor tutor_faculty_fk; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.tutor
    ADD CONSTRAINT tutor_faculty_fk FOREIGN KEY (faculty_id) REFERENCES public.faculty(faculty_id);


--
-- Name: tutor tutor_fk1; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.tutor
    ADD CONSTRAINT tutor_fk1 FOREIGN KEY (userid) REFERENCES public.student(snummer) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: unix_uid unix_uid_fk1; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.unix_uid
    ADD CONSTRAINT unix_uid_fk1 FOREIGN KEY (snummer) REFERENCES public.student(snummer) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: uploaddocumenttypes updt_fk1; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.uploaddocumenttypes
    ADD CONSTRAINT updt_fk1 FOREIGN KEY (prj_id) REFERENCES public.project(prj_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: uploads uploads_fk2; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.uploads
    ADD CONSTRAINT uploads_fk2 FOREIGN KEY (snummer) REFERENCES public.student(snummer) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: uploads uploads_fk3; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.uploads
    ADD CONSTRAINT uploads_fk3 FOREIGN KEY (prjm_id) REFERENCES public.prj_milestone(prjm_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: uploads uploads_fk4; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.uploads
    ADD CONSTRAINT uploads_fk4 FOREIGN KEY (prjm_id, doctype) REFERENCES public.project_deliverables(prjm_id, doctype);


--
-- Name: uploads uploads_fk5; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.uploads
    ADD CONSTRAINT uploads_fk5 FOREIGN KEY (prjtg_id) REFERENCES public.prj_tutor(prjtg_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: uploads uploads_prjtg_id_fk1; Type: FK CONSTRAINT; Schema: public; Owner: rpadmin
--

ALTER TABLE ONLY public.uploads
    ADD CONSTRAINT uploads_prjtg_id_fk1 FOREIGN KEY (prjtg_id) REFERENCES public.prj_tutor(prjtg_id);


--
-- Name: SCHEMA importer; Type: ACL; Schema: -; Owner: rpadmin
--

GRANT ALL ON SCHEMA importer TO peerweb;
GRANT ALL ON SCHEMA importer TO PUBLIC;


--
-- Name: TABLE grade_summer_result; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.grade_summer_result TO peerweb;


--
-- Name: TABLE assessment; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.assessment TO peerweb;


--
-- Name: TABLE blad1; Type: ACL; Schema: importer; Owner: rpadmin
--

GRANT ALL ON TABLE importer.blad1 TO PUBLIC;


--
-- Name: TABLE sv05_20190829; Type: ACL; Schema: importer; Owner: importer
--

GRANT ALL ON TABLE importer.sv05_20190829 TO PUBLIC;


--
-- Name: TABLE sv09_ingeschrevenen; Type: ACL; Schema: importer; Owner: importer
--

GRANT ALL ON TABLE importer.sv09_ingeschrevenen TO PUBLIC;


--
-- Name: TABLE worksheet; Type: ACL; Schema: importer; Owner: rpadmin
--

GRANT ALL ON TABLE importer.worksheet TO PUBLIC;


--
-- Name: TABLE absence_reason; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.absence_reason TO peerweb;


--
-- Name: TABLE activity_participant; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.activity_participant TO peerweb;


--
-- Name: TABLE act_part_count; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.act_part_count TO peerweb;


--
-- Name: TABLE activity; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.activity TO peerweb;


--
-- Name: TABLE grp_alias; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON TABLE public.grp_alias TO wwwrun;
GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.grp_alias TO peerweb;


--
-- Name: TABLE prj_grp; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.prj_grp TO peerweb;
GRANT SELECT ON TABLE public.prj_grp TO wwwrun;


--
-- Name: SEQUENCE prjm_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.prjm_id_seq TO peerweb;


--
-- Name: TABLE prj_milestone; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON TABLE public.prj_milestone TO wwwrun;
GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.prj_milestone TO peerweb;


--
-- Name: TABLE prj_tutor; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON TABLE public.prj_tutor TO wwwrun;
GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.prj_tutor TO peerweb;


--
-- Name: TABLE act_presence_list2; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.act_presence_list2 TO peerweb;


--
-- Name: TABLE fontys_course; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,REFERENCES ON TABLE public.fontys_course TO jager;
GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.fontys_course TO peerweb;
GRANT SELECT,REFERENCES ON TABLE public.fontys_course TO wwwrun;


--
-- Name: TABLE student; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT ON TABLE public.student TO jager;
GRANT SELECT ON TABLE public.student TO wwwrun;
GRANT SELECT,REFERENCES ON TABLE public.student TO importer;
GRANT ALL ON TABLE public.student TO peerweb;


--
-- Name: TABLE student_class; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.student_class TO peerweb;


--
-- Name: TABLE studieplan; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,REFERENCES ON TABLE public.studieplan TO jager;
GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.studieplan TO peerweb;
GRANT SELECT,REFERENCES ON TABLE public.studieplan TO wwwrun;
GRANT SELECT,REFERENCES ON TABLE public.studieplan TO importer;


--
-- Name: TABLE tutor; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.tutor TO peerweb;
GRANT SELECT ON TABLE public.tutor TO wwwrun;


--
-- Name: TABLE active_47; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,REFERENCES ON TABLE public.active_47 TO peerweb;
GRANT SELECT,REFERENCES ON TABLE public.active_47 TO wwwrun;


--
-- Name: SEQUENCE activity_act_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.activity_act_id_seq TO peerweb;


--
-- Name: TABLE activity_project; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.activity_project TO peerweb;


--
-- Name: TABLE activity_type; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.activity_type TO peerweb;


--
-- Name: TABLE additional_course; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.additional_course TO peerweb;


--
-- Name: TABLE additional_course_descr; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.additional_course_descr TO peerweb;


--
-- Name: TABLE alien_email; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.alien_email TO peerweb;


--
-- Name: TABLE alt_email; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.alt_email TO peerweb;


--
-- Name: TABLE alumni_email; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON TABLE public.alumni_email TO PUBLIC;


--
-- Name: TABLE project; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON TABLE public.project TO wwwrun;
GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.project TO peerweb;


--
-- Name: TABLE all_prj_tutor; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.all_prj_tutor TO peerweb;
GRANT SELECT,REFERENCES ON TABLE public.all_prj_tutor TO wwwrun;


--
-- Name: TABLE all_prj_tutor_y; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.all_prj_tutor_y TO peerweb;
GRANT SELECT,REFERENCES ON TABLE public.all_prj_tutor_y TO wwwrun;


--
-- Name: TABLE all_project_milestone; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON TABLE public.all_project_milestone TO PUBLIC;


--
-- Name: TABLE project_scribe; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.project_scribe TO peerweb;


--
-- Name: TABLE all_project_scribe; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.all_project_scribe TO peerweb;


--
-- Name: TABLE all_tab_columns; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.all_tab_columns TO peerweb;


--
-- Name: TABLE alumnus; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.alumnus TO peerweb;


--
-- Name: TABLE any_query; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.any_query TO peerweb;


--
-- Name: SEQUENCE any_query_any_query_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.any_query_any_query_id_seq TO peerweb;


--
-- Name: TABLE arbeitsaemterberatungstellen; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.arbeitsaemterberatungstellen TO peerweb;


--
-- Name: SEQUENCE arbeitsaemterberatungstellen__id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.arbeitsaemterberatungstellen__id_seq TO peerweb;


--
-- Name: TABLE assessment_remarks; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.assessment_remarks TO peerweb;


--
-- Name: SEQUENCE assessement_remark_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.assessement_remark_id_seq TO peerweb;


--
-- Name: TABLE base_criteria; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.base_criteria TO peerweb;


--
-- Name: TABLE prjm_criterium; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.prjm_criterium TO peerweb;


--
-- Name: TABLE criteria_v; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.criteria_v TO peerweb;


--
-- Name: TABLE assessment_builder3; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.assessment_builder3 TO peerweb;


--
-- Name: TABLE assessment_commit; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.assessment_commit TO peerweb;


--
-- Name: SEQUENCE assessment_commit_assessment_commit_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.assessment_commit_assessment_commit_id_seq TO peerweb;


--
-- Name: TABLE assessment_group_notready; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.assessment_group_notready TO peerweb;


--
-- Name: TABLE assessment_group_ready; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.assessment_group_ready TO peerweb;


--
-- Name: TABLE assessment_groups; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.assessment_groups TO peerweb;


--
-- Name: TABLE assessment_groups2; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.assessment_groups2 TO peerweb;


--
-- Name: TABLE assessment_grp_open; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.assessment_grp_open TO peerweb;


--
-- Name: TABLE assessment_grp_open2; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.assessment_grp_open2 TO peerweb;


--
-- Name: TABLE assessment_milestones; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.assessment_milestones TO peerweb;


--
-- Name: TABLE assessment_projects; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.assessment_projects TO peerweb;


--
-- Name: TABLE assessment_remarks_view; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.assessment_remarks_view TO peerweb;


--
-- Name: TABLE assessment_tr; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.assessment_tr TO peerweb;


--
-- Name: TABLE assessment_zero_count; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.assessment_zero_count TO peerweb;


--
-- Name: TABLE auth_grp_members; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.auth_grp_members TO peerweb;


--
-- Name: TABLE uploads; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.uploads TO peerweb;


--
-- Name: TABLE author_grp; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.author_grp TO peerweb;


--
-- Name: TABLE author_grp_members; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.author_grp_members TO peerweb;


--
-- Name: TABLE available_assessment; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.available_assessment TO peerweb;


--
-- Name: TABLE available_assessment_grp_contestant; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.available_assessment_grp_contestant TO peerweb;


--
-- Name: TABLE available_assessment_grp_judge; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.available_assessment_grp_judge TO peerweb;


--
-- Name: TABLE grp_size2; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.grp_size2 TO peerweb;


--
-- Name: TABLE judge_ready_count; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.judge_ready_count TO peerweb;


--
-- Name: TABLE barchart_view; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.barchart_view TO peerweb;


--
-- Name: TABLE berufskollegs; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.berufskollegs TO peerweb;


--
-- Name: SEQUENCE berufskollegs__id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.berufskollegs__id_seq TO peerweb;


--
-- Name: TABLE bigface_settings; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON TABLE public.bigface_settings TO peerweb;


--
-- Name: TABLE faculty; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.faculty TO peerweb;


--
-- Name: TABLE registered_mphotos; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.registered_mphotos TO peerweb;


--
-- Name: TABLE bigface_view; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON TABLE public.bigface_view TO peerweb;


--
-- Name: TABLE birthdays; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.birthdays TO peerweb;


--
-- Name: TABLE class_cluster; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.class_cluster TO peerweb;


--
-- Name: SEQUENCE class_cluster_class_cluster_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.class_cluster_class_cluster_seq TO peerweb;


--
-- Name: TABLE class_selector; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.class_selector TO peerweb;


--
-- Name: TABLE class_size; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.class_size TO peerweb;


--
-- Name: SEQUENCE classes_class_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.classes_class_id_seq TO peerweb;


--
-- Name: SEQUENCE colloquium_speakers_colloquium_speaker_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.colloquium_speakers_colloquium_speaker_id_seq TO peerweb;


--
-- Name: TABLE contestant_assessment; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON TABLE public.contestant_assessment TO peerweb;


--
-- Name: TABLE contestant_crit_avg; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.contestant_crit_avg TO peerweb;


--
-- Name: TABLE contestant_sum; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.contestant_sum TO peerweb;


--
-- Name: TABLE course_week; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.course_week TO peerweb;


--
-- Name: TABLE timetableweek; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.timetableweek TO peerweb;


--
-- Name: TABLE course_hours; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.course_hours TO peerweb;


--
-- Name: TABLE criteria_pm; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.criteria_pm TO peerweb;


--
-- Name: SEQUENCE criterium_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.criterium_id_seq TO peerweb;


--
-- Name: TABLE critique_history; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.critique_history TO peerweb;


--
-- Name: SEQUENCE critique_history_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.critique_history_id_seq TO peerweb;


--
-- Name: TABLE current_student_class; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.current_student_class TO peerweb;


--
-- Name: TABLE davinci_leden1; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT ON TABLE public.davinci_leden1 TO peerweb;


--
-- Name: TABLE diploma_dates; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.diploma_dates TO peerweb;


--
-- Name: SEQUENCE doc_critique_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.doc_critique_seq TO peerweb;


--
-- Name: SEQUENCE doctype_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.doctype_id_seq TO peerweb;


--
-- Name: TABLE doctype_upload_group_count; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.doctype_upload_group_count TO peerweb;


--
-- Name: TABLE document_audience; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.document_audience TO peerweb;


--
-- Name: TABLE document_author; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.document_author TO peerweb;


--
-- Name: SEQUENCE document_author_document_author_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.document_author_document_author_id_seq TO peerweb;


--
-- Name: TABLE document_critique; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.document_critique TO peerweb;


--
-- Name: TABLE document_critique_count; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.document_critique_count TO peerweb;


--
-- Name: TABLE project_deliverables; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.project_deliverables TO peerweb;


--
-- Name: TABLE uploaddocumenttypes; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.uploaddocumenttypes TO peerweb;


--
-- Name: TABLE document_data3; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.document_data3 TO peerweb;


--
-- Name: TABLE document_projects; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.document_projects TO peerweb;


--
-- Name: SEQUENCE dossier_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.dossier_id_seq TO peerweb;


--
-- Name: TABLE downloaded; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.downloaded TO peerweb;


--
-- Name: TABLE education_unit; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.education_unit TO peerweb;


--
-- Name: TABLE education_unit_description; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.education_unit_description TO peerweb;


--
-- Name: TABLE email_signature; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.email_signature TO peerweb;


--
-- Name: TABLE enumeraties; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.enumeraties TO peerweb;


--
-- Name: SEQUENCE enumeraties_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.enumeraties_id_seq TO peerweb;


--
-- Name: TABLE exam; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.exam TO peerweb;


--
-- Name: TABLE exam_account; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.exam_account TO peerweb;


--
-- Name: TABLE exam_event; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON TABLE public.exam_event TO peerweb;


--
-- Name: SEQUENCE exam_event_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.exam_event_id_seq TO peerweb;


--
-- Name: SEQUENCE exam_exam_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.exam_exam_id_seq TO peerweb;


--
-- Name: TABLE exam_focus; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.exam_focus TO peerweb;


--
-- Name: TABLE exam_focus_description; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.exam_focus_description TO peerweb;


--
-- Name: TABLE exam_grades; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON TABLE public.exam_grades TO peerweb;


--
-- Name: SEQUENCE exam_grades_exam_grade_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,USAGE ON SEQUENCE public.exam_grades_exam_grade_id_seq TO peerweb;


--
-- Name: TABLE exam_grading_aspect; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.exam_grading_aspect TO peerweb;


--
-- Name: TABLE exam_grading_aspect_description; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.exam_grading_aspect_description TO peerweb;


--
-- Name: TABLE exam_grading_level; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.exam_grading_level TO peerweb;


--
-- Name: TABLE exam_grading_level_description; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.exam_grading_level_description TO peerweb;


--
-- Name: TABLE exam_grading_type; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.exam_grading_type TO peerweb;


--
-- Name: TABLE exam_grading_type_description; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.exam_grading_type_description TO peerweb;


--
-- Name: TABLE module_part; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.module_part TO peerweb;


--
-- Name: TABLE exam_result_view; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,REFERENCES ON TABLE public.exam_result_view TO peerweb;


--
-- Name: TABLE exam_type; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.exam_type TO peerweb;


--
-- Name: TABLE exam_type_description; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.exam_type_description TO peerweb;


--
-- Name: TABLE examlist; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.examlist TO peerweb;


--
-- Name: TABLE fake_mail_address; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.fake_mail_address TO peerweb;


--
-- Name: TABLE fixed_contestant; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.fixed_contestant TO peerweb;


--
-- Name: TABLE judge_sum; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.judge_sum TO peerweb;


--
-- Name: TABLE fixed_judge; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.fixed_judge TO peerweb;


--
-- Name: TABLE fixed_student; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.fixed_student TO peerweb;


--
-- Name: TABLE fixed_student2; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.fixed_student2 TO peerweb;


--
-- Name: TABLE foto_prefix; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.foto_prefix TO peerweb;


--
-- Name: TABLE foto; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.foto TO peerweb;


--
-- Name: TABLE passwd; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT ON TABLE public.passwd TO jager;
GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.passwd TO peerweb;
GRANT SELECT ON TABLE public.passwd TO wwwrun;


--
-- Name: TABLE git_password; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,REFERENCES ON TABLE public.git_password TO wwwrun;


--
-- Name: TABLE git_project_users; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,REFERENCES ON TABLE public.git_project_users TO wwwrun;


--
-- Name: TABLE github_id; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON TABLE public.github_id TO peerweb;


--
-- Name: TABLE grp_alias_builder; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.grp_alias_builder TO peerweb;


--
-- Name: TABLE grp_alias_tr; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.grp_alias_tr TO peerweb;


--
-- Name: TABLE grp_average; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.grp_average TO peerweb;


--
-- Name: TABLE grp_average2; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.grp_average2 TO peerweb;


--
-- Name: TABLE grp_crit_avg; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.grp_crit_avg TO peerweb;


--
-- Name: TABLE grp_detail; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.grp_detail TO peerweb;


--
-- Name: TABLE grp_details; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.grp_details TO peerweb;


--
-- Name: TABLE grp_overall_average; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.grp_overall_average TO peerweb;


--
-- Name: TABLE grp_size; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.grp_size TO peerweb;


--
-- Name: TABLE grp_tg_size; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.grp_tg_size TO peerweb;


--
-- Name: TABLE grp_upload_count; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.grp_upload_count TO peerweb;


--
-- Name: TABLE grp_upload_count2; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.grp_upload_count2 TO peerweb;


--
-- Name: TABLE guest_users; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.guest_users TO peerweb;


--
-- Name: TABLE has_uploads; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.has_uploads TO peerweb;


--
-- Name: TABLE hoofdgrp; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.hoofdgrp TO peerweb;


--
-- Name: TABLE hoofdgrp_s; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.hoofdgrp_s TO peerweb;


--
-- Name: TABLE hoofdgrp_size; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.hoofdgrp_size TO peerweb;


--
-- Name: TABLE nat_mapper; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.nat_mapper TO peerweb;
GRANT SELECT,REFERENCES ON TABLE public.nat_mapper TO importer;


--
-- Name: SEQUENCE inchecked__id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.inchecked__id_seq TO peerweb;


--
-- Name: TABLE iso3166; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT ON TABLE public.iso3166 TO PUBLIC;
GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.iso3166 TO peerweb;
GRANT SELECT,REFERENCES ON TABLE public.iso3166 TO importer;


--
-- Name: TABLE jagers; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.jagers TO peerweb;


--
-- Name: SEQUENCE jagers__id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.jagers__id_seq TO peerweb;


--
-- Name: TABLE judge_assessment; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON TABLE public.judge_assessment TO peerweb;


--
-- Name: TABLE judge_crit_avg; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.judge_crit_avg TO peerweb;


--
-- Name: TABLE judge_grade_count; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.judge_grade_count TO peerweb;


--
-- Name: TABLE judge_grade_count2; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.judge_grade_count2 TO peerweb;


--
-- Name: TABLE judge_grp_avg; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.judge_grp_avg TO peerweb;


--
-- Name: TABLE judge_notready; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.judge_notready TO peerweb;


--
-- Name: TABLE language; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.language TO peerweb;


--
-- Name: TABLE last_assessment_commit; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.last_assessment_commit TO peerweb;


--
-- Name: TABLE learning_goal; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.learning_goal TO peerweb;


--
-- Name: TABLE learning_goal_description; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.learning_goal_description TO peerweb;


--
-- Name: TABLE learning_goal_exam_focus; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.learning_goal_exam_focus TO peerweb;


--
-- Name: TABLE lime_token; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.lime_token TO peerweb;


--
-- Name: TABLE logon; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.logon TO peerweb;


--
-- Name: TABLE logoff; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.logoff TO peerweb;


--
-- Name: SEQUENCE logoff_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.logoff_id_seq TO peerweb;


--
-- Name: SEQUENCE logon_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.logon_id_seq TO peerweb;


--
-- Name: TABLE logon_map_on_timetable; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.logon_map_on_timetable TO peerweb;


--
-- Name: TABLE lpi_id; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.lpi_id TO peerweb;


--
-- Name: TABLE menu; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.menu TO peerweb;


--
-- Name: SEQUENCE menu_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.menu_id_seq TO peerweb;


--
-- Name: TABLE menu_item; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.menu_item TO peerweb;


--
-- Name: TABLE menu_item_display; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.menu_item_display TO peerweb;


--
-- Name: TABLE menu_option_queries; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.menu_option_queries TO peerweb;


--
-- Name: TABLE menu_item_defs; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.menu_item_defs TO peerweb;


--
-- Name: SEQUENCE menu_item_display_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.menu_item_display_id_seq TO peerweb;


--
-- Name: SEQUENCE menu_item_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.menu_item_id_seq TO peerweb;


--
-- Name: SEQUENCE menu_option_queries_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.menu_option_queries_id_seq TO peerweb;


--
-- Name: TABLE milestone_grade; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.milestone_grade TO peerweb;


--
-- Name: SEQUENCE milestone_grade_milestone_grade_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.milestone_grade_milestone_grade_id_seq TO peerweb;


--
-- Name: TABLE milestone_grp; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.milestone_grp TO peerweb;


--
-- Name: TABLE milestone_open_past_due; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.milestone_open_past_due TO peerweb;


--
-- Name: TABLE registered_photos; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.registered_photos TO peerweb;


--
-- Name: TABLE portrait; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.portrait TO peerweb;


--
-- Name: TABLE minifoto; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON TABLE public.minifoto TO peerweb;


--
-- Name: TABLE minikiosk_visits; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON TABLE public.minikiosk_visits TO wwwrun;


--
-- Name: TABLE module; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.module TO peerweb;


--
-- Name: TABLE module_activity; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.module_activity TO peerweb;


--
-- Name: TABLE module_activity_description; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.module_activity_description TO peerweb;


--
-- Name: TABLE module_desciption_long; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.module_desciption_long TO peerweb;


--
-- Name: TABLE module_description_short; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.module_description_short TO peerweb;


--
-- Name: SEQUENCE module_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.module_id_seq TO peerweb;


--
-- Name: TABLE module_language; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.module_language TO peerweb;


--
-- Name: SEQUENCE module_part_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.module_part_seq TO peerweb;


--
-- Name: TABLE module_participant; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.module_participant TO peerweb;


--
-- Name: TABLE module_participant_hours; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.module_participant_hours TO peerweb;


--
-- Name: TABLE module_prerequisite; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.module_prerequisite TO peerweb;


--
-- Name: TABLE module_resource; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.module_resource TO peerweb;


--
-- Name: TABLE module_resource_type; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.module_resource_type TO peerweb;


--
-- Name: TABLE module_resource_type_description; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.module_resource_type_description TO peerweb;


--
-- Name: TABLE module_topic; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.module_topic TO peerweb;


--
-- Name: TABLE module_topic_description; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.module_topic_description TO peerweb;


--
-- Name: TABLE module_week_schedule; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.module_week_schedule TO peerweb;


--
-- Name: TABLE movable_student; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.movable_student TO peerweb;


--
-- Name: TABLE my_peer_results_2; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.my_peer_results_2 TO peerweb;


--
-- Name: TABLE repositories; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON TABLE public.repositories TO wwwrun;
GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.repositories TO peerweb;


--
-- Name: TABLE my_project_repositories; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.my_project_repositories TO peerweb;


--
-- Name: SEQUENCE nat_mapper_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.nat_mapper_id_seq TO peerweb;


--
-- Name: TABLE nationality; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.nationality TO peerweb;


--
-- Name: TABLE naw; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.naw TO peerweb;


--
-- Name: TABLE pa20181030; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT ON TABLE public.pa20181030 TO PUBLIC;


--
-- Name: TABLE page_help; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.page_help TO peerweb;


--
-- Name: SEQUENCE page_help_help_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.page_help_help_id_seq TO peerweb;


--
-- Name: TABLE participant_present_list; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.participant_present_list TO peerweb;


--
-- Name: TABLE password_request; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.password_request TO peerweb;


--
-- Name: SEQUENCE password_request_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.password_request_id_seq TO peerweb;


--
-- Name: TABLE peer_settings; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.peer_settings TO peerweb;


--
-- Name: TABLE personal_repos; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.personal_repos TO peerweb;
GRANT SELECT,UPDATE ON TABLE public.personal_repos TO wwwrun;


--
-- Name: SEQUENCE personal_repos_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.personal_repos_id_seq TO peerweb;


--
-- Name: TABLE portrait_with_name; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.portrait_with_name TO peerweb;
GRANT SELECT ON TABLE public.portrait_with_name TO wwwrun;


--
-- Name: TABLE weekdays; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.weekdays TO peerweb;


--
-- Name: TABLE present_anywhere; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.present_anywhere TO peerweb;


--
-- Name: TABLE present_at_fontys; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.present_at_fontys TO peerweb;


--
-- Name: TABLE present_in_coursehours; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.present_in_coursehours TO peerweb;


--
-- Name: TABLE present_in_courseweek; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.present_in_courseweek TO peerweb;


--
-- Name: TABLE prj_contact; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.prj_contact TO peerweb;


--
-- Name: TABLE prj_grp_builder2; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.prj_grp_builder2 TO peerweb;


--
-- Name: TABLE prj_grp_email; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.prj_grp_email TO peerweb;


--
-- Name: TABLE prj_grp_email_g0; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.prj_grp_email_g0 TO peerweb;


--
-- Name: TABLE prj_grp_open; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.prj_grp_open TO peerweb;


--
-- Name: TABLE prj_grp_ready; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.prj_grp_ready TO peerweb;


--
-- Name: TABLE prj_grp_tr; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.prj_grp_tr TO peerweb;


--
-- Name: TABLE prj_tutor_builder; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON TABLE public.prj_tutor_builder TO peerweb;


--
-- Name: TABLE prj_tutor_email; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.prj_tutor_email TO peerweb;


--
-- Name: SEQUENCE prj_tutor_prjtg_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.prj_tutor_prjtg_id_seq TO peerweb;


--
-- Name: TABLE prj_tutor_tr; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.prj_tutor_tr TO peerweb;


--
-- Name: TABLE prjm_activity_count; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.prjm_activity_count TO peerweb;


--
-- Name: TABLE prjm_size; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.prjm_size TO peerweb;


--
-- Name: TABLE prjtg_size; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.prjtg_size TO peerweb;


--
-- Name: TABLE project_attributes_def; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.project_attributes_def TO peerweb;


--
-- Name: SEQUENCE project_attributes_def_project_attributes_def_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.project_attributes_def_project_attributes_def_seq TO peerweb;


--
-- Name: TABLE project_attributes_values; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.project_attributes_values TO peerweb;


--
-- Name: SEQUENCE project_attributes_values_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.project_attributes_values_id_seq TO peerweb;


--
-- Name: TABLE project_auditor; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.project_auditor TO peerweb;


--
-- Name: SEQUENCE project_auditor_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.project_auditor_id_seq TO peerweb;


--
-- Name: SEQUENCE project_deliverables_pdeliverable_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.project_deliverables_pdeliverable_id_seq TO peerweb;


--
-- Name: TABLE project_deliverables_tr; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.project_deliverables_tr TO peerweb;


--
-- Name: TABLE project_grade_weight_sum_product; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.project_grade_weight_sum_product TO peerweb;


--
-- Name: TABLE project_group; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.project_group TO peerweb;
GRANT SELECT ON TABLE public.project_group TO wwwrun;


--
-- Name: TABLE project_grp_stakeholders; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.project_grp_stakeholders TO peerweb;


--
-- Name: TABLE project_member; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.project_member TO peerweb;


--
-- Name: SEQUENCE project_prj_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.project_prj_id_seq TO peerweb;


--
-- Name: TABLE project_roles; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.project_roles TO peerweb;


--
-- Name: SEQUENCE project_scribe_project_scribe_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.project_scribe_project_scribe_id_seq TO peerweb;


--
-- Name: TABLE project_task; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.project_task TO peerweb;


--
-- Name: TABLE project_task_completed; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.project_task_completed TO peerweb;


--
-- Name: TABLE project_task_completed_max_trans; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.project_task_completed_max_trans TO peerweb;


--
-- Name: TABLE project_task_completed_latest; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.project_task_completed_latest TO peerweb;


--
-- Name: SEQUENCE project_task_task_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.project_task_task_id_seq TO peerweb;


--
-- Name: TABLE project_tasks; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.project_tasks TO peerweb;


--
-- Name: TABLE project_tutor_owner; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.project_tutor_owner TO peerweb;


--
-- Name: TABLE project_weight_sum; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.project_weight_sum TO peerweb;


--
-- Name: TABLE prospects; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON TABLE public.prospects TO PUBLIC;


--
-- Name: TABLE prospect_portrait; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON TABLE public.prospect_portrait TO PUBLIC;


--
-- Name: TABLE ready_judge_count; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.ready_judge_count TO peerweb;


--
-- Name: TABLE recruiters_note; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.recruiters_note TO peerweb;


--
-- Name: SEQUENCE recruiters_note_recruiters_note_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.recruiters_note_recruiters_note_id_seq TO peerweb;


--
-- Name: TABLE repos_group_name; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON TABLE public.repos_group_name TO wwwrun;
GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.repos_group_name TO peerweb;


--
-- Name: SEQUENCE repositories_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.repositories_id_seq TO peerweb;
GRANT ALL ON SEQUENCE public.repositories_id_seq TO wwwrun;


--
-- Name: TABLE sebi_stick; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,REFERENCES ON TABLE public.sebi_stick TO peerweb;
GRANT SELECT,REFERENCES ON TABLE public.sebi_stick TO wwwrun;


--
-- Name: SEQUENCE unix_uid_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.unix_uid_seq TO peerweb;
GRANT ALL ON SEQUENCE public.unix_uid_seq TO wwwrun;


--
-- Name: TABLE unix_uid; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON TABLE public.unix_uid TO wwwrun;
GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.unix_uid TO peerweb;


--
-- Name: TABLE semester; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.semester TO peerweb;


--
-- Name: SEQUENCE semester_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.semester_seq TO peerweb;


--
-- Name: TABLE session_data; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.session_data TO peerweb;


--
-- Name: TABLE should_close_group_tutor; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.should_close_group_tutor TO peerweb;


--
-- Name: TABLE should_close_prj_milestone; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.should_close_prj_milestone TO peerweb;


--
-- Name: TABLE simple_group_member; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,REFERENCES ON TABLE public.simple_group_member TO wwwrun;


--
-- Name: TABLE slb_projects; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.slb_projects TO peerweb;


--
-- Name: TABLE statsvn_names; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.statsvn_names TO peerweb;


--
-- Name: TABLE stdresult; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.stdresult TO peerweb;


--
-- Name: TABLE stdresult2; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.stdresult2 TO peerweb;


--
-- Name: TABLE stdresult_overall; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.stdresult_overall TO peerweb;


--
-- Name: TABLE student_class_name; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.student_class_name TO peerweb;


--
-- Name: TABLE student_class_size; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.student_class_size TO peerweb;


--
-- Name: TABLE student_class_v; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON TABLE public.student_class_v TO peerweb;


--
-- Name: TABLE student_email; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON TABLE public.student_email TO peerweb;


--
-- Name: TABLE student_latin1; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.student_latin1 TO peerweb;


--
-- Name: TABLE student_name_email; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.student_name_email TO peerweb;


--
-- Name: TABLE student_plus; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.student_plus TO peerweb;


--
-- Name: TABLE student_project_attributes; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.student_project_attributes TO peerweb;


--
-- Name: TABLE student_role; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.student_role TO peerweb;


--
-- Name: TABLE student_short; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.student_short TO peerweb;


--
-- Name: TABLE student_upload_count; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.student_upload_count TO peerweb;


--
-- Name: TABLE studie_prog; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.studie_prog TO peerweb;


--
-- Name: TABLE sv09_import_summary; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON TABLE public.sv09_import_summary TO PUBLIC;


--
-- Name: TABLE svn_auditor; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,REFERENCES ON TABLE public.svn_auditor TO wwwrun;


--
-- Name: TABLE svn_groep; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,REFERENCES ON TABLE public.svn_groep TO wwwrun;


--
-- Name: TABLE svn_group; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.svn_group TO peerweb;
GRANT ALL ON TABLE public.svn_group TO wwwrun;


--
-- Name: TABLE svn_grp; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,REFERENCES ON TABLE public.svn_grp TO wwwrun;


--
-- Name: TABLE svn_guests; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.svn_guests TO peerweb;


--
-- Name: TABLE svn_progress; Type: ACL; Schema: public; Owner: hom
--

GRANT SELECT,REFERENCES ON TABLE public.svn_progress TO PUBLIC;


--
-- Name: TABLE svn_tutor; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,REFERENCES ON TABLE public.svn_tutor TO wwwrun;


--
-- Name: TABLE svn_tutor_snummer; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.svn_tutor_snummer TO peerweb;


--
-- Name: TABLE svn_users; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.svn_users TO peerweb;
GRANT SELECT,REFERENCES ON TABLE public.svn_users TO wwwrun;


--
-- Name: TABLE task_timer; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.task_timer TO peerweb;


--
-- Name: TABLE task_timer_anywhere; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.task_timer_anywhere TO peerweb;


--
-- Name: TABLE task_timer_at_fontys; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.task_timer_at_fontys TO peerweb;


--
-- Name: TABLE task_timer_group_sum; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.task_timer_group_sum TO peerweb;


--
-- Name: TABLE task_timer_grp_total; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.task_timer_grp_total TO peerweb;


--
-- Name: SEQUENCE task_timer_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.task_timer_id_seq TO peerweb;


--
-- Name: TABLE task_timer_project_sum; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.task_timer_project_sum TO peerweb;


--
-- Name: TABLE task_timer_sum; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.task_timer_sum TO peerweb;


--
-- Name: TABLE task_timer_week; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.task_timer_week TO peerweb;


--
-- Name: TABLE task_timer_year_month; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.task_timer_year_month TO peerweb;


--
-- Name: SEQUENCE teller; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.teller TO peerweb;


--
-- Name: TABLE tiny_portrait; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.tiny_portrait TO peerweb;


--
-- Name: TABLE trac_init_data; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.trac_init_data TO peerweb;
GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.trac_init_data TO wwwrun;


--
-- Name: TABLE trac_user_pass; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.trac_user_pass TO peerweb;
GRANT SELECT ON TABLE public.trac_user_pass TO wwwrun;


--
-- Name: TABLE tracusers; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.tracusers TO peerweb;


--
-- Name: TABLE transaction; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.transaction TO peerweb;


--
-- Name: TABLE transaction_operator; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.transaction_operator TO peerweb;


--
-- Name: SEQUENCE transaction_trans_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.transaction_trans_id_seq TO peerweb;


--
-- Name: TABLE tutor_class_cluster; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON TABLE public.tutor_class_cluster TO peerweb;


--
-- Name: TABLE tutor_data; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.tutor_data TO peerweb;


--
-- Name: TABLE tutor_join_student; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.tutor_join_student TO peerweb;


--
-- Name: TABLE tutor_snummer; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.tutor_snummer TO peerweb;


--
-- Name: TABLE tutor_upload_count; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.tutor_upload_count TO peerweb;


--
-- Name: TABLE uilang; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.uilang TO peerweb;


--
-- Name: TABLE upload_archive_names; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.upload_archive_names TO peerweb;


--
-- Name: TABLE upload_count; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.upload_count TO peerweb;


--
-- Name: TABLE upload_group_count; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.upload_group_count TO peerweb;


--
-- Name: SEQUENCE upload_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.upload_id_seq TO peerweb;


--
-- Name: TABLE upload_mime_types; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.upload_mime_types TO peerweb;


--
-- Name: TABLE upload_rename; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.upload_rename TO peerweb;


--
-- Name: TABLE used_criteria; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.used_criteria TO peerweb;


--
-- Name: SEQUENCE validator_map_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.validator_map_seq TO peerweb;


--
-- Name: TABLE validator_map; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.validator_map TO peerweb;


--
-- Name: TABLE validator_occurrences; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.validator_occurrences TO peerweb;


--
-- Name: SEQUENCE validator_occurrences_id_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.validator_occurrences_id_seq TO peerweb;


--
-- Name: SEQUENCE validator_occurrences_seq; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT ALL ON SEQUENCE public.validator_occurrences_seq TO peerweb;


--
-- Name: TABLE validator_regex; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.validator_regex TO peerweb;


--
-- Name: TABLE validator_regex_map; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.validator_regex_map TO peerweb;


--
-- Name: TABLE validator_regex_slashed; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.validator_regex_slashed TO peerweb;


--
-- Name: TABLE viewabledocument; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.viewabledocument TO peerweb;


--
-- Name: TABLE web_access_by_group; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,REFERENCES ON TABLE public.web_access_by_group TO peerweb;
GRANT SELECT,REFERENCES ON TABLE public.web_access_by_group TO wwwrun;


--
-- Name: TABLE web_access_by_project; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,REFERENCES ON TABLE public.web_access_by_project TO peerweb;
GRANT SELECT,REFERENCES ON TABLE public.web_access_by_project TO wwwrun;


--
-- Name: TABLE web_authentification; Type: ACL; Schema: public; Owner: rpadmin
--

GRANT SELECT,INSERT,REFERENCES,DELETE,TRIGGER,UPDATE ON TABLE public.web_authentification TO peerweb;
GRANT SELECT,REFERENCES ON TABLE public.web_authentification TO jenkins;
GRANT SELECT ON TABLE public.web_authentification TO wwwrun;


--
-- PostgreSQL database dump complete
--

