import MySQLdb
from MySQLdb.cursors import SSDictCursor
import sys
from scur_mgr import DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_TABLE_OPINIONS

bucket = 1
if len(sys.argv) > 1 and sys.argv[1].isdigit():
    bucket = int(sys.argv[1])

db = MySQLdb.connect(host=DB_HOST, user=DB_USER, passwd=DB_PASS, db=DB_NAME, cursorclass=SSDictCursor)
cur = db.cursor()

query = '' \
        'SELECT * ' \
        'FROM %(opinions)s o ' \
        'WHERE ' \
        '   bucket=%(bucket)d '
query = query % {
    'opinions': DB_TABLE_OPINIONS,
    'bucket': bucket,
}
cur.execute(query)

gexf = """<gexf xmlns="http://www.gexf.net/1.2draft" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.gexf.net/1.1draft http://www.gexf.net/1.2draft/gexf.xsd" version="1.2">
    <graph mode="static" defaultedgetype="undirected">
        <attributes class="edge">
            <attribute id="0" title="opinion" type="double"/>
        </attributes>
        <nodes>
%(nodes)s
        </nodes>
        <edges>
%(edges)s
        </edges>
    </graph>
</gexf>"""

node = '<node id="%(id)s"/>'
nodes = []
edge = '<edge source="%(source)s" target="%(target)s">%(attribute)s</edge>'
edges = []
attribute = """<attvalues>
    <attvalue for="0" value="%(opinion)s"/>
</attvalues>"""

users = set()
words = set()
opinions = {}
row = cur.fetchone()
while row is not None:
    user_id = row.get('user_id')
    word_id = row.get('word_id')
    opinion = row.get('opinion')
    users.add(user_id)
    words.add(word_id)
    edges.append(edge % {
        'source': user_id,
        'target': word_id,
        'attribute': attribute % {
            'opinion': opinion,
        },
    })
    if not opinions.get(word_id):
        opinions[word_id] = {
            '+': set(),
            '-': set(),
        }
    if opinion < 0:
        opinions[word_id]['-'].add(user_id)
    else:
        opinions[word_id]['+'].add(user_id)
    row = cur.fetchone()

for user in users:
    nodes.append(node % {
        'id': user
    })
for word in words:
    nodes.append(node % {
        'id': word,
    })
for word_id, opinion in opinions.iteritems():
    for positive1 in opinion['+']:
        for positive2 in opinion['+']:
            if positive1 == positive2:
                continue
            edges.append(edge % {
                'source': positive1,
                'target': positive2,
                'attribute': '',
            })
    for negative1 in opinion['-']:
        for negative2 in opinion['-']:
            if negative1 == negative2:
                continue
            edges.append(edge % {
                'source': negative1,
                'target': negative2,
                'attribute': '',
            })

print gexf % {
    'nodes': "\n".join(nodes),
    'edges': "\n".join(edges),
}
