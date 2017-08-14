begin work;
truncate sv09_ingeschrevenen;
alter table sv09_ingeschrevenen drop constraint if exists sv09_studielinkvariantcode_fk;
alter table sv09_ingeschrevenen drop constraint if exists sv09_nat_mapper_fk;
alter table sv09_ingeschrevenen drop constraint if exists sv09_iso3166_land_nl_fk;
alter table sv09_ingeschrevenen drop constraint if exists sv09_iso3166_geboorteland_fk;

commit;
