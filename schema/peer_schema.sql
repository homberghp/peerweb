--
-- PostgreSQL database dump
--

SET statement_timeout = 0;
SET lock_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


SET search_path = public, pg_catalog;

--
-- Name: armor(bytea); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION armor(bytea) RETURNS text
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pg_armor';


SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: grade_summer_result; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE grade_summer_result (
    prjtg_id integer NOT NULL,
    snummer integer NOT NULL,
    criterium integer[] NOT NULL,
    multiplier numeric[],
    grade numeric[]
);


--
-- Name: TABLE grade_summer_result; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE grade_summer_result IS 'result type for function assessment_grade_set';


--
-- Name: assessment_grade_set(integer, numeric); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION assessment_grade_set(grp integer, prod numeric) RETURNS SETOF grade_summer_result
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


--
-- Name: assessment; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE assessment (
    contestant integer NOT NULL,
    judge integer NOT NULL,
    criterium smallint NOT NULL,
    grade smallint,
    prjtg_id integer NOT NULL
);


--
-- Name: TABLE assessment; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE assessment IS 'core table. assessement grade raw data';


--
-- Name: COLUMN assessment.contestant; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN assessment.contestant IS 'the one being graded.';


--
-- Name: COLUMN assessment.judge; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN assessment.judge IS 'the one giving grades.';


--
-- Name: assessmentbuild(integer, smallint); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION assessmentbuild(integer, smallint) RETURNS assessment
    LANGUAGE sql
    AS $_$ select $1 as prj_id, $2 as milestone, contestant,judge,criterium,grade ,grp_num from build_assessment$_$;


--
-- Name: snummer; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE snummer (
    snummer integer NOT NULL
);


--
-- Name: TABLE snummer; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE snummer IS 'result type for authorized_document.';


--
-- Name: authorized_document(integer); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION authorized_document(doc_id_in integer) RETURNS SETOF snummer
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


--
-- Name: FUNCTION authorized_document(doc_id_in integer); Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON FUNCTION authorized_document(doc_id_in integer) IS 'set of users allowed to read document';


--
-- Name: crypt(text, text); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION crypt(text, text) RETURNS text
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pg_crypt';


--
-- Name: dearmor(text); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION dearmor(text) RETURNS bytea
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pg_dearmor';


--
-- Name: decrypt(bytea, bytea, text); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION decrypt(bytea, bytea, text) RETURNS bytea
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pg_decrypt';


--
-- Name: decrypt_iv(bytea, bytea, bytea, text); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION decrypt_iv(bytea, bytea, bytea, text) RETURNS bytea
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pg_decrypt_iv';


--
-- Name: digest(bytea, text); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION digest(bytea, text) RETURNS bytea
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pg_digest';


--
-- Name: digest(text, text); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION digest(text, text) RETURNS bytea
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pg_digest';


--
-- Name: email_to_href(text); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION email_to_href(inp text) RETURNS text
    LANGUAGE plpgsql
    AS $$ 
       begin
          return '<a href="mailto:'||trim(inp)||'">'||trim(inp)||'</a>'; 
      end;
$$;


--
-- Name: encrypt(bytea, bytea, text); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION encrypt(bytea, bytea, text) RETURNS bytea
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pg_encrypt';


--
-- Name: encrypt_iv(bytea, bytea, bytea, text); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION encrypt_iv(bytea, bytea, bytea, text) RETURNS bytea
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pg_encrypt_iv';


--
-- Name: gen_random_bytes(integer); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION gen_random_bytes(integer) RETURNS bytea
    LANGUAGE c STRICT
    AS '$libdir/pgcrypto', 'pg_random_bytes';


--
-- Name: gen_salt(text); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION gen_salt(text) RETURNS text
    LANGUAGE c STRICT
    AS '$libdir/pgcrypto', 'pg_gen_salt';


--
-- Name: gen_salt(text, integer); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION gen_salt(text, integer) RETURNS text
    LANGUAGE c STRICT
    AS '$libdir/pgcrypto', 'pg_gen_salt_rounds';


--
-- Name: getdocauthors(integer); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION getdocauthors(up_id integer) RETURNS text
    LANGUAGE plpgsql
    AS $$
	DECLARE 
	auth text;
	reslt text;
	sep text;
	BEGIN
	reslt := '';
	sep   := '';
	for auth in  select roepnaam||coalesce(' '||voorvoegsel||' ',' ')||achternaam||' ('||snummer||')' as coauthor
	  from student join document_author using(snummer) where upload_id = up_id order by achternaam loop
	  reslt := reslt||sep||auth;
	  sep :=', ';
	end loop;
	return reslt;
	END;
$$;


--
-- Name: hmac(bytea, bytea, text); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION hmac(bytea, bytea, text) RETURNS bytea
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pg_hmac';


--
-- Name: hmac(text, text, text); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION hmac(text, text, text) RETURNS bytea
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pg_hmac';


