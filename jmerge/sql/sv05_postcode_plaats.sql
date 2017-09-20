begin work;
alter table sv05_aanmelders add column if not exists postcode text;
alter table sv05_aanmelders add column if not exists woonplaats text;
alter table sv05_aanmelders add column if not exists huisnr char(4);
alter table sv05_aanmelders add column if not exists straat text;
update sv05_aanmelders set postcode=substr(postcode_en_plaats,1,7) where land in ('Nederland','Letland','Republiek Moldavië');
update sv05_aanmelders set woonplaats= initcap(substr(postcode_en_plaats,9)) where land in ('Nederland','Letland','Republiek Moldavië');

update sv05_aanmelders set postcode=substr(postcode_en_plaats,1,5) where land in ('Maleisië','Marokko','Spanje','Griekenland','Italië','Litouwen','Bondsrepubliek Duitsland');
update sv05_aanmelders set woonplaats= initcap(substr(postcode_en_plaats,7)) where land in ('Maleisië','Marokko','Spanje','Griekenland','Italië','Litouwen','Bondsrepubliek Duitsland');

update sv05_aanmelders set postcode=substr(postcode_en_plaats,1,6) where land in ('India','Rusland','China','Roemenië');
update sv05_aanmelders set woonplaats= initcap(substr(postcode_en_plaats,8)) where land in ('India','Rusland','China','Roemenië');

update sv05_aanmelders set postcode=substr(postcode_en_plaats,1,8) where land='Verenigd Koninkrijk';
update sv05_aanmelders set woonplaats= initcap(substr(postcode_en_plaats,10)) where land='Verenigd Koninkrijk';


update sv05_aanmelders set postcode=substr(postcode_en_plaats,1,4) where land in ('Hongarije','Australië','Bulgarije');
update sv05_aanmelders set woonplaats= initcap(substr(postcode_en_plaats,6)) where land in ('Hongarije','Australië','Bulgarije');

update sv05_aanmelders set woonplaats= initcap(postcode_en_plaats) where land='Aruba';
update sv05_aanmelders set woonplaats= initcap(postcode_en_plaats) where land='Kameroen';

update sv05_aanmelders set postcode=substr(postcode_en_plaats,1,5) where land in ('Griekenland','Italië','Litouwen');
update sv05_aanmelders set woonplaats= initcap(substr(postcode_en_plaats,7)) where land in ('Griekenland','Italië','Litouwen');
update sv05_aanmelders set huisnr = substr(regexp_replace(volledig_adres,E'(.*)?\\s(\\d+)\\s?([A-za-z])?$',E'\\2\\3'),1,4);
update sv05_aanmelders set straat = substr(regexp_replace(volledig_adres,E'(.*)?\\s(\\d+)\\s?([A-za-z])?$',E'\\1'),1,40);
commit;
