MODULES = peer_password
PGXS := $(shell pg_config --pgxs)
include $(PGXS)
#PG_VERSION=9.6
PG_VERSION=10


peer_password.so: peer_password.o generator.o
	gcc -shared -o peer_password.so peer_password.o generator.o

peer_password.o: peer_password.c generator.h
	gcc -I /usr/include/postgresql/$(PG_VERSION)/server -fpic -c peer_password.c 

generator.o : generator.c generator.h
	gcc -o generator.o -fpic -c generator.c

mkpassword: mkpassword.c generator.c generator.h
	gcc -o mkpassword mkpassword.c generator.c

realclean:
	rm -fr *~ *.o *.so

