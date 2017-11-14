begin work;
drop table if exists importer.sv09_ingeschrevenen cascade;
set search_path=importer,public;
-- CREATE TABLE if not exists importer.sv09_ingeschrevenen (
--     peildatum date,
--     studiejaar integer,
--     instituutcode integer,
--     instituutnaam text,
--     directeur text,
--     studentnummer integer,
--     achternaam text,
--     voorvoegsels text,
--     voorletters text,
--     voornamen text,
--     roepnaam text,
--     volledig_naam text,
--     geslacht text,
--     geboortedatum date,
--     geboorteplaats text,
--     geboorteland text,
--     "e_mail_privé" text,
--     e_mail_instelling text,
--     land_nummer_mobiel integer,
--     mobiel_nummer bigint,
--     land_nummer_vast_centrale_verificatie integer,
--     vast_nummer_centrale_verificatie bigint,
--     land_nummer_vast_decentrale_verificatie integer,
--     vast_nummer_decentrale_verificatie bigint,
--     pcn_nummer integer,
--     onderwijsnummer integer,
--     straat text,
--     huisnummer integer,
--     huisnummertoevoeging text,
--     postcode text,
--     woonplaats text,
--     buitenlandse_adresregel_1 text,
--     buitenlandse_adresregel_2 text,
--     buitenlandse_adresregel_3 text,
--     land text,
--     nationaliteit_1 text,
--     nationaliteit_2 text,
--     leidende_nationaliteit text,
--     eer text,
--     inschrijvingid integer,
--     isatcode integer,
--     opleiding text,
--     opleidingsnaam_voluit text,
--     opleidingsnaam_voluit_engels text,
--     studielinkvariantcode integer,
--     variant_omschrijving text,
--     lesplaats text,
--     vorm text,
--     fase text,
--     bijvakker character(4),
--     datum_van date,
--     datum_tot date,
--     datum_aankomst_fontys date,
--     datum_aankomst_instituut date,
--     datum_aankomst_opleiding date,
--     propedeuse_datum date,
--     propedeuse_judicium_datum date,
--     aanmelddatum date,
--     instroom text,
--     dipl_vooropl_behaald character(4),
--     datum_dipl_vooropl date,
--     detail_toelaatbare_vooropleiding text,
--     cluster_vooropl text,
--     toeleverende_school text,
--     plaats_toeleverende_school text,
--     soort_verzoek text,
--     datum_definitief_ingeschreven date,
--     lesgroep text,
--     indicatie_collegegeld text,
--     pasfoto_uploaddatum date,
--     voorkeurstaal text,
--     kop_opleiding text
-- );


-- ALTER TABLE importer.sv09_ingeschrevenen OWNER TO importer;
-- GRANT ALL ON TABLE importer.sv09_ingeschrevenen TO PUBLIC;


-- truncate importer.sv09_ingeschrevenen;
-- alter table importer.sv09_ingeschrevenen drop constraint if exists sv09_studielinkvariantcode_fk;
-- alter table importer.sv09_ingeschrevenen drop constraint if exists sv09_nat_mapper_fk;
-- alter table importer.sv09_ingeschrevenen drop constraint if exists sv09_iso3166_land_nl_fk;
-- alter table importer.sv09_ingeschrevenen drop constraint if exists sv09_iso3166_geboorteland_fk;

commit;
