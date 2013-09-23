from Queue import Empty
import os
import sys
import phpserialize
from multiprocessing import Process, JoinableQueue, cpu_count
from network import Network

__DIR__ = os.path.dirname(os.path.abspath(__file__))

too_few_args = len(sys.argv) < 2
too_many_args = len(sys.argv) > 3
bad_2_args_format = len(sys.argv) == 2 and not sys.argv[1].isdigit()
if too_few_args or too_many_args or bad_2_args_format:
    exit('usage: %s start_bucket' % sys.argv[0])


class NetworkWorker(Process):
    def __init__(self, _id, *args, **kwargs):
        self._id = _id
        self.stopped = False
        super(NetworkWorker, self).__init__(*args, **kwargs)

    def stop(self):
        print "Worker %d is going home" % self._id
        self.stopped = True

    def run(self, *args, **kwargs):
        print "starting %d " % self._id
        while not self.stopped:
            try:
                user = nodes.get(False)
                collaborative_similarity = network.collaborative_similarity('users', 'objects', user)
                print "[%d] C_u(%d) = %f" % (self._id, user, collaborative_similarity)
                outfile_path = '%s/nodes/user-%09d.txt' % (__DIR__, user)
                with open(outfile_path, 'a') as outfile:
                    # file format:
                    # bucket C_u(i)
                    outfile.write("%d %f\n" % (int(sys.argv[1]), collaborative_similarity))
                nodes.task_done()
            except Empty:
                if nodes.empty():
                    self.stop()


if __name__ == '__main__':
    # read data file
    infile_path = '%s/data/bucket-%03d.txt' % (__DIR__, int(sys.argv[1]))
    with open(infile_path) as infile:
        links = phpserialize.load(infile)
    # create network
    network = Network(links)
    # create nodes queue
    nodes = JoinableQueue()
    # add tasks to queue
    for user, words in links.get('users').iteritems():
        nodes.put(user)
    # start as much processes as CPU has threads
    processes = cpu_count()
    print 'Starting %d processes...' % processes
    for x in xrange(0, processes):
        NetworkWorker(x).start()
    # wait for Queue to empty
    print 'Waiting to complete tasks...'
    nodes.join()
    print 'Queue empty, finishing...'
