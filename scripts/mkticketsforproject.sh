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

prjm_id=$1; shift
product=phototicketforproject
latexcount=2
merger=merger.pl
latex=xelatex
case $product in
    classcard)
	merger=merger_bus.pl
	latexcount=1
	;;
esac
export product workdir latexcount merger FILETS prjm_id
# echo $product $latexcount $workdir $merger
# exit 0
workdir=../tex/out
outdir=${workdir}
mkdir -p ${outdir}
${scriptdir}/${product}.pl ${prjm_id} > ${outdir}/${product}-bus.tex
for i in $(seq 1 ${latexcount}); do
    ${latex} -interaction=batchmode -output-directory=${outdir} ${outdir}/${product}-bus.tex
done
${scriptdir}/${merger} -f ${outdir}/${product}-bus.pdf > ${outdir}/${product}.tex 2>/dev/null
pdflatex -interaction=batchmode -output-directory=${outdir} ${outdir}/${product}.tex
# rm -fr ${destdir}/${product}*.pdf
# mv ${outdir}/${product}.pdf ${destdir}/${product}-${FILETS}.pdf

