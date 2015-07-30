<?php

// rights given to students per project, 3 bits.
define("CAP_DEFAULT", 0);
define("CAP_SET_STUDENT_ROLE", 1 << 0);
define("CAP_READ_PEER_ASSESSMENT_DATA", 1 << 1);

// typically student/manager
define("CAP_SET_PROJECT_DATA", 1 << 3);

// for tutors and personell only
define("CAP_TUTOR", 1 << 0);
define("CAP_MKPROJECT", 1 << 1);
define("CAP_MKCLASSES", 1 << 2);
define("CAP_ALTER_STUDENT", 1 << 3);
define("CAP_ALTER_STUDENT_CLASS", 1 << 4);
define("CAP_LOOKUP_STUDENT", 1 << 5);
define("CAP_IMPERSONATE_STUDENT", 1 << 6);
define("CAP_TUTOR_OWNER", 1 << 7);
define("CAP_RECRUITER", 1 << 8);
define("CAP_STUDENT_ADMIN", 1 << 9);
define("CAP_TUTOR_ADMIN", 1 << 10);
define("CAP_SUBVERSION", 1 << 11);
define("CAP_SHARING", 1 << 12);
define("CAP_JAAG", 1 << 13);
define("CAP_SYSTEM", 1 << 14);
define("CAP_MENU_ADMIN", 1 << 15);
define("CAP_EDIT_RIGHTS", 1 << 16);
define("CAP_MODULES", 1 << 17);
define("CAP_GIT", 1 << 18);
define("CAP_SELECT_ALL", 1 << 19);
define("CAP_BIGFACE", 1 << 30);
define("CAP_ALL", 0x7FFFFFFF);
?>
