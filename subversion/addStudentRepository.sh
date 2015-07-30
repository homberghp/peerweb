#!/bin/bash
lastname=$1
firstname=$2
snumber=$3

repo=file:///home/svn/2012/PRO1/

reponame="${lastname}_${firstname}_${snumber}"
echo "Creating repository ${reponame}"

svn mkdir ${repo}/${reponame} -m "New Repo ${reponame}"
svn mkdir ${repo}/${reponame}/{trunk,tags,branches} -m ""

echo "g${snumber} = ${snumber}"

echo "[/${reponame}]"
echo "@g${snumber} = rw"
echo "@tutor = r"
echo "* = "

