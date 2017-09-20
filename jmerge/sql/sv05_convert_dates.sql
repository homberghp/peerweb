begin work;
-- alter table sv05_aanmelders alter column peildatum type date using to_date(peildatum,'dd-mm-yyyy'::text);
-- alter table sv05_aanmelders alter column aanmelddatum type date using to_date(aanmelddatum,'dd-mm-yyyy'::text);

-- alter table sv05_aanmelders alter column geboortedatum type date using to_date(geboortedatum,'dd-mm-yyyy'::text);
-- alter table sv05_aanmelders alter column datum_definitief_ingeschreven type date using to_date(datum_definitief_ingeschreven,'dd-mm-yyyy'::text);
-- alter table sv05_aanmelders alter column datum_annulering type date using to_date(datum_annulering,'dd-mm-yyyy'::text);
-- alter table sv05_aanmelders alter column datum_aankomst_fontys type date using to_date(datum_aankomst_fontys,'dd-mm-yyyy'::text);
-- alter table sv05_aanmelders alter column datum_aankomst_opleiding type date using to_date(datum_aankomst_opleiding,'dd-mm-yyyy'::text);
-- alter table sv05_aanmelders alter column pasfoto_uploaddatum type date using to_date(pasfoto_uploaddatum,'dd-mm-yyyy'::text);
--


commit;
