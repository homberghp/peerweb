#!/bin/bash
scriptdir=$(dirname $0)
me=$(basename $0)
GETOPT=/usr/bin/getopt
# sensible defaults: pick everything up from wd.
workdir=$(pwd)
confDirName=$(pwd)
destdir=$(pwd)
propfile=jMerge.properties
FILETS=$(date)
ARGS=$(${GETOPT} -o hc:d:w:t: --long help,confDirName:,destdir:,workdir:,timestamp: -- "$@")
eval set -- "$ARGS"
while [ $# -gt 0 ]
do
    case "$1" in
	-h|--help)
	    cat <<EOF
usage $me [-h|--help] [-d|--destdir <destdir>] [-c|--confDirname <confdir>] \
[-c|--workdir <workdir>]
EOF
	    exit 0;;
	-c|--confDirName) confDirName=$2; shift;;
	-t|--timestamp) FILETS=$2; shift;;
	-d|--destdir) destdir=$2; shift;;
	-w|--workdir) workdir=$2; shift;;
	--) shift; break;;
	-*) echo "$0: error - unrecognized option $1" 1>&2; exit 1;;
	*)  break;;
    esac
    shift
done

product=$(basename $me .sh)
# substr
product=${product:2}
latexcount=2
merger=merger.pl
latex=xelatex
case $product in
    classcard)
	merger=merger_bus.pl
	latexcount=1
	;;
esac
export product workdir latexcount merger FILETS
# echo $product $latexcount $workdir $merger
# exit 0
outdir=${workdir}/out
mkdir -p ${outdir}
${scriptdir}/${product}.pl > ${outdir}/${product}-bus.tex
for i in $(seq 1 ${latexcount}); do
    ${latex} -interaction=batchmode -output-directory=${outdir} ${outdir}/${product}-bus.tex
done
${scriptdir}/${merger} -f ${outdir}/${product}-bus.pdf > ${outdir}/${product}.tex 2>/dev/null
pdflatex -interaction=batchmode -output-directory=${outdir} ${outdir}/${product}.tex
rm -fr ${destdir}/${product}*.pdf
mv ${outdir}/${product}.pdf ${destdir}/${product}-${FILETS}.pdf

