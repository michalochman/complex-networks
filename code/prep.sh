#!/bin/bash
for BUCKET in `seq 1 63`
do
    php prepare-calc-collaborative-similarity.php $BUCKET > results/cs/data/bucket-`printf "%03d" $BUCKET`.txt
done
