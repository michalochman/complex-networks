import re
import MySQLdb
from MySQLdb.cursors import SSDictCursor
import sys
from scur_mgr import DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_TABLE_RELATIONSHIPS, DB_TABLE_WORDS, DB_TABLE_OPINIONS, DB_TABLE_WOPINIONS, DB_TABLE_POSTS

bucket = 1
if len(sys.argv) > 1 and sys.argv[1].isdigit():
    bucket = int(sys.argv[1])

db = MySQLdb.connect(host=DB_HOST, user=DB_USER, passwd=DB_PASS, db=DB_NAME, cursorclass=SSDictCursor)
cur = db.cursor()

query = '' \
        'SELECT r.user_id, r.word_id, r.post_id, w.meta, w.word, p.body ' \
        'FROM %(relationships)s r ' \
        'JOIN %(words)s w ON r.word_id=w.id ' \
        'JOIN %(posts)s p ON r.post_id=p.id ' \
        'WHERE ' \
        '   r.bucket=%(bucket)d AND ' \
        '   r.skip="%(skip)s" AND ' \
        '   w.meta IN ("-", "+", ".") '
query = query % {
    'relationships': DB_TABLE_RELATIONSHIPS,
    'words': DB_TABLE_WORDS,
    'posts': DB_TABLE_POSTS,
    'bucket': bucket,
    'skip': 'n',
}
cur.execute(query)

insert_query = 'INSERT INTO %(opinions)s VALUES(%%s, %%s, %%s, %%s)' % {
    'opinions': DB_TABLE_WOPINIONS,
}

posts = {}

row = cur.fetchone()
while row is not None:
    post_id = int(row.get('post_id'))
    user_id = int(row.get('user_id'))
    word_id = int(row.get('word_id'))
    body = row.get('body').lower()
    meta = row.get('meta')
    word = row.get('word')
    if posts.get(post_id) is None:
        posts[post_id] = {
            'user_id': user_id,
            'body': body,
            'words': [],
        }
    posts[post_id]['words'].append({
        'word_id': word_id,
        'word': word,
        'meta': meta,
    })
    row = cur.fetchone()

# iterate every post
for post_id, post in posts.iteritems():
    # normalize post body by eliminating extra spaces
    pattern = '[' + re.escape('!"#$%&()*+,-./:;<=>?@[\\]^_`{|}~') + ']+'
    body = re.sub(pattern, ' ', post.get('body'), flags=re.UNICODE)
    body = re.sub('\s+', ' ', body, flags=re.UNICODE)
    keywords = {}
    post['counts'] = {}
    dists = []
    # iterate every word
    for word1 in post.get('words'):
        # if keyword
        meta = word1.get('meta')
        if meta == '.':
            opinions = {}
            word1word = word1.get('word')
            # get keyword position, starting from beginning or previously found word
            # index = body.find(word1word, keywords.get(word1word, 0))
            re_keyword = re.compile('\\b[_\W]?'+re.escape(word1word)+'[_\W]?\\b')
            try:
                index = re_keyword.search(body, keywords.get(word1word, 0)).start()
                post['counts'][word1word] += 1
            except AttributeError:
                continue
            except KeyError:
                post['counts'][word1word] = 1
            # set keyword position to start from it later if needed
            keywords[word1word] = index + len(word1word)
            word1_mid = index + len(word1word)/2
            opinion = 0
            # iterate every word
            for word2 in post.get('words'):
                # if not keyword (ie. opinion)
                meta = word2.get('meta')
                if meta != '.':
                    word2word = word2.get('word')
                    # get opinion position, starting from beginning or previously found word
                    re_opinion = re.compile('\\b[_\W]?'+re.escape(word2word)+'[_\W]?\\b')
                    try:
                        oindex = re_opinion.search(body, opinions.get(word2word, 0)).start()
                    except AttributeError:
                        continue
                    # set opinion position to start from it later if needed
                    opinions[word2word] = oindex + len(word2word)
                    word2_mid = oindex + len(word2word)/2
                    # calculate distance between words (by counting spaces between words)
                    start = min(word1_mid, word2_mid)
                    end = max(word1_mid, word2_mid)
                    dist = body[start:end].count(' ')
                    # check if opinion is positive or negative
                    sign = 1 if meta == '+' else -1
                    # calculate weighted opinion
                    if dist < 1:
                        continue
                    opinion += float(sign)/dist**2
            word1['opinion'] = opinion

for post_id, post in posts.iteritems():
    user_id = post.get('user_id')
    for word in post.get('words'):
        # word_word = word.get('word')
        word_id = word.get('word_id')
        meta = word.get('meta')
        opinion = word.get('opinion')
        # opinion = word.get('opinion') / post['counts'][word_word]
        if meta == '.' and opinion:
            cur.execute(insert_query, (
                user_id, word_id, opinion, bucket
            ))

db.commit()
