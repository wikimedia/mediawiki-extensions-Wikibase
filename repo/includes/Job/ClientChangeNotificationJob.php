<?php

namespace Wikibase\Repo\Job;

use Wikibase\Change;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\ItemChange;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ClientChangeNotificationJob extends Job {

	public function run() {
		wfDebugLog( 'wikidata', __METHOD__ );
	}

}
