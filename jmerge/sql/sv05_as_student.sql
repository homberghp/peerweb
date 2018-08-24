begin work;
drop view if exists sv05_as_student_email_v;
update sv05_aanmelders set geboorteland='Bondsrepubliek Duitsland' where geboorteland='Duitsland';
-- alter table sv05_aanmelders drop constraint if exists sv05_studielinkvariantcode_fk;
-- alter table sv05_aanmelders drop constraint if exists sv05_nat_mapper_fk;
-- alter table sv05_aanmelders drop constraint if exists sv05_iso3166_land_nl_fk;
-- alter table sv05_aanmelders drop constraint if exists sv05_iso3166_geboorteland_fk;

-- alter table  sv05_aanmelders add constraint sv05_studielinkvariantcode_fk foreign key (studielinkvariantcode) references public.studieplan(studieplan) not valid;
-- alter table  sv05_aanmelders add constraint sv05_nat_mapper_fk foreign key (leidende_nationaliteit) references public.nat_mapper(nation_omschr) not valid;
-- alter table  sv05_aanmelders add constraint sv05_iso3166_land_nl_fk foreign key (land) references public.iso3166(land_nl) not valid;
-- alter table  sv05_aanmelders add constraint sv05_iso3166_geboorteland_fk foreign key (geboorteland) references public.iso3166(land_nl) not valid;

-- alter table sv05_aanmelders validate constraint sv05_studielinkvariantcode_fk ;
-- alter table sv05_aanmelders validate constraint sv05_nat_mapper_fk ;
-- alter table sv05_aanmelders validate constraint sv05_iso3166_land_nl_fk ;
-- alter table sv05_aanmelders validate constraint sv05_iso3166_geboorteland_fk ;
-- alter table sv05_aanmelders rename column geslacht to sex  ;
alter table sv05_aanmelders alter column geslacht type char(1);
update  sv05_aanmelders set geslacht='F' where geslacht='V';
-- alter table sv05_aanmelders alter column geboortedatum type date using to_date(geboortedatum,'dd-mm-yyyy');
create view sv05_as_student_email_v as
       select distinct on(studentnummer) studentnummer as snummer,
       achternaam,
       voorvoegsels as tussenvoegsel,
       voorletters,
       roepnaam,
       straat,
       huisnr as huisnr,
       postcode as pcode,
       woonplaats as plaats,
       e_mail_instelling  as email1,
       nm.nationaliteit,
       coalesce(extract(year from datum_aankomst_opleiding),studiejaar,extract(year from now()::date)) as cohort, 
       geboortedatum as gebdat, 
       geslacht as sex,
       case 
       	    when voorkeurstaal ='Engels'  then 'EN' 
       	    when voorkeurstaal ='Duits'     then 'DE'
	    else 'NL' end 
       as lang,
       pcn_nummer as pcn,
       sp.studieprogr as opl,
       '+'||land_nummer_vast||' '||vast_nummer as phone_home, 
       '+'||land_nummer_mobiel||' '||mobiel_nummer as phone_gsm,
       null::text as  phone_postaddress,
       instituutcode as faculty_id,
       course_grp as hoofdgrp,
       true as active,
       null::integer as slb,
       iso.a3 as land,
       studielinkvariantcode as studieplan,
       geboorteplaats,
       iso2.a3 as  geboorteland,
       voornamen,
       0 as class_id,
       --e_mail_privé as email2
       e_mail_privé as email2
from sv05_aanmelders a 
join public.studieplan sp on(sp.studieplan = a.studielinkvariantcode)
left join public.nat_mapper nm on(leidende_nationaliteit=nation_omschr)
left join public.iso3166 iso on (a.land=iso.land_nl)
left join public.iso3166 iso2 on (a.geboorteland=iso2.land_nl)
order by studentnummer,hoofdgrp,opl,studieplan;

commit;
