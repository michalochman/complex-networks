#!/bin/bash
for BUCKET in `seq 1 141`
do
    echo $BUCKET
#    python find-triangles2-gexf.py $BUCKET > triangles/bucket-`printf "%03d" $BUCKET`.gexf
    python find-triangles2.py $BUCKET
done
