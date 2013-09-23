import re
import MySQLdb
from MySQLdb.cursors import SSDictCursor
import sys
from scur_mgr import DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_TABLE_TOPICS, DB_TABLE_POSTS

bucket = 1
if len(sys.argv) > 1 and sys.argv[1].isdigit():
    bucket = int(sys.argv[1])

db = MySQLdb.connect(host=DB_HOST, user=DB_USER, passwd=DB_PASS, db=DB_NAME, cursorclass=SSDictCursor)
db2 = MySQLdb.connect(host=DB_HOST, user=DB_USER, passwd=DB_PASS, db=DB_NAME, cursorclass=SSDictCursor)
cur = db.cursor()
cur2 = db2.cursor()

query = 'SELECT id FROM %(topics)s t' % {
    'topics': DB_TABLE_TOPICS,
}
cur.execute(query)

max_date_diff = 0
min_date_diff = 999999999
avg_date_diff = 0
posts = 0

row = cur.fetchone()
while row is not None:
    topic_id = int(row.get('id'))
    query2 = 'SELECT id, date FROM %(posts)s p WHERE topic_id = %(topic)s ORDER by date asc' % {
        'posts': DB_TABLE_POSTS,
        'topic': topic_id,
    }
    cur2.execute(query2)
    row2 = cur2.fetchone()
    while row2 is not None:
        date1 = row2.get('date')
        id1 = row2.get('id')
        row2 = cur2.fetchone()
        try:
            date2 = row2.get('date')
            id2 = row2.get('id')
            diff = (date2 - date1).total_seconds()
            if diff < min_date_diff:
                min_date_diff = diff
            elif diff > max_date_diff:
                max_date_diff = diff
            # skip posts under a minute and over a week
            if 60 < diff < 604800:
                avg_date_diff += diff
                posts += 1
        except AttributeError:
            break
    row = cur.fetchone()

if posts > 0:
    avg_date_diff /= posts
else:
    avg_date_diff = 0

print int(min_date_diff)
print int(round(avg_date_diff))
print int(max_date_diff)
