<?php

namespace Wikibase\Client\Tests\Hooks;

use EchoEvent;
use MediaWikiTestCase;
use Wikibase\Client\Hooks\EchoNotificationsHandlers;
use Wikibase\Client\RepoLinker;
use Wikibase\Client\WikibaseClient;
use Wikibase\SettingsArray;
use Wikibase\Test\TestChanges;

/**
 * @covers Wikibase\Client\Hooks\EchoNotificationsHandlers
 *
 * @group Database
 * @group WikibaseClient
 * @group Wikibase
 */
class EchoNotificationsHandlersTestCase extends MediaWikiTestCase {

	/**
	 * @var RepoLinker
	 */
	private $repoLinker;

	protected function setUp() {
		parent::setUp();
		// if Echo is not loaded, skip this test
		if ( !class_exists( EchoEvent::class ) ) {
			$this->markTestSkipped( "Echo not loaded" );
		}

		$client = WikibaseClient::getDefaultInstance();
		$this->repoLinker = $this->getMockBuilder( RepoLinker::class )
			->disableOriginalConstructor()
			->getMock();
		$this->repoLinker
			->expects( $this->any() )
			->method( 'getEntityUrl' )
			->will( $this->returnValue( 'foo' ) );
	}

	/**
	 * @param SettingsArray $settings
	 *
	 * @return EchoNotificationsHandlers
	 */
	private function getHandlers( $settings ) {
		return new EchoNotificationsHandlers(
			$this->repoLinker,
			$settings
		);
	}

	public function testWikibaseHandleChange() {
		/** @var ChangeRow[] $changes */
		$changes = TestChanges::getChanges();

		$settings = new SettingsArray();
		$settings->setSetting( 'siteGlobalID', 'enwiki' );
		$settings->setSetting( 'sendEchoNotification', true );

		$handlers = $this->getHandlers( $settings );

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

		$settings->setSetting( 'siteGlobalID', 'dewiki' );
		$handlers = $this->getHandlers( $settings );

		$this->assertFalse(
			$handlers->doWikibaseHandleChange( $setEn ),
			"Failed asserting that 'dewiki' sitelink does not create an event"
		);

		$settings->setSetting( 'siteGlobalID', 'enwiki' );
		$handlers = $this->getHandlers( $settings );

		$this->assertFalse(
			$handlers->doWikibaseHandleChange( $changeEn ),
			"Failed asserting that non-existing 'Emmy2' does not create an event"
		);

		$this->insertPage( 'Emmy2' );
		$this->assertTrue(
			$handlers->doWikibaseHandleChange( $changeEn ),
			"Failed asserting that 'Emmy2' creates an event"
		);

		$settings->setSetting( 'sendEchoNotification', false );
		$handlers = $this->getHandlers( $settings );
		$this->assertFalse(
			$handlers->doWikibaseHandleChange( $setEn ),
			"Failed asserting that configuration supresses creating an event"
		);

		$changeDe = $changes['change-dewiki-sitelink'];

		$settings->setSetting( 'siteGlobalID', 'dewiki' );
		$settings->setSetting( 'sendEchoNotification', true );
		$handlers = $this->getHandlers( $settings );

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
			"Failed asserting that 'Dummy2' redirected to by 'Dummy' does not create an event"
		);

		$this->insertPage( 'Dummy' );
		$this->assertTrue(
			$handlers->doWikibaseHandleChange( $changeDe ),
			"Failed asserting that 'Dummy2' creates an event"
		);
	}

}
