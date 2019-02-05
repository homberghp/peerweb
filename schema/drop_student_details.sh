#!/bin/bash
targetdb=peer2

if [[ $# > 0  ]]; then
    targetdb=$1
    shift
fi
echo "working with ${targetdb}"

cat ../functions/class_selector.sql | psql -X ${targetdb}
cat ../functions/tutor_selector.sql | psql -X ${targetdb}
cat  drop_menu_items_on_student.sql  | psql -X ${targetdb}

./check_student_data.sh ${targetdb}
