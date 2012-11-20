#!/bin/bash
source /etc/wikibase.conf

ulimit -v 400000

trap 'kill %-; exit' SIGTERM

if [ -z "$1" ]; then
	echo "Starting default job queue runner"
fi

while [ 1 ];do
# you can put multiple lines here, such as with --wiki params
	nice -n 20 php $MW_INSTALL_DIR/maintenance/runJobs.php

# use this instead if you have multiversion stuff setup like WMF has
#    nice -n 20 mwscript maintenance/runJobs.php --wiki arwiki

	wait

	echo "No jobs..."
	sleep 5
done
