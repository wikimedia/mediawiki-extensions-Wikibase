#!/bin/bash
source /etc/wikibase.conf

ulimit -v 400000

trap 'kill %-; exit' SIGTERM

if [ -z "$1" ]; then
	echo "Starting poll for changes"
fi

while [ 1 ];do
	nice -n 20 php $MW_INSTALL_DIR/extensions/Wikibase/lib/maintenance/pollForChanges.php
	wait

	sleep 5
done
