-- begin work;
-- weg met afmelders
delete from sv05_aanmelders where datum_annulering notnull;
--alter table sv05_aanmelders alter column draaidatum type date using ('1899-12-30'::date+floor(draaidatum)::int);
alter table sv05_aanmelders rename column draaidatum  to peildatum;

update sv05_aanmelders set fase='Bachelor' where fase <> 'Bachelor';
update sv05_aanmelders set voorkeurstaal='Nederlands' where voorkeurstaal isnull;
-- voeg codering toe
alter table sv05_aanmelders add column if not exists course_grp text;
alter table sv05_aanmelders add column if not exists lang char(2);
update sv05_aanmelders set lang ='NL' where voorkeurstaal='Nederlands';
update sv05_aanmelders set lang ='DE' where voorkeurstaal='Duits';
update sv05_aanmelders set lang ='EN' where voorkeurstaal='Engels';


update sv05_aanmelders set course_grp='IPODE'||studiejaar where  opleiding='B Industrieel Produkt Ontwerpen' and voorkeurstaal='Duits';
update sv05_aanmelders set course_grp='IPONL'||studiejaar where  opleiding='B Industrieel Produkt Ontwerpen' and voorkeurstaal='Nederlands';
update sv05_aanmelders set course_grp='IPOEN'||studiejaar where  opleiding='B Industrieel Produkt Ontwerpen' and voorkeurstaal='Engels';

update sv05_aanmelders set course_grp='SEBIEN'||studiejaar where  variant_omschrijving='Software Engineering English';
update sv05_aanmelders set course_grp='SEBIEN'||studiejaar where  variant_omschrijving='Business Informatics English';

update sv05_aanmelders set course_grp='SEBIDE'||studiejaar where  variant_omschrijving='Software Engineering Deutsch';
update sv05_aanmelders set course_grp='SEBIDE'||studiejaar where  variant_omschrijving='Wirtschaftsinformatik Deutsch';

update sv05_aanmelders set course_grp='SEBINL'||studiejaar where variant_omschrijving='Business Informatics Nederlands';
update sv05_aanmelders set course_grp='SEBINL'||studiejaar where variant_omschrijving='Software Engineering Nederlands';

update sv05_aanmelders set course_grp='LEEN'||studiejaar where  opleiding='B Logistiek en Economie' and voorkeurstaal='Engels';
update sv05_aanmelders set course_grp='LEDE'||studiejaar where  opleiding='B Logistiek en Economie' and voorkeurstaal='Duits';
update sv05_aanmelders set course_grp='LENL'||studiejaar where  opleiding='B Logistiek en Economie' and voorkeurstaal='Nederlands';

update sv05_aanmelders set course_grp='LMEN'||studiejaar where  opleiding='B Logistics Management' and voorkeurstaal='Engels';
update sv05_aanmelders set course_grp='LMDE'||studiejaar where  opleiding='B Logistics Management' and voorkeurstaal='Duits';
update sv05_aanmelders set course_grp='LMNL'||studiejaar where  opleiding='B Logistics Management' and voorkeurstaal='Nederlands';

update sv05_aanmelders set course_grp='LTNL'||studiejaar where  opleiding='B Logistiek en Technische Vervoerskunde' and voorkeurstaal='Nederlands';
update sv05_aanmelders set course_grp='LTDE'||studiejaar where  opleiding='B Logistiek en Technische Vervoerskunde' and voorkeurstaal='Duits';
update sv05_aanmelders set course_grp='LTEN'||studiejaar where  opleiding='B Logistiek en Technische Vervoerskunde' and voorkeurstaal='Engels';
update sv05_aanmelders set course_grp='MEEN'||studiejaar where  opleiding='B Mechatronica' and voorkeurstaal='Engels';
update sv05_aanmelders set course_grp='MENL'||studiejaar where  opleiding='B Mechatronica' and voorkeurstaal='Nederlands';
update sv05_aanmelders set course_grp='MEDE'||studiejaar where  opleiding='B Mechatronica' and voorkeurstaal='Duits';
update sv05_aanmelders set course_grp='WTBDE'||studiejaar where  opleiding='B Werktuigbouwkunde' and voorkeurstaal='Duits';
update sv05_aanmelders set course_grp='WTBNL'||studiejaar where  opleiding='B Werktuigbouwkunde' and voorkeurstaal='Nederlands';
update sv05_aanmelders set course_grp='WTBEN'||studiejaar where  opleiding='B Werktuigbouwkunde' and voorkeurstaal='Engels';

update sv05_aanmelders set course_grp='BEDE'||studiejaar where  opleiding='B Bedrijfseconomie' and voorkeurstaal='Duits';
update sv05_aanmelders set course_grp='BENL'||studiejaar where  opleiding='B Bedrijfseconomie' and voorkeurstaal='Nederlands';
update sv05_aanmelders set course_grp='BEEN'||studiejaar where  opleiding='B Bedrijfseconomie' and voorkeurstaal='Engels';

update sv05_aanmelders set course_grp='IFBDE'||studiejaar where  opleiding='B International Fresh Business Managemen' and voorkeurstaal='Duits';
update sv05_aanmelders set course_grp='IFBNL'||studiejaar where  opleiding='B International Fresh Business Managemen' and voorkeurstaal='Nederlands';
update sv05_aanmelders set course_grp='IFBEN'||studiejaar where  opleiding='B International Fresh Business Managemen' and voorkeurstaal='Engels';

update sv05_aanmelders set course_grp='CEDE'||studiejaar where  opleiding='B Commerciële Economie' and voorkeurstaal='Duits';
update sv05_aanmelders set course_grp='CENL'||studiejaar where  opleiding='B Commerciële Economie' and voorkeurstaal='Nederlands';
update sv05_aanmelders set course_grp='CEEN'||studiejaar where  opleiding='B Commerciële Economie' and voorkeurstaal='Engels';

update sv05_aanmelders set course_grp='IBMSDE'||studiejaar where  opleiding='B Internat Business and Managem Studies' and voorkeurstaal='Duits';
update sv05_aanmelders set course_grp='IBMSNL'||studiejaar where  opleiding='B Internat Business and Managem Studies' and voorkeurstaal='Nederlands';
update sv05_aanmelders set course_grp='IBMSEN'||studiejaar where  opleiding='B Internat Business and Managem Studies' and voorkeurstaal='Engels';

update sv05_aanmelders set course_grp='LENGDE'||studiejaar where  opleiding='B Logistics Engineering' and voorkeurstaal='Duits';
update sv05_aanmelders set course_grp='LENGNL'||studiejaar where  opleiding='B Logistics Engineering' and voorkeurstaal='Nederlands';
update sv05_aanmelders set course_grp='LENGEN'||studiejaar where  opleiding='B Logistics Engineering' and voorkeurstaal='Engels';

update sv05_aanmelders set course_grp='ADENNL'||studiejaar where  opleiding='Ad Engineering' and voorkeurstaal='Nederlands';

update sv05_aanmelders set land_nummer_vast=null where vast_nummer =null or land_nummer_vast=0;
update sv05_aanmelders set land_nummer_mobiel=null where mobiel_nummer =null or land_nummer_mobiel =0;
-- commit; 
