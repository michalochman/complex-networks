import os
from multiprocessing import Process, Queue, JoinableQueue, cpu_count
import math

__DIR__ = os.path.dirname(os.path.abspath(__file__))


if __name__ == '__main__':
    data_dir = '%s/nodes/data' % __DIR__
    dist_dir = '%s/nodes/dist' % __DIR__
    partitions = 100
    buckets = 142
    distribution = [[0 for i in xrange(partitions)] for i in xrange(buckets)]
    for user_file in os.listdir(data_dir):
        with open('%s/%s' % (data_dir, user_file), 'r') as user_data:
            for line in user_data.readlines():
                line = line.strip().split(' ')
                bucket = int(line[0])
                cu = float(line[1])
                partition = int(cu * (partitions - 1))
                # total
                distribution[0][partition] += 1
                # per bucket
                distribution[bucket][partition] += 1
    for bucket in xrange(len(distribution)):
        data = distribution[bucket]
        with open('%s/%03d.txt' % (dist_dir, bucket), 'w') as ofile:
            for partition in xrange(len(data)):
                dist = data[partition]
                # file format:
                # partition count
                ofile.write("%d %d\n" % (partition+1, dist))