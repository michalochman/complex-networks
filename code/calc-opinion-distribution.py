from collections import OrderedDict
import os
from multiprocessing import Process, Queue, JoinableQueue, cpu_count
import math
import MySQLdb
from MySQLdb.cursors import SSDictCursor
from scur_mgr import DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_TABLE_RELATIONSHIPS, DB_TABLE_WOPINIONS

__DIR__ = os.path.dirname(os.path.abspath(__file__))
L = 0.01
BUCKETS = int(math.ceil(2*math.pi**2/3 / L))


if __name__ == '__main__':
    db = MySQLdb.connect(host=DB_HOST, user=DB_USER, passwd=DB_PASS, db=DB_NAME, cursorclass=SSDictCursor)
    cur = db.cursor()

    data_dir = '%s/results/' % __DIR__

    buckets = {}

    def bucket(value):
        if value <= -L:
            return BUCKETS/2-3+int((value+L)/L)
        elif value >= L:
            return BUCKETS/2-2+int((value+L)/L)
        else:
            if value < 0:
                return BUCKETS/2-1-1  # 0 indexed list
            else:
                return BUCKETS/2-1

    def unbucket(bucket):
        return (bucket - BUCKETS/2)*L

    opinions = {}
    cur.execute('SELECT opinion FROM %s' % (DB_TABLE_WOPINIONS,))
    row = cur.fetchone()
    while row is not None:
        opinion = float(row.get('opinion'))
        _bucket = bucket(opinion)
        try:
            buckets[_bucket] += 1
        except:
            buckets[_bucket] = 1
        row = cur.fetchone()

    with open('%s/opidist.txt' % data_dir, 'w') as ofile:
        gnuplot = """# set term postscript eps enhanced color solid 22
# set xrange [0:658]
# set xtics 50
# set out 'opidist.eps'
# plot 'opidist.txt' w lp pt 2 t 'estimated opinion distribution'
# set logscale y
# set out 'opidist_log.eps'
# replot
"""
        ofile.write(gnuplot)
        ofile.write("# degree count\n")
        for b, d in sorted(buckets.iteritems()):
            ofile.write("%.2f %d\n" % (unbucket(b), d))
