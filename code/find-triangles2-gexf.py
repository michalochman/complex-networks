import MySQLdb
from MySQLdb.cursors import SSDictCursor
import sys
from scur_mgr import DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_TABLE_WOPINIONS, DB_TABLE_USERS, DB_TABLE_WORDS

bucket = 1
if len(sys.argv) > 1 and sys.argv[1].isdigit():
    bucket = int(sys.argv[1])

db = MySQLdb.connect(host=DB_HOST, user=DB_USER, passwd=DB_PASS, db=DB_NAME, cursorclass=SSDictCursor)
cur = db.cursor()

query = '' \
        'SELECT * ' \
        'FROM %(opinions)s o ' \
        'JOIN %(users)s u ON u.id = o.user_id ' \
        'JOIN %(words)s w ON w.id = o.word_id ' \
        'WHERE ' \
        '   bucket=%(bucket)d AND ' \
        '   word_id IN (%(word_ids)s)'

word_ids = [
    # 116071387, 116071570, 116123230, 116071461, 116071423, 116071435, 116149676, 116095901, 116071680, 116076203,
    # 116097919, 116122018, 116071667, 116071626, 116080505, 116072067, 116325104, 116131412, 116346607, 116079229,
    # 116072323, 116074598, 116098259, 116174381, 116073381, 116074541, 116080109, 116071750, 116152868, 116187476,
    # 116090443, 116075893, 116216742, 116147885, 116071718, 116073873, 116077176, 116075343, 116405415, 116081178,
    # 116329514, 116073044, 116073117, 116095613, 116072299, 116137591, 116074229, 116078496, 116074887, 116074599,
    # 116075152, 116077173, 116123047, 116071780, 116225957, 116074976, 116073788, 116305828, 116147108, 116072743,
    # 116409009, 116208545, 116076163, 116071643, 116230978, 116076710, 116095393, 116078733, 116182136, 116081191,
    # 116074393, 116072853, 116159642, 116078921, 116077874, 116072224, 116073987, 116072971, 116080451, 116123702,
    # 116433193, 116072450, 116101611, 116077455, 116075176, 116072944, 116361077, 116076528, 116084339, 116084492,
    # 116430460, 116072418, 116339003, 116139853, 116076430, 116072380, 116073863, 116071755, 116071561, 116256874,
    116071387, 116071570, 116071461, 116071423, 116071435, 116076203, 116071626, 116072067, 116079229, 116073381,
    116071750, 116075893, 116074599, 116123047, 116074976, 116073788, 116072743, 116409009, 116076163, 116076710,
    116095393, 116081191, 116078921, 116072944, 116076528, 116076430, 116072380, 116071755, 116071561, 116212343,
]

query = query % {
    'opinions': DB_TABLE_WOPINIONS,
    'users': DB_TABLE_USERS,
    'words': DB_TABLE_WORDS,
    'bucket': bucket,
    'word_ids': str(word_ids).strip('[]')
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

node = '<node id="%(id)s" label="%(label)s"/>'
nodes = []
edge = '<edge source="%(source)s" target="%(target)s" label="%(label)s">%(attribute)s</edge>'
edges = []
attribute = """<attvalues>
    <attvalue for="0" value="%(opinion)s"/>
</attvalues>"""

users = set()
words = {}
opinions = {}
row = cur.fetchone()
while row is not None:
    user_id = row.get('user_id')
    user = row.get('name')
    word_id = row.get('word_id')
    word = row.get('word')
    opinion = row.get('opinion')
    users.add((user_id, user))
    words[word_id] = word
    # edges.append(edge % {
    #     'source': user_id,
    #     'target': word_id,
    #     'attribute': attribute % {
    #         'opinion': opinion,
    #     },
    # })
    if not opinions.get(word_id):
        opinions[word_id] = {
            '+': set(),
            '0': set(),
            '-': set(),
        }
    # (1/5; 4] range
    if opinion > 0.2:
        opinions[word_id]['+'].add(user_id)
    # [-4, 1/5) range
    elif opinion < -0.2:
        opinions[word_id]['-'].add(user_id)
    # [-1/5, 1/5] range
    else:
        opinions[word_id]['0'].add(user_id)
    row = cur.fetchone()

for user_id, user in users:
    nodes.append(node % {
        'id': user_id,
        'label': user,
    })
# for word in words:
#     nodes.append(node % {
#         'id': word,
#     })
for word_id, opinion in opinions.iteritems():
    for positive1 in opinion['+']:
        for positive2 in opinion['+']:
            if positive1 == positive2:
                continue
            edges.append(edge % {
                'source': positive1,
                'target': positive2,
                'label': words[word_id],
                'attribute': '',
            })
    for neutral1 in opinion['0']:
        for neutral2 in opinion['0']:
            if neutral1 == neutral2:
                continue
            edges.append(edge % {
                'source': neutral1,
                'target': neutral2,
                'label': words[word_id],
                'attribute': '',
            })
    for negative1 in opinion['-']:
        for negative2 in opinion['-']:
            if negative1 == negative2:
                continue
            edges.append(edge % {
                'source': negative1,
                'target': negative2,
                'label': words[word_id],
                'attribute': '',
            })

print gexf % {
    'nodes': "\n".join(nodes),
    'edges': "\n".join(edges),
}
