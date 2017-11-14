#include "generator.h"

char vowels[]="aeiou";                                        /* format code a */
char vowelsu[]="AEIOU";                                       /* format code A */
char vowelsb[]="aAeEiIoOuU";                                  /* format code @ */
char consonants[]="bBcCdDfFgGhHjJkKlLmMnNpPqQrRsStTvVwWyYzZ"; /* formatcode % */
char cons2[]="bcdfghjklmnpqrstvwyz";                          /* formactcode b */
char cons3[]="BCDFGHJKLMNPQRSTVWYZ";                          /* formactcode B */
char digits[]="0123456789";                                   /* formatcode 1 */
char punct[]="~!@#%^&()_+=:;\"<.>,?/{}[]|";
static int randseed;
char anyletter[]="aAbBcCdDeEfFgGhHiIjJkKlLmMnNoOpPqQrRsStTuUvVwWxXyYzZ"; /* formatcode ~*/
char anychar[]="aAbBcCdDeEfFgGhHiIjJkKlLmMnNoOpPqQrRsStTuUvVwWxXyYzZ0123456789.,;-_+|!?<>\\/"; /* default*/
/*                 "01234567890" */
char base_template[MAXPWLEN+1]="Bab.%@b11Ba~"; /* zero at end */
/*
 * password template : cvc1cvc1cvc
 */
char newpasswd[MAXPWLEN+1]="cvc1cvc1cvc"; /* zero at end */
static int seeded =0;
#define ANYOF(a) a[random() % strlen(a)]

char * mkPasswd(int pwlen, char * template, char newpasswd[]){
  int nvowels=strlen(vowels);
  int nvowelsu=strlen(vowelsu);
  int nvowelsb=strlen(vowelsb);
  int ncons = strlen(consonants);
  int ncons2 = strlen(cons2);
  int ncons3 = strlen(cons3);
  int ndigits = strlen(digits);
  int npunct = strlen(punct);
  int nletter =strlen(anyletter);
  int i;
  if (!seeded) {
    seeded =1;
    srandom( getURandomSeed() ); /* seed random */
  }
  for ( i=0; i< pwlen; i++){
    switch(template[i]){
    case 'a':  newpasswd[i] = ANYOF( vowels );     break; 
    case 'A':  newpasswd[i] = ANYOF( vowelsu );    break;
    case '@':  newpasswd[i] = ANYOF( vowelsb );    break;
    case 'b':  newpasswd[i] = ANYOF( cons2 );      break; 
    case 'B':  newpasswd[i] = ANYOF( cons3 );      break; 
    case '%':  newpasswd[i] = ANYOF( consonants ); break;
    case '1':  newpasswd[i] = ANYOF( digits );     break;
    case '~':  newpasswd[i] = ANYOF( anyletter );  break;
    case '.':  newpasswd[i] = ANYOF( punct );      break;
    default:   newpasswd[i] = ANYOF( anychar );    break;
    }
  }
  newpasswd[i]='\0';
  return newpasswd;
}

void setSeed(int seed) {
  randseed = seed;
}
/* only under linux */

int getURandomSeed() {
  int seed;
  FILE * randev = fopen("/dev/urandom","r");
  if (randev != NULL) {
    fread(&seed,sizeof(int),1,randev);
    fclose(randev);
  }
  return seed;
}
