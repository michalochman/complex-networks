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

    users = {}
    users_set = set()
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
            row = cur.fetchone()


    for user_id, uwords in users.iteritems():
        degree = len(uwords)
        if degree >= 1000:
            users_set.add(user_id)

    print len(users_set)/float(len(users))

    # single_topics = 0
    # for user_id in users_set:
    #     cur.execute('SELECT COUNT(DISTINCT topic_id) topics FROM headfi_posts WHERE user_id = %s' % user_id)
    #     topics = cur.fetchall()[0]['topics']
    #     if topics == 1:
    #         single_topics += 1
    #
    # print single_topics/float(len(users_set))