--
-- Name: insert_student_email(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION insert_student_email() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
       BEGIN 
         INSERT INTO student (snummer, achternaam, voorvoegsel, voorletters, roepnaam,
  	 	 straat, huisnr, pcode, plaats, email1, nationaliteit, 
  	 	 hoofdgrp, active, cohort, gebdat, sex, lang, pcn, 
		 opl, phone_home, phone_gsm, phone_postaddress, 
	 	 faculty_id, slb, studieplan, 
	 	 geboorteplaats, geboorteland, voornaam, class_id)
  	 VALUES (new.snummer, new.achternaam, new.voorvoegsel, new.voorletters, new.roepnaam,
  	 	new.straat, new.huisnr, new.pcode, new.plaats, new.email1, new.nationaliteit, 
  	 	new.hoofdgrp, new.active, new.cohort, new.gebdat, new.sex, new.lang, new.pcn,
	 	new.opl, new.phone_home, new.phone_gsm, new.phone_postaddress,
	 	new.faculty_id, new.slb, new.studieplan,
	 	new.geboorteplaats, new.geboorteland, new.voornaam, new.class_id
	 );
	INSERT INTO alt_email (snummer, email2) 
  	SELECT new.snummer,new.email2
        WHERE (new.email2 IS NOT NULL);
	return NEW; -- TO SIGNAL SUCCESS
END;
$$;


--
-- Name: interval_to_hms(interval); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION interval_to_hms(interval) RETURNS text
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


--
-- Name: iscribe(integer); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION iscribe(peerid integer) RETURNS TABLE(prjm_idv integer)
    LANGUAGE plpgsql
    AS $$
begin 
      return query
      select prjm_id from prj_milestone join project using (prj_id) where peerid=owner_id
      union
      select prjm_id from prj_tutor where peerid=tutor_id
      union
      select prjm_id from project_scribe join prj_milestone using(prj_id) where peerid=scribe;
end
  $$;


--
-- Name: peer_password(text); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION peer_password(text) RETURNS text
    LANGUAGE c STRICT
    AS 'peer_password', 'peer_password';


--
-- Name: pgp_key_id(bytea); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION pgp_key_id(bytea) RETURNS text
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pgp_key_id_w';


--
-- Name: pgp_pub_decrypt(bytea, bytea); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION pgp_pub_decrypt(bytea, bytea) RETURNS text
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pgp_pub_decrypt_text';


--
-- Name: pgp_pub_decrypt(bytea, bytea, text); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION pgp_pub_decrypt(bytea, bytea, text) RETURNS text
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pgp_pub_decrypt_text';


--
-- Name: pgp_pub_decrypt(bytea, bytea, text, text); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION pgp_pub_decrypt(bytea, bytea, text, text) RETURNS text
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pgp_pub_decrypt_text';


--
-- Name: pgp_pub_decrypt_bytea(bytea, bytea); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION pgp_pub_decrypt_bytea(bytea, bytea) RETURNS bytea
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pgp_pub_decrypt_bytea';


--
-- Name: pgp_pub_decrypt_bytea(bytea, bytea, text); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION pgp_pub_decrypt_bytea(bytea, bytea, text) RETURNS bytea
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pgp_pub_decrypt_bytea';


--
-- Name: pgp_pub_decrypt_bytea(bytea, bytea, text, text); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION pgp_pub_decrypt_bytea(bytea, bytea, text, text) RETURNS bytea
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pgp_pub_decrypt_bytea';


--
-- Name: pgp_pub_encrypt(text, bytea); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION pgp_pub_encrypt(text, bytea) RETURNS bytea
    LANGUAGE c STRICT
    AS '$libdir/pgcrypto', 'pgp_pub_encrypt_text';


--
-- Name: pgp_pub_encrypt(text, bytea, text); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION pgp_pub_encrypt(text, bytea, text) RETURNS bytea
    LANGUAGE c STRICT
    AS '$libdir/pgcrypto', 'pgp_pub_encrypt_text';


--
-- Name: pgp_pub_encrypt_bytea(bytea, bytea); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION pgp_pub_encrypt_bytea(bytea, bytea) RETURNS bytea
    LANGUAGE c STRICT
    AS '$libdir/pgcrypto', 'pgp_pub_encrypt_bytea';


--
-- Name: pgp_pub_encrypt_bytea(bytea, bytea, text); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION pgp_pub_encrypt_bytea(bytea, bytea, text) RETURNS bytea
    LANGUAGE c STRICT
    AS '$libdir/pgcrypto', 'pgp_pub_encrypt_bytea';


--
-- Name: pgp_sym_decrypt(bytea, text); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION pgp_sym_decrypt(bytea, text) RETURNS text
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pgp_sym_decrypt_text';


--
-- Name: pgp_sym_decrypt(bytea, text, text); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION pgp_sym_decrypt(bytea, text, text) RETURNS text
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pgp_sym_decrypt_text';


--
-- Name: pgp_sym_decrypt_bytea(bytea, text); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION pgp_sym_decrypt_bytea(bytea, text) RETURNS bytea
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pgp_sym_decrypt_bytea';


--
-- Name: pgp_sym_decrypt_bytea(bytea, text, text); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION pgp_sym_decrypt_bytea(bytea, text, text) RETURNS bytea
    LANGUAGE c IMMUTABLE STRICT
    AS '$libdir/pgcrypto', 'pgp_sym_decrypt_bytea';


--
-- Name: pgp_sym_encrypt(text, text); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION pgp_sym_encrypt(text, text) RETURNS bytea
    LANGUAGE c STRICT
    AS '$libdir/pgcrypto', 'pgp_sym_encrypt_text';


--
-- Name: pgp_sym_encrypt(text, text, text); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION pgp_sym_encrypt(text, text, text) RETURNS bytea
    LANGUAGE c STRICT
    AS '$libdir/pgcrypto', 'pgp_sym_encrypt_text';


--
-- Name: pgp_sym_encrypt_bytea(bytea, text); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION pgp_sym_encrypt_bytea(bytea, text) RETURNS bytea
    LANGUAGE c STRICT
    AS '$libdir/pgcrypto', 'pgp_sym_encrypt_bytea';


--
-- Name: pgp_sym_encrypt_bytea(bytea, text, text); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION pgp_sym_encrypt_bytea(bytea, text, text) RETURNS bytea
    LANGUAGE c STRICT
    AS '$libdir/pgcrypto', 'pgp_sym_encrypt_bytea';


--
-- Name: plpgsql_call_handler(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION plpgsql_call_handler() RETURNS language_handler
    LANGUAGE c
    AS '$libdir/plpgsql', 'plpgsql_call_handler';


--
-- Name: sclass_selector; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE sclass_selector (
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


--
-- Name: TABLE sclass_selector; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE sclass_selector IS 'result type for sclass_selector(userid)';


--
-- Name: sclass_selector(integer); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION sclass_selector(vuserid integer) RETURNS SETOF sclass_selector
    LANGUAGE plpgsql
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


--
-- Name: tutor_my_project_milestones(integer); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION tutor_my_project_milestones(peer_id integer) RETURNS TABLE(prjm_id integer, role_def text)
    LANGUAGE sql
    AS $$
	select distinct prjm_id, case when peer_id=owner_id then 'owner' else 'tutor' end as role from prj_milestone pm join project p using(prj_id) join prj_tutor pt using(prjm_id) where peer_id=p.owner_id or peer_id=pt.tutor_id 
$$;


--
-- Name: tutor_selector; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE tutor_selector (
    opl bigint,
    faculty_id smallint,
    mine smallint,
    namegrp text,
    name text,
    tutor character varying(5),
    userid integer
);


--
-- Name: TABLE tutor_selector; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE tutor_selector IS 'result type for tutor_selector(userid)';


--
-- Name: tutor_selector(integer); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION tutor_selector(vuserid integer) RETURNS SETOF tutor_selector
    LANGUAGE plpgsql
    AS $$
begin return query
select s.opl,s.faculty_id,
       case
       when me.opl=s.opl and me.faculty_id=s.faculty_id then 0::smallint
       when me.opl<>s.opl and me.faculty_id=s.faculty_id then 1::smallint
       else 2::smallint end as mine,
       faculty_short||'-'||course_short as namegrp,
       achternaam||', '||roepnaam||coalesce(' '||voorvoegsel,'')||' ['||tutor||': '||t.userid||']' as name,tutor,userid
       from tutor t
       join student s on(t.userid=s.snummer)
       join faculty f on (f.faculty_id=s.faculty_id)
       join fontys_course c on(s.opl=c.course)
       cross join (select opl,faculty_id
       from student where snummer=vuserid) me
;
end
$$;


--
-- Name: aanmelding20140827; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE aanmelding20140827 (
    peildatum date,
    aanmelddatum date,
    instroom character(15),
    studiejaar integer,
    instituutcode integer,
    instituutnaam character varying(50),
    studentnummer integer,
    achternaam character varying(30),
    voorvoegsels character(10),
    voorletters character(10),
    voornamen character varying(50),
    roepnaam character(20),
    volledige_naam character varying(30),
    sex character(3),
    geboortedatum date,
    geboorteplaats character varying(40),
    geboorteland_lang character varying(40),
    email_prive character varying(50),
    e_mail_instelling character varying(50),
    land_nummer_mobiel integer,
    mobiel_nummer bigint,
    land_nummer_vast integer,
    vast_nummer bigint,
    pcn_nummer integer,
    studielinknummer integer,
    volledig_adres character varying(50),
    postcode_en_plaats character varying(40),
    land_lang character varying(30),
    nationaliteit_1 character varying(50),
    nationaliteit_2 character varying(50),
    leidende_nationaliteit character varying(50),
    inschrijvingid integer,
    isatcode integer,
    opleiding character varying(50),
    opleidingnaamvoluit character varying(70),
    studielinkvariantcode integer,
    variant_omschrijving character varying(40),
    lesplaats character(10),
    vorm character(10),
    fase character varying(40),
    soort character(15),
    aanmeldingstatus character varying(30),
    datum_definitief_ingeschreven date,
    datum_annulering date,
    start_in_1e_jaar character(10),
    bijvakker character(10),
    datum_aankomst_fontys date,
    datum_aankomst_instituut date,
    datum_aankomst_opleiding date,
    indicatie_collegegeld character varying(30),
    pasfoto_uploaddatum date,
    voorkeurstaal character(15),
    exchange_kenmerk text,
    hoofdgrp character(10),
    postcode character(10),
    woonplaats character varying(40),
    phone_home character varying(40),
    phone_gsm character varying(40),
    phone_postaddress character varying(40),
    faculty_id smallint DEFAULT 47,
    active boolean DEFAULT true,
    slb integer,
    straat character varying(30),
    huisnr character(4),
    nationaliteit character(3),
    lang character(2),
    opl bigint,
    studieplan integer,
    class_id integer,
    land character(3),
    geboorteland character(3)
);


--
-- Name: absence_reason; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE absence_reason (
    act_id integer NOT NULL,
    snummer integer NOT NULL,
    reason text NOT NULL
);


--
-- Name: TABLE absence_reason; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE absence_reason IS 'absence in activity, if any reason, like sickness.';


SET default_with_oids = true;

--
-- Name: activity_participant; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE activity_participant (
    act_id integer NOT NULL,
    snummer integer NOT NULL,
    presence character(1) DEFAULT 'P'::bpchar
);


--
-- Name: TABLE activity_participant; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE activity_participant IS 'Participants to activity or event (e.g. collo).
';


--
-- Name: act_part_count; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW act_part_count AS
 SELECT activity_participant.act_id,
    count(*) AS count
   FROM activity_participant
  GROUP BY activity_participant.act_id;


--
-- Name: VIEW act_part_count; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW act_part_count IS 'count participants in activity. For reporting.';


--
-- Name: activity; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE activity (
    act_id integer NOT NULL,
    datum date DEFAULT now(),
    short character varying(30) DEFAULT 'Unnamed activity'::character varying,
    description text DEFAULT 'description will follow'::text,
    act_type smallint DEFAULT 0,
    part smallint DEFAULT 1 NOT NULL,
    start_time time without time zone DEFAULT '08:45:00'::time without time zone,
    prjm_id integer
);


--
-- Name: TABLE activity; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE activity IS 'Presence registration for colloquium, practicum etc.';


--
-- Name: grp_alias; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE grp_alias (
    long_name character varying(40),
    alias character(15),
    website character varying(128),
    productname character varying(128) DEFAULT ''::character varying,
    prjtg_id integer NOT NULL,
    youtube_link character varying(128),
    youtube_icon_url character varying(128)
);


--
-- Name: TABLE grp_alias; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE grp_alias IS 'aliases for groups that have one';


SET default_with_oids = false;

--
-- Name: prj_grp; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE prj_grp (
    snummer integer NOT NULL,
    prj_grp_open boolean DEFAULT false,
    written boolean DEFAULT false,
    prjtg_id integer NOT NULL
);


--
-- Name: TABLE prj_grp; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE prj_grp IS 'group is student in project-milestone and tutor.';


--
-- Name: prjm_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE prjm_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: prj_milestone; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE prj_milestone (
    prj_id integer NOT NULL,
    milestone smallint DEFAULT 1 NOT NULL,
    prj_milestone_open boolean DEFAULT false,
    assessment_due date DEFAULT (('now'::text)::date + 28),
    prjm_id integer DEFAULT nextval('prjm_id_seq'::regclass) NOT NULL,
    weight integer DEFAULT 1,
    milestone_name character varying(20) DEFAULT 'Milestone'::character varying,
    has_assessment boolean DEFAULT false
);


--
-- Name: TABLE prj_milestone; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE prj_milestone IS 'project milstone with due date';


--
-- Name: prj_tutor; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE prj_tutor (
    grp_num smallint DEFAULT 1 NOT NULL,
    prjm_id integer NOT NULL,
    prjtg_id integer NOT NULL,
    prj_tutor_open boolean DEFAULT false NOT NULL,
    assessment_complete boolean DEFAULT false NOT NULL,
    tutor_grade numeric(3,1) DEFAULT 7.0,
    tutor_id integer,
    grp_name character varying(15)
);


--
-- Name: TABLE prj_tutor; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE prj_tutor IS 'group tutor, defines group name, group number.';


--
-- Name: act_presence_list2; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW act_presence_list2 AS
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
   FROM ((((((prj_grp cand
     JOIN prj_tutor pt ON ((cand.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
     LEFT JOIN grp_alias ga ON ((ga.prjtg_id = pt.prjtg_id)))
     JOIN activity act ON ((pm.prjm_id = act.prjm_id)))
     LEFT JOIN activity_participant ap ON (((cand.snummer = ap.snummer) AND (act.act_id = ap.act_id))))
     LEFT JOIN absence_reason ar ON (((ap.snummer = ar.snummer) AND (ar.act_id = ap.act_id))));


--
-- Name: VIEW act_presence_list2; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW act_presence_list2 IS 'creates activity presence list';


--
-- Name: activity_act_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE activity_act_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: activity_act_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE activity_act_id_seq OWNED BY activity.act_id;


SET default_with_oids = true;

--
-- Name: activity_project; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE activity_project (
    prj_id integer NOT NULL
);


--
-- Name: TABLE activity_project; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE activity_project IS 'List the projects that have activities for which precense must be recorded.';


--
-- Name: activity_type; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE activity_type (
    act_type smallint NOT NULL,
    act_type_descr character varying(30)
);


--
-- Name: TABLE activity_type; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE activity_type IS 'type of recorded activtiy, like collo, excursion, practicum. ';


--
-- Name: additional_course; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE additional_course (
    snummer integer NOT NULL,
    course_code bigint NOT NULL
);


--
-- Name: TABLE additional_course; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE additional_course IS 'For students that follow more than  one course (eg ipo+wtb or LenE and TV).';


--
-- Name: fontys_course; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE fontys_course (
    course bigint NOT NULL,
    course_description character varying(64),
    faculty_id smallint,
    course_short character(4)
);


--
-- Name: TABLE fontys_course; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE fontys_course IS 'Fontys courses known by peerweb. A course is same to dutch  ''curriculum'' .
';


--
-- Name: additional_course_descr; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW additional_course_descr AS
 SELECT additional_course.snummer,
    additional_course.course_code,
    fontys_course.course,
    fontys_course.course_description,
    fontys_course.faculty_id AS institute,
    fontys_course.course_short AS abre
   FROM (additional_course
     JOIN fontys_course ON ((additional_course.course_code = fontys_course.course)));


--
-- Name: VIEW additional_course_descr; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW additional_course_descr IS 'describes additional course student is registered to';


SET default_with_oids = false;

--
-- Name: student; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE student (
    snummer integer NOT NULL,
    achternaam character varying(40),
    voorvoegsel character varying(10),
    voorletters character varying(10),
    roepnaam character varying(20),
    straat character varying(40),
    huisnr character(4),
    pcode character(7),
    plaats character varying(40),
    email1 character varying(50),
    nationaliteit character(2) DEFAULT 'NL'::bpchar,
    cohort smallint DEFAULT date_part('year'::text, (now())::date) NOT NULL,
    gebdat date,
    sex character(1) DEFAULT 'M'::bpchar,
    lang character(2) DEFAULT 'NL'::bpchar,
    pcn integer,
    opl bigint DEFAULT 0,
    phone_home character varying(40),
    phone_gsm character varying(40),
    phone_postaddress character varying(40),
    faculty_id smallint DEFAULT 0,
    hoofdgrp character(10) DEFAULT 'NEW'::bpchar,
    active boolean DEFAULT false,
    slb integer,
    land character(3) DEFAULT 'NLD'::bpchar,
    studieplan integer,
    geboorteplaats character varying(40),
    geboorteland character(3),
    voornaam character varying(40),
    class_id integer DEFAULT 0
);


--
-- Name: TABLE student; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE student IS 'user data e.g. n.a.r.';


--
-- Name: COLUMN student.snummer; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN student.snummer IS 'Student nummer';


--
-- Name: COLUMN student.achternaam; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN student.achternaam IS 'naam';


--
-- Name: COLUMN student.voorvoegsel; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN student.voorvoegsel IS 'naam zoals van den';


--
-- Name: COLUMN student.voorletters; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN student.voorletters IS 'naam';


--
-- Name: COLUMN student.roepnaam; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN student.roepnaam IS 'naam';


--
-- Name: COLUMN student.email1; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN student.email1 IS 'fontys email adres';


--
-- Name: COLUMN student.nationaliteit; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN student.nationaliteit IS 'nationaliteit, NL, DE of bijv PL BE etc';


--
-- Name: COLUMN student.cohort; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN student.cohort IS 'jaar van binnenkomst';


--
-- Name: COLUMN student.gebdat; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN student.gebdat IS 'geboortedatum, voor toegang';


--
-- Name: COLUMN student.lang; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN student.lang IS 'taal, NL,DE of EN';


--
-- Name: COLUMN student.pcn; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN student.pcn IS 'fontys pcn';


SET default_with_oids = true;

--
-- Name: student_class; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE student_class (
    sort1 integer DEFAULT 1,
    sort2 integer DEFAULT 1,
    comment text DEFAULT 'XXX'::text,
    faculty_id smallint DEFAULT 0,
    class_id integer NOT NULL,
    sclass character(10),
    class_cluster integer DEFAULT 0,
    owner integer DEFAULT 0
);


--
-- Name: TABLE student_class; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE student_class IS 'classes used in student selection';


--
-- Name: unix_uid_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE unix_uid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


SET default_with_oids = false;

--
-- Name: unix_uid; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE unix_uid (
    snummer integer,
    uid integer DEFAULT nextval('unix_uid_seq'::regclass) NOT NULL,
    gid integer DEFAULT 10001,
    username text NOT NULL
);


--
-- Name: TABLE unix_uid; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE unix_uid IS 'Unix uid for linux etc. Not actively used on 20130712.';


--
-- Name: ads_data3; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW ads_data3 AS
 SELECT student.snummer,
    unix_uid.username,
    peer_password('B@b11B@b1'::text) AS password,
    unix_uid.uid,
    unix_uid.gid,
    student.achternaam,
    student.roepnaam,
    student.voorvoegsel,
    fontys_course.course_short AS opl,
    student.cohort,
    btrim(lower((student.email1)::text)) AS email1,
    COALESCE(student.pcn, 0) AS pcn,
    classes.sclass,
    student.lang,
    btrim((student.hoofdgrp)::text) AS hoofdgrp
   FROM (((student
     JOIN fontys_course ON ((student.opl = fontys_course.course)))
     JOIN unix_uid USING (snummer))
     JOIN student_class classes USING (class_id));


--
-- Name: VIEW ads_data3; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW ads_data3 IS 'for authentication generation. Currently used for performance assessments.
';


--
-- Name: aldaview; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW aldaview AS
 SELECT 1 AS milestone,
    ('/home/svn/2013/alda/g'::text || prj_tutor.grp_num) AS repospath,
    ('alda g'::text || prj_tutor.grp_num) AS description,
    false AS isroot,
    ('/svn/2013/alda/g'::text || prj_tutor.grp_num) AS url_tail,
    prj_tutor.tutor_id AS owner,
    prj_tutor.grp_num,
    prj_tutor.prjm_id,
    prj_tutor.prjtg_id
   FROM prj_tutor
  WHERE (prj_tutor.prjm_id = 535);


--
-- Name: alien_email; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW alien_email AS
 SELECT student.snummer
   FROM student
  WHERE (((student.email1)::text !~~ '%fontys.nl'::text) AND (student.hoofdgrp !~~ 'ALU%'::text));


--
-- Name: VIEW alien_email; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW alien_email IS 'email not fitting the fontys mold, except alumni';


--
-- Name: project; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE project (
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


--
-- Name: TABLE project; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE project IS 'project data';


--
-- Name: tutor; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE tutor (
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


--
-- Name: TABLE tutor; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE tutor IS 'users with tutor role and some attributes';


--
-- Name: all_prj_tutor; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW all_prj_tutor AS
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
   FROM (((((prj_tutor
     JOIN prj_milestone USING (prjm_id))
     JOIN project USING (prj_id))
     JOIN tutor t ON ((t.userid = prj_tutor.tutor_id)))
     JOIN tutor tt ON ((tt.userid = project.owner_id)))
     LEFT JOIN grp_alias USING (prjtg_id));


--
-- Name: VIEW all_prj_tutor; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW all_prj_tutor IS 'all from prj_tutor (by prjtg_id) up to project';


--
-- Name: all_prj_tutor_y; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW all_prj_tutor_y AS
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
   FROM ((((prj_tutor
     JOIN tutor t ON ((prj_tutor.tutor_id = t.userid)))
     JOIN prj_milestone USING (prjm_id))
     JOIN project USING (prj_id))
     JOIN tutor tt ON ((project.owner_id = tt.userid)));


--
-- Name: project_scribe; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE project_scribe (
    prj_id integer NOT NULL,
    scribe integer NOT NULL,
    project_scribe_id integer NOT NULL
);


--
-- Name: TABLE project_scribe; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE project_scribe IS 'records per project who may record task grades and presence list entries.';


--
-- Name: all_project_scribe; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW all_project_scribe AS
 SELECT ps.prj_id,
    ps.scribe
   FROM project_scribe ps
UNION
 SELECT pm.prj_id,
    pt.tutor_id AS scribe
   FROM (prj_milestone pm
     JOIN prj_tutor pt ON ((pm.prjm_id = pt.prjm_id)));


--
-- Name: VIEW all_project_scribe; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW all_project_scribe IS 'tutors and scribes can update presence and tasks';


--
-- Name: all_tab_columns; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW all_tab_columns AS
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
  WHERE (((tab.oid = col.attrelid) AND (typ.oid = col.atttypid)) AND (col.attnum > 0));


--
-- Name: VIEW all_tab_columns; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW all_tab_columns IS 'describes details for relations; is used in ste (simple table editor)';


SET default_with_oids = true;

--
-- Name: alt_email; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE alt_email (
    snummer integer NOT NULL,
    email2 character varying(50),
    email3 character varying(60),
    CONSTRAINT emails_distinct CHECK (((email2)::text <> (email3)::text))
);


--
-- Name: TABLE alt_email; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE alt_email IS 'alternate email addresses';


--
-- Name: alu_student_mail; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW alu_student_mail AS
 SELECT student.snummer
   FROM (student
     JOIN alt_email USING (snummer))
  WHERE ((((student.email1)::text ~~ '%student.fontys.nl%'::text) AND ((alt_email.email2)::text !~~ '%student.fontys.nl%'::text)) AND (student.class_id = 363));


--
-- Name: alumnus; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW alumnus AS
 SELECT s.snummer
   FROM (student s
     JOIN student_class c USING (class_id))
  WHERE (c.sclass ~~ ('ALUMN%'::bpchar)::text);


--
-- Name: VIEW alumnus; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW alumnus IS 'alumni are in student_class ALUMNI';


SET default_with_oids = false;

--
-- Name: any_query; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE any_query (
    any_query_id integer NOT NULL,
    owner integer,
    query_name character varying(30),
    query_comment text,
    query text,
    active boolean DEFAULT true
);


--
-- Name: TABLE any_query; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE any_query IS 'Saved queries. Not operational on 20130712.';


--
-- Name: any_query_any_query_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE any_query_any_query_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: any_query_any_query_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE any_query_any_query_id_seq OWNED BY any_query.any_query_id;


--
-- Name: arbeitsaemterberatungstellen; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE arbeitsaemterberatungstellen (
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


--
-- Name: TABLE arbeitsaemterberatungstellen; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE arbeitsaemterberatungstellen IS 'For recruitement.';


--
-- Name: arbeitsaemterberatungstellen__id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE arbeitsaemterberatungstellen__id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: arbeitsaemterberatungstellen__id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE arbeitsaemterberatungstellen__id_seq OWNED BY arbeitsaemterberatungstellen._id;


--
-- Name: assessment_remarks; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE assessment_remarks (
    contestant integer NOT NULL,
    judge integer NOT NULL,
    prjtg_id integer NOT NULL,
    remark text NOT NULL,
    id integer NOT NULL
);


--
-- Name: assessement_remark_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE assessement_remark_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: assessement_remark_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE assessement_remark_id_seq OWNED BY assessment_remarks.id;


--
-- Name: base_criteria; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE base_criteria (
    criterium_id smallint DEFAULT nextval(('criterium_id_seq'::text)::regclass) NOT NULL,
    author integer,
    nl_short character varying(80),
    de_short character varying(80),
    en_short character varying(80),
    nl character varying(200),
    de character varying(200),
    en character varying(200)
);


--
-- Name: TABLE base_criteria; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE base_criteria IS 'Reusable set of criteria in 3 languages.';


--
-- Name: prjm_criterium; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE prjm_criterium (
    prjm_id integer NOT NULL,
    criterium_id smallint NOT NULL
);


--
-- Name: TABLE prjm_criterium; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE prjm_criterium IS 'selected criterium for assessment in project/mil.';


--
-- Name: criteria_v; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW criteria_v AS
 SELECT prjm_criterium.prjm_id,
    prjm_criterium.criterium_id AS criterium,
    base_criteria.nl_short,
    base_criteria.de_short,
    base_criteria.en_short,
    base_criteria.nl,
    base_criteria.de,
    base_criteria.en
   FROM (prjm_criterium
     JOIN base_criteria USING (criterium_id));


--
-- Name: assessment_builder3; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW assessment_builder3 AS
 SELECT c.snummer AS contestant,
    j.snummer AS judge,
    cr.criterium,
    0 AS grade,
    j.prjtg_id,
    pt.prjm_id
   FROM (((prj_grp j
     JOIN prj_grp c USING (prjtg_id))
     JOIN prj_tutor pt USING (prjtg_id))
     JOIN criteria_v cr ON ((pt.prjm_id = cr.prjm_id)))
  WHERE (j.snummer <> c.snummer);


--
-- Name: assessment_commit; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE assessment_commit (
    snummer integer NOT NULL,
    commit_time timestamp without time zone NOT NULL,
    prjtg_id integer NOT NULL,
    assessment_commit_id integer NOT NULL
);


--
-- Name: TABLE assessment_commit; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE assessment_commit IS 'to keep track of the commits by filling in the forms';


--
-- Name: assessment_commit_assessment_commit_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE assessment_commit_assessment_commit_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: assessment_commit_assessment_commit_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE assessment_commit_assessment_commit_id_seq OWNED BY assessment_commit.assessment_commit_id;


--
-- Name: assessment_group_notready; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW assessment_group_notready AS
 SELECT DISTINCT assessment.prjtg_id
   FROM assessment
  WHERE (assessment.grade = 0)
  GROUP BY assessment.prjtg_id;


--
-- Name: VIEW assessment_group_notready; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW assessment_group_notready IS 'lists prjtg_id where exists grade =0 (not graded)';


--
-- Name: assessment_group_ready; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW assessment_group_ready AS
 SELECT pm.prj_id,
    pm.milestone,
    pt.grp_num
   FROM ((( SELECT DISTINCT assessment.prjtg_id
           FROM assessment
          WHERE (NOT (assessment.prjtg_id IN ( SELECT DISTINCT assessment.prjtg_id
                  WHERE (assessment.grade = 0)
                  ORDER BY assessment.prjtg_id)))
          GROUP BY assessment.prjtg_id
          ORDER BY assessment.prjtg_id) rdy
     JOIN prj_tutor pt ON ((rdy.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pm.prjm_id = pt.prjtg_id)));


--
-- Name: VIEW assessment_group_ready; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW assessment_group_ready IS 'select groups that are ready';


--
-- Name: assessment_groups; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW assessment_groups AS
 SELECT DISTINCT a.judge AS snummer,
    a.prjtg_id
   FROM assessment a;


--
-- Name: VIEW assessment_groups; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW assessment_groups IS 'needed by student_project_attributes';


--
-- Name: assessment_groups2; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW assessment_groups2 AS
 SELECT DISTINCT assessment.judge AS snummer,
    assessment.prjtg_id
   FROM assessment;


--
-- Name: VIEW assessment_groups2; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW assessment_groups2 IS 'needed by student_project_attributes and student milestone selector';


--
-- Name: assessment_grp_open; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW assessment_grp_open AS
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
   FROM ((prj_grp pg
     JOIN prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
  GROUP BY pt.grp_num, pm.prj_id, pt.prjtg_id, pm.milestone
  ORDER BY pt.grp_num;


--
-- Name: VIEW assessment_grp_open; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW assessment_grp_open IS 'used for selector in groupresult';


--
-- Name: assessment_grp_open2; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW assessment_grp_open2 AS
 SELECT prj_grp.prjtg_id,
    bool_or(prj_grp.prj_grp_open) AS open
   FROM prj_grp
  GROUP BY prj_grp.prjtg_id;


--
-- Name: assessment_milestones; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW assessment_milestones AS
 SELECT DISTINCT prj_tutor.prjm_id
   FROM (assessment_groups2
     JOIN prj_tutor USING (prjtg_id));


--
-- Name: assessment_projects; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW assessment_projects AS
 SELECT DISTINCT pm.prj_id,
    pm.milestone
   FROM ((assessment a
     JOIN prj_tutor pt ON ((a.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pm.prjm_id = pt.prjtg_id)))
  ORDER BY pm.prj_id, pm.milestone;


--
-- Name: VIEW assessment_projects; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW assessment_projects IS 'used by ipeer.php';


--
-- Name: assessment_remarks_view; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW assessment_remarks_view AS
 SELECT ar.prjtg_id,
    ar.contestant,
    ar.judge,
    j.achternaam AS jachternaam,
    j.roepnaam AS jroepnaam,
    c.achternaam AS cachternaam,
    c.roepnaam AS croepnaam,
    ((((j.roepnaam)::text || COALESCE((' '::text || (j.voorvoegsel)::text), ''::text)) || ' '::text) || (j.achternaam)::text) AS jname,
    ((((c.roepnaam)::text || COALESCE((' '::text || (c.voorvoegsel)::text), ''::text)) || ' '::text) || (c.achternaam)::text) AS cname,
    ar.remark
   FROM ((assessment_remarks ar
     JOIN student j ON ((j.snummer = ar.judge)))
     JOIN student c ON ((c.snummer = ar.contestant)));


--
-- Name: assessment_student; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE assessment_student (
    snummer integer,
    username text,
    password text,
    uid integer,
    gid integer,
    achternaam character varying(40),
    roepnaam character varying(20),
    voorvoegsel character varying(10),
    opl character(4),
    cohort smallint,
    email1 text,
    pcn integer,
    sclass character(10),
    lang character(2),
    hoofdgrp text
);


--
-- Name: assessment_studnet; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE assessment_studnet (
    snummer integer,
    username text,
    password text,
    uid integer,
    gid integer,
    achternaam character varying(40),
    roepnaam character varying(20),
    voorvoegsel character varying(10),
    opl character(4),
    cohort smallint,
    email1 text,
    pcn integer,
    sclass character(10),
    lang character(2),
    hoofdgrp text
);


--
-- Name: assessment_tr; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW assessment_tr AS
 SELECT assessment.prjtg_id,
    assessment.contestant,
    assessment.judge,
    assessment.criterium,
    assessment.grade
   FROM assessment;


--
-- Name: VIEW assessment_tr; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW assessment_tr IS 'assessment with prj_id,milestone,grp_num and prjm_id dropped';


--
-- Name: assessment_zero_count; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW assessment_zero_count AS
 SELECT pm.prj_id,
    pm.milestone,
    pt.grp_num,
    gz.gcount AS count
   FROM ((( SELECT count(assessment.grade) AS gcount,
            assessment.prjtg_id
           FROM assessment
          WHERE (assessment.grade = 0)
          GROUP BY assessment.prjtg_id) gz
     JOIN prj_tutor pt ON ((gz.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pm.prjm_id = pt.prjtg_id)));


--
-- Name: VIEW assessment_zero_count; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW assessment_zero_count IS 'count zeros per group';


--
-- Name: auth_grp_members; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW auth_grp_members AS
 SELECT (pg.snummer)::text AS username,
    (((((((p.afko)::text || '_'::text) || p.year) || '_'::text) || pm.milestone) || '_'::text) || (COALESCE(ga.alias, (('group'::text || lpad((pt.grp_num)::text, 2, '00'::text)))::bpchar))::text) AS groupname
   FROM ((((prj_grp pg
     JOIN prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
     JOIN project p ON ((pm.prj_id = p.prj_id)))
     LEFT JOIN grp_alias ga ON ((pt.prjtg_id = ga.prjtg_id)));


SET default_with_oids = true;

--
-- Name: uploads; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE uploads (
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


--
-- Name: TABLE uploads; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE uploads IS 'uploads of student per project';


--
-- Name: COLUMN uploads.rights; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN uploads.rights IS 'read rights for several; 0=groupshared,1=projectshared';


--
-- Name: author_grp; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW author_grp AS
 SELECT pm.prj_id,
    pm.milestone,
    pt.grp_num,
    u.upload_id,
    u.rights,
    u.snummer AS author,
    pt.prjtg_id
   FROM (((uploads u
     JOIN prj_grp pg ON (((u.snummer = pg.snummer) AND (u.prjtg_id = pg.prjtg_id))))
     JOIN prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)));


--
-- Name: author_grp_members; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW author_grp_members AS
 SELECT author_grp.prj_id,
    author_grp.milestone,
    author_grp.grp_num,
    author_grp.upload_id,
    prj_grp.snummer,
    prj_grp.prj_grp_open AS open,
    author_grp.rights,
    author_grp.author
   FROM (author_grp
     JOIN prj_grp USING (prjtg_id));


--
-- Name: available_assessment; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW available_assessment AS
 SELECT DISTINCT pm.prjm_id
   FROM ((assessment a
     JOIN prj_tutor pt ON ((a.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pm.prjm_id = pt.prjm_id)));


--
-- Name: VIEW available_assessment; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW available_assessment IS 'used by iresult.php; tutor/groupresult.php';


--
-- Name: available_assessment_grp_contestant; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW available_assessment_grp_contestant AS
 SELECT DISTINCT assessment_tr.prjtg_id,
    assessment_tr.contestant
   FROM assessment_tr;


--
-- Name: VIEW available_assessment_grp_contestant; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW available_assessment_grp_contestant IS 'assessment enabled for this contestant';


--
-- Name: available_assessment_grp_judge; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW available_assessment_grp_judge AS
 SELECT DISTINCT assessment_tr.prjtg_id,
    assessment_tr.judge
   FROM assessment_tr;


--
-- Name: VIEW available_assessment_grp_judge; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW available_assessment_grp_judge IS 'assessment enabled for this judge';


--
-- Name: grp_size2; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW grp_size2 AS
 SELECT pm.prj_id,
    pm.milestone,
    pm.prjm_id,
    pt.grp_num,
    pt.prjtg_id,
    gs.gsize AS size
   FROM ((( SELECT prj_grp.prjtg_id,
            count(*) AS gsize
           FROM prj_grp
          GROUP BY prj_grp.prjtg_id) gs
     JOIN prj_tutor pt USING (prjtg_id))
     JOIN prj_milestone pm USING (prjm_id));


--
-- Name: judge_ready_count; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW judge_ready_count AS
 SELECT gs2.prjtg_id,
    gs2.prj_id,
    gs2.milestone,
    gs2.prjm_id,
    gs2.grp_num,
    gs2.size,
    COALESCE(rc.ready_count, (0)::bigint) AS ready_count
   FROM (grp_size2 gs2
     LEFT JOIN ( SELECT count(*) AS ready_count,
            prj_grp.prjtg_id
           FROM prj_grp
          WHERE (prj_grp.written = true)
          GROUP BY prj_grp.prjtg_id) rc USING (prjtg_id));


--
-- Name: barchart_view; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW barchart_view AS
 SELECT COALESCE(jrc.size, (0)::bigint) AS size,
    pt.prjtg_id,
    pm.prjm_id,
    pm.prj_id,
    pm.milestone,
    COALESCE(ga.alias, (('g'::text || pt.grp_num))::bpchar) AS alias,
    pt.grp_num,
    ((((ts.roepnaam)::text || ' '::text) || COALESCE(((ts.voorvoegsel)::text || ' '::text), ''::text)) || (ts.achternaam)::text) AS tut_name,
    t.tutor,
    jrc.ready_count,
    pm.prj_milestone_open,
    pt.prj_tutor_open
   FROM (((((prj_milestone pm
     JOIN prj_tutor pt ON ((pm.prjm_id = pt.prjm_id)))
     JOIN tutor t ON ((pt.tutor_id = t.userid)))
     JOIN student ts ON ((t.userid = ts.snummer)))
     LEFT JOIN judge_ready_count jrc ON ((pt.prjtg_id = jrc.prjtg_id)))
     LEFT JOIN grp_alias ga ON ((ga.prjtg_id = pt.prjtg_id)));


--
-- Name: VIEW barchart_view; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW barchart_view IS 'used in openBarChart2.php';


SET default_with_oids = false;

--
-- Name: berufskollegs; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE berufskollegs (
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


--
-- Name: TABLE berufskollegs; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE berufskollegs IS 'For recruitement.';


--
-- Name: berufskollegs__id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE berufskollegs__id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: berufskollegs__id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE berufskollegs__id_seq OWNED BY berufskollegs._id;


--
-- Name: bigface_settings; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE bigface_settings (
    bfkey character varying(30) NOT NULL,
    bfvalue text,
    comment text
);


--
-- Name: TABLE bigface_settings; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE bigface_settings IS 'Serivce big faces for fibs. Controls and settings.';


--
-- Name: faculty; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE faculty (
    full_name character varying(64),
    faculty_id smallint NOT NULL,
    faculty_short character(6),
    schedule_url character varying(30) DEFAULT 'fihe'::character varying
);


--
-- Name: TABLE faculty; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE faculty IS 'faculties within fontys';


--
-- Name: registered_mphotos; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE registered_mphotos (
    snummer integer NOT NULL
);


--
-- Name: TABLE registered_mphotos; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE registered_mphotos IS 'Employee with big photo.';


--
-- Name: bigface_view; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW bigface_view AS
 SELECT s.snummer AS userid,
    s.achternaam,
    s.roepnaam,
    s.voorvoegsel,
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
   FROM (((tutor t
     JOIN student s ON ((t.userid = s.snummer)))
     LEFT JOIN registered_mphotos r USING (snummer))
     JOIN faculty fac ON ((t.faculty_id = fac.faculty_id)))
  WHERE (s.active = true);


--
-- Name: birthdays; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW birthdays AS
 SELECT student.snummer,
    student.achternaam,
    student.voorvoegsel,
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
   FROM ((student
     JOIN faculty ON ((student.faculty_id = faculty.faculty_id)))
     JOIN student_class classes USING (class_id))
  WHERE ((classes.sclass !~~ 'UITVAL%'::text) AND (to_char((student.gebdat)::timestamp with time zone, 'MM-DD'::text) = to_char(((now())::date)::timestamp with time zone, 'MM-DD'::text)));


--
-- Name: VIEW birthdays; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW birthdays IS 'Who''s birthday is it today?';


--
-- Name: campus20120113; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE campus20120113 (
    _id integer NOT NULL,
    snummer integer,
    loopbaan character varying(3),
    opleidingsnr integer,
    studieplan integer,
    aanmeld_nr integer,
    plan_volgnr integer,
    pcn integer,
    sl_nummer integer,
    organisatie integer,
    inst_omschr character varying(29),
    studielast character varying(1),
    campus character varying(5),
    studieprogr integer,
    stud_prog_omsch character varying(26),
    stud_plan_omsch character varying(30),
    soort_plan character varying(3),
    status character varying(2),
    ingangsdatum date,
    progr_actie character varying(4),
    actiereden character varying(4),
    datum_actie date,
    srt_toelating character varying(3),
    naam character varying(39),
    voorvoegsel character varying(4),
    roepnaam character varying(20),
    voornaam character varying(28),
    initialen character varying(5),
    tussenvoegsel character varying(7),
    achternaam character varying(26),
    adres_3 character varying(26),
    nr_1 character varying(4),
    nr_2 character varying(6),
    postcode character varying(7),
    plaats character varying(22),
    land character varying(3),
    land_omschr character varying(9),
    tel_nr_thuis character varying(19),
    tel_nr_mobiel character varying(16),
    tel_nr_overig character varying(14),
    tel_nr_werk character varying(1),
    e_mail character varying(44),
    sl_e_mail_adres character varying(38),
    volledig_adres character varying(30),
    postcode_plaats character varying(28),
    geslacht character varying(1),
    geboortedatum date,
    geboorteplaats character varying(25),
    geboorteland character varying(3),
    corresp_nr bigint,
    instr_jaar integer,
    per_toelating integer,
    studievorm character varying(1),
    studieniveau character varying(1),
    studiejaar integer,
    maand_vanaf integer,
    brin_code character varying(4),
    onderwijsgroep character varying(4),
    isat_code integer,
    cip_code_omschr character varying(26),
    ondrw_cohort integer,
    ondrw_coh_dat date,
    nation_omschr character varying(29),
    national_code integer,
    fac_nfac character varying(4),
    an_vn_vl_vv character varying(45),
    checkl_compl character varying(1),
    inst_1e_inschr character varying(1),
    vl_vv_an character varying(31),
    vn_vv_an character varying(39),
    ah_vv_an character varying(31),
    aanhef_en_naam character varying(36),
    an_vl_vv character varying(32),
    off_vn_vl_vv_an character varying(46),
    brin_toel_sch character varying(4),
    org_id integer,
    toel_sch_omschr character varying(30),
    toel_sch_plaats character varying(18),
    suborg_ts integer,
    suborg_tsomschr character varying(26),
    vrg_opleiding integer,
    vooropl_omschr character varying(30),
    clustercode character varying(4),
    jr_dipl_vooropl integer,
    verific_vopl character varying(2),
    diploma_behaald character varying(1),
    datum_aank_opl date,
    datum_aank_prop date,
    datum_aank_pp date,
    datum_vertr_opl character varying(10),
    prop_behaald character varying(1),
    datum_prop_beh date,
    getuig_beh character varying(1),
    datum_get_beh character varying(10),
    betaald character varying(1),
    betaaldatum date,
    betaalvorm character varying(3),
    bet_geg_bron character varying(3),
    grp character varying(10),
    hfd_bij_grp character varying(1),
    groep_omschr character varying(30),
    groep_datum date,
    cd_team_inschr integer,
    ti_omschr character varying(29),
    status_cluster character varying(4),
    vak_01 character varying(28),
    vak_02 character varying(28),
    vak_03 character varying(28),
    vak_04 character varying(28),
    vak_05 character varying(28),
    vak_06 character varying(28),
    vak_07 character varying(28),
    vak_08 character varying(28),
    vak_09 character varying(28),
    vak_10 character varying(25),
    vak_11 character varying(28),
    vak_12 character varying(28),
    vak_13 character varying(28),
    vak_14 character varying(28),
    vak_15 character varying(25),
    vak_16 character varying(28),
    vak_17 character varying(25),
    vak_18 character varying(25),
    vak_19 character varying(15),
    vak_20 character varying(25),
    stand_vereist character varying(1),
    prog_volgnr integer,
    huid_prog_stat character varying(2),
    dt_huid_prog_st date,
    tel_nr_mail character varying(14),
    tel_nr_mob_wrk character varying(1),
    pref_phone character varying(19),
    financiering character varying(1),
    aantal_eenheden integer,
    instelling character varying(5),
    si_ba character varying(1)
);


--
-- Name: TABLE campus20120113; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE campus20120113 IS 'old peoplesoft schema.';


--
-- Name: campus_20120516; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE campus_20120516 (
    _id integer NOT NULL,
    id integer,
    loopbaan text,
    opleidingsnr integer,
    pcn integer,
    sl_nummer integer,
    organisatie integer,
    inst_omschr text,
    studielast text,
    campus text,
    studieprogr integer,
    stud_prog_omsch text,
    studieplan integer,
    stud_plan_omsch text,
    soort_plan text,
    status text,
    ingangsdatum date,
    progr_actie text,
    actiereden text,
    datum_actie date,
    aanmeld_nr integer,
    srt_toelating text,
    naam text,
    voorvoegsel text,
    roepnaam text,
    voornaam text,
    initialen text,
    tussenvoegsel text,
    achternaam text,
    adres_3 text,
    nr_1 integer,
    nr_2 text,
    postcode text,
    plaats text,
    land text,
    land_omschr text,
    tel_nr_thuis text,
    tel_nr_mobiel text,
    tel_nr_overig text,
    tel_nr_werk text,
    e_mail text,
    sl_e_mail_adres text,
    volledig_adres text,
    postcode_plaats text,
    geslacht text,
    geboortedatum date,
    geboorteplaats text,
    geboorteland text,
    corresp_nr bigint,
    instr_jaar integer,
    per_toelating integer,
    studievorm text,
    studieniveau text,
    studiejaar integer,
    maand_vanaf integer,
    brin_code text,
    onderwijsgroep text,
    isat_code integer,
    cip_code_omschr text,
    ondrw_cohort integer,
    ondrw_coh_dat date,
    nation_omschr text,
    national_code integer,
    fac_nfac text,
    an_vn_vl_vv text,
    checkl_compl text,
    inst_1e_inschr text,
    vl_vv_an text,
    vn_vv_an text,
    ah_vv_an text,
    aanhef_en_naam text,
    an_vl_vv text,
    off_vn_vl_vv_an text,
    org_id integer,
    toel_sch_omschr text,
    toel_sch_plaats text,
    vrg_opleiding integer,
    vooropl_omschr text,
    clustercode text,
    jr_dipl_vooropl integer,
    verific_vopl text,
    diploma_behaald text,
    datum_aank_opl date,
    datum_aank_prop date,
    datum_aank_pp date,
    datum_vertr_opl date,
    prop_behaald text,
    datum_prop_beh date,
    getuig_beh text,
    datum_get_beh text,
    betaald text,
    betaaldatum date,
    betaalvorm text,
    bet_geg_bron text,
    grp text,
    hfd_bij_grp text,
    groep_omschr text,
    groep_datum date,
    cd_team_inschr integer,
    ti_omschr text,
    status_cluster text,
    vak_01 text,
    vak_02 text,
    vak_03 text,
    vak_04 text,
    vak_05 text,
    vak_06 text,
    vak_07 text,
    vak_08 text,
    vak_09 text,
    vak_10 text,
    vak_11 text,
    vak_12 text,
    vak_13 text,
    vak_14 text,
    vak_15 text,
    vak_16 text,
    vak_17 text,
    vak_18 text,
    vak_19 text,
    vak_20 text,
    stand_vereist text,
    prog_volgnr integer,
    plan_volgnr integer,
    huid_prog_stat text,
    dt_huid_prog_st date,
    tel_nr_mail text,
    tel_nr_mob_wrk text,
    pref_phone text,
    financiering text,
    aantal_eenheden integer,
    instelling text,
    si_ba text
);


--
-- Name: TABLE campus_20120516; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE campus_20120516 IS 'old peoplesoft schema.';


--
-- Name: iso3166; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE iso3166 (
    country character varying(64),
    a2 character(2),
    a3 character(3),
    number smallint NOT NULL,
    country_by_lang character varying(64),
    land_nl character varying(40)
);


--
-- Name: TABLE iso3166; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE iso3166 IS 'Country codes.';


--
-- Name: campus2_as_student; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW campus2_as_student AS
 SELECT campus.id AS snummer,
    rtrim(campus.achternaam) AS achternaam,
    rtrim(campus.tussenvoegsel) AS voorvoegsel,
    rtrim(campus.initialen) AS voorletters,
    rtrim(campus.roepnaam) AS roepnaam,
    rtrim(campus.adres_3) AS straat,
    "substring"((rtrim((campus.nr_1)::text) || rtrim(COALESCE(campus.nr_2, ''::text))), 1, 4) AS huisnr,
    campus.postcode AS pcode,
    rtrim(campus.plaats) AS plaats,
    rtrim(campus.e_mail) AS email1,
    iso.a2 AS nationaliteit,
    campus.instr_jaar AS cohort,
    campus.geboortedatum AS gebdat,
    campus.geslacht AS sex,
        CASE
            WHEN (iso.a2 = ANY (ARRAY['NL'::bpchar, 'DE'::bpchar, 'EN'::bpchar])) THEN iso.a2
            ELSE 'EN'::bpchar
        END AS lang,
    campus.pcn,
    campus.studieprogr AS opl,
    campus.tel_nr_thuis AS phone_home,
    campus.tel_nr_mobiel AS phone_gsm,
    campus.tel_nr_mail AS phone_postaddress,
    campus.organisatie AS faculty_id,
    "substring"(campus.grp, 1, 10) AS hoofdgrp,
    true AS active,
    0 AS slb,
    campus.land,
    campus.studieplan,
    "substring"(campus.geboorteplaats, 1, 40) AS geboorteplaats,
    campus.geboorteland
   FROM (campus_20120516 campus
     JOIN iso3166 iso ON ((campus.geboorteland = (iso.a3)::text)));


--
-- Name: campus_20120516__id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE campus_20120516__id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: campus_20120516__id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE campus_20120516__id_seq OWNED BY campus_20120516._id;


--
-- Name: campus_as_student; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW campus_as_student AS
 SELECT campus.snummer,
    rtrim((campus.achternaam)::text) AS achternaam,
    rtrim((campus.tussenvoegsel)::text) AS voorvoegsel,
    rtrim((campus.initialen)::text) AS voorletters,
    rtrim((campus.roepnaam)::text) AS roepnaam,
    rtrim((campus.adres_3)::text) AS straat,
    "substring"((rtrim((campus.nr_1)::text) || rtrim(COALESCE((campus.nr_2)::text, ''::text))), 1, 4) AS huisnr,
    campus.postcode AS pcode,
    rtrim((campus.plaats)::text) AS plaats,
    rtrim((campus.e_mail)::text) AS email1,
    iso.a2 AS nationaliteit,
    campus.instr_jaar AS cohort,
    campus.geboortedatum AS gebdat,
    campus.geslacht AS sex,
        CASE
            WHEN (iso.a2 = ANY (ARRAY['NL'::bpchar, 'DE'::bpchar, 'EN'::bpchar])) THEN iso.a2
            ELSE 'EN'::bpchar
        END AS lang,
    campus.pcn,
    campus.studieprogr AS opl,
    campus.tel_nr_thuis AS phone_home,
    campus.tel_nr_mobiel AS phone_gsm,
    campus.tel_nr_mail AS phone_postaddress,
    campus.organisatie AS faculty_id,
    "substring"((campus.grp)::text, 1, 10) AS hoofdgrp,
    true AS active,
    0 AS slb,
    campus.land,
    campus.studieplan
   FROM (campus20120113 campus
     JOIN iso3166 iso ON (((campus.geboorteland)::bpchar = iso.a3)));


--
-- Name: class_cluster; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE class_cluster (
    class_cluster integer NOT NULL,
    cluster_name character varying(20),
    cluster_description text,
    sort_order smallint DEFAULT 0
);


--
-- Name: TABLE class_cluster; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE class_cluster IS 'Grouping of classes that might transgress institute bouadaries, like Food and Flower.';


--
-- Name: class_cluster_class_cluster_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE class_cluster_class_cluster_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: class_cluster_class_cluster_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE class_cluster_class_cluster_seq OWNED BY class_cluster.class_cluster;


--
-- Name: class_selector; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW class_selector AS
 SELECT faculty.faculty_short,
    classes.sclass,
    classes.class_id AS value,
    (((((faculty.faculty_short)::text || ' .'::text) || btrim((classes.sclass)::text)) || '#'::text) || classes.class_id) AS name
   FROM (student_class classes
     JOIN faculty ON ((faculty.faculty_id = classes.faculty_id)));


--
-- Name: VIEW class_selector; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW class_selector IS 'used through menu_option_query for student_admin';


--
-- Name: class_size; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW class_size AS
 SELECT s.class_id,
    count(*) AS student_count
   FROM student s
  GROUP BY s.class_id;


--
-- Name: classes_class_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE classes_class_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: classes_class_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE classes_class_id_seq OWNED BY student_class.class_id;


--
-- Name: classes_in_2014; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE classes_in_2014 (
    sort1 integer,
    sort2 integer,
    comment text,
    faculty_id smallint,
    class_id integer,
    sclass character(10),
    class_cluster integer,
    owner integer
);


--
-- Name: colloquium_speakers; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE colloquium_speakers (
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


--
-- Name: TABLE colloquium_speakers; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE colloquium_speakers IS 'Colloquium speakers collection';


--
-- Name: COLUMN colloquium_speakers.colloquium_speaker_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN colloquium_speakers.colloquium_speaker_id IS 'primary key';


--
-- Name: COLUMN colloquium_speakers.infix; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN colloquium_speakers.infix IS 'Dutch tussenvoegsel';


--
-- Name: COLUMN colloquium_speakers.speaker_org; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN colloquium_speakers.speaker_org IS 'Business name';


--
-- Name: colloquium_speakers_colloquium_speaker_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE colloquium_speakers_colloquium_speaker_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: colloquium_speakers_colloquium_speaker_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE colloquium_speakers_colloquium_speaker_id_seq OWNED BY colloquium_speakers.colloquium_speaker_id;


--
-- Name: contestant_assessment; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW contestant_assessment AS
 SELECT s.snummer,
    s.achternaam,
    s.voorvoegsel,
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
   FROM ((((student s
     JOIN assessment a ON ((s.snummer = a.contestant)))
     JOIN prj_grp pg ON (((a.contestant = pg.snummer) AND (a.prjtg_id = pg.prjtg_id))))
     JOIN prj_tutor pt ON ((a.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)));


--
-- Name: contestant_crit_avg; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW contestant_crit_avg AS
 SELECT assessment.prjtg_id,
    assessment.criterium,
    assessment.contestant AS snummer,
    sum(assessment.grade) AS contestant_crit_grade_sum
   FROM assessment
  GROUP BY assessment.prjtg_id, assessment.criterium, assessment.contestant;


--
-- Name: contestant_sum; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW contestant_sum AS
 SELECT pm.prj_id,
    pm.milestone,
    a.snummer,
    a.grade_sum
   FROM ((( SELECT assessment.contestant AS snummer,
            assessment.prjtg_id,
            sum(assessment.grade) AS grade_sum
           FROM assessment
          GROUP BY assessment.contestant, assessment.prjtg_id) a
     JOIN prj_tutor pt ON ((a.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pm.prjm_id = pt.prjtg_id)));


--
-- Name: course_week; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE course_week (
    start_date date,
    stop_date date,
    course_week_no smallint NOT NULL
);


--
-- Name: TABLE course_week; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE course_week IS 'Weeks for schedule schemas';


SET default_with_oids = true;

--
-- Name: timetableweek; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE timetableweek (
    day smallint NOT NULL,
    hourcode smallint NOT NULL,
    start_time time without time zone,
    stop_time time without time zone
);


--
-- Name: TABLE timetableweek; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE timetableweek IS 'Hour definitions in course or schedule week.';


--
-- Name: course_hours; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW course_hours AS
 SELECT course_week.start_date,
    course_week.stop_date,
    course_week.course_week_no,
    timetableweek.day,
    timetableweek.hourcode,
    timetableweek.start_time,
    timetableweek.stop_time
   FROM course_week,
    timetableweek;


SET default_with_oids = false;

--
-- Name: crit_temp; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE crit_temp (
    criterium_id smallint,
    author integer,
    nl_short character varying(30),
    de_short character varying(30),
    en_short character varying(30),
    nl character varying(200),
    de character varying(200),
    en character varying(200)
);


--
-- Name: criteria_pm; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW criteria_pm AS
 SELECT base_criteria.criterium_id,
    base_criteria.author,
    base_criteria.nl_short,
    base_criteria.de_short,
    base_criteria.en_short,
    base_criteria.nl,
    base_criteria.de,
    base_criteria.en,
    prjm_criterium.prjm_id
   FROM (base_criteria
     JOIN prjm_criterium USING (criterium_id));


--
-- Name: criterium_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE criterium_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    MAXVALUE 65535
    CACHE 1;


--
-- Name: critique_history; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE critique_history (
    critique_id integer,
    id bigint NOT NULL,
    edit_time timestamp without time zone DEFAULT now(),
    critique_text text
);


--
-- Name: TABLE critique_history; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE critique_history IS 'simple versioning for critiques.';


--
-- Name: critique_history_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE critique_history_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: critique_history_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE critique_history_id_seq OWNED BY critique_history.id;


--
-- Name: current_student_class; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW current_student_class AS
 SELECT student.snummer,
    student.class_id,
    date_part('year'::text, now()) AS course_year
   FROM student;


--
-- Name: davinci_leden1; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE davinci_leden1 (
    jaar integer,
    email character varying(40),
    snummer integer,
    sinds smallint,
    actief boolean,
    laatste_lid_jaar smallint,
    iban character varying(30)
);


--
-- Name: dead_class; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW dead_class AS
 SELECT DISTINCT s1.class_id
   FROM student s1
  WHERE (NOT (EXISTS ( SELECT 1
           FROM student
          WHERE ((student.class_id = s1.class_id) AND (student.active = true)))));


--
-- Name: diploma_dates; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE diploma_dates (
    snummer integer NOT NULL,
    propedeuse date,
    bachelor date,
    stopped_non_diploma date
);


--
-- Name: TABLE diploma_dates; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE diploma_dates IS 'Record diploma status of students.';


--
-- Name: doc_critique_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE doc_critique_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: doctype_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE doctype_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: doctype_upload_group_count; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW doctype_upload_group_count AS
 SELECT u.prjtg_id,
    u.doctype,
    count(u.upload_id) AS doc_count
   FROM uploads u
  GROUP BY u.prjtg_id, u.doctype;


--
-- Name: document_audience; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW document_audience AS
 SELECT uploads.upload_id,
    uploads.rights,
    uploads.snummer AS reader,
    uploads.prjm_id,
    uploads.prjtg_id AS viewergrp,
    '0 author'::text AS reader_role
   FROM uploads
UNION
 SELECT uploads.upload_id,
    uploads.rights,
    prj_tutor.tutor_id AS reader,
    uploads.prjm_id,
    uploads.prjtg_id AS viewergrp,
    '1 project tutor'::text AS reader_role
   FROM (uploads
     JOIN prj_tutor USING (prjm_id))
UNION
 SELECT uploads.upload_id,
    uploads.rights,
    prj_grp.snummer AS reader,
    uploads.prjm_id,
    uploads.prjtg_id AS viewergrp,
    '2 group member'::text AS reader_role
   FROM (uploads
     JOIN prj_grp USING (prjtg_id))
  WHERE ((uploads.rights[1] = true) AND (prj_grp.snummer <> uploads.snummer))
UNION
 SELECT u.upload_id,
    u.rights,
    pg.snummer AS reader,
    u.prjm_id,
    pg.prjtg_id AS viewergrp,
    '3 project member'::text AS reader_role
   FROM ((uploads u
     JOIN prj_tutor pt ON ((u.prjm_id = pt.prjm_id)))
     JOIN prj_grp pg ON ((pt.prjtg_id = pg.prjtg_id)))
  WHERE ((u.rights[2] = true) AND (u.prjtg_id <> pg.prjtg_id));


--
-- Name: document_author; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE document_author (
    upload_id integer NOT NULL,
    snummer integer NOT NULL,
    document_author_id integer NOT NULL
);


--
-- Name: TABLE document_author; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE document_author IS 'who is author for uploaded docs';


--
-- Name: document_author_document_author_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE document_author_document_author_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: document_author_document_author_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE document_author_document_author_id_seq OWNED BY document_author.document_author_id;


--
-- Name: document_critique; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE document_critique (
    critique_id integer DEFAULT nextval(('public.doc_critique_seq'::text)::regclass) NOT NULL,
    doc_id integer,
    critiquer integer,
    ts timestamp without time zone DEFAULT now(),
    critique_text text,
    edit_time timestamp without time zone DEFAULT now(),
    deleted boolean DEFAULT false
);


--
-- Name: TABLE document_critique; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE document_critique IS 'critiques by students (and tutors) on uploaded documents';


--
-- Name: COLUMN document_critique.doc_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN document_critique.doc_id IS 'link to critisized document';


--
-- Name: COLUMN document_critique.critiquer; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN document_critique.critiquer IS 'snummer';


--
-- Name: COLUMN document_critique.critique_text; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN document_critique.critique_text IS 'The critique text';


--
-- Name: document_critique_count; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW document_critique_count AS
 SELECT count(document_critique.critique_id) AS critique_count,
    document_critique.doc_id
   FROM document_critique
  WHERE (document_critique.deleted = false)
  GROUP BY document_critique.doc_id;


--
-- Name: project_deliverables; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE project_deliverables (
    doctype smallint NOT NULL,
    version_limit smallint DEFAULT 2,
    due date DEFAULT (((now())::text)::date + 28),
    publish_early boolean DEFAULT true,
    rights boolean[] DEFAULT '{f,f,f}'::boolean[],
    prjm_id integer NOT NULL,
    pdeliverable_id integer NOT NULL
);


--
-- Name: TABLE project_deliverables; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE project_deliverables IS 'deliverables of project';


--
-- Name: COLUMN project_deliverables.rights; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN project_deliverables.rights IS 'read rights for several; 0=groupshared,1=projectshared';


--
-- Name: uploaddocumenttypes; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE uploaddocumenttypes (
    doctype smallint DEFAULT nextval(('public.doctype_id_seq'::text)::regclass) NOT NULL,
    description character varying(40),
    prj_id smallint NOT NULL,
    url text,
    warn_members boolean DEFAULT false,
    indiv_group character(1) DEFAULT 'I'::bpchar,
    CONSTRAINT uploaddocumenttypes_indiv_group_check CHECK ((indiv_group = ANY (ARRAY['I'::bpchar, 'G'::bpchar])))
);


--
-- Name: TABLE uploaddocumenttypes; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE uploaddocumenttypes IS 'type description deliverables of project';


--
-- Name: document_data3; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW document_data3 AS
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
    st.voorvoegsel,
    st.achternaam,
    cl.sclass,
    document_critique_count.critique_count,
    u.rights
   FROM (((((((((uploads u
     JOIN prj_milestone pm ON ((u.prjm_id = pm.prjm_id)))
     JOIN uploaddocumenttypes ut ON (((pm.prj_id = ut.prj_id) AND (u.doctype = ut.doctype))))
     JOIN project_deliverables pd ON (((u.prjm_id = pd.prjm_id) AND (u.doctype = pd.doctype))))
     JOIN prj_tutor pt ON (((pm.prjm_id = pt.prjm_id) AND (u.prjtg_id = pt.prjtg_id))))
     JOIN student st ON ((u.snummer = st.snummer)))
     JOIN student_class cl ON ((st.class_id = cl.class_id)))
     LEFT JOIN grp_alias ga ON ((pt.prjtg_id = ga.prjtg_id)))
     JOIN project p ON ((p.prj_id = pm.prj_id)))
     LEFT JOIN document_critique_count ON ((u.upload_id = document_critique_count.doc_id)));


--
-- Name: VIEW document_data3; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW document_data3 IS 'all document data with prj_id and milestone removed';


--
-- Name: document_projects; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW document_projects AS
 SELECT uploaddocumenttypes.prj_id
   FROM uploaddocumenttypes;


--
-- Name: dossier_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE dossier_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: SEQUENCE dossier_id_seq; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON SEQUENCE dossier_id_seq IS 'Seq for dossier table';


SET default_with_oids = true;

--
-- Name: downloaded; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE downloaded (
    snummer integer NOT NULL,
    upload_id integer NOT NULL,
    downloadts timestamp without time zone DEFAULT now() NOT NULL
);


--
-- Name: TABLE downloaded; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE downloaded IS 'Record downloading of documents.';


SET default_with_oids = false;

--
-- Name: education_unit; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE education_unit (
    module_id integer NOT NULL,
    credits integer,
    weight integer,
    education_unit_id integer NOT NULL,
    CONSTRAINT weight_check CHECK ((weight >= 0))
);


--
-- Name: TABLE education_unit; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE education_unit IS 'module parts, producing separate credits.';


--
-- Name: education_unit_description; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE education_unit_description (
    education_unit_id integer NOT NULL,
    language_id character(2) NOT NULL,
    module_id integer NOT NULL,
    description character varying(50)
);


--
-- Name: TABLE education_unit_description; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE education_unit_description IS 'description of parts of modules.';


SET default_with_oids = true;

--
-- Name: email_signature; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE email_signature (
    snummer integer NOT NULL,
    signature text
);


--
-- Name: TABLE email_signature; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE email_signature IS 'for email personalisation.';


--
-- Name: enumeraties; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE enumeraties (
    menu_name character varying(30) NOT NULL,
    column_name character varying(30) NOT NULL,
    name character varying(30) NOT NULL,
    value character varying(30) NOT NULL,
    sort_order smallint DEFAULT 0,
    is_default character(1) DEFAULT 'N'::bpchar,
    id integer NOT NULL
);


--
-- Name: TABLE enumeraties; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE enumeraties IS 'Lists for several drop down menus in menu and menu_item. ';


--
-- Name: enumeraties_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE enumeraties_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: enumeraties_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE enumeraties_id_seq OWNED BY enumeraties.id;


SET default_with_oids = false;

--
-- Name: exam; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE exam (
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


--
-- Name: TABLE exam; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE exam IS 'Module exam type.';


--
-- Name: exam_account; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW exam_account AS
 SELECT ('x'::text || (prj_grp.snummer)::text) AS uname,
    student.roepnaam AS password,
    1 AS uid,
    (pm.prj_id + 10000) AS gid,
    (((student.roepnaam)::text || ' '::text) || (student.achternaam)::text) AS gecos,
    ('/exam/x'::text || (prj_grp.snummer)::text) AS homedir,
    '/bin/bash'::text AS shell,
    pm.prj_id,
    pm.milestone
   FROM (((prj_grp
     JOIN student USING (snummer))
     JOIN prj_tutor pt ON ((prj_grp.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)));


--
-- Name: exam_event; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE exam_event (
    exam_event_id integer NOT NULL,
    module_part_id integer,
    exam_date date,
    examiner integer
);


--
-- Name: exam_event_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE exam_event_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: exam_event_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE exam_event_id_seq OWNED BY exam_event.exam_event_id;


--
-- Name: exam_exam_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE exam_exam_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: exam_exam_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE exam_exam_id_seq OWNED BY exam.exam_id;


--
-- Name: exam_focus; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE exam_focus (
    exam_focus_id integer NOT NULL
);


--
-- Name: exam_focus_description; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE exam_focus_description (
    exam_focus_id integer NOT NULL,
    language_id character(2) NOT NULL,
    description character varying(50)
);


--
-- Name: exam_grades; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE exam_grades (
    snummer integer,
    event text,
    grade numeric,
    trans_id bigint
);


--
-- Name: exam_grading_aspect; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE exam_grading_aspect (
    exam_grading_aspect_id character varying(10) NOT NULL
);


--
-- Name: exam_grading_aspect_description; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE exam_grading_aspect_description (
    exam_grading_aspect_id character varying(10) NOT NULL,
    language_id character(2) NOT NULL,
    description character varying(50)
);


--
-- Name: exam_grading_level; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE exam_grading_level (
    exam_grading_level_id character varying(10) NOT NULL
);


--
-- Name: exam_grading_level_description; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE exam_grading_level_description (
    exam_grading_level_id character varying(10) NOT NULL,
    language_id character(2) NOT NULL,
    description character varying(50)
);


--
-- Name: exam_grading_type; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE exam_grading_type (
    exam_grading_type_id character varying(10) NOT NULL
);


--
-- Name: exam_grading_type_description; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE exam_grading_type_description (
    exam_grading_type_id character varying(10) NOT NULL,
    language_id character(2) NOT NULL,
    description character varying(50)
);


--
-- Name: exam_type; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE exam_type (
    exam_type_id character varying(10) NOT NULL
);


--
-- Name: exam_type_description; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE exam_type_description (
    exam_type_id character varying(10) NOT NULL,
    language_id character(2) NOT NULL,
    description character varying(50)
);


--
-- Name: examlist; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW examlist AS
 SELECT s.snummer,
    s.achternaam,
    s.voorletters,
    s.roepnaam,
    s.voorvoegsel,
    s.lang,
    pm.prj_id
   FROM (((student s
     JOIN prj_grp pg USING (snummer))
     JOIN prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pm.prjm_id = pt.prjtg_id)));


SET default_with_oids = true;

--
-- Name: fake_mail_address; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE fake_mail_address (
    email1 text NOT NULL
);


--
-- Name: TABLE fake_mail_address; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE fake_mail_address IS 'Email address used during tests. ';


SET default_with_oids = false;

--
-- Name: februar2014; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE februar2014 (
    _id integer NOT NULL,
    snummer integer,
    naam character varying(20),
    roepnaam character varying(17),
    klas character varying(6),
    grp_num integer
);


--
-- Name: februar2014__id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE februar2014__id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: februar2014__id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE februar2014__id_seq OWNED BY februar2014._id;


--
-- Name: fixed_contestant; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW fixed_contestant AS
 SELECT contestant_sum.prj_id,
    contestant_sum.milestone,
    contestant_sum.snummer
   FROM contestant_sum
  WHERE (contestant_sum.grade_sum > 0);


--
-- Name: judge_sum; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW judge_sum AS
 SELECT pm.prj_id,
    pm.milestone,
    a.snummer,
    a.grade_sum
   FROM ((( SELECT assessment.judge AS snummer,
            assessment.prjtg_id,
            sum(assessment.grade) AS grade_sum
           FROM assessment
          GROUP BY assessment.judge, assessment.prjtg_id) a
     JOIN prj_tutor pt ON ((a.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pm.prjm_id = pt.prjtg_id)));


--
-- Name: fixed_judge; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW fixed_judge AS
 SELECT judge_sum.prj_id,
    judge_sum.milestone,
    judge_sum.snummer
   FROM judge_sum
  WHERE (judge_sum.grade_sum > 0);


--
-- Name: fixed_student; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW fixed_student AS
 SELECT fixed_judge.prj_id,
    fixed_judge.milestone,
    fixed_judge.snummer
   FROM fixed_judge
UNION
 SELECT fixed_contestant.prj_id,
    fixed_contestant.milestone,
    fixed_contestant.snummer
   FROM fixed_contestant;


--
-- Name: fixed_student2; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW fixed_student2 AS
 SELECT DISTINCT pg.snummer,
    pm.prj_id,
    pm.milestone,
    pm.prjm_id,
    pt.prjtg_id,
    pt.grp_num
   FROM (((assessment a
     JOIN prj_grp pg ON (((a.prjtg_id = pg.prjtg_id) AND ((pg.snummer = a.judge) OR (pg.snummer = a.contestant)))))
     JOIN prj_tutor pt ON ((a.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)));


--
-- Name: foins27__id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE foins27__id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: foins27__id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE foins27__id_seq OWNED BY campus20120113._id;


SET default_with_oids = true;

--
-- Name: foto_prefix; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE foto_prefix (
    prefix character varying(64) NOT NULL
);


--
-- Name: TABLE foto_prefix; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE foto_prefix IS 'used by images derived from snummers';


--
-- Name: foto; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW foto AS
 SELECT student.snummer,
    (((((('<img src="'::text || (foto_prefix.prefix)::text) || '/'::text) || (student.snummer)::text) || '.jpg" alt="'::text) || (student.snummer)::text) || '"/>'::text) AS image
   FROM student,
    foto_prefix;


--
-- Name: VIEW foto; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW foto IS 'used by images derived from snummers';


SET default_with_oids = false;

--
-- Name: geslaagdentechniek2013; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE geslaagdentechniek2013 (
    _id integer NOT NULL,
    grp character varying(7),
    grpn character varying(10),
    id integer,
    pcn character varying(10),
    land character varying(3),
    naam character varying(22),
    volledig_adres character varying(26),
    postcode_plaats character varying(23),
    tel_nr_thuis character varying(13),
    tel_nr_mobiel character varying(13),
    e_mail character varying(36),
    class_id integer
);


--
-- Name: geslaagdentechniek2013__id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE geslaagdentechniek2013__id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: geslaagdentechniek2013__id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE geslaagdentechniek2013__id_seq OWNED BY geslaagdentechniek2013._id;


SET default_with_oids = true;

--
-- Name: passwd; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE passwd (
    userid integer NOT NULL,
    capabilities integer DEFAULT 0 NOT NULL,
    password character varying(64) DEFAULT 'No password'::bpchar NOT NULL,
    disabled boolean DEFAULT false
);


--
-- Name: TABLE passwd; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE passwd IS 'authentication, capability; note tutor column is only as aid, not maintained';


--
-- Name: git_password; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW git_password AS
 SELECT s.snummer,
    s.email1 AS username,
    p.password
   FROM (( SELECT passwd.userid AS snummer,
            passwd.password
           FROM passwd
          WHERE ((passwd.capabilities & 262144) <> 0)) p
     JOIN student s USING (snummer))
UNION
 SELECT s.snummer,
    (s.snummer)::text AS username,
    p.password
   FROM (( SELECT passwd.userid AS snummer,
            passwd.password
           FROM passwd
          WHERE ((passwd.capabilities & 262144) <> 0)) p
     JOIN student s USING (snummer));


--
-- Name: git_project_users; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW git_project_users AS
 SELECT a.prjm_id,
    0 AS gid,
    s1.snummer,
    (((((('@'::text || lower(btrim((fontys_course.course_short)::text))) || a.year) || lower(btrim((a.afko)::text))) || 'm'::text) || a.milestone) || '_tutor'::text) AS git_grp,
    s1.email1 AS git_grp_member,
    ''::text AS repo
   FROM ((all_prj_tutor a
     JOIN student s1 ON ((a.tutor_id = s1.snummer)))
     JOIN fontys_course USING (course))
UNION
 SELECT a.prjm_id,
    a.grp_num AS gid,
    prj_grp.snummer,
    ((((((('@'::text || lower(btrim((fontys_course.course_short)::text))) || a.year) || lower(btrim((a.afko)::text))) || 'm'::text) || a.milestone) || '_g'::text) || a.grp_num) AS git_grp,
    s2.email1 AS git_grp_member,
    ((((((a.year || '/'::text) || lower(btrim((a.afko)::text))) || 'm'::text) || a.milestone) || '/g'::text) || a.grp_num) AS repo
   FROM (((all_prj_tutor a
     JOIN prj_grp USING (prjtg_id))
     JOIN student s2 USING (snummer))
     JOIN fontys_course USING (course))
  ORDER BY 1, 2;


--
-- Name: grp_alias_builder; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW grp_alias_builder AS
 SELECT pm.prj_id,
    ga.long_name,
    pt.grp_num,
    pm.milestone,
    ga.alias,
    ga.website,
    ga.productname,
    pm.prjm_id,
    pt.prjtg_id
   FROM ((prj_milestone pm
     JOIN prj_tutor pt ON ((pm.prjm_id = pt.prjm_id)))
     LEFT JOIN grp_alias ga ON ((ga.prjtg_id = pt.prjtg_id)));


--
-- Name: VIEW grp_alias_builder; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW grp_alias_builder IS 'used for duplication of project groups -> grp_aliases';


--
-- Name: grp_alias_tr; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW grp_alias_tr AS
 SELECT grp_alias.prjtg_id,
    grp_alias.long_name,
    grp_alias.alias,
    grp_alias.website,
    grp_alias.productname
   FROM grp_alias;


--
-- Name: VIEW grp_alias_tr; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW grp_alias_tr IS 'grp_alias with redeundant columns removed';


--
-- Name: grp_average; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW grp_average AS
 SELECT pm.prj_id,
    av.criterium,
    pm.milestone,
    pt.grp_num,
    av.grp_avg
   FROM ((( SELECT assessment.prjtg_id,
            assessment.criterium,
            avg(assessment.grade) AS grp_avg
           FROM assessment
          GROUP BY assessment.prjtg_id, assessment.criterium) av
     JOIN prj_tutor pt ON ((av.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pm.prjm_id = pt.prjtg_id)));


--
-- Name: VIEW grp_average; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW grp_average IS 'Used by tutor/groupresult.php';


--
-- Name: grp_average2; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW grp_average2 AS
 SELECT assessment.prjtg_id,
    assessment.criterium,
    avg(assessment.grade) AS grp_avg
   FROM assessment
  GROUP BY assessment.prjtg_id, assessment.criterium;


--
-- Name: grp_crit_avg; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW grp_crit_avg AS
 SELECT assessment.prjtg_id,
    assessment.criterium,
    sum(assessment.grade) AS crit_grade_sum
   FROM assessment
  GROUP BY assessment.prjtg_id, assessment.criterium;


--
-- Name: grp_detail; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW grp_detail AS
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
   FROM ((((((project p
     JOIN prj_milestone USING (prj_id))
     JOIN prj_tutor pt USING (prjm_id))
     JOIN grp_alias ga USING (prjtg_id))
     JOIN tutor town ON ((p.owner_id = town.userid)))
     JOIN tutor tu ON ((pt.tutor_id = tu.userid)))
     JOIN faculty f ON ((f.faculty_id = town.faculty_id)));


--
-- Name: VIEW grp_detail; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW grp_detail IS 'detail attributes for project group, updatable.';


--
-- Name: grp_details; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW grp_details AS
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
   FROM ((((((project p
     JOIN prj_milestone USING (prj_id))
     JOIN prj_tutor pt USING (prjm_id))
     JOIN grp_alias ga USING (prjtg_id))
     JOIN tutor town ON ((p.owner_id = town.userid)))
     JOIN tutor tu ON ((pt.tutor_id = tu.userid)))
     JOIN faculty f ON ((f.faculty_id = town.faculty_id)));


--
-- Name: VIEW grp_details; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW grp_details IS 'detail attributes for project group, updatable.';


--
-- Name: grp_overall_average; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW grp_overall_average AS
 SELECT assessment.prjtg_id,
    avg(assessment.grade) AS grp_avg
   FROM assessment
  GROUP BY assessment.prjtg_id;


--
-- Name: VIEW grp_overall_average; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW grp_overall_average IS 'used by tutor/groupresult.php';


--
-- Name: grp_size; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW grp_size AS
 SELECT grp_size2.prjtg_id,
    grp_size2.prj_id,
    grp_size2.milestone,
    grp_size2.grp_num,
    grp_size2.size
   FROM grp_size2;


--
-- Name: grp_tg_size; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW grp_tg_size AS
 SELECT prj_grp.prjtg_id,
    count(*) AS grp_size
   FROM prj_grp
  GROUP BY prj_grp.prjtg_id;


--
-- Name: grp_upload_count; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW grp_upload_count AS
 SELECT pm.prj_id,
    pm.milestone,
    pt.grp_num,
    count(u.upload_id) AS doc_count
   FROM (((uploads u
     JOIN prj_grp pg ON (((u.prjtg_id = pg.prjtg_id) AND (u.snummer = pg.snummer))))
     JOIN prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
  GROUP BY pm.prj_id, pm.milestone, pt.grp_num
  ORDER BY pm.prj_id, pm.milestone, pt.grp_num;


--
-- Name: VIEW grp_upload_count; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW grp_upload_count IS 'used by folderview';


--
-- Name: grp_upload_count2; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW grp_upload_count2 AS
 SELECT u.prjtg_id,
    count(u.upload_id) AS doc_count
   FROM uploads u
  GROUP BY u.prjtg_id;


SET default_with_oids = false;

--
-- Name: guest_users; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE guest_users (
    username text NOT NULL,
    password character varying(64)
);


--
-- Name: TABLE guest_users; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE guest_users IS 'Guests, e.g. for subversion.';


--
-- Name: has_uploads; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW has_uploads AS
 SELECT DISTINCT prj_milestone.prj_id,
    prj_milestone.milestone
   FROM (prj_milestone
     JOIN uploaddocumenttypes USING (prj_id))
  ORDER BY prj_milestone.prj_id, prj_milestone.milestone;


--
-- Name: hoofdgrp; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW hoofdgrp AS
 SELECT DISTINCT s.hoofdgrp,
    s.faculty_id,
    f.full_name,
    f.faculty_short,
    fc.course_short
   FROM ((student s
     JOIN faculty f ON ((s.faculty_id = f.faculty_id)))
     JOIN fontys_course fc ON (((s.opl = fc.course) AND (fc.faculty_id = f.faculty_id))));


--
-- Name: hoofdgrp_map; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE hoofdgrp_map (
    instituutcode integer,
    opleiding character varying(39),
    lang character(2),
    hoofdgrp character(10),
    _id integer NOT NULL,
    course bigint
);


--
-- Name: hoofdgrp_map__id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE hoofdgrp_map__id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: hoofdgrp_map__id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE hoofdgrp_map__id_seq OWNED BY hoofdgrp_map._id;


--
-- Name: hoofdgrp_s; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW hoofdgrp_s AS
 SELECT DISTINCT student.hoofdgrp,
    student.faculty_id,
    faculty.faculty_short,
    fontys_course.course,
    fontys_course.course_short
   FROM ((student
     JOIN faculty USING (faculty_id))
     JOIN fontys_course ON ((student.opl = fontys_course.course)));


--
-- Name: hoofdgrp_size; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW hoofdgrp_size AS
 SELECT student.hoofdgrp,
    student.faculty_id,
    count(*) AS grp_size
   FROM (student
     JOIN hoofdgrp USING (faculty_id, hoofdgrp))
  GROUP BY student.hoofdgrp, student.faculty_id;


--
-- Name: ingeschrevenen; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE ingeschrevenen (
    peildatum date,
    studiejaar integer,
    instituutcode integer,
    instituutnaam character varying(40),
    directeur character(15),
    studentnummer integer,
    achternaam character varying(30),
    voorvoegsels character(10),
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


--
-- Name: map_land_nl_iso3166; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE map_land_nl_iso3166 (
    land_nl character varying(40),
    a3 character(3),
    id integer NOT NULL
);


--
-- Name: nat_mapper; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE nat_mapper (
    nation_omschr character(40),
    nationaliteit character(2),
    id integer NOT NULL
);


--
-- Name: TABLE nat_mapper; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE nat_mapper IS 'Map nationality description in dutch to iso3166 2 letter country code.';


--
-- Name: import_naw; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW import_naw AS
 SELECT DISTINCT ig.studentnummer AS snummer,
    initcap((ig.achternaam)::text) AS achternaam,
    ig.voorvoegsels AS voorvoegsel,
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


--
-- Name: in2013; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE in2013 (
    snummer integer,
    achternaam character varying(32),
    voorvoegsel character varying(8),
    voorletters character varying(8),
    roepnaam character varying(24),
    straat character varying(30),
    huisnr character(4),
    pcode character(7),
    plaats character varying(30),
    email1 character varying(49),
    nationaliteit character(2),
    cohort integer,
    gebdat date,
    sex character varying(1),
    lang character(2),
    pcn integer,
    opl bigint,
    phone_home character varying(40),
    phone_gsm character varying(40),
    phone_postaddress character varying(40),
    faculty_id integer,
    hoofdgrp character(10),
    active boolean,
    slb integer,
    land character(3),
    studieplan integer,
    geboorteplaats character varying(36),
    geboorteland character(3),
    voornaam character varying(24),
    class_id integer
);


--
-- Name: inchecked; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE inchecked (
    _id integer NOT NULL,
    snummer integer
);


--
-- Name: inchecked__id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE inchecked__id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: inchecked__id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE inchecked__id_seq OWNED BY inchecked._id;


--
-- Name: infcap; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE infcap (
    userid integer,
    capabilities integer,
    password character varying(64),
    disabled boolean
);


--
-- Name: ininf20130827; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE ininf20130827 (
    snummer integer
);


--
-- Name: iso3166a; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE iso3166a (
    country character varying(44),
    a2 character varying(2),
    a3 character varying(3),
    number integer,
    country_by_lang character varying(11),
    land_nl character varying(28)
);


--
-- Name: jagers; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE jagers (
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


--
-- Name: TABLE jagers; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE jagers IS 'employee with jager role. Not used in 2013.';


--
-- Name: jagers__id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE jagers__id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jagers__id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE jagers__id_seq OWNED BY jagers._id;


--
-- Name: judge_assessment; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW judge_assessment AS
 SELECT s.snummer,
    s.achternaam,
    s.voorvoegsel,
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
   FROM (((student s
     JOIN assessment a ON ((s.snummer = a.judge)))
     JOIN prj_tutor USING (prjtg_id))
     JOIN prj_milestone USING (prjm_id));


--
-- Name: judge_crit_avg; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW judge_crit_avg AS
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
           FROM assessment
          GROUP BY assessment.prjtg_id, assessment.judge, assessment.criterium) av
     JOIN prj_tutor pt ON ((av.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pm.prjm_id = pt.prjtg_id)));


--
-- Name: judge_grade_count; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW judge_grade_count AS
 SELECT pm.prj_id,
    pm.milestone,
    pt.grp_num,
    rdy.ready_judge
   FROM ((( SELECT assessment.prjtg_id,
            count(*) AS ready_judge
           FROM assessment
          WHERE (assessment.grade <> 0)
          GROUP BY assessment.prjtg_id) rdy
     JOIN prj_tutor pt ON ((rdy.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pm.prjm_id = pt.prjtg_id)));


--
-- Name: judge_grade_count2; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW judge_grade_count2 AS
 SELECT pm.prj_id,
    pm.milestone,
    pm.prjm_id,
    pt.grp_num,
    pt.prjtg_id,
    rdy.ready_judge
   FROM ((( SELECT assessment.prjtg_id,
            count(assessment.judge) AS ready_judge
           FROM assessment
          WHERE (assessment.grade <> 0)
          GROUP BY assessment.prjtg_id) rdy
     JOIN prj_tutor pt ON ((rdy.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pm.prjm_id = pt.prjtg_id)));


--
-- Name: judge_grp_avg; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW judge_grp_avg AS
 SELECT pm.prj_id,
    pm.milestone,
    pt.grp_num,
    av.criterium,
    av.grade
   FROM ((( SELECT assessment.prjtg_id,
            assessment.criterium,
            avg(assessment.grade) AS grade
           FROM assessment
          GROUP BY assessment.prjtg_id, assessment.criterium) av
     JOIN prj_tutor pt ON ((av.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pm.prjm_id = pt.prjtg_id)));


--
-- Name: judge_notready; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW judge_notready AS
 SELECT DISTINCT assessment.judge AS snummer,
    assessment.prjtg_id
   FROM assessment
  WHERE (assessment.grade = 0);


--
-- Name: klassenfibs20140226; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE klassenfibs20140226 (
    _id integer NOT NULL,
    klas character varying(9),
    studentnummer integer,
    achternaam character varying(27),
    voorvoegsels character varying(7),
    roepnaam character varying(17),
    variant_omschrijving character varying(49),
    bijvakker character varying(2),
    datum_definitief_ingeschreven date,
    pasfoto_uploaddatum date,
    class_id integer
);


--
-- Name: klassenfibs20140226__id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE klassenfibs20140226__id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: klassenfibs20140226__id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE klassenfibs20140226__id_seq OWNED BY klassenfibs20140226._id;


--
-- Name: klassenmec2012; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE klassenmec2012 (
    _id integer NOT NULL,
    grp character varying(7),
    id integer,
    pcn character varying(8),
    naam character varying(22),
    volledig_adres character varying(27),
    postcode_plaats character varying(26),
    land character varying(3),
    tel_nr_thuis character varying(11),
    tel_nr_mobiel character varying(12),
    e_mail character varying(35),
    class_id integer
);


--
-- Name: klassenmec2012__id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE klassenmec2012__id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: klassenmec2012__id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE klassenmec2012__id_seq OWNED BY klassenmec2012._id;


--
-- Name: language; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE language (
    language_id character(2) NOT NULL,
    language character varying(30)
);


--
-- Name: TABLE language; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE language IS 'Choices between dutch,  german and english.';


--
-- Name: last_assessment_commit; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW last_assessment_commit AS
 SELECT ac.snummer,
    pm.prj_id,
    pm.milestone,
    pt.prjtg_id,
    max(ac.commit_time) AS commit_time
   FROM ((assessment_commit ac
     JOIN prj_tutor pt ON ((pt.prjtg_id = ac.prjtg_id)))
     JOIN prj_milestone pm ON ((pm.prjm_id = pt.prjm_id)))
  GROUP BY ac.snummer, pm.prj_id, pm.milestone, pt.prjtg_id;


--
-- Name: VIEW last_assessment_commit; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW last_assessment_commit IS 'used by ipeer.php tutor/groupresult.php tutor/moduleresults.php';


--
-- Name: last_upload; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW last_upload AS
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
   FROM uploads
  WHERE (uploads.upload_id = ( SELECT max(uploads_1.upload_id) AS max_id
           FROM uploads uploads_1));


--
-- Name: learning_goal; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE learning_goal (
    module_id integer NOT NULL,
    learning_goal_id integer NOT NULL
);


--
-- Name: learning_goal_description; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE learning_goal_description (
    module_id integer NOT NULL,
    language_id character(2) NOT NULL,
    learning_goal_id integer NOT NULL,
    description character varying(250)
);


--
-- Name: learning_goal_exam_focus; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE learning_goal_exam_focus (
    module_id integer NOT NULL,
    learning_goal_id integer NOT NULL,
    exam_focus_id integer NOT NULL,
    weight integer,
    CONSTRAINT learning_goal_exam_focus_check CHECK ((weight >= 0))
);


--
-- Name: lime_token; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW lime_token AS
 SELECT s.roepnaam AS firstname,
    (COALESCE(((s.voorvoegsel)::text || ' '::text), ''::text) || (s.achternaam)::text) AS lastname,
    s.email1 AS email,
    'OK'::text AS emailstatus,
    md5(((s.snummer)::text || now())) AS token,
    s.lang AS language_code,
    s.snummer AS attribute_1,
    pm.prjm_id AS attribute_2,
    pm.prj_id,
    pm.milestone
   FROM (((prj_grp pg
     JOIN student s USING (snummer))
     JOIN prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pm.prjm_id = pt.prjm_id)));


SET default_with_oids = true;

--
-- Name: logon; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE logon (
    userid integer NOT NULL,
    since timestamp without time zone DEFAULT now(),
    id bigint NOT NULL,
    from_ip inet
);


--
-- Name: TABLE logon; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE logon IS 'time of logon.';


--
-- Name: logged_in_today; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW logged_in_today AS
 SELECT logon.userid,
    logon.since,
    logon.id,
    logon.from_ip
   FROM logon
  WHERE (logon.since > (now())::date);


--
-- Name: loggedin; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW loggedin AS
 SELECT s.achternaam,
    s.roepnaam,
    l.userid,
    l.since,
    l.id,
    l.from_ip
   FROM (logged_in_today l
     JOIN student s ON ((s.snummer = l.userid)));


--
-- Name: logoff; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE logoff (
    userid integer,
    since timestamp without time zone DEFAULT now(),
    id bigint NOT NULL
);


--
-- Name: TABLE logoff; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE logoff IS 'time of log off.';


--
-- Name: logoff_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE logoff_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: logoff_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE logoff_id_seq OWNED BY logoff.id;


--
-- Name: logon_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE logon_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: logon_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE logon_id_seq OWNED BY logon.id;


--
-- Name: logon_map_on_timetable; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW logon_map_on_timetable AS
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
   FROM ((logon
     JOIN timetableweek ON (((((substr(to_char(logon.since, 'HH24:MI:SS'::text), 1, 8))::time without time zone >= timetableweek.start_time) AND ((substr(to_char(logon.since, 'HH24:MI:SS'::text), 1, 8))::time without time zone <= timetableweek.stop_time)) AND (date_part('dow'::text, logon.since) = (timetableweek.day)::double precision))))
     JOIN course_week ON (((logon.since >= (course_week.start_date)::timestamp without time zone) AND (logon.since <= (course_week.stop_date)::timestamp without time zone))));


SET default_with_oids = false;

--
-- Name: lpi_id; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE lpi_id (
    snummer integer NOT NULL,
    lpi_id character(12)
);


--
-- Name: TABLE lpi_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE lpi_id IS 'lpi membership for ICT students. For LPI exams.';


--
-- Name: map_land_nl_iso3166_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE map_land_nl_iso3166_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: map_land_nl_iso3166_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE map_land_nl_iso3166_id_seq OWNED BY map_land_nl_iso3166.id;


--
-- Name: meeloopmail; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE meeloopmail (
    meeloopmail_id integer NOT NULL,
    owner integer,
    meeloop_datum date NOT NULL,
    invitation_datum date DEFAULT (now())::date NOT NULL,
    subject_nl text,
    subject_de text,
    mailbody_nl text,
    mailbody_de text
);


--
-- Name: TABLE meeloopmail; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE meeloopmail IS 'invitations to meeloop dagen.';


--
-- Name: meeloopmail_meeloopmail_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE meeloopmail_meeloopmail_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: meeloopmail_meeloopmail_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE meeloopmail_meeloopmail_id_seq OWNED BY meeloopmail.meeloopmail_id;


--
-- Name: meelopen; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE meelopen (
    meelopen_id integer NOT NULL,
    achternaam text,
    roepnaam text,
    tussenvoegsel text,
    taal text,
    straat text,
    huisnr integer,
    toevoeging text,
    postcode text,
    plaats text,
    telefoon text,
    email text,
    vooropleiding text,
    datum_in date DEFAULT (now())::date,
    invitation date,
    _confirmed integer,
    participation date,
    sex character(1) DEFAULT 'M'::bpchar,
    land character(2),
    confirmed date,
    email_address_validated boolean DEFAULT false,
    opl_voorkeur bigint
);


--
-- Name: TABLE meelopen; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE meelopen IS 'aangemelde meelopers vanaf oct 2011';


--
-- Name: meelopen_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE meelopen_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: meelopen_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE meelopen_id_seq OWNED BY meelopen.meelopen_id;


SET default_with_oids = true;

--
-- Name: menu; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE menu (
    menu_name character varying(40) NOT NULL,
    relation_name character varying(40) NOT NULL,
    menu_top smallint DEFAULT 1 NOT NULL,
    menu_left smallint DEFAULT 1 NOT NULL,
    capability smallint DEFAULT 32767 NOT NULL,
    id integer NOT NULL
);


--
-- Name: TABLE menu; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE menu IS 'simple editor definition table.';


--
-- Name: menu_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE menu_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: menu_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE menu_id_seq OWNED BY menu.id;


--
-- Name: menu_item; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE menu_item (
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


--
-- Name: TABLE menu_item; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE menu_item IS 'simple editor definition table. define items';


--
-- Name: menu_item_display; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE menu_item_display (
    menu_name character varying(40) NOT NULL,
    column_name character varying(40),
    length smallint,
    "precision" smallint,
    id integer NOT NULL
);


--
-- Name: TABLE menu_item_display; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE menu_item_display IS 'used for menu item formatting.';


--
-- Name: menu_option_queries; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE menu_option_queries (
    menu_name character varying(40) NOT NULL,
    column_name character varying(40) NOT NULL,
    query text,
    id integer NOT NULL
);


--
-- Name: TABLE menu_option_queries; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE menu_option_queries IS 'produces html select lists or sequences for menu_items.';


--
-- Name: menu_item_defs; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW menu_item_defs AS
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
    mi.regex_name
   FROM ((((menu m
     JOIN menu_item mi USING (menu_name))
     JOIN all_tab_columns atc ON ((((m.relation_name)::text = atc.table_name) AND ((mi.column_name)::text = atc.column_name))))
     LEFT JOIN menu_option_queries moq ON ((((m.menu_name)::text = (moq.menu_name)::text) AND ((mi.column_name)::text = (moq.column_name)::text))))
     LEFT JOIN menu_item_display mid ON ((((mid.menu_name)::text = (mi.menu_name)::text) AND ((mid.column_name)::text = (mi.column_name)::text))));


--
-- Name: menu_item_display_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE menu_item_display_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: menu_item_display_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE menu_item_display_id_seq OWNED BY menu_item_display.id;


--
-- Name: menu_item_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE menu_item_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: menu_item_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE menu_item_id_seq OWNED BY menu_item.id;


--
-- Name: menu_option_queries_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE menu_option_queries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: menu_option_queries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE menu_option_queries_id_seq OWNED BY menu_option_queries.id;


SET default_with_oids = false;

--
-- Name: milestone_grade; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE milestone_grade (
    milestone_grade_id bigint NOT NULL,
    snummer integer,
    prjm_id integer,
    grade numeric(3,1),
    multiplier double precision DEFAULT 1.0,
    trans_id bigint,
    CONSTRAINT milestone_grade_grade_check CHECK ((grade > (0)::numeric)),
    CONSTRAINT milestone_grade_grade_check1 CHECK ((grade <= (10)::numeric))
);


--
-- Name: TABLE milestone_grade; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE milestone_grade IS 'Use to persist tutor grading through peer assessment results table.';


--
-- Name: milestone_grade_milestone_grade_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE milestone_grade_milestone_grade_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: milestone_grade_milestone_grade_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE milestone_grade_milestone_grade_id_seq OWNED BY milestone_grade.milestone_grade_id;


--
-- Name: milestone_grp; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW milestone_grp AS
 SELECT DISTINCT pm.prj_id,
    pm.milestone
   FROM (prj_tutor pt
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
  ORDER BY pm.prj_id, pm.milestone;


--
-- Name: milestone_open_past_due; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW milestone_open_past_due AS
 SELECT pm.prj_id,
    pm.milestone,
    pg.snummer,
    pt.prjtg_id,
    pm.assessment_due
   FROM ((prj_grp pg
     JOIN prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
  WHERE (((pm.assessment_due < now()) AND (pg.prj_grp_open = true)) AND (pm.prj_milestone_open = true));


--
-- Name: mini2013; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE mini2013 (
    _id integer NOT NULL,
    snummer integer,
    first_name character varying(15),
    day character varying(3),
    hai_class character varying(5),
    email character varying(44),
    name_2 character varying(28),
    naam_nln character varying(20),
    grp_num integer
);


--
-- Name: mini2013__id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE mini2013__id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: mini2013__id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE mini2013__id_seq OWNED BY mini2013._id;


--
-- Name: minifeb2015; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE minifeb2015 (
    minifeb2015_id integer NOT NULL,
    snummer integer,
    name character varying(20),
    mini_number integer
);


--
-- Name: minifeb2015_minifeb2015_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE minifeb2015_minifeb2015_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: minifeb2015_minifeb2015_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE minifeb2015_minifeb2015_id_seq OWNED BY minifeb2015.minifeb2015_id;


--
-- Name: registered_photos; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE registered_photos (
    snummer integer NOT NULL
);


--
-- Name: TABLE registered_photos; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE registered_photos IS 'students with photo.';


--
-- Name: portrait; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW portrait AS
 SELECT st.snummer,
    (('fotos/'::text || COALESCE((rf.snummer)::text, 'anonymous'::text)) || '.jpg'::text) AS photo
   FROM (student st
     LEFT JOIN registered_photos rf USING (snummer));


--
-- Name: minifoto; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW minifoto AS
 SELECT student.snummer,
    (((('<img src="'::text || portrait.photo) || '" alt="'::text) || (student.snummer)::text) || '" style="width:24px;height:auto"/>'::text) AS minifoto
   FROM (student
     JOIN portrait USING (snummer));


--
-- Name: minikiosk_visits; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE minikiosk_visits (
    counter integer NOT NULL
);


--
-- Name: TABLE minikiosk_visits; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE minikiosk_visits IS 'visit count for mini kiosk.';


--
-- Name: minir; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE minir (
    minir_id integer NOT NULL,
    nummer integer
);


--
-- Name: minir_minir_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE minir_minir_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: minir_minir_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE minir_minir_id_seq OWNED BY minir.minir_id;


--
-- Name: minissep2014; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE minissep2014 (
    minissep2014_id integer NOT NULL,
    snummer integer,
    last_name character varying(20),
    class_2nd_y character varying(5),
    mini integer
);


--
-- Name: minissep2014_minissep2014_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE minissep2014_minissep2014_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: minissep2014_minissep2014_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE minissep2014_minissep2014_id_seq OWNED BY minissep2014.minissep2014_id;


--
-- Name: module; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE module (
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


--
-- Name: COLUMN module.module_id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN module.module_id IS 'Generated sequence number';


--
-- Name: COLUMN module.duration; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN module.duration IS 'something like 1 semester of 7 weeks';


--
-- Name: module_activity; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE module_activity (
    module_activity_id character varying(10) NOT NULL
);


--
-- Name: module_activity_description; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE module_activity_description (
    module_activity_id character varying(10) NOT NULL,
    language_id character(2) NOT NULL,
    description character varying(50)
);


--
-- Name: module_desciption_long; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE module_desciption_long (
    module_id integer NOT NULL,
    language_id character(2) NOT NULL,
    description text
);


--
-- Name: module_description_short; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE module_description_short (
    module_id integer NOT NULL,
    language_id character(2) NOT NULL,
    description text
);


--
-- Name: module_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE module_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: SEQUENCE module_id_seq; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON SEQUENCE module_id_seq IS 'modules in curriculum';


--
-- Name: module_language; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE module_language (
    module_id integer NOT NULL,
    language_id character(2) NOT NULL
);


--
-- Name: module_part; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE module_part (
    module_id integer,
    progress_code character(6) DEFAULT 'UNDE11'::bpchar,
    credits double precision DEFAULT 1,
    module_part_id integer DEFAULT nextval(('module_part_seq'::text)::regclass) NOT NULL,
    part_description text DEFAULT 'default description'::text
);


--
-- Name: COLUMN module_part.credits; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN module_part.credits IS 'credits per part';


--
-- Name: module_part_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE module_part_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: module_participant; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW module_participant AS
 SELECT DISTINCT pm.prj_id,
    pg.snummer
   FROM ((prj_grp pg
     JOIN prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
  ORDER BY pm.prj_id, pg.snummer;


--
-- Name: module_participant_hours; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW module_participant_hours AS
 SELECT module_participant.prj_id,
    module_participant.snummer,
    course_hours.start_date,
    course_hours.stop_date,
    course_hours.course_week_no,
    course_hours.day,
    course_hours.hourcode,
    course_hours.start_time,
    course_hours.stop_time
   FROM module_participant,
    course_hours;


--
-- Name: module_prerequisite; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE module_prerequisite (
    module_id integer NOT NULL,
    prerequisite integer NOT NULL
);


--
-- Name: module_resource; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE module_resource (
    module_id integer NOT NULL,
    module_resource_id integer NOT NULL,
    module_resource_type_id character varying(10),
    description character varying(10)
);


--
-- Name: module_resource_type; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE module_resource_type (
    module_resource_type_id character varying(10) NOT NULL
);


--
-- Name: module_resource_type_description; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE module_resource_type_description (
    module_resource_type_id character varying(10) NOT NULL,
    language_id character(2) NOT NULL,
    description character varying(50)
);


--
-- Name: module_topic; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE module_topic (
    module_id integer NOT NULL,
    module_topic_id integer NOT NULL,
    week_id integer NOT NULL,
    hour_id integer NOT NULL
);


--
-- Name: module_topic_description; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE module_topic_description (
    module_id integer NOT NULL,
    module_topic_id integer NOT NULL,
    language_id character(2) NOT NULL,
    description character varying(50)
);


--
-- Name: module_week_schedule; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE module_week_schedule (
    module_id integer NOT NULL,
    week_id integer NOT NULL,
    module_activity_id character varying(10) NOT NULL,
    hours_planned integer
);


--
-- Name: movable_student; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW movable_student AS
 SELECT judge_sum.prj_id,
    judge_sum.milestone,
    judge_sum.snummer
   FROM (judge_sum
     JOIN contestant_sum USING (prj_id, milestone, snummer, grade_sum))
  WHERE (judge_sum.grade_sum = 0);


--
-- Name: mr; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE mr (
    snummer integer
);


--
-- Name: my_peer_results_2; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW my_peer_results_2 AS
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
   FROM ((((((grp_crit_avg gca
     JOIN contestant_crit_avg cca USING (prjtg_id, criterium))
     JOIN grp_tg_size gts USING (prjtg_id))
     JOIN prj_tutor pt USING (prjtg_id))
     JOIN tutor t ON ((pt.tutor_id = t.userid)))
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
     JOIN criteria_v c ON (((pm.prjm_id = c.prjm_id) AND (c.criterium = gca.criterium))));


--
-- Name: repositories; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE repositories (
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


--
-- Name: TABLE repositories; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE repositories IS 'Repositories used in peerweb.';


--
-- Name: my_project_repositories; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW my_project_repositories AS
 SELECT pm.prj_id,
    (pm.milestone)::integer AS milestone,
    pg.snummer,
    r.grp_num,
    r.description,
    r.url_tail,
    r.id AS repo_id
   FROM (((repositories r
     JOIN prj_tutor pt ON (((r.prjm_id = pt.prjm_id) AND (r.grp_num = pt.grp_num))))
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
     JOIN prj_grp pg ON ((pt.prjtg_id = pg.prjtg_id)))
UNION
 SELECT pm.prj_id,
    pm.milestone,
    pg.snummer,
    0 AS grp_num,
    r.description,
    r.url_tail,
    r.id AS repo_id
   FROM (((repositories r
     JOIN prj_tutor pt ON (((r.prjm_id = pt.prjm_id) AND (r.grp_num = 0))))
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
     JOIN prj_grp pg ON ((pt.prjtg_id = pg.prjtg_id)));


--
-- Name: nat_mapper_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE nat_mapper_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: nat_mapper_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE nat_mapper_id_seq OWNED BY nat_mapper.id;


--
-- Name: nationality; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW nationality AS
 SELECT iso3166.a2 AS value,
    initcap((iso3166.country)::text) AS name
   FROM iso3166;


--
-- Name: VIEW nationality; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW nationality IS 'used in student_admin';


--
-- Name: naw; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW naw AS
 SELECT student.snummer,
    student.achternaam,
    student.roepnaam,
    student.voorvoegsel,
    student.straat,
    student.plaats,
    student.huisnr,
    student.pcode
   FROM student;


--
-- Name: newstudent; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE newstudent (
    snummer integer NOT NULL,
    achternaam character varying(20),
    voorvoegsel character varying(7),
    voorletters character varying(5),
    roepnaam character varying(14),
    straat character varying(30),
    huisnr character(4),
    pcode character(7),
    plaats character varying(30),
    email1 character varying(42),
    nationaliteit character(2),
    cohort integer,
    gebdat date,
    sex character varying(1),
    lang character(2),
    pcn integer,
    opl bigint,
    phone_home character varying(40),
    phone_gsm character varying(40),
    phone_postaddress text,
    faculty_id integer,
    hoofdgrp character varying(8),
    active boolean,
    slb integer,
    land character(3),
    studieplan integer,
    geboorteplaats character varying(32),
    geboorteland text,
    voornaam character varying(14),
    class_id integer
);


--
-- Name: newstudent_v; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW newstudent_v AS
 SELECT a.studentnummer AS snummer,
    a.achternaam,
    a.voorvoegsels AS voorvoegsel,
    a.voorletters,
    a.roepnaam,
    a.straat,
    a.huisnr,
    a.postcode AS pcode,
    a.woonplaats AS plaats,
    a.e_mail_instelling AS email1,
    a.nationaliteit,
    2014 AS cohort,
    a.geboortedatum AS gebdat,
    a.sex,
    a.lang,
    a.pcn_nummer AS pcn,
    a.opl,
    a.phone_home,
    a.phone_gsm,
    a.phone_postaddress,
    47 AS faculty_id,
    a.hoofdgrp,
    true AS active,
    a.slb,
    a.land,
    a.studielinkvariantcode AS studieplan,
    a.geboorteplaats,
    a.geboorteland,
    a.voornamen,
    c.class_id
   FROM (aanmelding20140827 a
     JOIN classes_in_2014 c ON ((a.hoofdgrp = c.sclass)))
  WHERE (NOT (a.studentnummer IN ( SELECT student.snummer
           FROM student)));


--
-- Name: newstudents; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE newstudents (
    snummer integer,
    achternaam character varying(30),
    voorvoegsel character(10),
    voorletters character(10),
    roepnaam character(20),
    straat character varying(30),
    huisnr character(4),
    pcode character(7),
    plaats character varying(40),
    email1 character varying(50),
    nationaliteit character(3),
    cohort integer,
    gebdat date,
    sex character(3),
    lang character(2),
    pcn integer,
    opl bigint,
    phone_home character varying(40),
    phone_gsm character varying(40),
    phone_postaddress character varying(40),
    faculty_id integer,
    hoofdgrp character(10),
    active boolean,
    slb integer,
    land character(3),
    studieplan integer,
    geboorteplaats character varying(40),
    geboorteland character(3),
    voornamen character varying(50),
    class_id integer
);


--
-- Name: page_help; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE page_help (
    help_id integer NOT NULL,
    page character varying(40),
    author integer,
    helptext text
);


--
-- Name: TABLE page_help; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE page_help IS 'diy help texts';


--
-- Name: page_help_help_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE page_help_help_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: page_help_help_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE page_help_help_id_seq OWNED BY page_help.help_id;


--
-- Name: participant_present_list; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW participant_present_list AS
 SELECT mh.snummer,
    mh.prj_id,
    mh.course_week_no,
    mh.day,
    mh.hourcode,
    lt.from_ip,
    lt.since
   FROM (module_participant_hours mh
     LEFT JOIN logon_map_on_timetable lt ON ((((((mh.course_week_no = lt.course_week_no) AND (mh.day = lt.day)) AND (mh.hourcode = lt.hourcode)) AND (mh.snummer = lt.snummer)) OR (lt.snummer IS NULL))));


SET default_with_oids = true;

--
-- Name: password_request; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE password_request (
    userid integer NOT NULL,
    request_time timestamp without time zone DEFAULT now() NOT NULL,
    id bigint NOT NULL
);


--
-- Name: TABLE password_request; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE password_request IS 'log password requests.';


--
-- Name: password_request_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE password_request_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: password_request_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE password_request_id_seq OWNED BY password_request.id;


SET default_with_oids = false;

--
-- Name: peer_settings; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE peer_settings (
    key character varying(30) NOT NULL,
    value text,
    comment text
);


--
-- Name: TABLE peer_settings; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE peer_settings IS 'several settings which would otherwise be hardcode. 
in php read on each request.';


--
-- Name: personal_repos; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE personal_repos (
    owner integer,
    repospath text,
    url_tail text,
    isroot boolean DEFAULT false,
    id integer NOT NULL,
    description text,
    youngest integer DEFAULT 0,
    last_commit timestamp without time zone
);


--
-- Name: TABLE personal_repos; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE personal_repos IS 'personal repositories, created through peerweb.';


--
-- Name: personal_repos_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE personal_repos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: personal_repos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE personal_repos_id_seq OWNED BY personal_repos.id;


--
-- Name: pg20141031; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE pg20141031 (
    snummer integer,
    prj_grp_open boolean,
    written boolean,
    prjtg_id integer
);


--
-- Name: planned_school_visit; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE planned_school_visit (
    planned_school_visit integer NOT NULL,
    trans_id bigint,
    visit_date date NOT NULL,
    visit_start_time time without time zone NOT NULL,
    visit_end_time time without time zone,
    visit_short character varying(30),
    visit_description text,
    visit_scholen_id integer,
    visit_schulen_id integer
);


--
-- Name: TABLE planned_school_visit; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE planned_school_visit IS 'For recruitement, planning.';


--
-- Name: planned_school_visit_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE planned_school_visit_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: planned_school_visit_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE planned_school_visit_seq OWNED BY planned_school_visit.planned_school_visit;


--
-- Name: portrait_with_name; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW portrait_with_name AS
 SELECT s.snummer,
    ((((s.roepnaam)::text || ' '::text) || COALESCE(((s.voorvoegsel)::text || ' '::text), ''::text)) || (s.achternaam)::text) AS name,
    (('fotos/'::text || COALESCE((rp.snummer)::text, 'anonymous'::text)) || '.jpg'::text) AS image
   FROM (student s
     LEFT JOIN registered_photos rp USING (snummer));


--
-- Name: VIEW portrait_with_name; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW portrait_with_name IS 'used for wsb password creation scripts';


SET default_with_oids = true;

--
-- Name: weekdays; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE weekdays (
    day smallint NOT NULL,
    dayname character varying(12),
    day_lang character(2),
    shortname character(2)
);


--
-- Name: TABLE weekdays; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE weekdays IS 'Days of the weeks.';


--
-- Name: present_anywhere; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW present_anywhere AS
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
   FROM logon,
    (timetableweek
     JOIN weekdays USING (day))
  WHERE (((to_char(logon.since, 'HH24:MI:SS'::text) >= (timetableweek.start_time)::text) AND (to_char(logon.since, 'HH24:MI:SS'::text) <= (timetableweek.stop_time)::text)) AND (date_part('dow'::text, logon.since) = (timetableweek.day)::double precision));


--
-- Name: present_at_fontys; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW present_at_fontys AS
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
   FROM present_anywhere
  WHERE (present_anywhere.from_ip <<= '145.85.0.0/16'::inet);


--
-- Name: present_in_coursehours; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW present_in_coursehours AS
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
   FROM (course_hours ch
     LEFT JOIN logon lo ON ((((lo.since)::text >= (ch.start_time)::text) AND ((lo.since)::text <= (ch.stop_time)::text))));


--
-- Name: present_in_courseweek; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW present_in_courseweek AS
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
   FROM (course_week
     LEFT JOIN present_anywhere ON (((present_anywhere.since >= (course_week.start_date)::timestamp without time zone) AND (present_anywhere.since <= (course_week.stop_date)::timestamp without time zone))));


SET default_with_oids = false;

--
-- Name: presse; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE presse (
    presse_id integer NOT NULL,
    firmentype character varying(18),
    firma character varying(35),
    kontaktperson character varying(22),
    abteilung character varying(41),
    adresse character varying(34),
    plz integer,
    ort character varying(17),
    telefon character varying(16),
    telefax character varying(17),
    email_allgemein character varying(43),
    email_kontaktperson character varying(42),
    website character varying(37)
);


--
-- Name: TABLE presse; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE presse IS 'For recruitement.';


--
-- Name: presse__id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE presse__id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: presse__id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE presse__id_seq OWNED BY presse.presse_id;


--
-- Name: scholen_int; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE scholen_int (
    scholen_int_id integer NOT NULL,
    nr_administratie character varying(6),
    naam_volledig character varying(79),
    naam_straat_vest character varying(24),
    nr_huis_vest character varying(6),
    postcode_vest character varying(6),
    naam_plaats_vest character varying(19),
    gemeentenaam character varying(19),
    naam_straat_corr character varying(23),
    nr_huis_corr integer,
    postcode_corr character varying(6),
    naam_plaats_corr character varying(19),
    naam_functie character varying(20),
    school_type character(10),
    CONSTRAINT scholen_int_typ_check CHECK ((school_type = ANY (ARRAY['MBO'::bpchar, 'VWO'::bpchar, 'HAVO'::bpchar, 'OVERIG'::bpchar])))
);


--
-- Name: TABLE scholen_int; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE scholen_int IS 'for recruitement.';


--
-- Name: scholen_int_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE scholen_int_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: scholen_int_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE scholen_int_id_seq OWNED BY scholen_int.scholen_int_id;


--
-- Name: schulen; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE schulen (
    schulen_id integer DEFAULT nextval('scholen_int_id_seq'::regclass) NOT NULL,
    schultyp character(10),
    naam_school character varying(71),
    aan character varying(43),
    adres character varying(29),
    postcode character varying(5),
    woonplaats character varying(26),
    telefon character varying(26),
    telefon_alt character varying(25),
    telefax character varying(27),
    email character varying(50),
    url character varying(80)
);


--
-- Name: TABLE schulen; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE schulen IS 'For recruitement.';


--
-- Name: prev_school_view; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW prev_school_view AS
 SELECT schulen.schulen_id AS school_id,
    schulen.schultyp AS school_type,
    schulen.naam_school AS school_name,
    schulen.woonplaats AS plaats
   FROM schulen
UNION
 SELECT scholen_int.scholen_int_id AS school_id,
    scholen_int.school_type,
    scholen_int.naam_volledig AS school_name,
    scholen_int.naam_plaats_vest AS plaats
   FROM scholen_int
  ORDER BY 4, 3;


--
-- Name: prj2_2015; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE prj2_2015 (
    prj2_2015_id integer NOT NULL,
    snummer integer,
    naam character varying(20),
    roepnaam character varying(10),
    code character varying(5),
    level integer,
    grp integer
);


--
-- Name: prj2_2015_prj2_2015_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE prj2_2015_prj2_2015_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: prj2_2015_prj2_2015_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE prj2_2015_prj2_2015_id_seq OWNED BY prj2_2015.prj2_2015_id;


--
-- Name: prj2_student; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE prj2_student (
    snummer integer,
    achternaam character varying(30),
    voorvoegsel character varying(10),
    voorletters character varying(10),
    roepnaam character varying(20),
    straat character varying(30),
    huisnr character(4),
    pcode character(7),
    plaats character varying(30),
    email1 character varying(50),
    nationaliteit character(2),
    cohort smallint,
    gebdat date,
    sex character(1),
    lang character(2),
    pcn integer,
    opl bigint,
    phone_home character varying(40),
    phone_gsm character varying(40),
    phone_postaddress character varying(40),
    faculty_id smallint,
    hoofdgrp character(10),
    active boolean,
    slb integer,
    land character(3),
    studieplan integer,
    geboorteplaats character varying(40),
    geboorteland character(3),
    voornaam character varying(40),
    class_id integer
);


SET default_with_oids = true;

--
-- Name: prj_contact; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE prj_contact (
    snummer integer,
    prjtg_id integer NOT NULL
);


--
-- Name: TABLE prj_contact; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE prj_contact IS 'Contact is central person in a group that is primary contact for tutor and group.';


--
-- Name: prj_grp_builder2; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW prj_grp_builder2 AS
 SELECT pmt.prj_id,
    pgo.snummer,
    pmt.milestone,
    false AS prj_grp_open,
    ptt.grp_num,
    false AS written,
    pmt.prjm_id,
    ptt.prjtg_id,
    pto.prjm_id AS orig_prjm_id
   FROM ((((prj_grp pgo
     JOIN prj_tutor pto ON ((pgo.prjtg_id = pto.prjtg_id)))
     JOIN prj_milestone pmo ON ((pto.prjm_id = pmo.prjm_id)))
     JOIN prj_tutor ptt ON ((pto.grp_num = ptt.grp_num)))
     JOIN prj_milestone pmt ON ((ptt.prjm_id = pmt.prjm_id)));


--
-- Name: VIEW prj_grp_builder2; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW prj_grp_builder2 IS 'used in copying project groups';


--
-- Name: prj_grp_email; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW prj_grp_email AS
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
   FROM ((((((student s
     JOIN prj_grp pg USING (snummer))
     JOIN prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
     JOIN project p ON ((pm.prj_id = p.prj_id)))
     JOIN fontys_course USING (course))
     LEFT JOIN grp_alias ga ON ((pt.prjtg_id = ga.prjtg_id)))
  ORDER BY pt.grp_num;


--
-- Name: VIEW prj_grp_email; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW prj_grp_email IS 'used to create maillist per project group';


--
-- Name: prj_grp_email_g0; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW prj_grp_email_g0 AS
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
   FROM (((((student s
     JOIN prj_grp pg USING (snummer))
     JOIN prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
     JOIN project p ON ((pm.prj_id = p.prj_id)))
     JOIN fontys_course USING (course))
  ORDER BY s.achternaam, s.roepnaam;


--
-- Name: VIEW prj_grp_email_g0; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW prj_grp_email_g0 IS 'used to create maillist for all members of project';


--
-- Name: prj_grp_open; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW prj_grp_open AS
 SELECT bool_and(pg.prj_grp_open) AS bool_and,
    pm.prj_id,
    pm.milestone,
    pt.grp_num,
    pt.prjtg_id
   FROM ((prj_grp pg
     JOIN prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
  GROUP BY pm.prj_id, pm.milestone, pt.grp_num, pt.prjtg_id;


--
-- Name: prj_grp_ready; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW prj_grp_ready AS
 SELECT prj_grp.prjtg_id,
    (bool_and(prj_grp.written) AND (NOT bool_and(prj_grp.prj_grp_open))) AS ready
   FROM prj_grp
  GROUP BY prj_grp.prjtg_id;


--
-- Name: prj_grp_tr; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW prj_grp_tr AS
 SELECT prj_grp.prjtg_id,
    prj_grp.snummer,
    prj_grp.prj_grp_open,
    prj_grp.written
   FROM prj_grp;


--
-- Name: VIEW prj_grp_tr; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW prj_grp_tr IS 'prj_grp with prj_id, milestone, prjm_id and grp_num dropped';


--
-- Name: prj_tutor_builder; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW prj_tutor_builder AS
 SELECT pm.prj_id,
    t.tutor,
    pt.tutor_id,
    pm.milestone,
    pt.grp_num,
    pm.prjm_id,
    pt.prjtg_id,
    pt.grp_name
   FROM ((prj_milestone pm
     JOIN prj_tutor pt USING (prjm_id))
     JOIN tutor t ON ((t.userid = pt.tutor_id)));


--
-- Name: prj_tutor_email; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW prj_tutor_email AS
 SELECT DISTINCT pt.prjm_id,
    pt.afko,
    pt.year,
    'tutors'::text AS alias,
    (lower((((((btrim((fontys_course.course_short)::text) || '.'::text) || btrim((pt.afko)::text)) || '.'::text) || pt.year) || '.'::text)) || 'tutors'::text) AS maillist,
    s.email1,
    (-1) AS grp_num,
    0 AS prjtg_id,
    s.achternaam,
    s.roepnaam
   FROM (((all_prj_tutor pt
     JOIN tutor t ON ((pt.tutor_id = t.userid)))
     JOIN student s ON ((t.userid = s.snummer)))
     JOIN fontys_course USING (course));


--
-- Name: VIEW prj_tutor_email; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW prj_tutor_email IS 'used to create maillist per project group';


--
-- Name: prj_tutor_prjtg_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE prj_tutor_prjtg_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: prj_tutor_prjtg_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE prj_tutor_prjtg_id_seq OWNED BY prj_tutor.prjtg_id;


--
-- Name: prj_tutor_tr; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW prj_tutor_tr AS
 SELECT pt.prjm_id,
    pt.grp_num,
    t.tutor,
    pt.tutor_id,
    pt.prjtg_id,
    pt.prj_tutor_open,
    pt.assessment_complete
   FROM (prj_tutor pt
     JOIN tutor t ON ((pt.tutor_id = t.userid)));


--
-- Name: VIEW prj_tutor_tr; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW prj_tutor_tr IS 'prj_tutor with prj_id and milestone dropped';


--
-- Name: prjm_activity_count; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW prjm_activity_count AS
 SELECT activity.prjm_id,
    count(*) AS act_count
   FROM activity
  GROUP BY activity.prjm_id;


--
-- Name: VIEW prjm_activity_count; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW prjm_activity_count IS 'used by peerpresenceoverview';


--
-- Name: prjm_size; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW prjm_size AS
 SELECT pm.prjm_id,
    count(*) AS size
   FROM ((prj_grp pg
     JOIN prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pm.prjm_id = pt.prjm_id)))
  GROUP BY pm.prjm_id;


--
-- Name: prjtg_size; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW prjtg_size AS
 SELECT count(*) AS size,
    prj_grp.prjtg_id
   FROM prj_grp
  GROUP BY prj_grp.prjtg_id;


SET default_with_oids = false;

--
-- Name: project_attributes_def; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE project_attributes_def (
    project_attributes_def integer NOT NULL,
    author integer,
    prj_id integer,
    pi_name character varying(40),
    pi_description text,
    interpretation character(1) DEFAULT 'N'::bpchar,
    due_date date
);


--
-- Name: TABLE project_attributes_def; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE project_attributes_def IS 'Define project (group) attributes, simple key value pairs like appraisal, performance number like profit of defect percentage etc. Freely defined by project owner.';


--
-- Name: project_attributes_def_project_attributes_def_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE project_attributes_def_project_attributes_def_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: project_attributes_def_project_attributes_def_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE project_attributes_def_project_attributes_def_seq OWNED BY project_attributes_def.project_attributes_def;


--
-- Name: project_attributes_values; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE project_attributes_values (
    project_attributes_def integer,
    pi_value text,
    prjtg_id integer,
    trans_id bigint,
    id integer NOT NULL
);


--
-- Name: TABLE project_attributes_values; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE project_attributes_values IS 'Values of project attributes per group and milestone.';


--
-- Name: COLUMN project_attributes_values.id; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN project_attributes_values.id IS 'pk';


--
-- Name: project_attributes_values_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE project_attributes_values_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: project_attributes_values_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE project_attributes_values_id_seq OWNED BY project_attributes_values.id;


--
-- Name: project_auditor; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE project_auditor (
    snummer integer,
    prjm_id integer,
    gid integer,
    id integer NOT NULL
);


--
-- Name: TABLE project_auditor; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE project_auditor IS 'Someone with an interest in the group, like a scribe (for presence recording).
';


--
-- Name: project_auditor_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE project_auditor_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: project_auditor_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE project_auditor_id_seq OWNED BY project_auditor.id;


--
-- Name: project_deliverables_pdeliverable_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE project_deliverables_pdeliverable_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: project_deliverables_pdeliverable_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE project_deliverables_pdeliverable_id_seq OWNED BY project_deliverables.pdeliverable_id;


--
-- Name: project_deliverables_tr; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW project_deliverables_tr AS
 SELECT project_deliverables.prjm_id,
    project_deliverables.doctype,
    project_deliverables.version_limit,
    project_deliverables.due,
    project_deliverables.publish_early,
    project_deliverables.rights
   FROM project_deliverables;


--
-- Name: VIEW project_deliverables_tr; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW project_deliverables_tr IS 'project_deliverables minus prj_id and milestone';


--
-- Name: project_grade_weight_sum_product; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW project_grade_weight_sum_product AS
 SELECT prj_milestone.prj_id,
    milestone_grade.snummer,
    sum((milestone_grade.grade * (prj_milestone.weight)::numeric)) AS grade_weight_sum,
    sum(prj_milestone.weight) AS weight_sum
   FROM (prj_milestone
     LEFT JOIN milestone_grade USING (prjm_id))
  GROUP BY prj_milestone.prj_id, milestone_grade.snummer;


--
-- Name: project_group; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW project_group AS
 SELECT (student.snummer)::text AS username,
    p.password,
    pt.prjm_id,
    pm.prj_id,
    pm.milestone,
    pt.grp_num AS gid
   FROM ((((student
     JOIN prj_grp pg USING (snummer))
     JOIN prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pm.prjm_id = pt.prjm_id)))
     JOIN passwd p ON ((student.snummer = p.userid)))
UNION
 SELECT (pt.tutor_id)::text AS username,
    p.password,
    pt.prjm_id,
    pm.prj_id,
    pm.milestone,
    0 AS gid
   FROM ((prj_tutor pt
     JOIN prj_milestone pm ON ((pm.prjm_id = pt.prjm_id)))
     JOIN passwd p ON ((p.userid = pt.tutor_id)))
UNION
 SELECT (project_auditor.snummer)::text AS username,
    p.password,
    project_auditor.prjm_id,
    prj_milestone.prj_id,
    prj_milestone.milestone,
    project_auditor.gid
   FROM ((project_auditor
     JOIN prj_milestone USING (prjm_id))
     JOIN passwd p ON ((project_auditor.snummer = p.userid)));


--
-- Name: project_grp_stakeholders; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW project_grp_stakeholders AS
 SELECT pg.snummer,
    pg.prjtg_id
   FROM prj_grp pg
UNION
 SELECT prj_tutor.tutor_id AS snummer,
    prj_tutor.prjtg_id
   FROM prj_tutor;


--
-- Name: project_member; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW project_member AS
 SELECT DISTINCT pm.prj_id,
    pg.snummer
   FROM ((prj_milestone pm
     JOIN prj_tutor pt USING (prjm_id))
     JOIN prj_grp pg USING (prjtg_id));


--
-- Name: VIEW project_member; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW project_member IS 'project member without milstone';


--
-- Name: project_prj_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE project_prj_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: project_prj_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE project_prj_id_seq OWNED BY project.prj_id;


--
-- Name: project_roles; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE project_roles (
    prj_id smallint NOT NULL,
    role character varying(30) DEFAULT 'Project manager'::character varying NOT NULL,
    rolenum smallint NOT NULL,
    capabilities integer DEFAULT 0,
    short character(4) NOT NULL
);


--
-- Name: TABLE project_roles; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE project_roles IS 'Roles defined in a project';


--
-- Name: project_scribe_project_scribe_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE project_scribe_project_scribe_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: project_scribe_project_scribe_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE project_scribe_project_scribe_id_seq OWNED BY project_scribe.project_scribe_id;


--
-- Name: project_task; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE project_task (
    prj_id integer NOT NULL,
    task_id integer NOT NULL,
    name character varying(20) DEFAULT 'undefined task'::character varying NOT NULL,
    description text DEFAULT 'description will follow'::text,
    task_number integer DEFAULT 1
);


--
-- Name: TABLE project_task; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE project_task IS 'tasks to be checked by tutors or assistants';


--
-- Name: project_task_completed; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE project_task_completed (
    task_id integer NOT NULL,
    snummer integer NOT NULL,
    mark character(1),
    comment text,
    trans_id bigint NOT NULL,
    grade numeric(3,1)
);


--
-- Name: TABLE project_task_completed; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE project_task_completed IS 'Tasks completed for project with grade and comment field.';


--
-- Name: project_task_completed_max_trans; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW project_task_completed_max_trans AS
 SELECT max(project_task_completed.trans_id) AS trans_id,
    project_task_completed.task_id,
    project_task_completed.snummer
   FROM project_task_completed
  GROUP BY project_task_completed.task_id, project_task_completed.snummer;


--
-- Name: project_task_completed_latest; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW project_task_completed_latest AS
 SELECT ptc.task_id,
    ptc.snummer,
    ptc.mark,
    ptc.grade,
    ptc.comment,
    ptc.trans_id
   FROM project_task_completed ptc
  WHERE (ptc.trans_id = ( SELECT project_task_completed_max_trans.trans_id
           FROM project_task_completed_max_trans
          WHERE ((ptc.snummer = project_task_completed_max_trans.snummer) AND (ptc.task_id = project_task_completed_max_trans.task_id))));


--
-- Name: project_task_task_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE project_task_task_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: project_task_task_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE project_task_task_id_seq OWNED BY project_task.task_id;


--
-- Name: project_tasks; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE project_tasks (
    prj_id smallint NOT NULL,
    task_id smallint DEFAULT 0 NOT NULL,
    task_description character varying(40) DEFAULT 'idle'::character varying NOT NULL,
    snummer integer NOT NULL
);


--
-- Name: TABLE project_tasks; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE project_tasks IS 'tasks in project, individual per user';


--
-- Name: project_tutor_owner; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW project_tutor_owner AS
 SELECT s.roepnaam,
    s.voorvoegsel,
    s.achternaam,
    s.email1,
    s.snummer,
    p.prj_id
   FROM (project p
     JOIN student s ON ((p.owner_id = s.snummer)));


--
-- Name: project_weight_sum; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW project_weight_sum AS
 SELECT prj_milestone.prj_id,
    sum(prj_milestone.weight) AS weight_sum
   FROM prj_milestone
  GROUP BY prj_milestone.prj_id;


--
-- Name: ready_judge_count; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW ready_judge_count AS
 SELECT prj_grp.prjtg_id,
    count(*) AS count
   FROM prj_grp
  WHERE (prj_grp.written = true)
  GROUP BY prj_grp.prjtg_id;


--
-- Name: recruiters_note; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE recruiters_note (
    recruiters_note_id integer NOT NULL,
    followup integer,
    trans_id bigint,
    note_text text
);


--
-- Name: TABLE recruiters_note; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE recruiters_note IS 'For recruiting. Take notes.';


--
-- Name: recruiters_note_recruiters_note_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE recruiters_note_recruiters_note_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: recruiters_note_recruiters_note_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE recruiters_note_recruiters_note_id_seq OWNED BY recruiters_note.recruiters_note_id;


--
-- Name: register; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE register (
    snummer integer
);


--
-- Name: registerl; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE registerl (
    snummer integer
);


--
-- Name: repos_group_name; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW repos_group_name AS
 SELECT pm.prj_id,
    pt.tutor_id AS owner,
    pm.milestone,
    pt.grp_num,
    pt.prjm_id,
    pt.prjtg_id,
    btrim((COALESCE(pt.grp_name, (('g'::text || pt.grp_num))::character varying))::text) AS group_name
   FROM (prj_tutor pt
     JOIN prj_milestone pm USING (prjm_id));


--
-- Name: VIEW repos_group_name; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW repos_group_name IS 'used to create repository entries';


--
-- Name: repositories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE repositories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: repositories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE repositories_id_seq OWNED BY repositories.id;


--
-- Name: resit; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE resit (
    _id integer NOT NULL,
    resit integer
);


--
-- Name: resit__id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE resit__id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: resit__id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE resit__id_seq OWNED BY resit._id;


--
-- Name: resitexpected; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE resitexpected (
    resitexpected_id integer NOT NULL,
    snummer integer
);


--
-- Name: resitexpected_resitexpected_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE resitexpected_resitexpected_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: resitexpected_resitexpected_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE resitexpected_resitexpected_id_seq OWNED BY resitexpected.resitexpected_id;


--
-- Name: schedule_hours; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE schedule_hours (
    day smallint,
    hourcode smallint,
    start_time time without time zone,
    stop_time time without time zone
);


--
-- Name: semester; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE semester (
    id smallint DEFAULT nextval(('semester_seq'::text)::regclass) NOT NULL,
    semnr smallint DEFAULT 1,
    cohort smallint,
    theme text
);


--
-- Name: TABLE semester; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE semester IS 'semester with themes for cohort';


--
-- Name: COLUMN semester.semnr; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN semester.semnr IS 'sem nr 1..8';


--
-- Name: COLUMN semester.cohort; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN semester.cohort IS 'cohort year for theme of semester';


--
-- Name: semester_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE semester_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


SET default_with_oids = true;

--
-- Name: session_data; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE session_data (
    snummer integer NOT NULL,
    session text
);


--
-- Name: TABLE session_data; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE session_data IS 'Persists most of session, so loging out has some benefits.';


--
-- Name: should_close_group_tutor; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW should_close_group_tutor AS
 SELECT ((pt.prj_tutor_open = true) AND (pt.prj_tutor_open <> algo.any_group_open)) AS should_close,
    pt.prj_tutor_open,
    algo.any_group_open,
    pt.prjtg_id
   FROM (prj_tutor pt
     JOIN ( SELECT bool_or(prj_grp.prj_grp_open) AS any_group_open,
            prj_grp.prjtg_id
           FROM prj_grp
          GROUP BY prj_grp.prjtg_id) algo USING (prjtg_id));


--
-- Name: should_close_prj_milestone; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW should_close_prj_milestone AS
 SELECT ((pm.prj_milestone_open = true) AND (pm.prj_milestone_open <> alpt.any_tutor_open)) AS should_close,
    pm.prj_milestone_open,
    alpt.any_tutor_open,
    pm.prjm_id
   FROM (prj_milestone pm
     JOIN ( SELECT bool_or(prj_tutor.prj_tutor_open) AS any_tutor_open,
            prj_tutor.prjm_id
           FROM prj_tutor
          GROUP BY prj_tutor.prjm_id) alpt USING (prjm_id));


--
-- Name: slb_projects; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW slb_projects AS
 SELECT project.prj_id
   FROM project
  WHERE (project.afko = 'SLB'::bpchar);


--
-- Name: statsvn_names; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW statsvn_names AS
 SELECT ((((('user.'::text || prj_grp.snummer) || '.realName='::text) || (student.achternaam)::text) || ','::text) || (student.roepnaam)::text) AS member,
    prj_tutor.prjm_id,
    prj_tutor.grp_num
   FROM (((prj_grp
     JOIN student USING (snummer))
     JOIN prj_tutor USING (prjtg_id))
     JOIN prj_milestone USING (prjm_id));


--
-- Name: stdresult; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW stdresult AS
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
           FROM assessment
          GROUP BY assessment.contestant, assessment.prjtg_id, assessment.criterium) a
     JOIN prj_tutor pt ON ((a.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pm.prjm_id = pt.prjtg_id)));


--
-- Name: VIEW stdresult; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW stdresult IS 'used by ~/_include/test/peerutils,tutor/groupresult.php';


--
-- Name: stdresult2; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW stdresult2 AS
 SELECT p.prjtg_id,
    a.contestant AS snummer,
    a.criterium,
    avg(a.grade) AS grade
   FROM (prj_grp p
     JOIN assessment a ON (((p.snummer = a.contestant) AND (p.prjtg_id = a.prjtg_id))))
  GROUP BY p.prjtg_id, a.criterium, a.contestant;


--
-- Name: stdresult_overall; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW stdresult_overall AS
 SELECT assessment.prjtg_id,
    assessment.contestant AS snummer,
    avg(assessment.grade) AS grade
   FROM assessment
  GROUP BY assessment.prjtg_id, assessment.contestant;


--
-- Name: VIEW stdresult_overall; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW stdresult_overall IS 'used by ~/_include/test/peerutils,tutor/groupresult.php';


SET default_with_oids = false;

--
-- Name: stp_inf; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE stp_inf (
    snummer integer,
    studieplan integer
);


--
-- Name: student_class2; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE student_class2 (
    sort1 integer,
    sort2 integer,
    comment text,
    faculty_id smallint,
    class_id integer,
    sclass character(10),
    class_cluster integer,
    owner integer
);


--
-- Name: student_class_name; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW student_class_name AS
 SELECT student.snummer,
    classes.sclass
   FROM (student
     JOIN student_class classes USING (class_id));


--
-- Name: student_class_size; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW student_class_size AS
 SELECT student.class_id,
    student.snummer,
    cs.student_count
   FROM ((student
     JOIN student_class classes USING (class_id))
     JOIN class_size cs USING (class_id));


--
-- Name: student_class_v; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW student_class_v AS
 SELECT student.snummer,
    student.class_id
   FROM student;


--
-- Name: student_email; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW student_email AS
 SELECT student.snummer,
    student.achternaam,
    student.voorvoegsel,
    student.voorletters,
    student.roepnaam,
    student.straat,
    student.huisnr,
    student.pcode,
    student.plaats,
    student.email1,
    student.nationaliteit,
    student.hoofdgrp,
    student.active,
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
    alt_email.email2,
    student.slb,
    COALESCE((registered_photos.snummer || '.jpg'::text), 'anonymous.jpg'::text) AS image,
    student.class_id,
    student.studieplan,
    student.geboorteplaats,
    student.geboorteland,
    student.voornaam
   FROM ((student
     LEFT JOIN alt_email USING (snummer))
     LEFT JOIN registered_photos USING (snummer));


--
-- Name: student_latin1; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW student_latin1 AS
 SELECT student.snummer,
    convert_to((student.achternaam)::text, 'iso_8859_1'::name) AS achternaam,
    convert_to((student.voorvoegsel)::text, 'iso_8859_1'::name) AS voorvoegsel,
    student.voorletters,
    convert_to((student.roepnaam)::text, 'iso_8859_1'::name) AS roepnaam,
    convert_to((student.straat)::text, 'iso_8859_1'::name) AS straat,
    student.huisnr,
    student.pcode,
    convert_to((student.plaats)::text, 'iso_8859_1'::name) AS plaats,
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
   FROM student;


--
-- Name: VIEW student_latin1; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW student_latin1 IS 'win/excel do not grasp utf-8 encoding header';


--
-- Name: student_name_email; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW student_name_email AS
 SELECT student.snummer,
    convert_to(rtrim((student.roepnaam)::text), 'latin1'::name) AS roepnaam,
    student.voorvoegsel,
    convert_to(rtrim((student.achternaam)::text), 'latin1'::name) AS achternaam
   FROM student;


--
-- Name: student_plus; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW student_plus AS
 SELECT student.snummer,
    student.achternaam,
    student.voorvoegsel,
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
   FROM (student
     LEFT JOIN alt_email USING (snummer));


--
-- Name: student_project_attributes; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW student_project_attributes AS
 SELECT DISTINCT s.snummer,
    pg.snummer AS has_project,
    p.afko,
    p.year,
    p.description,
    pm.milestone,
    pt.grp_num,
    pt.prjtg_id,
    p.valid_until,
    ag.snummer AS has_assessment,
    hd.prjm_id AS has_doc
   FROM ((((((student s
     JOIN prj_grp pg USING (snummer))
     JOIN prj_tutor pt USING (prjtg_id))
     JOIN prj_milestone pm USING (prjm_id))
     JOIN project p USING (prj_id))
     LEFT JOIN assessment_groups ag USING (snummer, prjtg_id))
     LEFT JOIN project_deliverables hd USING (prjm_id))
  ORDER BY s.snummer, p.year DESC, p.afko;


--
-- Name: student_role; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE student_role (
    snummer integer NOT NULL,
    rolenum smallint NOT NULL,
    capabilities integer DEFAULT 0,
    prjm_id integer NOT NULL
);


--
-- Name: TABLE student_role; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE student_role IS 'role of a student in a project. Multiple roles per project/student are allowed.';


--
-- Name: student_short; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW student_short AS
 SELECT student.snummer,
    student.achternaam,
    student.roepnaam,
    student.pcn
   FROM student;


--
-- Name: student_upload_count; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW student_upload_count AS
 SELECT pm.prj_id,
    pm.milestone,
    u.snummer,
    count(u.upload_id) AS doc_count
   FROM ((uploads u
     JOIN prj_tutor pt ON ((u.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
  GROUP BY pm.prj_id, pm.milestone, u.snummer;


--
-- Name: VIEW student_upload_count; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW student_upload_count IS 'used by folderview';


--
-- Name: studie_prog; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE studie_prog (
    studieprogr integer NOT NULL,
    stud_prog_omsch character varying(50)
);


--
-- Name: TABLE studie_prog; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE studie_prog IS 'Description of study program';


--
-- Name: studieplan; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE studieplan (
    studieplan integer NOT NULL,
    studieplan_omschrijving character(64),
    studieplan_short character(10),
    studieprogr integer
);


--
-- Name: TABLE studieplan; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE studieplan IS 'descrition of study plan. Lang and course.';


--
-- Name: study_progress; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE study_progress (
    snummer integer NOT NULL
);


--
-- Name: TABLE study_progress; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE study_progress IS 'study progess of students.
Start as fresh, that progress to prop, stager, bready alumnus, all with dates.
Current status is highest acheived.';


--
-- Name: COLUMN study_progress.snummer; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN study_progress.snummer IS 'student';


--
-- Name: svn_auditor; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW svn_auditor AS
 SELECT DISTINCT project_scribe.scribe AS auditor,
    project_scribe.prj_id,
    prj_milestone.milestone,
    prj_milestone.prjm_id
   FROM (project_scribe
     JOIN prj_milestone USING (prj_id))
  WHERE (NOT (project_scribe.scribe IN ( SELECT tutor.userid
           FROM tutor)));


--
-- Name: svn_groep; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW svn_groep AS
 SELECT prj_grp.prjtg_id AS "group",
    (prj_grp.snummer)::text AS username
   FROM prj_grp
  WHERE (prj_grp.snummer = 879417)
UNION
 SELECT prj_tutor.prjtg_id AS "group",
    (prj_tutor.tutor_id)::text AS username
   FROM prj_tutor;


--
-- Name: svn_group; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW svn_group AS
 SELECT pt.grp_name AS groupname,
    pg.snummer AS username,
    pm.prj_id,
    pm.milestone,
    s.achternaam,
    s.roepnaam,
    pt.prjm_id,
    pt.prjtg_id
   FROM ((((prj_grp pg
     JOIN student s USING (snummer))
     JOIN prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
     LEFT JOIN grp_alias ga ON ((pt.prjtg_id = ga.prjtg_id)));


--
-- Name: svn_grp; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW svn_grp AS
 SELECT prj_grp.prjtg_id,
    (prj_grp.snummer)::text AS username
   FROM prj_grp
  WHERE (prj_grp.snummer = 879417)
UNION
 SELECT prj_tutor.prjtg_id,
    (prj_tutor.tutor_id)::text AS username
   FROM prj_tutor;


--
-- Name: svn_guests; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE svn_guests (
    username character varying(128) NOT NULL,
    password character varying(64)
);


--
-- Name: TABLE svn_guests; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE svn_guests IS 'allow externals access to svn.';


--
-- Name: svn_tutor; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW svn_tutor AS
 SELECT DISTINCT t.userid AS username,
    t.tutor,
    pm.prj_id,
    pm.milestone,
    pm.prjm_id
   FROM ((prj_tutor pt
     JOIN tutor t ON ((pt.tutor_id = t.userid)))
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
  ORDER BY t.userid, t.tutor, pm.prj_id, pm.milestone;


--
-- Name: svn_tutor_snummer; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW svn_tutor_snummer AS
 SELECT pt.tutor_id AS snummer,
    pm.prj_id
   FROM (prj_tutor pt
     JOIN prj_milestone pm USING (prjm_id))
UNION
 SELECT project_scribe.scribe AS snummer,
    project_scribe.prj_id
   FROM project_scribe;


--
-- Name: VIEW svn_tutor_snummer; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW svn_tutor_snummer IS 'get snummer from repo authz file in svn admin page';


--
-- Name: svn_users; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW svn_users AS
 SELECT (''::text || (password.userid)::text) AS username,
    password.password
   FROM passwd password
  WHERE (password.disabled = false);


--
-- Name: task_timer; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE task_timer (
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


--
-- Name: TABLE task_timer; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE task_timer IS 'time the various tasks';


--
-- Name: task_timer_anywhere; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW task_timer_anywhere AS
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
   FROM task_timer t,
    (timetableweek w
     JOIN weekdays USING (day))
  WHERE (((to_char(t.time_tag, 'HH24:MI:SS'::text) >= (w.start_time)::text) AND (to_char(t.time_tag, 'HH24:MI:SS'::text) <= (w.stop_time)::text)) AND (date_part('dow'::text, t.time_tag) = (w.day)::double precision));


--
-- Name: task_timer_at_fontys; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW task_timer_at_fontys AS
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
   FROM task_timer_anywhere
  WHERE (task_timer_anywhere.from_ip <<= '145.85.0.0/16'::inet);


--
-- Name: task_timer_group_sum; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW task_timer_group_sum AS
 SELECT pt.grp_num,
    pm.prj_id,
    pm.milestone,
    sum((tt.stop_time - tt.start_time)) AS project_time,
    pt.prjtg_id
   FROM (((task_timer tt
     JOIN prj_milestone pm ON (((tt.prj_id = pm.prj_id) AND (tt.milestone = pm.milestone))))
     JOIN prj_tutor pt ON ((pm.prjm_id = pt.prjm_id)))
     JOIN prj_grp pg ON (((pg.prjtg_id = pt.prjtg_id) AND (tt.snummer = pg.snummer))))
  GROUP BY pt.prjtg_id, pt.grp_num, pm.prj_id, pm.milestone;


--
-- Name: task_timer_grp_total; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW task_timer_grp_total AS
 SELECT pm.prj_id,
    pm.milestone,
    pt.grp_num,
    sum((tt.stop_time - tt.start_time)) AS project_total
   FROM (((task_timer tt
     JOIN prj_milestone pm ON (((tt.prj_id = pm.prj_id) AND (tt.milestone = pm.milestone))))
     JOIN prj_tutor pt ON ((pm.prjm_id = pt.prjm_id)))
     JOIN prj_grp pg ON (((pg.prjtg_id = pt.prjtg_id) AND (tt.snummer = pg.snummer))))
  GROUP BY pm.prj_id, pm.milestone, pt.grp_num;


--
-- Name: task_timer_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE task_timer_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: task_timer_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE task_timer_id_seq OWNED BY task_timer.id;


--
-- Name: task_timer_project_sum; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW task_timer_project_sum AS
 SELECT task_timer.snummer,
    task_timer.prj_id,
    task_timer.milestone,
    sum((task_timer.stop_time - task_timer.start_time)) AS project_time
   FROM task_timer
  GROUP BY task_timer.snummer, task_timer.prj_id, task_timer.milestone;


--
-- Name: task_timer_sum; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW task_timer_sum AS
 SELECT task_timer.snummer,
    task_timer.prj_id,
    task_timer.milestone,
    task_timer.task_id,
    sum((task_timer.stop_time - task_timer.start_time)) AS task_time
   FROM task_timer
  GROUP BY task_timer.snummer, task_timer.prj_id, task_timer.milestone, task_timer.task_id;


--
-- Name: task_timer_week; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW task_timer_week AS
 SELECT DISTINCT task_timer.snummer,
    date_part('year'::text, task_timer.start_time) AS year,
    date_part('week'::text, task_timer.start_time) AS week
   FROM task_timer
  ORDER BY task_timer.snummer, date_part('year'::text, task_timer.start_time), date_part('week'::text, task_timer.start_time);


--
-- Name: task_timer_year_month; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW task_timer_year_month AS
 SELECT DISTINCT date_part('year'::text, task_timer.start_time) AS year,
    date_part('month'::text, task_timer.start_time) AS month,
    to_char(task_timer.start_time, 'IYYY Mon'::text) AS year_month,
    date_trunc('month'::text, task_timer.start_time) AS first_second,
    (date_trunc('month'::text, (task_timer.start_time + '31 days'::interval)) - '00:00:01'::interval) AS last_second
   FROM task_timer
  ORDER BY date_part('year'::text, task_timer.start_time), date_part('month'::text, task_timer.start_time), to_char(task_timer.start_time, 'IYYY Mon'::text), date_trunc('month'::text, task_timer.start_time), (date_trunc('month'::text, (task_timer.start_time + '31 days'::interval)) - '00:00:01'::interval);


--
-- Name: teller; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE teller
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: this_week_schedule; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW this_week_schedule AS
 SELECT s.day,
    s.hourcode,
    s.start_time,
    s.stop_time,
    ((date_trunc('week'::text, ((now())::date)::timestamp with time zone))::date + (s.day - 1)) AS datum,
    (((date_trunc('week'::text, ((now())::date)::timestamp with time zone))::date + (s.day - 1)) + s.start_time) AS start_ts,
    (((now())::date + (s.day - 1)) + s.stop_time) AS stop_ts
   FROM schedule_hours s;


--
-- Name: tiny_portrait; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW tiny_portrait AS
 SELECT st.snummer,
    (('<img src=''fotos/'::text || COALESCE((rf.snummer)::text, 'anonymous'::text)) || '.jpg'' border=''0'' width=''18'' height=''27''/>'::text) AS portrait
   FROM (student st
     LEFT JOIN registered_photos rf USING (snummer));


--
-- Name: trac_init_data; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW trac_init_data AS
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
   FROM (all_prj_tutor apt
     JOIN repositories r USING (prjtg_id));


--
-- Name: trac_user_pass; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW trac_user_pass AS
 SELECT (passwd.userid)::text AS username,
    passwd.password
   FROM passwd;


--
-- Name: tracusers; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE tracusers (
    username text NOT NULL,
    password text
);


--
-- Name: TABLE tracusers; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE tracusers IS 'access to trac. Must verify if still used 20130712.';


--
-- Name: transaction; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE transaction (
    ts timestamp without time zone DEFAULT now(),
    trans_id bigint NOT NULL,
    operator integer NOT NULL,
    from_ip inet NOT NULL
);


--
-- Name: TABLE transaction; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE transaction IS 'simple transaction log, to save on column history data.';


--
-- Name: transaction_operator; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW transaction_operator AS
 SELECT t.ts,
    t.trans_id,
    t.operator,
    t.from_ip,
    (((s.roepnaam)::text || COALESCE(((' '::text || (s.voorvoegsel)::text) || ' '::text), ' '::text)) || (s.achternaam)::text) AS op_name
   FROM (transaction t
     JOIN student s ON ((t.operator = s.snummer)));


--
-- Name: transaction_trans_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE transaction_trans_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: transaction_trans_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE transaction_trans_id_seq OWNED BY transaction.trans_id;


--
-- Name: tutor_class_cluster; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE tutor_class_cluster (
    userid integer NOT NULL,
    class_cluster integer NOT NULL,
    cluster_order smallint DEFAULT 1
);


--
-- Name: tutor_data; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW tutor_data AS
 SELECT student.snummer,
    student.*::student AS student,
    student.snummer AS tutor_id,
    student.achternaam,
    student.roepnaam,
    student.voorvoegsel,
    tutor.tutor,
    tutor.faculty_id,
    student.hoofdgrp,
    student.email1 AS tutor_email,
    faculty.faculty_short AS faculty,
    fontys_course.course_short AS opl
   FROM (((tutor
     JOIN student ON ((tutor.userid = student.snummer)))
     JOIN faculty ON ((tutor.faculty_id = faculty.faculty_id)))
     JOIN fontys_course ON ((student.opl = fontys_course.course)));


--
-- Name: VIEW tutor_data; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW tutor_data IS 'data from tutor selectors in e.g. slb';


--
-- Name: tutor_join_student; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW tutor_join_student AS
 SELECT t.tutor,
    t.userid AS snummer,
    t.userid,
    s.achternaam,
    s.roepnaam,
    s.voorvoegsel,
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
   FROM (tutor t
     JOIN student s ON ((t.userid = s.snummer)));


--
-- Name: VIEW tutor_join_student; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW tutor_join_student IS 'Tutor view used to repesent tutor';


--
-- Name: tutor_snummer; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW tutor_snummer AS
 SELECT tutor.userid AS snummer,
    tutor.tutor AS tutor_code
   FROM tutor;


--
-- Name: tutor_upload_count; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW tutor_upload_count AS
 SELECT pm.prj_id,
    pm.milestone,
    t.tutor,
    pt.tutor_id,
    count(u.upload_id) AS doc_count
   FROM ((((uploads u
     JOIN prj_grp pg ON (((pg.prjtg_id = u.prjtg_id) AND (pg.snummer = u.snummer))))
     JOIN prj_tutor pt ON ((pg.prjtg_id = pt.prjtg_id)))
     JOIN tutor t ON ((pt.tutor_id = t.userid)))
     JOIN prj_milestone pm ON ((pt.prjm_id = pm.prjm_id)))
  GROUP BY pm.prj_id, pm.milestone, t.tutor, pt.tutor_id;


SET default_with_oids = true;

--
-- Name: uilang; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE uilang (
    lang_code character(2) NOT NULL,
    language character varying(30)
);


--
-- Name: TABLE uilang; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE uilang IS 'Language used in UI. Still valid? 20130712.';


--
-- Name: unknown_student; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW unknown_student AS
 SELECT import_naw.snummer,
    import_naw.achternaam,
    import_naw.voorvoegsel,
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
   FROM import_naw
  WHERE (NOT (import_naw.snummer IN ( SELECT student.snummer
           FROM student)));


--
-- Name: upload_archive_names; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW upload_archive_names AS
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
    ((up.snummer || '_'::text) || regexp_replace((student.achternaam)::text, '\s+'::text, '_'::text, 'g'::text)) AS author_name,
    regexp_replace(((((((((((btrim((apt.afko)::text) || '_'::text) || apt.year) || 'M'::text) || apt.milestone) || '/'::text) || (apt.tutor)::text) || '_G'::text) || apt.grp_num) || '/'::text) || (udt.description)::text), '([({}]|\s)+'::text, '_'::text, 'g'::text) AS archfilename
   FROM (((uploads up
     JOIN ( SELECT prj_grp.prjtg_id,
            t.tutor,
            pt.prjm_id,
            prj_milestone.prj_id,
            prj_milestone.milestone,
            pt.grp_num,
            project.afko,
            project.year
           FROM ((((prj_grp
             JOIN prj_tutor pt USING (prjtg_id))
             JOIN tutor t ON ((pt.tutor_id = t.userid)))
             JOIN prj_milestone USING (prjm_id))
             JOIN project USING (prj_id))) apt USING (prjtg_id))
     JOIN uploaddocumenttypes udt USING (prj_id, doctype))
     JOIN student USING (snummer));


--
-- Name: VIEW upload_archive_names; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW upload_archive_names IS 'used to create zip archives from uploads';


--
-- Name: upload_count; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW upload_count AS
 SELECT uploads.snummer,
    count(uploads.snummer) AS document_count
   FROM uploads
  GROUP BY uploads.snummer;


--
-- Name: VIEW upload_count; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW upload_count IS 'documents per user';


--
-- Name: upload_group_count; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW upload_group_count AS
 SELECT uploads.prjtg_id,
    count(uploads.upload_id) AS doc_count
   FROM uploads
  GROUP BY uploads.prjtg_id;


--
-- Name: VIEW upload_group_count; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW upload_group_count IS 'count document per group disregarding type';


--
-- Name: upload_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE upload_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: upload_mime_types; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW upload_mime_types AS
 SELECT DISTINCT (uploads.mime_type_long)::text AS mime_type
   FROM uploads;


--
-- Name: upload_rename; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW upload_rename AS
 SELECT uploads.upload_id,
    uploads.rel_file_path,
    regexp_replace(regexp_replace(uploads.rel_file_path, '\s+'::text, '_'::text, 'g'::text), '\.{2}'::text, '.'::text, 'g'::text) AS new_rel_file_path
   FROM uploads;


--
-- Name: uploads_tr; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW uploads_tr AS
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
   FROM uploads;


--
-- Name: used_criteria; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW used_criteria AS
 SELECT DISTINCT pm.prj_id,
    a.criterium AS used_criterium
   FROM ((assessment a
     JOIN prj_tutor pt ON ((a.prjtg_id = pt.prjtg_id)))
     JOIN prj_milestone pm ON ((pm.prjm_id = pt.prjtg_id)));


--
-- Name: VIEW used_criteria; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW used_criteria IS 'used in criteria3';


--
-- Name: validator_map_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE validator_map_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


SET default_with_oids = false;

--
-- Name: validator_map; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE validator_map (
    input_name character varying(120) NOT NULL,
    regex_name character varying(30) NOT NULL,
    starred character(1) DEFAULT 'Y'::bpchar NOT NULL,
    id integer DEFAULT nextval('validator_map_seq'::regclass) NOT NULL
);


--
-- Name: TABLE validator_map; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE validator_map IS 'map regex to table x colums';


--
-- Name: validator_occurrences; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE validator_occurrences (
    page character varying(64),
    identifier character varying(30),
    data text,
    id integer NOT NULL
);


--
-- Name: TABLE validator_occurrences; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE validator_occurrences IS 'record missing validators for menu x columns.';


--
-- Name: validator_occurrences_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE validator_occurrences_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: validator_occurrences_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE validator_occurrences_id_seq OWNED BY validator_occurrences.id;


--
-- Name: validator_occurrences_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE validator_occurrences_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: validator_regex; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE validator_regex (
    regex_name character varying(30) NOT NULL,
    regex text
);


--
-- Name: TABLE validator_regex; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE validator_regex IS 'validation per regex.';


--
-- Name: validator_regex_map; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW validator_regex_map AS
 SELECT validator_map.input_name,
    validator_regex.regex,
    validator_regex.regex_name,
    validator_map.starred
   FROM (validator_map
     JOIN validator_regex USING (regex_name));


--
-- Name: VIEW validator_regex_map; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW validator_regex_map IS ' regex_name join map for validation';


--
-- Name: validator_regex_slashed; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW validator_regex_slashed AS
 SELECT validator_regex.regex_name,
    replace(validator_regex.regex, '\'::text, '\\'::text) AS regex
   FROM validator_regex;


--
-- Name: VIEW validator_regex_slashed; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW validator_regex_slashed IS ' regex_name join map for validation; used in regex editor';


--
-- Name: verbaende; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE verbaende (
    verbaende_id integer NOT NULL,
    naam_school character varying(73),
    anrede character varying(14),
    aan character varying(14),
    adres character varying(28),
    postcode integer,
    woonplaats character varying(17),
    telefon character varying(16),
    telefon_alt character varying(13),
    telefax character varying(17),
    email character varying(35)
);


--
-- Name: TABLE verbaende; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE verbaende IS 'For recruitement.';


--
-- Name: verbaende__id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE verbaende__id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: verbaende__id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE verbaende__id_seq OWNED BY verbaende.verbaende_id;


--
-- Name: viewabledocument; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW viewabledocument AS
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
   FROM ((((((uploads up
     JOIN prj_grp pga ON (((up.prjtg_id = pga.prjtg_id) AND (up.snummer = pga.snummer))))
     JOIN prj_tutor pta ON ((pta.prjtg_id = pga.prjtg_id)))
     JOIN prj_milestone pma ON ((pta.prjm_id = pma.prjm_id)))
     JOIN uploaddocumenttypes ut ON (((pma.prj_id = ut.prj_id) AND (ut.doctype = up.doctype))))
     JOIN ( SELECT ptv.prjtg_id,
            pgv.snummer,
            ptv.prjm_id,
            ptv.grp_num
           FROM (prj_grp pgv
             JOIN prj_tutor ptv ON ((pgv.prjtg_id = ptv.prjtg_id)))) pgtv ON ((pgtv.prjm_id = pta.prjm_id)))
     JOIN project_deliverables pd ON (((pd.prjm_id = pta.prjm_id) AND (up.doctype = pd.doctype))))
  WHERE ((pta.prjtg_id = pgtv.prjtg_id) OR ((pd.due)::timestamp with time zone < now()));


--
-- Name: VIEW viewabledocument; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW viewabledocument IS 'group and project members that might view an upload document';


--
-- Name: web_authentification; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW web_authentification AS
 SELECT (''::text || (password.userid)::text) AS username,
    password.password
   FROM passwd password
UNION
 SELECT guest_users.username,
    guest_users.password
   FROM guest_users;


--
-- Name: VIEW web_authentification; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON VIEW web_authentification IS 'used by generic web authentification for private sites ';


--
-- Name: act_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY activity ALTER COLUMN act_id SET DEFAULT nextval('activity_act_id_seq'::regclass);


--
-- Name: any_query_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY any_query ALTER COLUMN any_query_id SET DEFAULT nextval('any_query_any_query_id_seq'::regclass);


--
-- Name: _id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY arbeitsaemterberatungstellen ALTER COLUMN _id SET DEFAULT nextval('arbeitsaemterberatungstellen__id_seq'::regclass);


--
-- Name: assessment_commit_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY assessment_commit ALTER COLUMN assessment_commit_id SET DEFAULT nextval('assessment_commit_assessment_commit_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY assessment_remarks ALTER COLUMN id SET DEFAULT nextval('assessement_remark_id_seq'::regclass);


--
-- Name: _id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY berufskollegs ALTER COLUMN _id SET DEFAULT nextval('berufskollegs__id_seq'::regclass);


--
-- Name: _id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY campus20120113 ALTER COLUMN _id SET DEFAULT nextval('foins27__id_seq'::regclass);


--
-- Name: _id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY campus_20120516 ALTER COLUMN _id SET DEFAULT nextval('campus_20120516__id_seq'::regclass);


--
-- Name: class_cluster; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY class_cluster ALTER COLUMN class_cluster SET DEFAULT nextval('class_cluster_class_cluster_seq'::regclass);


--
-- Name: colloquium_speaker_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY colloquium_speakers ALTER COLUMN colloquium_speaker_id SET DEFAULT nextval('colloquium_speakers_colloquium_speaker_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY critique_history ALTER COLUMN id SET DEFAULT nextval('critique_history_id_seq'::regclass);


--
-- Name: document_author_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY document_author ALTER COLUMN document_author_id SET DEFAULT nextval('document_author_document_author_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY enumeraties ALTER COLUMN id SET DEFAULT nextval('enumeraties_id_seq'::regclass);


--
-- Name: exam_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY exam ALTER COLUMN exam_id SET DEFAULT nextval('exam_exam_id_seq'::regclass);


--
-- Name: exam_event_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY exam_event ALTER COLUMN exam_event_id SET DEFAULT nextval('exam_event_id_seq'::regclass);


--
-- Name: _id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY februar2014 ALTER COLUMN _id SET DEFAULT nextval('februar2014__id_seq'::regclass);


--
-- Name: _id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY geslaagdentechniek2013 ALTER COLUMN _id SET DEFAULT nextval('geslaagdentechniek2013__id_seq'::regclass);


--
-- Name: _id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY hoofdgrp_map ALTER COLUMN _id SET DEFAULT nextval('hoofdgrp_map__id_seq'::regclass);


--
-- Name: _id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY inchecked ALTER COLUMN _id SET DEFAULT nextval('inchecked__id_seq'::regclass);


--
-- Name: _id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY jagers ALTER COLUMN _id SET DEFAULT nextval('jagers__id_seq'::regclass);


--
-- Name: _id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY klassenfibs20140226 ALTER COLUMN _id SET DEFAULT nextval('klassenfibs20140226__id_seq'::regclass);


--
-- Name: _id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY klassenmec2012 ALTER COLUMN _id SET DEFAULT nextval('klassenmec2012__id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY logoff ALTER COLUMN id SET DEFAULT nextval('logoff_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY logon ALTER COLUMN id SET DEFAULT nextval('logon_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY map_land_nl_iso3166 ALTER COLUMN id SET DEFAULT nextval('map_land_nl_iso3166_id_seq'::regclass);


--
-- Name: meeloopmail_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY meeloopmail ALTER COLUMN meeloopmail_id SET DEFAULT nextval('meeloopmail_meeloopmail_id_seq'::regclass);


--
-- Name: meelopen_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY meelopen ALTER COLUMN meelopen_id SET DEFAULT nextval('meelopen_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY menu ALTER COLUMN id SET DEFAULT nextval('menu_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY menu_item ALTER COLUMN id SET DEFAULT nextval('menu_item_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY menu_item_display ALTER COLUMN id SET DEFAULT nextval('menu_item_display_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY menu_option_queries ALTER COLUMN id SET DEFAULT nextval('menu_option_queries_id_seq'::regclass);


--
-- Name: milestone_grade_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY milestone_grade ALTER COLUMN milestone_grade_id SET DEFAULT nextval('milestone_grade_milestone_grade_id_seq'::regclass);


--
-- Name: _id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY mini2013 ALTER COLUMN _id SET DEFAULT nextval('mini2013__id_seq'::regclass);


--
-- Name: minifeb2015_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY minifeb2015 ALTER COLUMN minifeb2015_id SET DEFAULT nextval('minifeb2015_minifeb2015_id_seq'::regclass);


--
-- Name: minir_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY minir ALTER COLUMN minir_id SET DEFAULT nextval('minir_minir_id_seq'::regclass);


--
-- Name: minissep2014_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY minissep2014 ALTER COLUMN minissep2014_id SET DEFAULT nextval('minissep2014_minissep2014_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY nat_mapper ALTER COLUMN id SET DEFAULT nextval('nat_mapper_id_seq'::regclass);


--
-- Name: help_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY page_help ALTER COLUMN help_id SET DEFAULT nextval('page_help_help_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY password_request ALTER COLUMN id SET DEFAULT nextval('password_request_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY personal_repos ALTER COLUMN id SET DEFAULT nextval('personal_repos_id_seq'::regclass);


--
-- Name: planned_school_visit; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY planned_school_visit ALTER COLUMN planned_school_visit SET DEFAULT nextval('planned_school_visit_seq'::regclass);


--
-- Name: presse_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY presse ALTER COLUMN presse_id SET DEFAULT nextval('presse__id_seq'::regclass);


--
-- Name: prj2_2015_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY prj2_2015 ALTER COLUMN prj2_2015_id SET DEFAULT nextval('prj2_2015_prj2_2015_id_seq'::regclass);


--
-- Name: prjtg_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY prj_tutor ALTER COLUMN prjtg_id SET DEFAULT nextval('prj_tutor_prjtg_id_seq'::regclass);


--
-- Name: prj_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY project ALTER COLUMN prj_id SET DEFAULT nextval('project_prj_id_seq'::regclass);


--
-- Name: project_attributes_def; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY project_attributes_def ALTER COLUMN project_attributes_def SET DEFAULT nextval('project_attributes_def_project_attributes_def_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY project_attributes_values ALTER COLUMN id SET DEFAULT nextval('project_attributes_values_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY project_auditor ALTER COLUMN id SET DEFAULT nextval('project_auditor_id_seq'::regclass);


--
-- Name: pdeliverable_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY project_deliverables ALTER COLUMN pdeliverable_id SET DEFAULT nextval('project_deliverables_pdeliverable_id_seq'::regclass);


--
-- Name: project_scribe_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY project_scribe ALTER COLUMN project_scribe_id SET DEFAULT nextval('project_scribe_project_scribe_id_seq'::regclass);


--
-- Name: task_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY project_task ALTER COLUMN task_id SET DEFAULT nextval('project_task_task_id_seq'::regclass);


--
-- Name: recruiters_note_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY recruiters_note ALTER COLUMN recruiters_note_id SET DEFAULT nextval('recruiters_note_recruiters_note_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY repositories ALTER COLUMN id SET DEFAULT nextval('repositories_id_seq'::regclass);


--
-- Name: _id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY resit ALTER COLUMN _id SET DEFAULT nextval('resit__id_seq'::regclass);


--
-- Name: resitexpected_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY resitexpected ALTER COLUMN resitexpected_id SET DEFAULT nextval('resitexpected_resitexpected_id_seq'::regclass);


--
-- Name: scholen_int_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY scholen_int ALTER COLUMN scholen_int_id SET DEFAULT nextval('scholen_int_id_seq'::regclass);


--
-- Name: class_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY student_class ALTER COLUMN class_id SET DEFAULT nextval('classes_class_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY task_timer ALTER COLUMN id SET DEFAULT nextval('task_timer_id_seq'::regclass);


--
-- Name: trans_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY transaction ALTER COLUMN trans_id SET DEFAULT nextval('transaction_trans_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY validator_occurrences ALTER COLUMN id SET DEFAULT nextval('validator_occurrences_id_seq'::regclass);


--
-- Name: verbaende_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY verbaende ALTER COLUMN verbaende_id SET DEFAULT nextval('verbaende__id_seq'::regclass);


--
-- Name: absence_reason_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY absence_reason
    ADD CONSTRAINT absence_reason_pk PRIMARY KEY (act_id, snummer);


--
-- Name: absence_reason_un; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY absence_reason
    ADD CONSTRAINT absence_reason_un UNIQUE (act_id, snummer);


--
-- Name: act_part_un; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY activity_participant
    ADD CONSTRAINT act_part_un UNIQUE (act_id, snummer);


--
-- Name: act_type_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY activity_type
    ADD CONSTRAINT act_type_pk PRIMARY KEY (act_type);


--
-- Name: activity_participant_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY activity_participant
    ADD CONSTRAINT activity_participant_pk PRIMARY KEY (act_id, snummer);


--
-- Name: activity_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY activity
    ADD CONSTRAINT activity_pk PRIMARY KEY (act_id);


--
-- Name: activity_project_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY activity_project
    ADD CONSTRAINT activity_project_pk PRIMARY KEY (prj_id);


--
-- Name: additional_course_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY additional_course
    ADD CONSTRAINT additional_course_pk PRIMARY KEY (snummer, course_code);


--
-- Name: alt_email_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY alt_email
    ADD CONSTRAINT alt_email_pkey PRIMARY KEY (snummer);


--
-- Name: any_query_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY any_query
    ADD CONSTRAINT any_query_pkey PRIMARY KEY (any_query_id);


--
-- Name: arbeitsaemterberatungstellen_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY arbeitsaemterberatungstellen
    ADD CONSTRAINT arbeitsaemterberatungstellen_pk PRIMARY KEY (_id);


--
-- Name: assessement_remark_un; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY assessment_remarks
    ADD CONSTRAINT assessement_remark_un UNIQUE (contestant, judge, prjtg_id);


--
-- Name: assessment_commit_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY assessment_commit
    ADD CONSTRAINT assessment_commit_pkey PRIMARY KEY (assessment_commit_id);


--
-- Name: assessment_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY assessment
    ADD CONSTRAINT assessment_pk PRIMARY KEY (contestant, judge, criterium, prjtg_id);


--
-- Name: assessment_un; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY assessment
    ADD CONSTRAINT assessment_un UNIQUE (prjtg_id, judge, contestant, criterium);


--
-- Name: base_criteria_de_short_un; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY base_criteria
    ADD CONSTRAINT base_criteria_de_short_un UNIQUE (de_short);


--
-- Name: base_criteria_en_short_un; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY base_criteria
    ADD CONSTRAINT base_criteria_en_short_un UNIQUE (en_short);


--
-- Name: base_criteria_nl_short_un; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY base_criteria
    ADD CONSTRAINT base_criteria_nl_short_un UNIQUE (nl_short);


--
-- Name: base_criteria_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY base_criteria
    ADD CONSTRAINT base_criteria_pk PRIMARY KEY (criterium_id);


--
-- Name: bigface_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY bigface_settings
    ADD CONSTRAINT bigface_settings_pkey PRIMARY KEY (bfkey);


--
-- Name: breufskollegs_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY berufskollegs
    ADD CONSTRAINT breufskollegs_pk PRIMARY KEY (_id);


--
-- Name: campus20120113_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY campus20120113
    ADD CONSTRAINT campus20120113_pk PRIMARY KEY (_id);


--
-- Name: campus_20120516_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY campus_20120516
    ADD CONSTRAINT campus_20120516_pk PRIMARY KEY (_id);


--
-- Name: class_cluster_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY class_cluster
    ADD CONSTRAINT class_cluster_pk PRIMARY KEY (class_cluster);


--
-- Name: classes_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY student_class
    ADD CONSTRAINT classes_pk PRIMARY KEY (class_id);


--
-- Name: colloquium_speaker_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY colloquium_speakers
    ADD CONSTRAINT colloquium_speaker_pk PRIMARY KEY (colloquium_speaker_id);


--
-- Name: course_week_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY course_week
    ADD CONSTRAINT course_week_pk PRIMARY KEY (course_week_no);


--
-- Name: cr_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY document_critique
    ADD CONSTRAINT cr_pk PRIMARY KEY (critique_id);


--
-- Name: critique_history_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY critique_history
    ADD CONSTRAINT critique_history_pk PRIMARY KEY (id);


--
-- Name: diploma_dates_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY diploma_dates
    ADD CONSTRAINT diploma_dates_pk PRIMARY KEY (snummer);


--
-- Name: document_author_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY document_author
    ADD CONSTRAINT document_author_pkey PRIMARY KEY (document_author_id);


--
-- Name: document_author_un; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY document_author
    ADD CONSTRAINT document_author_un UNIQUE (upload_id, snummer);


--
-- Name: downloaded_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY downloaded
    ADD CONSTRAINT downloaded_pk PRIMARY KEY (snummer, upload_id, downloadts);


--
-- Name: education_unit_description_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY education_unit_description
    ADD CONSTRAINT education_unit_description_pk PRIMARY KEY (education_unit_id, language_id, module_id);


--
-- Name: education_unit_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY education_unit
    ADD CONSTRAINT education_unit_pk PRIMARY KEY (module_id, education_unit_id);


--
-- Name: email_signature_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY email_signature
    ADD CONSTRAINT email_signature_pkey PRIMARY KEY (snummer);


--
-- Name: enumeraties_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY enumeraties
    ADD CONSTRAINT enumeraties_pkey PRIMARY KEY (menu_name, column_name, name);


--
-- Name: enumeraties_un; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY enumeraties
    ADD CONSTRAINT enumeraties_un UNIQUE (id);


--
-- Name: exam_event_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY exam_event
    ADD CONSTRAINT exam_event_pkey PRIMARY KEY (exam_event_id);


--
-- Name: exam_focus_description_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY exam_focus_description
    ADD CONSTRAINT exam_focus_description_pk PRIMARY KEY (exam_focus_id, language_id);


--
-- Name: exam_focus_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY exam_focus
    ADD CONSTRAINT exam_focus_pk PRIMARY KEY (exam_focus_id);


--
-- Name: exam_grading_aspect_description_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY exam_grading_aspect_description
    ADD CONSTRAINT exam_grading_aspect_description_pk PRIMARY KEY (exam_grading_aspect_id, language_id);


--
-- Name: exam_grading_aspect_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY exam_grading_aspect
    ADD CONSTRAINT exam_grading_aspect_pk PRIMARY KEY (exam_grading_aspect_id);


--
-- Name: exam_grading_level_description_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY exam_grading_level_description
    ADD CONSTRAINT exam_grading_level_description_pk PRIMARY KEY (exam_grading_level_id, language_id);


--
-- Name: exam_grading_level_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY exam_grading_level
    ADD CONSTRAINT exam_grading_level_pk PRIMARY KEY (exam_grading_level_id);


--
-- Name: exam_grading_type_description_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY exam_grading_type_description
    ADD CONSTRAINT exam_grading_type_description_pk PRIMARY KEY (exam_grading_type_id, language_id);


--
-- Name: exam_grading_type_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY exam_grading_type
    ADD CONSTRAINT exam_grading_type_pk PRIMARY KEY (exam_grading_type_id);


--
-- Name: exam_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY exam
    ADD CONSTRAINT exam_pkey PRIMARY KEY (exam_id);


--
-- Name: exam_type_description_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY exam_type_description
    ADD CONSTRAINT exam_type_description_pk PRIMARY KEY (exam_type_id, language_id);


--
-- Name: exam_type_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY exam_type
    ADD CONSTRAINT exam_type_pk PRIMARY KEY (exam_type_id);


--
-- Name: fac_scl_cuslt_unique; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY student_class
    ADD CONSTRAINT fac_scl_cuslt_unique UNIQUE (faculty_id, sclass, class_cluster);


--
-- Name: fac_sclass_un; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY student_class
    ADD CONSTRAINT fac_sclass_un UNIQUE (faculty_id, sclass);


--
-- Name: faculty_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY faculty
    ADD CONSTRAINT faculty_pk PRIMARY KEY (faculty_id);


--
-- Name: fake_email_address_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY fake_mail_address
    ADD CONSTRAINT fake_email_address_pk PRIMARY KEY (email1);


--
-- Name: fontys_course_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY fontys_course
    ADD CONSTRAINT fontys_course_pk PRIMARY KEY (course);


--
-- Name: foto_prefix_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY foto_prefix
    ADD CONSTRAINT foto_prefix_pk PRIMARY KEY (prefix);


--
-- Name: grade_summer_result_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY grade_summer_result
    ADD CONSTRAINT grade_summer_result_pk PRIMARY KEY (prjtg_id, snummer, criterium);


--
-- Name: grp_alias_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY grp_alias
    ADD CONSTRAINT grp_alias_pk PRIMARY KEY (prjtg_id);


--
-- Name: guest_users_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY guest_users
    ADD CONSTRAINT guest_users_pk PRIMARY KEY (username);


--
-- Name: hoofdgrp_map_hoofdgrp_key; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY hoofdgrp_map
    ADD CONSTRAINT hoofdgrp_map_hoofdgrp_key UNIQUE (hoofdgrp);


--
-- Name: id_u; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY uploads
    ADD CONSTRAINT id_u UNIQUE (upload_id);


--
-- Name: iso3166_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY iso3166
    ADD CONSTRAINT iso3166_pk PRIMARY KEY (number);


--
-- Name: iso_a2_un; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY iso3166
    ADD CONSTRAINT iso_a2_un UNIQUE (a2);


--
-- Name: iso_a3_un; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY iso3166
    ADD CONSTRAINT iso_a3_un UNIQUE (a3);


--
-- Name: iso_number_un; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY iso3166
    ADD CONSTRAINT iso_number_un UNIQUE (number);


--
-- Name: jager_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY jagers
    ADD CONSTRAINT jager_pk PRIMARY KEY (pcn);


--
-- Name: language_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY language
    ADD CONSTRAINT language_pkey PRIMARY KEY (language_id);


--
-- Name: learning_goal_description_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY learning_goal_description
    ADD CONSTRAINT learning_goal_description_pk PRIMARY KEY (module_id, language_id, learning_goal_id);


--
-- Name: learning_goal_exam_focus_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY learning_goal_exam_focus
    ADD CONSTRAINT learning_goal_exam_focus_pk PRIMARY KEY (module_id, learning_goal_id, exam_focus_id);


--
-- Name: learning_goal_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY learning_goal
    ADD CONSTRAINT learning_goal_pk PRIMARY KEY (module_id, learning_goal_id);


--
-- Name: logoff_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY logoff
    ADD CONSTRAINT logoff_pk PRIMARY KEY (id);


--
-- Name: logon_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY logon
    ADD CONSTRAINT logon_pk PRIMARY KEY (id);


--
-- Name: lpi_id_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY lpi_id
    ADD CONSTRAINT lpi_id_pk PRIMARY KEY (snummer);


--
-- Name: map_land_nl_iso3166_land_nl_un; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY map_land_nl_iso3166
    ADD CONSTRAINT map_land_nl_iso3166_land_nl_un UNIQUE (land_nl);


--
-- Name: meeloopmail_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY meeloopmail
    ADD CONSTRAINT meeloopmail_pkey PRIMARY KEY (meeloopmail_id);


--
-- Name: meelopen_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY meelopen
    ADD CONSTRAINT meelopen_pk PRIMARY KEY (meelopen_id);


--
-- Name: menu_item_diplay_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY menu_item_display
    ADD CONSTRAINT menu_item_diplay_pk PRIMARY KEY (id);


--
-- Name: menu_item_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY menu_item
    ADD CONSTRAINT menu_item_pk PRIMARY KEY (id);


--
-- Name: menu_item_un; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY menu_item
    ADD CONSTRAINT menu_item_un UNIQUE (menu_name, column_name);


--
-- Name: menu_option_q_un; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY menu_option_queries
    ADD CONSTRAINT menu_option_q_un UNIQUE (menu_name, column_name);


--
-- Name: menu_option_queries_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY menu_option_queries
    ADD CONSTRAINT menu_option_queries_pk PRIMARY KEY (id);


--
-- Name: menu_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY menu
    ADD CONSTRAINT menu_pk UNIQUE (menu_name, relation_name);


--
-- Name: menu_pk1; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY menu
    ADD CONSTRAINT menu_pk1 PRIMARY KEY (id);


--
-- Name: milestone_grade_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY milestone_grade
    ADD CONSTRAINT milestone_grade_pkey PRIMARY KEY (milestone_grade_id);


--
-- Name: milestone_grade_snummer_key; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY milestone_grade
    ADD CONSTRAINT milestone_grade_snummer_key UNIQUE (snummer, prjm_id);


--
-- Name: minifeb2015_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY minifeb2015
    ADD CONSTRAINT minifeb2015_pkey PRIMARY KEY (minifeb2015_id);


--
-- Name: minikiosk_visits_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY minikiosk_visits
    ADD CONSTRAINT minikiosk_visits_pk PRIMARY KEY (counter);


--
-- Name: minir_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY minir
    ADD CONSTRAINT minir_pkey PRIMARY KEY (minir_id);


--
-- Name: minissep2014_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY minissep2014
    ADD CONSTRAINT minissep2014_pkey PRIMARY KEY (minissep2014_id);


--
-- Name: module_activity_description_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY module_activity_description
    ADD CONSTRAINT module_activity_description_pk PRIMARY KEY (module_activity_id, language_id);


--
-- Name: module_activity_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY module_activity
    ADD CONSTRAINT module_activity_pk PRIMARY KEY (module_activity_id);


--
-- Name: module_description_long_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY module_desciption_long
    ADD CONSTRAINT module_description_long_pk PRIMARY KEY (module_id, language_id);


--
-- Name: module_description_short_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY module_description_short
    ADD CONSTRAINT module_description_short_pk PRIMARY KEY (module_id, language_id);


--
-- Name: module_language_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY module_language
    ADD CONSTRAINT module_language_pk PRIMARY KEY (module_id, language_id);


--
-- Name: module_part_id; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY module_part
    ADD CONSTRAINT module_part_id PRIMARY KEY (module_part_id);


--
-- Name: module_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY module
    ADD CONSTRAINT module_pk PRIMARY KEY (module_id);


--
-- Name: module_prerequisite_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY module_prerequisite
    ADD CONSTRAINT module_prerequisite_pk PRIMARY KEY (module_id, prerequisite);


--
-- Name: module_resource_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY module_resource
    ADD CONSTRAINT module_resource_pk PRIMARY KEY (module_id, module_resource_id);


--
-- Name: module_resource_type_description_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY module_resource_type_description
    ADD CONSTRAINT module_resource_type_description_pk PRIMARY KEY (module_resource_type_id, language_id);


--
-- Name: module_resource_type_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY module_resource_type
    ADD CONSTRAINT module_resource_type_pk PRIMARY KEY (module_resource_type_id);


--
-- Name: module_topic_description_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY module_topic_description
    ADD CONSTRAINT module_topic_description_pk PRIMARY KEY (module_id, module_topic_id, language_id);


--
-- Name: module_topic_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY module_topic
    ADD CONSTRAINT module_topic_pk PRIMARY KEY (module_id, module_topic_id);


--
-- Name: module_week_schedule_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY module_week_schedule
    ADD CONSTRAINT module_week_schedule_pk PRIMARY KEY (module_id, week_id, module_activity_id);


--
-- Name: nat_mapper_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY nat_mapper
    ADD CONSTRAINT nat_mapper_pk PRIMARY KEY (id);


--
-- Name: nation_omschr_un; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY nat_mapper
    ADD CONSTRAINT nation_omschr_un UNIQUE (nation_omschr);


--
-- Name: newsnummer_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY newstudent
    ADD CONSTRAINT newsnummer_pk PRIMARY KEY (snummer);


--
-- Name: page_help_page_key; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY page_help
    ADD CONSTRAINT page_help_page_key UNIQUE (page);


--
-- Name: page_help_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY page_help
    ADD CONSTRAINT page_help_pk PRIMARY KEY (help_id);


--
-- Name: password_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY passwd
    ADD CONSTRAINT password_pkey PRIMARY KEY (userid);


--
-- Name: password_request_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY password_request
    ADD CONSTRAINT password_request_pk PRIMARY KEY (id);


--
-- Name: peer_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY peer_settings
    ADD CONSTRAINT peer_settings_pkey PRIMARY KEY (key);


--
-- Name: personal_repos_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY personal_repos
    ADD CONSTRAINT personal_repos_pk PRIMARY KEY (id);


--
-- Name: personal_repos_un1; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY personal_repos
    ADD CONSTRAINT personal_repos_un1 UNIQUE (repospath);


--
-- Name: personal_repos_un2; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY personal_repos
    ADD CONSTRAINT personal_repos_un2 UNIQUE (url_tail);


--
-- Name: pgr_grp_un2; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY prj_grp
    ADD CONSTRAINT pgr_grp_un2 UNIQUE (prjtg_id, snummer);


--
-- Name: planned_school_visit_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY planned_school_visit
    ADD CONSTRAINT planned_school_visit_pkey PRIMARY KEY (planned_school_visit);


--
-- Name: pnc_un; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY tutor
    ADD CONSTRAINT pnc_un UNIQUE (userid);


--
-- Name: presse_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY presse
    ADD CONSTRAINT presse_pk PRIMARY KEY (presse_id);


--
-- Name: prj2_2015_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY prj2_2015
    ADD CONSTRAINT prj2_2015_pkey PRIMARY KEY (prj2_2015_id);


--
-- Name: prj_contact_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY prj_contact
    ADD CONSTRAINT prj_contact_pk PRIMARY KEY (prjtg_id);


--
-- Name: prj_grp_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY prj_grp
    ADD CONSTRAINT prj_grp_pk PRIMARY KEY (snummer, prjtg_id);


--
-- Name: prj_m_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY prj_milestone
    ADD CONSTRAINT prj_m_pk PRIMARY KEY (prjm_id);


--
-- Name: prj_milstone_un; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY prj_milestone
    ADD CONSTRAINT prj_milstone_un UNIQUE (prjm_id);


--
-- Name: prj_role_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY project_roles
    ADD CONSTRAINT prj_role_pk PRIMARY KEY (prj_id, rolenum);


--
-- Name: prj_tutor_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY prj_tutor
    ADD CONSTRAINT prj_tutor_pk PRIMARY KEY (prjtg_id);


--
-- Name: prj_tutor_prjtg_id_un; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY prj_tutor
    ADD CONSTRAINT prj_tutor_prjtg_id_un UNIQUE (prjtg_id);


--
-- Name: prj_tutor_u3; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY prj_tutor
    ADD CONSTRAINT prj_tutor_u3 UNIQUE (prjm_id, grp_num);


--
-- Name: prjm_criterium_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY prjm_criterium
    ADD CONSTRAINT prjm_criterium_pk PRIMARY KEY (prjm_id, criterium_id);


--
-- Name: prjm_criterium_un; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY prjm_criterium
    ADD CONSTRAINT prjm_criterium_un UNIQUE (prjm_id, criterium_id);


--
-- Name: project_attributes_def_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY project_attributes_def
    ADD CONSTRAINT project_attributes_def_pkey PRIMARY KEY (project_attributes_def);


--
-- Name: project_attributes_value_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY project_attributes_values
    ADD CONSTRAINT project_attributes_value_pk PRIMARY KEY (id);


--
-- Name: project_auditor_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY project_auditor
    ADD CONSTRAINT project_auditor_pk PRIMARY KEY (id);


--
-- Name: project_auditor_un1; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY project_auditor
    ADD CONSTRAINT project_auditor_un1 UNIQUE (snummer, prjm_id, gid);


--
-- Name: project_deliverables_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY project_deliverables
    ADD CONSTRAINT project_deliverables_pk PRIMARY KEY (pdeliverable_id);


--
-- Name: project_deliverables_un; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY project_deliverables
    ADD CONSTRAINT project_deliverables_un UNIQUE (prjm_id, doctype);


--
-- Name: project_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY project
    ADD CONSTRAINT project_pk PRIMARY KEY (prj_id);


--
-- Name: project_scribe_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY project_scribe
    ADD CONSTRAINT project_scribe_pk PRIMARY KEY (project_scribe_id);


--
-- Name: project_scribe_un1; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY project_scribe
    ADD CONSTRAINT project_scribe_un1 UNIQUE (prj_id, scribe);


--
-- Name: project_task_completed_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY project_task_completed
    ADD CONSTRAINT project_task_completed_pk PRIMARY KEY (task_id, snummer, trans_id);


--
-- Name: project_task_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY project_task
    ADD CONSTRAINT project_task_pk PRIMARY KEY (task_id);


--
-- Name: project_tasks_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY project_tasks
    ADD CONSTRAINT project_tasks_pk PRIMARY KEY (prj_id, task_id, snummer);


--
-- Name: project_un; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY project
    ADD CONSTRAINT project_un UNIQUE (afko, year, course);


--
-- Name: recruiters_note_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY recruiters_note
    ADD CONSTRAINT recruiters_note_pkey PRIMARY KEY (recruiters_note_id);


--
-- Name: registered_mphotos_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY registered_mphotos
    ADD CONSTRAINT registered_mphotos_pkey PRIMARY KEY (snummer);


--
-- Name: registered_photos_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY registered_photos
    ADD CONSTRAINT registered_photos_pkey PRIMARY KEY (snummer);


--
-- Name: repositories_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY repositories
    ADD CONSTRAINT repositories_pkey PRIMARY KEY (repospath);


--
-- Name: repositories_un1; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY repositories
    ADD CONSTRAINT repositories_un1 UNIQUE (repospath);


--
-- Name: repositories_un2; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY repositories
    ADD CONSTRAINT repositories_un2 UNIQUE (url_tail);


--
-- Name: resitexpected_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY resitexpected
    ADD CONSTRAINT resitexpected_pkey PRIMARY KEY (resitexpected_id);


--
-- Name: scholen_int_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY scholen_int
    ADD CONSTRAINT scholen_int_pk PRIMARY KEY (scholen_int_id);


--
-- Name: schulen_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY schulen
    ADD CONSTRAINT schulen_pk PRIMARY KEY (schulen_id);


--
-- Name: sclass_selector_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY sclass_selector
    ADD CONSTRAINT sclass_selector_pk PRIMARY KEY (class_id);


--
-- Name: semester_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY semester
    ADD CONSTRAINT semester_pk PRIMARY KEY (id);


--
-- Name: session_data_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY session_data
    ADD CONSTRAINT session_data_pkey PRIMARY KEY (snummer);


--
-- Name: snummer_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY snummer
    ADD CONSTRAINT snummer_pkey PRIMARY KEY (snummer);


--
-- Name: student_pcn_un; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY student
    ADD CONSTRAINT student_pcn_un UNIQUE (pcn);


--
-- Name: student_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY student
    ADD CONSTRAINT student_pk PRIMARY KEY (snummer);


--
-- Name: student_role_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY student_role
    ADD CONSTRAINT student_role_pk PRIMARY KEY (snummer, prjm_id);


--
-- Name: student_role_un; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY student_role
    ADD CONSTRAINT student_role_un UNIQUE (snummer, prjm_id);


--
-- Name: CONSTRAINT student_role_un ON student_role; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON CONSTRAINT student_role_un ON student_role IS 'One role per project_milestone';


--
-- Name: studie_prog_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY studie_prog
    ADD CONSTRAINT studie_prog_pk PRIMARY KEY (studieprogr);


--
-- Name: studieplan_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY studieplan
    ADD CONSTRAINT studieplan_pkey PRIMARY KEY (studieplan);


--
-- Name: svn_guests_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY svn_guests
    ADD CONSTRAINT svn_guests_pkey PRIMARY KEY (username);


--
-- Name: task_timer_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY task_timer
    ADD CONSTRAINT task_timer_pk PRIMARY KEY (snummer, prj_id, milestone, task_id, start_time);


--
-- Name: timetableweek_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY timetableweek
    ADD CONSTRAINT timetableweek_pk PRIMARY KEY (day, hourcode);


--
-- Name: tracusers_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY tracusers
    ADD CONSTRAINT tracusers_pk PRIMARY KEY (username);


--
-- Name: transaction_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY transaction
    ADD CONSTRAINT transaction_pk PRIMARY KEY (trans_id);


--
-- Name: tutor_class_cluster_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY tutor_class_cluster
    ADD CONSTRAINT tutor_class_cluster_pkey PRIMARY KEY (userid, class_cluster);


--
-- Name: tutor_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY tutor
    ADD CONSTRAINT tutor_pk PRIMARY KEY (userid);


--
-- Name: uilang_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY uilang
    ADD CONSTRAINT uilang_pkey PRIMARY KEY (lang_code);


--
-- Name: unix_uid_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY unix_uid
    ADD CONSTRAINT unix_uid_pk PRIMARY KEY (uid);


--
-- Name: unix_uid_snummer_un; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY unix_uid
    ADD CONSTRAINT unix_uid_snummer_un UNIQUE (snummer);


--
-- Name: updt_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY uploaddocumenttypes
    ADD CONSTRAINT updt_pk PRIMARY KEY (doctype, prj_id);


--
-- Name: uploads_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY uploads
    ADD CONSTRAINT uploads_pk PRIMARY KEY (upload_id);


--
-- Name: validator_map_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY validator_map
    ADD CONSTRAINT validator_map_pk PRIMARY KEY (id);


--
-- Name: validator_occurences_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY validator_occurrences
    ADD CONSTRAINT validator_occurences_pk PRIMARY KEY (id);


--
-- Name: validator_occurrences_un; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY validator_occurrences
    ADD CONSTRAINT validator_occurrences_un UNIQUE (page, identifier);


--
-- Name: validator_regex_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY validator_regex
    ADD CONSTRAINT validator_regex_pk PRIMARY KEY (regex_name);


--
-- Name: validator_regex_un; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY validator_regex
    ADD CONSTRAINT validator_regex_un UNIQUE (regex_name, regex);


--
-- Name: verbaende_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY verbaende
    ADD CONSTRAINT verbaende_pk PRIMARY KEY (verbaende_id);


--
-- Name: weekdays_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY weekdays
    ADD CONSTRAINT weekdays_pk PRIMARY KEY (day);


--
-- Name: assessment_grp_crit_idx; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX assessment_grp_crit_idx ON assessment USING btree (prjtg_id, criterium);


--
-- Name: assessment_idx1; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX assessment_idx1 ON assessment USING btree (prjtg_id, judge, contestant, criterium);


--
-- Name: fki_project_scribe_fk1; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX fki_project_scribe_fk1 ON project_scribe USING btree (scribe);


--
-- Name: idx_gebdat; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX idx_gebdat ON student USING btree (gebdat);


--
-- Name: prj_grp_tg_idx; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX prj_grp_tg_idx ON prj_grp USING btree (prjtg_id);


--
-- Name: prj_grp_tg_stud_idx; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX prj_grp_tg_stud_idx ON prj_grp USING btree (prjtg_id, snummer);


--
-- Name: prj_milestone_idx; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE UNIQUE INDEX prj_milestone_idx ON prj_milestone USING btree (prj_id, milestone);


--
-- Name: prj_milestone_idx2; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE UNIQUE INDEX prj_milestone_idx2 ON prj_milestone USING btree (prj_id, milestone, prjm_id);


--
-- Name: project_task_completed_task_student; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX project_task_completed_task_student ON project_task_completed USING btree (task_id, snummer);


--
-- Name: student_email_idx; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX student_email_idx ON student USING btree (email1);


--
-- Name: student_hoofdgrp_idx; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX student_hoofdgrp_idx ON student USING btree (hoofdgrp);


--
-- Name: grp_detail_delete; Type: RULE; Schema: public; Owner: -
--

CREATE RULE grp_detail_delete AS
    ON DELETE TO grp_detail DO INSTEAD  DELETE FROM grp_alias g
  WHERE (g.prjtg_id = old.prjtg_id);


--
-- Name: grp_detail_insert; Type: RULE; Schema: public; Owner: -
--

CREATE RULE grp_detail_insert AS
    ON INSERT TO grp_detail DO INSTEAD  INSERT INTO grp_alias (prjtg_id, long_name, alias, website, productname, youtube_link)
  VALUES (new.prjtg_id, new.alias, new.long_name, new.website, new.productname, new.youtube_link);


--
-- Name: grp_detail_update; Type: RULE; Schema: public; Owner: -
--

CREATE RULE grp_detail_update AS
    ON UPDATE TO grp_detail DO INSTEAD  UPDATE grp_alias SET long_name = new.long_name, alias = new.alias, website = new.website, productname = new.productname, youtube_link = new.youtube_link
  WHERE (grp_alias.prjtg_id = new.prjtg_id);


--
-- Name: grp_details_delete; Type: RULE; Schema: public; Owner: -
--

CREATE RULE grp_details_delete AS
    ON DELETE TO grp_details DO INSTEAD  DELETE FROM grp_alias g
  WHERE (g.prjtg_id = old.prjtg_id);


--
-- Name: grp_details_insert; Type: RULE; Schema: public; Owner: -
--

CREATE RULE grp_details_insert AS
    ON INSERT TO grp_details DO INSTEAD  INSERT INTO grp_alias (prjtg_id, long_name, alias, website, productname, youtube_link)
  VALUES (new.prjtg_id, new.alias, new.long_name, new.website, new.productname, new.youtube_link);


--
-- Name: grp_details_update; Type: RULE; Schema: public; Owner: -
--

CREATE RULE grp_details_update AS
    ON UPDATE TO grp_details DO INSTEAD  UPDATE grp_alias SET long_name = new.long_name, alias = new.alias, website = new.website, productname = new.productname, youtube_link = new.youtube_link
  WHERE (grp_alias.prjtg_id = new.prjtg_id);


--
-- Name: student_email_delete; Type: RULE; Schema: public; Owner: -
--

CREATE RULE student_email_delete AS
    ON DELETE TO student_email DO INSTEAD NOTHING;


--
-- Name: student_email_update; Type: RULE; Schema: public; Owner: -
--

CREATE RULE student_email_update AS
    ON UPDATE TO student_email DO INSTEAD ( UPDATE student SET achternaam = new.achternaam, voorvoegsel = new.voorvoegsel, voorletters = new.voorletters, roepnaam = new.roepnaam, straat = new.straat, huisnr = new.huisnr, pcode = new.pcode, plaats = new.plaats, email1 = new.email1, nationaliteit = new.nationaliteit, hoofdgrp = new.hoofdgrp, active = new.active, cohort = new.cohort, gebdat = new.gebdat, sex = new.sex, lang = new.lang, pcn = new.pcn, opl = new.opl, phone_home = new.phone_home, phone_gsm = new.phone_gsm, phone_postaddress = new.phone_postaddress, faculty_id = new.faculty_id, slb = new.slb, studieplan = new.studieplan, geboorteplaats = new.geboorteplaats, geboorteland = new.geboorteland, voornaam = new.voornaam, class_id = new.class_id
  WHERE (student.snummer = new.snummer);
 DELETE FROM alt_email
  WHERE ((((alt_email.snummer = new.snummer) AND (new.email2 IS NULL)) AND (NOT (old.email2 IS NULL))) AND (alt_email.email3 IS NULL));
 INSERT INTO alt_email (snummer, email2)  SELECT new.snummer,
            new.email2
          WHERE (NOT (new.snummer IN ( SELECT alt_email.snummer
                   FROM alt_email)));
 UPDATE alt_email SET email2 = new.email2
  WHERE ((alt_email.snummer = new.snummer) AND (NOT (new.email2 IS NULL)));
);


--
-- Name: student_has_password; Type: RULE; Schema: public; Owner: -
--

CREATE RULE student_has_password AS
    ON INSERT TO student DO  INSERT INTO passwd (userid, password)
  VALUES (new.snummer, DEFAULT);


--
-- Name: tutor_data_update; Type: RULE; Schema: public; Owner: -
--

CREATE RULE tutor_data_update AS
    ON UPDATE TO tutor_data DO INSTEAD ( UPDATE student SET hoofdgrp = new.hoofdgrp
  WHERE (student.snummer = new.snummer);
 UPDATE tutor SET faculty_id = new.faculty_id
  WHERE (tutor.userid = new.snummer);
);


--
-- Name: tutor_join_student_delete; Type: RULE; Schema: public; Owner: -
--

CREATE RULE tutor_join_student_delete AS
    ON DELETE TO tutor_join_student DO INSTEAD  DELETE FROM tutor
  WHERE ((tutor.tutor)::text = (old.tutor)::text);


--
-- Name: tutor_join_student_insert; Type: RULE; Schema: public; Owner: -
--

CREATE RULE tutor_join_student_insert AS
    ON INSERT TO tutor_join_student DO INSTEAD  INSERT INTO tutor (tutor, userid, faculty_id, team, office, building, city, room, office_phone, schedule_id, display_name)
  VALUES (new.tutor, new.userid, new.faculty_id, new.team, new.function, new.building, new.city, new.room, new.office_phone, new.schedule_id, new.display_name);


--
-- Name: tutor_join_student_update; Type: RULE; Schema: public; Owner: -
--

CREATE RULE tutor_join_student_update AS
    ON UPDATE TO tutor_join_student DO INSTEAD  UPDATE tutor SET tutor = new.tutor, userid = new.userid, faculty_id = new.faculty_id, team = new.team, office = new.function, building = new.building, city = new.city, room = new.room, office_phone = new.office_phone, schedule_id = new.schedule_id, display_name = new.display_name
  WHERE (tutor.userid = old.userid);


--
-- Name: validator_regex_slashed_r_delete; Type: RULE; Schema: public; Owner: -
--

CREATE RULE validator_regex_slashed_r_delete AS
    ON DELETE TO validator_regex_slashed DO INSTEAD  DELETE FROM validator_regex
  WHERE (((validator_regex.regex_name)::text = (old.regex_name)::text) AND (validator_regex.regex = old.regex));


--
-- Name: validator_regex_slashed_r_insert; Type: RULE; Schema: public; Owner: -
--

CREATE RULE validator_regex_slashed_r_insert AS
    ON INSERT TO validator_regex_slashed DO INSTEAD  INSERT INTO validator_regex (regex_name, regex)
  VALUES (new.regex_name, new.regex);


--
-- Name: validator_regex_slashed_r_update; Type: RULE; Schema: public; Owner: -
--

CREATE RULE validator_regex_slashed_r_update AS
    ON UPDATE TO validator_regex_slashed DO INSTEAD  UPDATE validator_regex SET regex_name = new.regex_name, regex = new.regex
  WHERE (((validator_regex.regex_name)::text = (new.regex_name)::text) AND (validator_regex.regex = new.regex));


--
-- Name: student_email_insert; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER student_email_insert INSTEAD OF INSERT ON student_email FOR EACH ROW EXECUTE PROCEDURE insert_student_email();


--
-- Name: absence_reason_fk1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY absence_reason
    ADD CONSTRAINT absence_reason_fk1 FOREIGN KEY (act_id) REFERENCES activity(act_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: absence_reason_fk2; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY absence_reason
    ADD CONSTRAINT absence_reason_fk2 FOREIGN KEY (snummer) REFERENCES student(snummer) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: ac_fk1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY assessment_commit
    ADD CONSTRAINT ac_fk1 FOREIGN KEY (snummer) REFERENCES student(snummer) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: act_part_fk1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY activity_participant
    ADD CONSTRAINT act_part_fk1 FOREIGN KEY (snummer) REFERENCES student(snummer) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: act_part_fk2; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY activity_participant
    ADD CONSTRAINT act_part_fk2 FOREIGN KEY (act_id) REFERENCES activity(act_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: activity_fk1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY activity
    ADD CONSTRAINT activity_fk1 FOREIGN KEY (act_type) REFERENCES activity_type(act_type);


--
-- Name: activity_fk2; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY activity
    ADD CONSTRAINT activity_fk2 FOREIGN KEY (act_type) REFERENCES activity_type(act_type) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: activity_fk3; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY activity
    ADD CONSTRAINT activity_fk3 FOREIGN KEY (prjm_id) REFERENCES prj_milestone(prjm_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: activity_project_fk1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY activity_project
    ADD CONSTRAINT activity_project_fk1 FOREIGN KEY (prj_id) REFERENCES project(prj_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: alt_email_fk1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY alt_email
    ADD CONSTRAINT alt_email_fk1 FOREIGN KEY (snummer) REFERENCES student(snummer) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: alt_email_snummer_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY alt_email
    ADD CONSTRAINT alt_email_snummer_fk FOREIGN KEY (snummer) REFERENCES student(snummer);


--
-- Name: any_query_owner_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY any_query
    ADD CONSTRAINT any_query_owner_fkey FOREIGN KEY (owner) REFERENCES tutor(userid);


--
-- Name: assess_crit; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY assessment
    ADD CONSTRAINT assess_crit FOREIGN KEY (criterium) REFERENCES base_criteria(criterium_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: assessment_commit_fk2; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY assessment_commit
    ADD CONSTRAINT assessment_commit_fk2 FOREIGN KEY (prjtg_id, snummer) REFERENCES prj_grp(prjtg_id, snummer) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: assessment_prjtg_id_fk4; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY assessment
    ADD CONSTRAINT assessment_prjtg_id_fk4 FOREIGN KEY (prjtg_id) REFERENCES prj_tutor(prjtg_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: classes_cluster_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY student_class
    ADD CONSTRAINT classes_cluster_fk FOREIGN KEY (class_cluster) REFERENCES class_cluster(class_cluster) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: classes_owner; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY student_class
    ADD CONSTRAINT classes_owner FOREIGN KEY (owner) REFERENCES student(snummer);


--
-- Name: contestant_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY assessment
    ADD CONSTRAINT contestant_fk FOREIGN KEY (contestant) REFERENCES student(snummer) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: critique_history_fk1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY critique_history
    ADD CONSTRAINT critique_history_fk1 FOREIGN KEY (critique_id) REFERENCES document_critique(critique_id);


--
-- Name: critique_student_fk1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY document_critique
    ADD CONSTRAINT critique_student_fk1 FOREIGN KEY (critiquer) REFERENCES student(snummer);


--
-- Name: diploma_dates_fk1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY diploma_dates
    ADD CONSTRAINT diploma_dates_fk1 FOREIGN KEY (snummer) REFERENCES student(snummer);


--
-- Name: doc_dr_fk1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY document_critique
    ADD CONSTRAINT doc_dr_fk1 FOREIGN KEY (doc_id) REFERENCES uploads(upload_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: document_author_snummer_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY document_author
    ADD CONSTRAINT document_author_snummer_fkey FOREIGN KEY (snummer) REFERENCES student(snummer) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: document_author_upload_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY document_author
    ADD CONSTRAINT document_author_upload_id_fkey FOREIGN KEY (upload_id) REFERENCES uploads(upload_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: downloaded_id_fk1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY downloaded
    ADD CONSTRAINT downloaded_id_fk1 FOREIGN KEY (upload_id) REFERENCES uploads(upload_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: education_unit_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY education_unit_description
    ADD CONSTRAINT education_unit_fk FOREIGN KEY (education_unit_id, module_id) REFERENCES education_unit(education_unit_id, module_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: exam_event_examiner_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY exam_event
    ADD CONSTRAINT exam_event_examiner_fkey FOREIGN KEY (examiner) REFERENCES tutor(userid);


--
-- Name: exam_event_module_part_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY exam_event
    ADD CONSTRAINT exam_event_module_part_id_fkey FOREIGN KEY (module_part_id) REFERENCES module_part(module_part_id);


--
-- Name: exam_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY exam
    ADD CONSTRAINT exam_fk FOREIGN KEY (module_id, education_unit_id) REFERENCES education_unit(module_id, education_unit_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: exam_focus_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY learning_goal_exam_focus
    ADD CONSTRAINT exam_focus_fk FOREIGN KEY (exam_focus_id) REFERENCES exam_focus(exam_focus_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: exam_focus_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY exam_focus_description
    ADD CONSTRAINT exam_focus_id_fk FOREIGN KEY (exam_focus_id) REFERENCES exam_focus(exam_focus_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: exam_grades_snummer_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY exam_grades
    ADD CONSTRAINT exam_grades_snummer_fkey FOREIGN KEY (snummer) REFERENCES student(snummer);


--
-- Name: exam_grades_trans_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY exam_grades
    ADD CONSTRAINT exam_grades_trans_id_fkey FOREIGN KEY (trans_id) REFERENCES transaction(trans_id);


--
-- Name: exam_grading_aspect_description_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY exam_grading_aspect_description
    ADD CONSTRAINT exam_grading_aspect_description_fk FOREIGN KEY (exam_grading_aspect_id) REFERENCES exam_grading_aspect(exam_grading_aspect_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: exam_grading_aspect_description_fk2; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY exam_grading_aspect_description
    ADD CONSTRAINT exam_grading_aspect_description_fk2 FOREIGN KEY (language_id) REFERENCES language(language_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: exam_grading_aspect_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY exam
    ADD CONSTRAINT exam_grading_aspect_fk FOREIGN KEY (exam_grading_aspect_id) REFERENCES exam_grading_aspect(exam_grading_aspect_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: exam_grading_level_description_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY exam_grading_level_description
    ADD CONSTRAINT exam_grading_level_description_fk FOREIGN KEY (exam_grading_level_id) REFERENCES exam_grading_level(exam_grading_level_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: exam_grading_level_description_fk2; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY exam_grading_level_description
    ADD CONSTRAINT exam_grading_level_description_fk2 FOREIGN KEY (language_id) REFERENCES language(language_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: exam_grading_level_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY exam
    ADD CONSTRAINT exam_grading_level_fk FOREIGN KEY (exam_grading_level_id) REFERENCES exam_grading_level(exam_grading_level_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: exam_grading_type_description_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY exam_grading_type_description
    ADD CONSTRAINT exam_grading_type_description_fk FOREIGN KEY (exam_grading_type_id) REFERENCES exam_grading_type(exam_grading_type_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: exam_grading_type_description_fk2; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY exam_grading_type_description
    ADD CONSTRAINT exam_grading_type_description_fk2 FOREIGN KEY (language_id) REFERENCES language(language_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: exam_grading_type_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY exam
    ADD CONSTRAINT exam_grading_type_fk FOREIGN KEY (exam_grading_type_id) REFERENCES exam_grading_type(exam_grading_type_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: exam_type_description_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY exam_type_description
    ADD CONSTRAINT exam_type_description_fk FOREIGN KEY (exam_type_id) REFERENCES exam_type(exam_type_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: exam_type_description_fk2; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY exam_type_description
    ADD CONSTRAINT exam_type_description_fk2 FOREIGN KEY (language_id) REFERENCES language(language_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: exam_type_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY exam
    ADD CONSTRAINT exam_type_id_fk FOREIGN KEY (exam_type_id) REFERENCES exam_type(exam_type_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: grp_alias_prjtg_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY grp_alias
    ADD CONSTRAINT grp_alias_prjtg_id_fk FOREIGN KEY (prjtg_id) REFERENCES prj_tutor(prjtg_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: hoofdgrp_map_course_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY hoofdgrp_map
    ADD CONSTRAINT hoofdgrp_map_course_fkey FOREIGN KEY (course) REFERENCES fontys_course(course);


--
-- Name: judge_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY assessment
    ADD CONSTRAINT judge_fk FOREIGN KEY (judge) REFERENCES student(snummer) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: land_iso3166; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY student
    ADD CONSTRAINT land_iso3166 FOREIGN KEY (land) REFERENCES iso3166(a3) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: language_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY module_language
    ADD CONSTRAINT language_id_fk FOREIGN KEY (language_id) REFERENCES language(language_id);


--
-- Name: language_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY exam_focus_description
    ADD CONSTRAINT language_id_fk FOREIGN KEY (language_id) REFERENCES language(language_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: language_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY education_unit_description
    ADD CONSTRAINT language_id_fk FOREIGN KEY (module_id, language_id) REFERENCES module_language(module_id, language_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: learning_goal_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY learning_goal_exam_focus
    ADD CONSTRAINT learning_goal_fk FOREIGN KEY (module_id, learning_goal_id) REFERENCES learning_goal(module_id, learning_goal_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: meeloopmail_owner_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY meeloopmail
    ADD CONSTRAINT meeloopmail_owner_fkey FOREIGN KEY (owner) REFERENCES student(snummer);


--
-- Name: meelopen_opl_voorkeur_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY meelopen
    ADD CONSTRAINT meelopen_opl_voorkeur_fkey FOREIGN KEY (opl_voorkeur) REFERENCES fontys_course(course);


--
-- Name: milestone_grade_prjm_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY milestone_grade
    ADD CONSTRAINT milestone_grade_prjm_id_fkey FOREIGN KEY (prjm_id) REFERENCES prj_milestone(prjm_id);


--
-- Name: milestone_grade_snummer_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY milestone_grade
    ADD CONSTRAINT milestone_grade_snummer_fkey FOREIGN KEY (snummer) REFERENCES student(snummer);


--
-- Name: mini_student_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY mini2013
    ADD CONSTRAINT mini_student_fk FOREIGN KEY (snummer) REFERENCES student(snummer);


--
-- Name: module_activity_description_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY module_activity_description
    ADD CONSTRAINT module_activity_description_fk FOREIGN KEY (module_activity_id) REFERENCES module_activity(module_activity_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: module_activity_description_fk2; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY module_activity_description
    ADD CONSTRAINT module_activity_description_fk2 FOREIGN KEY (language_id) REFERENCES language(language_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: module_activity_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY module_week_schedule
    ADD CONSTRAINT module_activity_id_fk FOREIGN KEY (module_activity_id) REFERENCES module_activity(module_activity_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: module_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY module_prerequisite
    ADD CONSTRAINT module_id_fk FOREIGN KEY (module_id) REFERENCES module(module_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: module_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY module_language
    ADD CONSTRAINT module_id_fk FOREIGN KEY (module_id) REFERENCES module(module_id);


--
-- Name: module_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY module_desciption_long
    ADD CONSTRAINT module_id_fk FOREIGN KEY (module_id) REFERENCES module(module_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: module_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY learning_goal
    ADD CONSTRAINT module_id_fk FOREIGN KEY (module_id) REFERENCES module(module_id);


--
-- Name: module_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY module_week_schedule
    ADD CONSTRAINT module_id_fk FOREIGN KEY (module_id) REFERENCES module(module_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: module_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY module_resource
    ADD CONSTRAINT module_id_fk FOREIGN KEY (module_id) REFERENCES module(module_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: module_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY module_description_short
    ADD CONSTRAINT module_id_fk FOREIGN KEY (module_id) REFERENCES module(module_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: module_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY education_unit
    ADD CONSTRAINT module_id_fk FOREIGN KEY (module_id) REFERENCES module(module_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: module_language_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY module_description_short
    ADD CONSTRAINT module_language_fk FOREIGN KEY (module_id, language_id) REFERENCES module_language(module_id, language_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: module_language_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY learning_goal_description
    ADD CONSTRAINT module_language_fk FOREIGN KEY (module_id, language_id) REFERENCES module_language(module_id, language_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: module_language_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY module_desciption_long
    ADD CONSTRAINT module_language_id_fk FOREIGN KEY (module_id, language_id) REFERENCES module_language(module_id, language_id);


--
-- Name: module_part_fk1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY module_part
    ADD CONSTRAINT module_part_fk1 FOREIGN KEY (module_id) REFERENCES module(module_id);


--
-- Name: module_resource_id_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY module_resource
    ADD CONSTRAINT module_resource_id_fk FOREIGN KEY (module_resource_type_id) REFERENCES module_resource_type(module_resource_type_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: module_resource_type_description_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY module_resource_type_description
    ADD CONSTRAINT module_resource_type_description_fk FOREIGN KEY (module_resource_type_id) REFERENCES module_resource_type(module_resource_type_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: module_resource_type_description_fk2; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY module_resource_type_description
    ADD CONSTRAINT module_resource_type_description_fk2 FOREIGN KEY (language_id) REFERENCES language(language_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: module_topic_description_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY module_topic_description
    ADD CONSTRAINT module_topic_description_fk FOREIGN KEY (module_id, module_topic_id) REFERENCES module_topic(module_id, module_topic_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: module_topic_description_fk2; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY module_topic_description
    ADD CONSTRAINT module_topic_description_fk2 FOREIGN KEY (language_id) REFERENCES language(language_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: password_fk1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY passwd
    ADD CONSTRAINT password_fk1 FOREIGN KEY (userid) REFERENCES student(snummer) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: pd_fk3; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY project_deliverables
    ADD CONSTRAINT pd_fk3 FOREIGN KEY (prjm_id) REFERENCES prj_milestone(prjm_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: personal_repos_fk1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY personal_repos
    ADD CONSTRAINT personal_repos_fk1 FOREIGN KEY (owner) REFERENCES student(snummer) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: planned_school_visit_trans_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY planned_school_visit
    ADD CONSTRAINT planned_school_visit_trans_id_fkey FOREIGN KEY (trans_id) REFERENCES transaction(trans_id);


--
-- Name: planned_school_visit_visit_scholen_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY planned_school_visit
    ADD CONSTRAINT planned_school_visit_visit_scholen_id_fkey FOREIGN KEY (visit_scholen_id) REFERENCES scholen_int(scholen_int_id);


--
-- Name: planned_school_visit_visit_schulen_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY planned_school_visit
    ADD CONSTRAINT planned_school_visit_visit_schulen_id_fkey FOREIGN KEY (visit_schulen_id) REFERENCES schulen(schulen_id);


--
-- Name: pr_tasks_fk2; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY project_tasks
    ADD CONSTRAINT pr_tasks_fk2 FOREIGN KEY (snummer) REFERENCES student(snummer) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: prerequisite_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY module_prerequisite
    ADD CONSTRAINT prerequisite_fk FOREIGN KEY (prerequisite) REFERENCES module(module_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: prj_contact_fk1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY prj_contact
    ADD CONSTRAINT prj_contact_fk1 FOREIGN KEY (prjtg_id) REFERENCES prj_tutor(prjtg_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: prj_contact_prjtg_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY prj_contact
    ADD CONSTRAINT prj_contact_prjtg_id_fkey FOREIGN KEY (prjtg_id) REFERENCES prj_tutor(prjtg_id);


--
-- Name: prj_grp_fk2; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY prj_grp
    ADD CONSTRAINT prj_grp_fk2 FOREIGN KEY (snummer) REFERENCES student(snummer) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: prj_grp_fk3; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY prj_grp
    ADD CONSTRAINT prj_grp_fk3 FOREIGN KEY (prjtg_id) REFERENCES prj_tutor(prjtg_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: prj_m_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY prj_milestone
    ADD CONSTRAINT prj_m_fk FOREIGN KEY (prj_id) REFERENCES project(prj_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: prj_role_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY project_roles
    ADD CONSTRAINT prj_role_fk FOREIGN KEY (prj_id) REFERENCES project(prj_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: prj_tutor_fk2; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY prj_tutor
    ADD CONSTRAINT prj_tutor_fk2 FOREIGN KEY (prjm_id) REFERENCES prj_milestone(prjm_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: prj_tutor_tutor_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY prj_tutor
    ADD CONSTRAINT prj_tutor_tutor_id_fkey FOREIGN KEY (tutor_id) REFERENCES tutor(userid) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: prjm_criterium_fk1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY prjm_criterium
    ADD CONSTRAINT prjm_criterium_fk1 FOREIGN KEY (prjm_id) REFERENCES prj_milestone(prjm_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: prjm_criterium_fk2; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY prjm_criterium
    ADD CONSTRAINT prjm_criterium_fk2 FOREIGN KEY (criterium_id) REFERENCES base_criteria(criterium_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: project_attributes_def_author_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY project_attributes_def
    ADD CONSTRAINT project_attributes_def_author_fkey FOREIGN KEY (author) REFERENCES student(snummer);


--
-- Name: project_attributes_def_prj_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY project_attributes_def
    ADD CONSTRAINT project_attributes_def_prj_id_fkey FOREIGN KEY (prj_id) REFERENCES project(prj_id);


--
-- Name: project_attributes_values_prjtg_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY project_attributes_values
    ADD CONSTRAINT project_attributes_values_prjtg_id_fkey FOREIGN KEY (prjtg_id) REFERENCES prj_tutor(prjtg_id);


--
-- Name: project_attributes_values_project_attributes_def_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY project_attributes_values
    ADD CONSTRAINT project_attributes_values_project_attributes_def_fkey FOREIGN KEY (project_attributes_def) REFERENCES project_attributes_def(project_attributes_def);


--
-- Name: project_auditor_fk1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY project_auditor
    ADD CONSTRAINT project_auditor_fk1 FOREIGN KEY (snummer) REFERENCES student(snummer);


--
-- Name: project_auditor_fk2; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY project_auditor
    ADD CONSTRAINT project_auditor_fk2 FOREIGN KEY (prjm_id) REFERENCES prj_milestone(prjm_id);


--
-- Name: project_course_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY project
    ADD CONSTRAINT project_course_fkey FOREIGN KEY (course) REFERENCES fontys_course(course);


--
-- Name: project_owner_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY project
    ADD CONSTRAINT project_owner_id_fkey FOREIGN KEY (owner_id) REFERENCES tutor(userid) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: project_scribe_fk1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY project_scribe
    ADD CONSTRAINT project_scribe_fk1 FOREIGN KEY (scribe) REFERENCES student(snummer);


--
-- Name: project_scribe_fk2; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY project_scribe
    ADD CONSTRAINT project_scribe_fk2 FOREIGN KEY (prj_id) REFERENCES project(prj_id);


--
-- Name: project_task_completed_fk1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY project_task_completed
    ADD CONSTRAINT project_task_completed_fk1 FOREIGN KEY (snummer) REFERENCES student(snummer);


--
-- Name: project_task_completed_fk2; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY project_task_completed
    ADD CONSTRAINT project_task_completed_fk2 FOREIGN KEY (task_id) REFERENCES project_task(task_id);


--
-- Name: project_task_completed_trans_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY project_task_completed
    ADD CONSTRAINT project_task_completed_trans_id_fkey FOREIGN KEY (trans_id) REFERENCES transaction(trans_id);


--
-- Name: project_task_fk1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY project_tasks
    ADD CONSTRAINT project_task_fk1 FOREIGN KEY (prj_id) REFERENCES project(prj_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: project_task_fk1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY project_task
    ADD CONSTRAINT project_task_fk1 FOREIGN KEY (prj_id) REFERENCES project(prj_id);


--
-- Name: recruiters_note_followup_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY recruiters_note
    ADD CONSTRAINT recruiters_note_followup_fkey FOREIGN KEY (followup) REFERENCES recruiters_note(recruiters_note_id);


--
-- Name: recruiters_note_trans_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY recruiters_note
    ADD CONSTRAINT recruiters_note_trans_id_fkey FOREIGN KEY (trans_id) REFERENCES transaction(trans_id);


--
-- Name: registered_mphotos_snummer_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY registered_mphotos
    ADD CONSTRAINT registered_mphotos_snummer_fkey FOREIGN KEY (snummer) REFERENCES student(snummer);


--
-- Name: registered_photos_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY registered_photos
    ADD CONSTRAINT registered_photos_fk FOREIGN KEY (snummer) REFERENCES student(snummer) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: repositories_prjtg_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY repositories
    ADD CONSTRAINT repositories_prjtg_id_fkey FOREIGN KEY (prjtg_id) REFERENCES prj_tutor(prjtg_id);


--
-- Name: slb_student_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY student
    ADD CONSTRAINT slb_student_fk FOREIGN KEY (slb) REFERENCES student(snummer);


--
-- Name: student_class_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY student
    ADD CONSTRAINT student_class_id_fkey FOREIGN KEY (class_id) REFERENCES student_class(class_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: student_faculty_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY student
    ADD CONSTRAINT student_faculty_fk FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: student_language_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY student
    ADD CONSTRAINT student_language_fk FOREIGN KEY (lang) REFERENCES language(language_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: student_role_fk2; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY student_role
    ADD CONSTRAINT student_role_fk2 FOREIGN KEY (prjm_id) REFERENCES prj_milestone(prjm_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: student_slb_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY student
    ADD CONSTRAINT student_slb_fk FOREIGN KEY (slb) REFERENCES tutor(userid) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: student_studieplan_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY student
    ADD CONSTRAINT student_studieplan_fk FOREIGN KEY (studieplan) REFERENCES studieplan(studieplan) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: task_timer_fk1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY task_timer
    ADD CONSTRAINT task_timer_fk1 FOREIGN KEY (prjm_id) REFERENCES prj_milestone(prjm_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: task_timer_fk2; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY task_timer
    ADD CONSTRAINT task_timer_fk2 FOREIGN KEY (snummer, prj_id, task_id) REFERENCES project_tasks(snummer, prj_id, task_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: task_timer_prjm_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY task_timer
    ADD CONSTRAINT task_timer_prjm_id_fkey FOREIGN KEY (prjm_id) REFERENCES prj_milestone(prjm_id);


--
-- Name: transaction_operator_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY transaction
    ADD CONSTRAINT transaction_operator_fkey FOREIGN KEY (operator) REFERENCES student(snummer);


--
-- Name: tutor_class_cluster_class_cluster_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY tutor_class_cluster
    ADD CONSTRAINT tutor_class_cluster_class_cluster_fkey FOREIGN KEY (class_cluster) REFERENCES class_cluster(class_cluster);


--
-- Name: tutor_class_cluster_userid_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY tutor_class_cluster
    ADD CONSTRAINT tutor_class_cluster_userid_fkey FOREIGN KEY (userid) REFERENCES tutor(userid);


--
-- Name: tutor_faculty_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY tutor
    ADD CONSTRAINT tutor_faculty_fk FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id);


--
-- Name: tutor_fk1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY tutor
    ADD CONSTRAINT tutor_fk1 FOREIGN KEY (userid) REFERENCES student(snummer) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: unix_uid_fk1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY unix_uid
    ADD CONSTRAINT unix_uid_fk1 FOREIGN KEY (snummer) REFERENCES student(snummer) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: updt_fk1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY uploaddocumenttypes
    ADD CONSTRAINT updt_fk1 FOREIGN KEY (prj_id) REFERENCES project(prj_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: uploads_fk2; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY uploads
    ADD CONSTRAINT uploads_fk2 FOREIGN KEY (snummer) REFERENCES student(snummer) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: uploads_fk3; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY uploads
    ADD CONSTRAINT uploads_fk3 FOREIGN KEY (prjm_id) REFERENCES prj_milestone(prjm_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: uploads_fk4; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY uploads
    ADD CONSTRAINT uploads_fk4 FOREIGN KEY (prjm_id, doctype) REFERENCES project_deliverables(prjm_id, doctype);


--
-- Name: uploads_fk5; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY uploads
    ADD CONSTRAINT uploads_fk5 FOREIGN KEY (prjtg_id) REFERENCES prj_tutor(prjtg_id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: uploads_prjtg_id_fk1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY uploads
    ADD CONSTRAINT uploads_prjtg_id_fk1 FOREIGN KEY (prjtg_id) REFERENCES prj_tutor(prjtg_id);


--
-- PostgreSQL database dump complete
--

