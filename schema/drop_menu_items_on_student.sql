begin work;
delete from menu_item where menu_name='student_admin' and column_name in ('phone_gsm','phone_home','phone_postaddress','pcode','huisnr','plaats','straat','geboorteplaats','geboorteland','email2');

delete from menu_item where menu_name='prospects' and column_name in ('phone_gsm','phone_home','phone_postaddress','pcode','huisnr','plaats','straat','geboorteplaats','geboorteland','email2','slb');

-- take down non FHTnL users
update student set active=false where faculty_id <> 47;

commit;
