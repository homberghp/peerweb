#!/bin/bash
reposparentpath=$1
userid=$2
reposname=$3

if [ 3 -gt $# ] ; then
    echo usage $0 reposparent userid reposname
    exit 1
fi
umask 007
newrepospath=${reposparentpath}/${userid}/${reposname}
mkdir -p ${newrepospath}
svnadmin create ${newrepospath} 
result=$?
if [ 0  -ne $result ]; then 
    echo -e "cannot create ${reposname}, probably already exists"
    exit 1
fi

rootauthzdir=${reposparentpath}/${userid}/svnroot/conf
rootauthzfile=${rootauthzdir}/authz
if [ -f ${rootauthzfile} -a $reposname != 'svnroot' ] ; then
cd ${rootauthzdir}
cat - <<EOF >> ${rootauthzfile}

[$reposname:/]
@owner = rw
* = 

EOF
    svn ci -m"added repos $reposname" .
# add initial struct to new repos
    mkdir  ${newrepospath}/tmp
    cd ${newrepospath}/tmp
    mkdir branches tags trunk
    svn import -m' initial structure' file://${newrepospath}
    cat /etc/repos-templates/hooks/post-commit-simple >  ${newrepospath}/hooks/post-commit
    chmod ug+rx ${newrepospath}/hooks/post-commit
fi


