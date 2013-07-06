#!/usr/bin/env python

"""
Download http://pub7.di.fm/ to index.html . Then run this script.

Written by Jabba Laci - http://ubuntuincident.wordpress.com/2013/06/06/digitally-imported-station-urls/
"""

import re

INPUT = 'index.html'


def main():
    mp = []
    st = []
    with open(INPUT) as f:
        for line in f:
            mp += re.findall(r'<h3>Mount Point /(.*?)</h3>', line)
            st += re.findall(r'Stream Title:</td><td class="streamdata">(.*?)</td>', line)

#    print len(mp)
#    print len(st)
    li = zip(mp, st)
    li = [(title, desc) for title, desc in li if '_aac' not in title]
#    print li
#    print len(li)

    BASE = 'http://pub7.di.fm/'
    for index, tup in enumerate(li, start=1):
        title, desc = tup
        desc = re.sub(r' DIGITALLY IMPORTED -', '', desc)
        print "({i:02}) {url:<45} {desc}".format(i=index, url=BASE+title, desc=desc)

#############################################################################

if __name__ == "__main__":
    main()
