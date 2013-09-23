from collections import OrderedDict
import os
from xml.sax.saxutils import escape
import MySQLdb
from MySQLdb.cursors import SSDictCursor
import sys
from scur_mgr import DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_TABLE_USERS, DB_TABLE_WORDS, DB_TABLE_RELATIONSHIPS

bucket = 1
if len(sys.argv) > 1 and sys.argv[1].isdigit():
    bucket = int(sys.argv[1])

__dir__ = os.path.dirname(os.path.abspath(__file__))

db = MySQLdb.connect(host=DB_HOST, user=DB_USER, passwd=DB_PASS, db=DB_NAME, cursorclass=SSDictCursor)
cur = db.cursor()

users = {}
words = {}
relationships = {}

# USERS
cur.execute('SELECT id, name FROM %s u' % DB_TABLE_USERS)
row = cur.fetchone()
while row is not None:
    user_id = row.get('id')
    user_name = row.get('name')
    users[user_id] = user_name
    row = cur.fetchone()

# WORDS
cur.execute('SELECT id, word FROM %s w' % DB_TABLE_WORDS)
row = cur.fetchone()
while row is not None:
    word_id = row.get('id')
    word_word = row.get('word')
    words[word_id] = word_word
    row = cur.fetchone()

# EDGES
# cur.execute('SELECT DISTINCT word_id, user_id FROM %s r WHERE skip = "n"' % DB_TABLE_RELATIONSHIPS)
limit = 1000000
# limit = 1000
for i in range(0, 19):
# for i in range(0, 1):
    cur.execute('SELECT word_id, user_id FROM %s r WHERE skip = "n" LIMIT %s,%s' % (DB_TABLE_RELATIONSHIPS, i*limit, limit))
    row = cur.fetchone()
    while row is not None:
        word_id = row.get('word_id')
        user_id = row.get('user_id')
        rel = '%s,%s' % (user_id, word_id)
        relationships[rel] = relationships.get(rel, 0) + 1
        row = cur.fetchone()

gexf = """<gexf xmlns="http://www.gexf.net/1.2draft" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.gexf.net/1.1draft http://www.gexf.net/1.2draft/gexf.xsd" version="1.2">
    <graph mode="static" defaultedgetype="undirected">
        <nodes>
%(nodes)s
        </nodes>
        <edges>
%(edges)s
        </edges>
    </graph>
</gexf>"""

# node = '<node id="%(id)s" label="%(label)s"/>'
node = '<node id="%(id)s"/>'
# nodes = []
nodes = set()
edge = '<edge source="%(source)s" target="%(target)s" weight="%(weight)s"></edge>'
# edge = '<edge source="%(source)s" target="%(target)s"></edge>'
edges = []


print 'preparing edges for removal'

user_edges = OrderedDict()
word_edges = OrderedDict()
remove_edges = set()
for rel, weight in relationships.iteritems():
    user_id, word_id = rel.split(',', 1)
    if user_id not in user_edges:
        user_edges[user_id] = {}
    if weight not in user_edges[user_id]:
        user_edges[user_id][weight] = []
    user_edges[user_id][weight].append(word_id)

    if word_id not in word_edges:
        word_edges[word_id] = {}
    if weight not in word_edges[word_id]:
        word_edges[word_id][weight] = []
    word_edges[word_id][weight].append(user_id)

print 'removing edges'

for w in range(1, 10):
    for user_id in user_edges.iterkeys():
        if w in user_edges[user_id]:
            if len(user_edges[user_id]) == 1 and len(user_edges[user_id][w]) == 1:
                continue
            for word_id in user_edges[user_id][w]:
                if len(user_edges[user_id][w]) == 1:
                    break
                for ww in word_edges[word_id]:
                    if user_id in word_edges[word_id][ww]:
                        if len(word_edges[word_id][ww]) > 1:
                            user_edges[user_id][w].remove(word_id)
                            word_edges[word_id][ww].remove(user_id)
                            remove_edges.add('%s,%s' % (user_id, word_id))
                        break

print 'removed %d edges' % len(remove_edges)

# exit(0)

# for user_id, user in users.iteritems():
#     nodes.append(node % {
#         'id': user_id,
#         'label': 'u| %s' % escape(user).replace('"', '&quot;'),
#     })
# for word_id, word in words.iteritems():
#     nodes.append(node % {
#         'id': word_id,
#         'label': 'w| %s' % escape(word).replace('"', '&quot;'),
#     })
for rel, weight in relationships.iteritems():
    if rel in remove_edges:
        continue
    user_id, word_id = rel.split(',', 1)
    user_id = 'u_%s' % user_id
    word_id = 'w_%s' % word_id
    nodes.add(node % {
        'id': user_id,
    })
    nodes.add(node % {
        'id': word_id,
    })
    edges.append(edge % {
        'source': user_id,
        'target': word_id,
        'weight': weight,
    })

with open('%s/results/network.gexf' % __dir__, 'w') as ofile:
    data = gexf % {
        'nodes': "\n".join(nodes),
        'edges': "\n".join(edges),
    }
    ofile.write(data)
