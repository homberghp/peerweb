-- üß utf-8
begin work;
drop view if exists sv09_as_student_email_v cascade;
update sv09_ingeschrevenen set geboorteland='Bondsrepubliek Duitsland' where geboorteland='Duitsland';
update sv09_ingeschrevenen set land='Bondsrepubliek Duitsland' where land='Duitsland';
update sv09_ingeschrevenen set land='Verenigd Koninkrijk'  where land='Groot-Brittannië';
update sv09_ingeschrevenen set geboorteland='Verenigd Koninkrijk'  where geboorteland='Groot-Brittannië';
update sv09_ingeschrevenen set land='Republiek Moldavië'  where land='Moldavië';
update sv09_ingeschrevenen set geboorteland='Republiek Moldavië'  where geboorteland='Moldavië';
update sv09_ingeschrevenen set land='Groothertogdom Luxemburg'  where land='Luxemburg';
update sv09_ingeschrevenen set geboorteland='Groothertogdom Luxemburg'  where geboorteland='Luxemburg';
update sv09_ingeschrevenen set geboorteland='Israël'  where geboorteland='Israel';
update sv09_ingeschrevenen set land='Israël'  where land='Israel';
update sv09_ingeschrevenen set land='Verenigde Staten'  where land='Verenigde Staten van Amerika';
update sv09_ingeschrevenen set geboorteland='Verenigde Staten'  where geboorteland='Verenigde Staten van Amerika';
update sv09_ingeschrevenen set geboorteland='Tsjechië'  where geboorteland='Tsjecho-Slowakije';
update sv09_ingeschrevenen set land='Tsjechië'  where land='Tsjecho-Slowakije';

alter table  sv09_ingeschrevenen add constraint sv09_studielinkvariantcode_fk foreign key (studielinkvariantcode) references public.studieplan(studieplan);
alter table  sv09_ingeschrevenen add constraint sv09_nat_mapper_fk foreign key (leidende_nationaliteit) references public.nat_mapper(nation_omschr);
alter table  sv09_ingeschrevenen add constraint sv09_iso3166_land_nl_fk foreign key (land) references public.iso3166(land_nl);
alter table  sv09_ingeschrevenen add constraint sv09_iso3166_geboorteland_fk foreign key (geboorteland) references public.iso3166(land_nl);

create view sv09_as_student_email_v as
       select distinct on(studentnummer)
       studentnummer as snummer,
       achternaam,
       voorvoegsels as tussenvoegsel,
       voorletters,
       roepnaam,
       substr(straat,1,40) as straat,
       substr(upper(trim(leading '0' from coalesce(huisnummer||huisnummertoevoeging,huisnummer::text,huisnummertoevoeging))),1,4) as huisnr,
       postcode as pcode,
       woonplaats as plaats,
       e_mail_instelling  as email1,
       nm.nationaliteit,
       extract(year from datum_aankomst_opleiding) as cohort, 
       geboortedatum as gebdat, 
       case when substr(geslacht,1,1) ~* 'v' then 'F' else 'M' end  as sex,
       case
       	    when voorkeurstaal ='Engels'  then 'EN' 
       	    when voorkeurstaal ='Duits'   then 'DE'
	    else 'NL' end 
       as lang,
       pcn_nummer as pcn,
       sp.studieprogr as opl,
       '+'||land_nummer_vast_centrale_verificatie||' '||vast_nummer_centrale_verificatie as phone_home, 
       '+'||land_nummer_mobiel||' '||mobiel_nummer as phone_gsm,
       null::varchar(40) as  phone_postaddress,
       instituutcode::int as faculty_id,
       lesgroep as hoofdgrp,
       true as active,
       null::integer as slb,
       iso.a3 as land,
       studielinkvariantcode as studieplan,
       geboorteplaats,
       iso2.a3 as  geboorteland,
       voornamen,
       0 as class_id,
       e_mail_privé  as email2
from sv09_ingeschrevenen a
left join public.studieplan sp on(sp.studieplan = a.studielinkvariantcode)
left join public.nat_mapper nm on(leidende_nationaliteit=nation_omschr)
left join public.iso3166 iso on (a.land=iso.land_nl)
left join public.iso3166 iso2 on (a.geboorteland=iso2.land_nl);

-- select studentnummer,geboorteland,studielinkvariantcode,opleiding
--        from sv09_ingeschrevenen
--        where not exists (select 1 from sv09_ingeschrevenen_as_student_v where snummer=studentnummer);

-- with s1 as (select count(1) as x,'read distinct and filtered records'::text as comment,1 as r from  sv09_as_student_email_v),
--   s2 as (select count(1) as x,'new students'::text as comment,2 as r from sv09_as_student_email_v where snummer not in (select snummer from student)),
--   s3 as (select count(1) as x,'updated students'::text as comment,3 as r  from sv09_as_student_email_v  join student using(snummer))
--   select r,x,comment  from s1 union select r,x,comment from s2 union select r,x,comment from s3 order by r;
--
-- insert into student_email select * from sv09_as_student_email_v;
commit;

-- select count(1) as ingeschrevenen from sv09_ingeschrevenen;
-- select count(1) as ingeschrevenen_undup from sv09_view_no_doubles;
-- select count(1) as doubles from sv09_as_student_email_v group by snummer having count(snummer) >1 ;
