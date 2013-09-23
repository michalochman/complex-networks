import time
from multiprocessing import Process, JoinableQueue
from random import randint


class Worker(Process):
    def __init__(self, *args, **kwargs):
        self._id = kwargs.pop('id')
        self.stopped = False
        super(Worker, self).__init__(*args, **kwargs)

    def stop(self):
        print "Worker %d is going home" % self._id
        self.stopped = True

    def run(self):
        print "starting %d " % self._id
        # while not self.stopped:
        #     print "[%d] Excuting task %d, sleeping %d seconds" % (self._id, task_no, time_to_sleep)
        #     time.sleep(time_to_sleep)


def func(queue, x):
    task = queue.get(x)
    print '%d got %d' % (x, task)
    time.sleep(randint(1, 3))
    print '%d finished' % x
    queue.task_done()


if __name__ == '__main__':
    queue = JoinableQueue()
    new_tasks = randint(5, 20)
    for x in xrange(1, 9):
        queue.put(randint(1, 9))
    print len(queue)
    for x in xrange(1, 9):
        Process(target=func, args=(queue, x)).start()
    print 'Waiting to complete tasks...'
    queue.join()
    print 'Tasks finished'
