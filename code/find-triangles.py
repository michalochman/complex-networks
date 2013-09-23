import MySQLdb
from MySQLdb.cursors import SSDictCursor
import sys
from scur_mgr import DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_TABLE_RELATIONSHIPS, DB_TABLE_WORDS, DB_TABLE_OPINIONS

bucket = 1
if len(sys.argv) > 1 and sys.argv[1].isdigit():
    bucket = int(sys.argv[1])

db = MySQLdb.connect(host=DB_HOST, user=DB_USER, passwd=DB_PASS, db=DB_NAME, cursorclass=SSDictCursor)
cur = db.cursor()

query = '' \
        'SELECT user_id, word_id, post_id, meta, word ' \
        'FROM %(relationships)s r ' \
        'JOIN %(words)s w ON r.word_id=w.id ' \
        'WHERE ' \
        '   r.bucket=%(bucket)d AND ' \
        '   r.skip="%(skip)s" AND ' \
        '   w.meta IN ("-", "+", ".")'
query = query % {
    'relationships': DB_TABLE_RELATIONSHIPS,
    'words': DB_TABLE_WORDS,
    'bucket': bucket,
    'skip': 'n',
}
cur.execute(query)

insert_query = 'INSERT INTO %(opinions)s VALUES(%%s, %%s, %%s, %%s, %%s, %%s)' % {
    'opinions': DB_TABLE_OPINIONS,
}

usages = {}

row = cur.fetchone()
while row is not None:
    # print row
    post_id = int(row.get('post_id'))
    user_id = int(row.get('user_id'))
    word_id = int(row.get('word_id'))
    meta = row.get('meta')
    try:
        try:
            keyerror_trigger = usages[post_id]
        except KeyError:
            usages[post_id] = {}
        try:
            keyerror_trigger = usages[post_id][user_id]
        except KeyError:
            usages[post_id][user_id] = {}
        keyerror_trigger = usages[post_id][user_id][word_id]
    except KeyError:
        word = {
            '.': 0,
            '+': 0,
            '-': 0,
        }
        usages[post_id][user_id][word_id] = word
    finally:
        usages[post_id][user_id][word_id][meta] += 1

    row = cur.fetchone()


users = {}
for post_id, post in usages.iteritems():
    for user_id, user in post.iteritems():
        try:
            keyerror_trigger = users[user_id]
        except KeyError:
            users[user_id] = {}
        positive = 0
        negative = 0
        for word_id, word in user.iteritems():
            try:
                keyerror_trigger = users[user_id][word_id]
            except KeyError:
                users[user_id][word_id] = {
                    '.': 0,
                    '+': 0,
                    '-': 0,
                }
            positive += word['+']
            negative += word['-']
        for word_id, word in user.iteritems():
            if word['.'] > 0:
                users[user_id][word_id]['.'] += word['.']
                users[user_id][word_id]['+'] += positive
                users[user_id][word_id]['-'] += negative

for user_id, words in users.iteritems():
    for word_id, word in words.iteritems():
        if word['.'] > 0:
            cur.execute(insert_query, (
                user_id, word_id, word['.'], word['+'], word['-'], bucket
            ))

db.commit()
