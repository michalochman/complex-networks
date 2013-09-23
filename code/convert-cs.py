import os
import sys

if len(sys.argv) != 3 or not sys.argv[2].isdigit():
    exit('usage: %s path_to_buckets buckets' % sys.argv[0])

__DIR__ = os.path.abspath(sys.argv[1])

# read data files
outfile = '%s/0_buckets.txt' % __DIR__
with open(outfile, 'w') as ofile:
    ofile.write("# set term postscript eps enhanced color solid\n")
    ofile.write("# set out 'C_us_m.eps'\n")
    ofile.write("# set xrange [0:142]\n")
    ofile.write("# set xtics 10\n")
    ofile.write("# set yrange [0:0.17]\n")
    ofile.write("# set ytics 0.01\n")
    ofile.write("# plot '0_buckets.txt' u 1:7 w lp t 'C_u', '' u 1:8 w lp pt 1 lt 3 t '@^{\\261}s_m'\n")
    ofile.write("# bucket N M E <k> <d> C_u s_o C_o s_u\n")
    oformat = "%(bucket)s %(N)s %(M)s %(E)s %(<k>)s %(<d>)s %(C_u)s %(s_o)s %(C_o)s %(s_u)s\n"
    # sformat = "$%(bucket)d$ & $%(N)d$ & $%(M)d$ & $%(E)d$ & $%(<k>).2f$ & $%(<d>).2f$ & $%(C_u).4f$ & $%(s_o).4f$ & $%(C_o).4f$ & $%(s_u).4f$ \\\\\\hline"
    for x in range(1, int(sys.argv[2])+1):
        infile = '%s/bucket-%03d.txt' % (__DIR__, x)
        data = {'bucket': x}
        with open(infile) as ifile:
            for line in ifile:
                tokens = line.split(' = ')
                data[tokens[0]] = tokens[1].strip()
                # data[tokens[0]] = float(tokens[1].strip())
            ofile.write(oformat % data)
            # if x % 10 == 1:
            #     print sformat % data
