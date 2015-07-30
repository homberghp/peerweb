#!/bin/bash
reposparentpath=$1
userid=$2
reposname=svnroot
scriptdir=$(dirname $0)
${scriptdir}/mksimplerepos.sh ${reposparentpath} ${userid} ${reposname} || exit 1

reposparent=${reposparentpath}/${userid}
newrepospath=${reposparent}/${reposname}
if [ $(hostname) = 'fontysvenlo.org' ] ; then
    # ubuntu host at strato
    host_url=https://www.fontysvenlo.org
    auth_include_file=/etc/apache2/localconf.d/subversion.auth
else
    # debian host at home
    host_url=http://localhost
    auth_include_file=/etc/apache2/localconf.d/subversion.auth
fi

# make initial struct and conf files
mkdir -p ${newrepospath}/tmp/conf

cd ${newrepospath}/tmp


cat - <<EOF >>conf/authz
### This authz file is generated under control of a 'peerweb' script.
### To access the repository the visitor must have a valid peerweb account.
### 
### This authz file is auto generated once but henceforth under your (the user's) control.
### The authz file in the svnroot repository is used for all your other repositories
### under the same root too.
### By default the repositories are personal and cannot read by anyone, not even the teachers.
###
### To enable access to your repositor(y|ies) to other peerweb users, create 
### a group for them and give the appropriate access rights.
### See the redbean svnbook at http://svnbook.red-bean.com
###
### The svnroot repository is your first repo and will be used as access root
### for all your other repositories.

# Using groups or roles makes authorization management easy. 
# Groups can have more members. Put them on the same line, comma separated.
# 
[groups]
owner = ${userid}

### this is your root repository. Use [name:/] for your other repositories too.  
[${reposname}:/]
@owner = rw
* = 

# make sure owner keeps control of access rights.
[${reposname}:/conf]
@owner = rw
* = 


### You can make stricter rules too.
### The rule below allow the client to read only the 'tags' subtree in the repository.
# [${reposname}:/tags]
# @owner = rw
# @client = r
### To make the above have effect, uncomment the lines by removing the '#' characters
### and add a group named 'client' with the appropriate members. Then of course you should use
### tagging as well.

### More repositories will be added below. Manage them in the same way as your root repository.

EOF

mkdir branches tags trunk 
svn import -m'initial content' file://${newrepospath}
cd ${newrepospath}
mv conf _conf
svn co file://${newrepospath}/conf
cat /etc/repos-templates/hooks/post-commit > hooks/post-commit
chmod +x hooks/post-commit
# rm -fr tmp
cat - <<EOF > /etc/apache2/svnrepos/svnp/${userid}.conf
Use SVNPersonalRepo ${userid}
EOF

# notify apache deamon on new repos
echo ${reposname} >> /etc/apache2/svnrepos/pings
#echo ${reposname} > /etc/apache2/svnrepos/makeloopfifo
/usr/sbin/kickhttpd

echo  "Repository creation complete"
exit 0

