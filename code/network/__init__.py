import fractions


class Network(object):
    def __init__(self, network):
        self.network = network

    def degree(self, link_type, key):
        return len(self.network.get(link_type).get(key))

    def average_degree(self, link_type):
        degree = 0
        for link in self.network.get(link_type).itervalues():
            degree += len(link)
        return float(degree) / float(len(self.network.get(link_type)))

    def nn_degree(self, link_type, link_n_type, key):
        degree = self.degree(link_type, key)
        nn_degree = 0
        for n_key in self.network.get(link_type, key):
            nn_degree += self.degree(link_n_type, n_key)
        return '%d/%d' % (nn_degree, degree)

    def jaccard_index(self, set_a, set_b):
        n = len(set_a & set_b)
        return float(n)/float(len(set_a) + len(set_b) - n)
    
    def jaccard_similarity(self, link_type, key_a, key_b, return_string=False):
        key_a = int(key_a)
        key_b = int(key_b)
        set_a = set(self.network.get(link_type).get(key_a).values())
        set_b = set(self.network.get(link_type).get(key_b).values())
        if return_string:
            intersection = len(set_a & set_b)
            union = len(set_a | set_b)
            gcd = fractions.gcd(intersection, union)
            return '%d/%d' % (intersection/gcd, union/gcd)
        return self.jaccard_index(set_a, set_b)

    def collaborative_similarity(self, link_type, link_n_type, key, return_string=False):
        degree = self.degree(link_type, key)
        if degree <= 1:
            return 0
        similarity_sum = 0
        for n_key_1 in self.network.get(link_type).get(key).itervalues():
            for n_key_2 in self.network.get(link_type).get(key).itervalues():
                if n_key_1 == n_key_2:
                    continue
                similarity_sum += self.jaccard_similarity(link_n_type, n_key_1, n_key_2)
        if return_string:
            precision = 1e3
            new_similarity_sum = round(similarity_sum * degree*(degree-1) * precision)
            gcd = fractions.gcd(new_similarity_sum, degree*(degree-1) * precision)
            new_similarity_sum /= gcd
            return '%d/%d' % (new_similarity_sum, degree*(degree-1)*round(new_similarity_sum/similarity_sum))
        return similarity_sum / (degree*(degree-1))

    def average_jaccard_similarity(self, link_type, link_n_type, return_string=False):
        nodes = 0
        similarity_sum = 0
        for key_links in self.network.get(link_type).itervalues():
            for n_key_1 in key_links.itervalues():
                for n_key_2 in key_links.itervalues():
                    if n_key_1 == n_key_2:
                        continue
                    nodes += 1
                    similarity_sum += self.jaccard_similarity(link_n_type, n_key_1, n_key_2)
        if nodes == 0:
            return 0
        if return_string:
            precision = 1e3
            new_similarity_sum = round(similarity_sum * nodes * precision)
            gcd = fractions.gcd(new_similarity_sum, nodes * precision)
            new_similarity_sum /= gcd
            return '%d/%d' % (new_similarity_sum, nodes*round(new_similarity_sum/similarity_sum))
        return similarity_sum / nodes

    def network_collaborative_similarity(self, link_type, link_n_type, return_string=False):
        nodes = 0
        similarity_sum = 0
        for key, key_links in self.network.get(link_type).iteritems():
            if self.degree(link_type, key) <= 1:
                continue
            nodes += 1
            collaborative_similarity = self.collaborative_similarity(link_type, link_n_type, key)
            similarity_sum += collaborative_similarity
        if nodes == 0:
            return 0
        if return_string:
            precision = 1e3
            new_similarity_sum = round(similarity_sum * nodes * precision)
            gcd = fractions.gcd(new_similarity_sum, nodes * precision)
            new_similarity_sum /= gcd
            return '%d/%d' % (new_similarity_sum, nodes*(new_similarity_sum/similarity_sum))
        return similarity_sum/nodes
