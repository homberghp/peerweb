#!/bin/bash
cat ../functions/class_selector.sql | psql -X peer2
cat ../functions/tutor_selector.sql | psql -X peer2
cat  drop_menu_items_on_student.sql  | psql -X peer2


