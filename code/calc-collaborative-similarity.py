import os
import sys
import phpserialize
from network import Network

if len(sys.argv) != 2 or not sys.argv[1].isdigit():
    exit('usage: %s bucket' % sys.argv[0])

# read data file
infile = '%s/data/bucket-%03d.txt' % (os.path.dirname(os.path.abspath(__file__)), int(sys.argv[1]))
f = open(infile)
links = phpserialize.load(f)

edges = 0
for words in links.get('users').itervalues():
    edges += len(words)

# print basic network info
print 'N = %d' % len(links.get('users'))
print 'M = %d' % len(links.get('objects'))
print 'E = %d' % edges

# calc and print extended network info
network = Network(links)
print "<k> = %f" % network.average_degree('users')
print "<d> = %f" % network.average_degree('objects')
print "C_u = %s" % (network.network_collaborative_similarity('users', 'objects', False))
print "s_o = %s" % (network.average_jaccard_similarity('objects', 'users', False))
print "C_o = %s" % (network.network_collaborative_similarity('objects', 'users', False))
print "s_u = %s" % (network.average_jaccard_similarity('users', 'objects', False))
