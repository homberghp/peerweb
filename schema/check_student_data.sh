#!/bin/bash
targetdb=peer2

if [[ $# > 0  ]]; then
    targetdb=$1
    shift
    
fi
echo "working with ${targetdb}"

cat - <<EOF | psql -X ${targetdb}
select snummer,roepnaam, achternaam, straat,pcode,plaats,
  phone_home,
  phone_gsm,
  phone_postaddress,
  geboorteplaats,geboorteland from student_email where class_id in (400,401,1568);
EOF

