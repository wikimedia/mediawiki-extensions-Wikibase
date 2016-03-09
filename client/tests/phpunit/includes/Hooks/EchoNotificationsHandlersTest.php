<?php

namespace Wikibase\Client\Tests\Hooks;

use Wikibase\Client\Hooks\EchoNotificationsHandlers;
use Wikibase\Client\WikibaseClient;
use Wikibase\Test\TestChanges;

/**
 * @covers Wikibase\Client\Hooks\EchoNotificationsHandlers
 *
 * @group Database
 * @group WikibaseClient
 * @group Wikibase
 */
class EchoNotificationsHandlersTestCase extends \MediaWikiTestCase {

	/**
	 * @var RepoLinker
	 */
	private $repoLinker;

	/**
	 * @var SettingsArray
	 */
	private $settings;

	protected function setUp() {
		parent::setUp();

		$client = WikibaseClient::getDefaultInstance();
		$this->repoLinker = $client->newRepoLinker();

		$settings = $client->getSettings();
		$settings->setSetting( 'siteGlobalID', 'enwiki' );
		$this->settings = $settings;
	}

	/**
	 * @return EchoNotificationsHandlers
	 */
	private function getHandlers() {
		return new EchoNotificationsHandlers(
			$this->repoLinker,
			$this->settings
		);
	}

	public function testWikibaseHandleChange() {
		$changes = TestChanges::getChanges();
		$handlers = $this->getHandlers();

		foreach ( $changes as $key => $change ) {
			if ( $key === 'set-enwiki-sitelink' || $key === 'change-enwiki-sitelink' ) {
				continue;
			}
			$this->assertFalse(
				$handlers->doWikibaseHandleChange( $change ),
				"Failed asserting that $key does not create an event"
			);
		}

		$set = $changes['set-enwiki-sitelink'];
		$change = $changes['change-enwiki-sitelink'];

		$this->assertFalse(
			$handlers->doWikibaseHandleChange( $set ),
			"Failed asserting that non-existing Emmy does not create an event"
		);

		$this->insertPage( 'Emmy' );
		$this->assertTrue(
			$handlers->doWikibaseHandleChange( $set ),
			"Failed asserting that Emmy creates an event"
		);

		$this->assertFalse(
			$handlers->doWikibaseHandleChange( $change ),
			"Failed asserting that non-existing Emmy2 does not create an event"
		);

		$this->insertPage( 'Emmy2' );
		$this->assertTrue(
			$handlers->doWikibaseHandleChange( $change ),
			"Failed asserting that Emmy2 creates an event"
		);
	}

}