#!/bin/bash
source /etc/wikibase.conf

ulimit -v 400000

trap 'kill %-; exit' SIGTERM

if [ -z "$1" ]; then
	echo "Starting default job queue runner"
fi

while [ 1 ];do
	nice -n 20 php $MW_INSTALL_DIR/maintenance/runJobs.php
	wait

	echo "No jobs..."
	sleep 5
done
