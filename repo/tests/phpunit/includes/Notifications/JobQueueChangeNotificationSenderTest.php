<?php

namespace Wikibase\Repo\Tests\Notifications;

/**
 * @covers Wikibase\Repo\Notifications\JobQueueChangeNotificationSender
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group WikibaseChange
 * @group WikibaseRepo
 * @group Database
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class JobQueueChangeNotificationSenderTest extends \MediaWikiTestCase {

	public function testSendNotification() {
		// @todo: inject JobQueueGroup into JobQueueChangeNotificationSender
		$this->markTestIncomplete( 'Need a good way to test job queue interaction!' );
	}

}
