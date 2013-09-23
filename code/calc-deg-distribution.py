from collections import OrderedDict
import os
from multiprocessing import Process, Queue, JoinableQueue, cpu_count
import math
import MySQLdb
from MySQLdb.cursors import SSDictCursor
from scur_mgr import DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_TABLE_RELATIONSHIPS

__DIR__ = os.path.dirname(os.path.abspath(__file__))


if __name__ == '__main__':
    db = MySQLdb.connect(host=DB_HOST, user=DB_USER, passwd=DB_PASS, db=DB_NAME, cursorclass=SSDictCursor)
    cur = db.cursor()

    data_dir = '%s/results/' % __DIR__
    u_distribution = {}
    w_distribution = {}
    distribution = {}

    users = {}
    words = {}
    limit = 1000000
    for i in range(0, 19):
        cur.execute('SELECT word_id, user_id FROM %s r WHERE skip = "n" LIMIT %s,%s' % (DB_TABLE_RELATIONSHIPS, i*limit, limit))
        row = cur.fetchone()
        while row is not None:
            user_id = row.get('user_id')
            word_id = row.get('word_id')
            if user_id not in users:
                users[user_id] = set()
            else:
                users[user_id].add(word_id)
            if word_id not in words:
                words[word_id] = set()
            else:
                words[word_id].add(user_id)
            row = cur.fetchone()


    for uwords in users.itervalues():
        degree = len(uwords)
        if degree not in u_distribution:
            u_distribution[degree] = 1
        else:
            u_distribution[degree] += 1
        if degree not in distribution:
            distribution[degree] = 1
        else:
            distribution[degree] += 1
    for wusers in words.itervalues():
        degree = len(wusers)
        if degree not in w_distribution:
            w_distribution[degree] = 1
        else:
            w_distribution[degree] += 1
        if degree not in distribution:
            distribution[degree] = 1
        else:
            distribution[degree] += 1

    with open('%s/degdist.txt' % data_dir, 'w') as ofile:
        gnuplot = """# set term postscript eps enhanced color solid
# set logscale xy
# set xrange [0.8:5e3]
# set yrange [1.5e-5:0.05]
# set xlabel 'k'
# set ylabel 'P(k)'
# set key spacing 2
# f(x) = a*x**b
# b=-1
# fit [50:*] f(x) 'degdist_users.txt' u 1:2 via a,b
# fcut(x) = x < 17 ? 1/0 : f(x)
# g(x) = c*x**d
# fit [1:17] g(x) 'degdist_users.txt' u 1:2 via c,d
# gcut(x) = x > 90 ? 1/0 : g(x)
# set out 'degdist_users.eps'
# plot 'degdist_users.txt' pt 5 ps 0.33 t 'k distribution', fcut(x) lt 3 t '3.72 x^{-1.63}', gcut(x) lt 4 t '0.155 x^0'
"""
        ofile.write(gnuplot)
        ofile.write("# degree count\n")
        for degree, count in distribution.iteritems():
            ofile.write("%d %f\n" % (degree, float(count)/len(users)))

    with open('%s/degdist_users.txt' % data_dir, 'w') as ofile:
        gnuplot = """# set term postscript eps enhanced color solid
# set logscale xy
# set xrange [0.8:5e3]
# set yrange [1.5e-5:0.05]
# set xlabel 'k'
# set ylabel 'P(k)'
# set key spacing 2
# f(x) = a*x**b
# b=-1
# fit [50:*] f(x) 'degdist_users.txt' u 1:2 via a,b
# fcut(x) = x < 17 ? 1/0 : f(x)
# g(x) = c*x**d
# fit [1:17] g(x) 'degdist_users.txt' u 1:2 via c,d
# gcut(x) = x > 90 ? 1/0 : g(x)
# set out 'degdist_users.eps'
# plot 'degdist_users.txt' pt 5 ps 0.33 t 'k distribution', fcut(x) lt 3 t '3.72 x^{-1.63}', gcut(x) lt 4 t '0.155 x^0'
"""
        ofile.write(gnuplot)
        ofile.write("# degree count\n")
        for degree, count in u_distribution.iteritems():
            ofile.write("%d %f\n" % (degree, float(count)/len(users)))

    with open('%s/degdist_words.txt' % data_dir, 'w') as ofile:
        gnuplot = """# set term postscript eps enhanced color solid
# set logscale xy
# set xrange [0.8:5e4]
# set yrange [9e-5:0.12]
# set xlabel 'd'
# set ylabel 'P(d)'
# set key spacing 2
# f(x) = a*x**b
# b=-1
# fit [1:*] f(x) 'degdist_words.txt' u 1:2 via a,b
# set out 'degdist_words.eps'
# plot 'degdist_words.txt' pt 5 ps 0.33 t 'd distribution', f(x) lt 3 t '0.09 x^{-1.04}'
"""
        ofile.write(gnuplot)
        ofile.write("# degree count\n")
        for degree, count in w_distribution.iteritems():
            ofile.write("%d %f\n" % (degree, float(count)/len(words)))
