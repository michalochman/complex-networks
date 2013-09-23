import os
import sys
import phpserialize
from multiprocessing import Process
from network import Network

__DIR__ = os.path.dirname(os.path.abspath(__file__))

if len(sys.argv) != 2 or not sys.argv[1].isdigit():
    exit('usage: %s bucket' % sys.argv[0])

# read data file
infile = '%s/data/bucket-%03d.txt' % __DIR__
f = open(infile)
links = phpserialize.load(f)

edges = 0
for words in links.get('users').itervalues():
    edges += len(words)


def average_degree(network, alias, link_type):
    print "<%s> = %f" % (alias, network.average_degree(link_type))


def network_collaborative_similarity(network, alias, link_type, link_n_type):
    print "C_%s = %s" % (alias, network.network_collaborative_similarity(link_type, link_n_type, False))

def average_jaccard_similarity(network, alias, link_type, link_n_type):
    print "s_%s = %s" % (alias, network.average_jaccard_similarity(link_type, link_n_type, False))

# print basic network info
print 'N = %d' % len(links.get('users'))
print 'M = %d' % len(links.get('objects'))
print 'E = %d' % edges

network = Network(links)
Process(target=average_degree, args=(network, 'k', 'users')).start()
Process(target=average_degree, args=(network, 'd', 'objects')).start()
Process(target=network_collaborative_similarity, args=(network, 'u', 'users', 'objects')).start()
Process(target=average_jaccard_similarity, args=(network, 'o', 'objects', 'users')).start()
Process(target=network_collaborative_similarity, args=(network, 'o', 'objects', 'users')).start()
Process(target=average_jaccard_similarity, args=(network, 'u', 'users', 'objects')).start()
