#!/bin/bash
for BUCKET in `seq 5 141`
do
    python calc-node-collaborative-similarity-async.py $BUCKET
done
