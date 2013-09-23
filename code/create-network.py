import os
from time import sleep
import MySQLdb
from MySQLdb.cursors import SSDictCursor
import sys
from scur_mgr import DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_TABLE_USERS, DB_TABLE_WORDS, DB_TABLE_RELATIONSHIPS

from Queue import Empty
from multiprocessing import Process, JoinableQueue, cpu_count

import networkx as nx
from networkx.algorithms import bipartite

bucket = 1
if len(sys.argv) > 1 and sys.argv[1].isdigit():
    bucket = int(sys.argv[1])

__dir__ = os.path.dirname(os.path.abspath(__file__))


class DictDiffer(object):
    """
    Calculate the difference between two dictionaries as:
    (1) items added
    (2) items removed
    (3) keys same in both but changed values
    (4) keys same in both and unchanged values
    """
    def __init__(self, current_dict, past_dict):
        self.current_dict, self.past_dict = current_dict, past_dict
        self.set_current, self.set_past = set(current_dict.keys()), set(past_dict.keys())
        self.intersect = self.set_current.intersection(self.set_past)
    def added(self):
        return self.set_current - self.intersect
    def removed(self):
        return self.set_past - self.intersect
    def changed(self):
        return set(o for o in self.intersect if self.past_dict[o] != self.current_dict[o])
    def unchanged(self):
        return set(o for o in self.intersect if self.past_dict[o] == self.current_dict[o])



class NetworkXWorker(Process):
    def __init__(self, _id, *args, **kwargs):
        self._id = _id
        self.stopped = False
        super(NetworkXWorker, self).__init__(*args, **kwargs)

    def stop(self):
        print "Worker %d is going home" % self._id
        self.stopped = True

    def run(self, *args, **kwargs):
        print "starting %d " % self._id
        while not self.stopped:
            try:
                node = nodes.get(False)
                length = 0
                try:
                    lengths = nx.shortest_path_length(network, source=node)
                    for l in lengths.itervalues():
                        length += l
                    length /= len(lengths)
                except:
                    pass
                lengthsum.put(length)
                # collaborative_similarity = network.collaborative_similarity('users', 'objects', user)
                # print "[%d] C_u(%d) = %f" % (self._id, user, collaborative_similarity)
                nodes.task_done()
            except Empty:
                if nodes.empty():
                    self.stop()

if __name__ == '__main__':
    db = MySQLdb.connect(host=DB_HOST, user=DB_USER, passwd=DB_PASS, db=DB_NAME, cursorclass=SSDictCursor)
    cur = db.cursor()

    # NETWORK
    network = nx.Graph(length=0)

    """
    # USERS
    cur.execute('SELECT id, name FROM %s u' % DB_TABLE_USERS)
    row = cur.fetchone()
    while row is not None:
        user_id = row.get('id')
        user_name = row.get('name')
        # network.add_node(user_id, label=user_name, bipartite=0)
        # network.add_node(user_id, label=user_name)
        row = cur.fetchone()

    # WORDS
    cur.execute('SELECT id, word FROM %s w' % DB_TABLE_WORDS)
    row = cur.fetchone()
    while row is not None:
        word_id = row.get('id')
        word_word = row.get('word')
        # network.add_node(word_id, label=word_word, bipartite=1)
        # network.add_node(word_id, label=word_word)
        row = cur.fetchone()
    """

    # EDGES
    relationships = {}
    limit = 1000000
    for i in range(0, 19):
        cur.execute('SELECT word_id, user_id FROM %s r WHERE skip = "n" LIMIT %s,%s' % (DB_TABLE_RELATIONSHIPS, i*limit, limit))
        row = cur.fetchone()
        while row is not None:
            user_id = row.get('user_id')
            word_id = row.get('word_id')
            rel = '%s,%s' % (user_id, word_id)
            relationships[rel] = relationships.get(rel, 0) + 1
            row = cur.fetchone()
    for rel, weight in relationships.iteritems():
        user_id, word_id = rel.split(',', 1)
        network.add_node(user_id, bipartite=0)
        network.add_node(word_id, bipartite=1)
        network.add_edge(user_id, word_id, weight=weight)

    print network.number_of_nodes()
    print network.number_of_edges()
    print bipartite.average_clustering(network)
    exit(0)
    # print network.nodes()
    # print network.edges()
    print ' --- '

    # create lengths queue
    lengthsum = JoinableQueue()
    # create nodes queue
    nodes = JoinableQueue()
    # add tasks to queue
    for node in network.nodes():
        nodes.put(node)
    # start as much processes as CPU has threads
    processes = cpu_count()
    print 'Starting %d processes...' % processes
    for x in range(0, processes):
        NetworkXWorker(x).start()
    # wait for Queue to empty
    print 'Waiting to complete tasks...'
    nodes.join()
    print 'Calculating average path length'
    total = 0
    count = 0
    while True:
        try:
            total += lengthsum.get(False)
            count += 1
            lengthsum.task_done()
        except Empty:
            if lengthsum.empty():
                break
    print 'L_G = %.2f' % (float(total)/float(count))
    lengthsum.join()
    print 'Queue empty, finishing...'
