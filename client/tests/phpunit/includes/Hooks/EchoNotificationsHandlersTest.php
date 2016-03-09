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
		$this->settings = $client->getSettings();
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
		$this->settings->setSetting( 'siteGlobalID', 'enwiki' );
		$this->settings->setSetting( 'sendEchoNotification', true );
		$handlers = $this->getHandlers();

		$special = [
			'change-dewiki-sitelink',
			'change-enwiki-sitelink',
			'set-enwiki-sitelink'
		];
		foreach ( $changes as $key => $change ) {
			if ( in_array( $key, $special ) ) {
				continue;
			}
			$this->assertFalse(
				$handlers->doWikibaseHandleChange( $change ),
				"Failed asserting that '$key' does not create an event"
			);
		}


		$setEn = $changes['set-enwiki-sitelink'];
		$changeEn = $changes['change-enwiki-sitelink'];

		$this->assertFalse(
			$handlers->doWikibaseHandleChange( $setEn ),
			"Failed asserting that non-existing 'Emmy' does not create an event"
		);

		$this->insertPage( 'Emmy' );
		$this->assertTrue(
			$handlers->doWikibaseHandleChange( $setEn ),
			"Failed asserting that 'Emmy' creates an event"
		);

		$this->settings->setSetting( 'siteGlobalID', 'dewiki' );
		$handlers = $this->getHandlers();

		$this->assertFalse(
			$handlers->doWikibaseHandleChange( $setEn ),
			"Failed asserting that 'dewiki' sitelink does not create an event"
		);

		$this->settings->setSetting( 'siteGlobalID', 'enwiki' );
		$handlers = $this->getHandlers();

		$this->assertFalse(
			$handlers->doWikibaseHandleChange( $changeEn ),
			"Failed asserting that non-existing 'Emmy2' does not create an event"
		);

		$this->insertPage( 'Emmy2' );
		$this->assertTrue(
			$handlers->doWikibaseHandleChange( $changeEn ),
			"Failed asserting that 'Emmy2' creates an event"
		);

		$this->settings->setSetting( 'sendEchoNotification', false );
		$handlers = $this->getHandlers();
		$this->assertFalse(
			$handlers->doWikibaseHandleChange( $setEn ),
			"Failed asserting that configuration supresses creating an event"
		);


		$changeDe = $changes['change-dewiki-sitelink'];

		$this->settings->setSetting( 'siteGlobalID', 'dewiki' );
		$this->settings->setSetting( 'sendEchoNotification', true );
		$handlers = $this->getHandlers();

		$this->assertFalse(
			$handlers->doWikibaseHandleChange( $changeDe ),
			"Failed asserting that 'Dummy' does not create an event"
		);

		$this->insertPage( 'Dummy2' );
		$this->assertFalse(
			$handlers->doWikibaseHandleChange( $changeDe ),
			"Failed asserting that 'Dummy2' does not create an event"
		);

		$this->insertPage( 'Dummy', '#REDIRECT [[Dummy2]]' );
		$this->assertFalse(
			$handlers->doWikibaseHandleChange( $changeDe ),
			"Failed asserting that 'Dummy2' redirected by 'Dummy' does not create an event"
		);

		$this->insertPage( 'Dummy' );
		$this->assertTrue(
			$handlers->doWikibaseHandleChange( $changeDe ),
			"Failed asserting that 'Dummy2' creates an event"
		);
	}

}