#ifndef _GENERATOR_H
#define _GENERATOR_H
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#define MAXPWLEN 14
extern char vowels[];
extern char vowelsu[];
extern char vowelsb[];
extern char consonants[];
extern char cons2[];
extern char cons3[];
extern char digits[];
extern char punct[];
extern char anyletter[];
extern char base_template[];
extern void setSeed(int seed);
extern char * mkPasswd(int pwlen, char * template, char newpasswd[]);
extern int getURandomSeed();

#endif
