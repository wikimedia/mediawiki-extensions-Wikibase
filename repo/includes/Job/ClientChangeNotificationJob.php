<?php

namespace Wikibase\Repo\Job;

use Job;
use Title;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ClientChangeNotificationJob extends Job {

	public function __construct( Title $title, array $params ) {
		parent::__construct( 'ClientChangeNotification', $title, $params );
	}

	public function run() {
		wfDebugLog( 'wikidata', __METHOD__ );
	}

}
