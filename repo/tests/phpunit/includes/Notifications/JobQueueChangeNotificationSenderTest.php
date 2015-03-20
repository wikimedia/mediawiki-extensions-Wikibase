<?php

namespace Wikibase\Test;

/**
 * @covers Wikibase\Rep\Notifications\JobQueueChangeNotificationSender
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group WikibaseChange
 * @group WikibaseRepo
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class JobQueueChangeNotificationSenderTest extends \MediaWikiTestCase {

	public function testSendNotification() {
		// @todo: inject JobQueueGroup into JobQueueChangeNotificationSender
		$this->markTestIncomplete( 'Need a good way to test job queue interaction!' );
	}

}
