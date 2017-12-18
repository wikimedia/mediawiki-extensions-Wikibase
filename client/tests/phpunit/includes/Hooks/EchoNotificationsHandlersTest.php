<?php

namespace Wikibase\Client\Tests\Hooks;

use EchoEvent;
use MediaWikiTestCase;
use Title;
use User;
use Wikibase\ChangeRow;
use Wikibase\Client\Hooks\EchoNotificationsHandlers;
use Wikibase\Client\NamespaceChecker;
use Wikibase\Client\RepoLinker;
use Wikibase\Lib\Tests\Changes\TestChanges;
use Wikibase\SettingsArray;

/**
 * @covers Wikibase\Client\Hooks\EchoNotificationsHandlers
 *
 * @group Database
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Matěj Suchánek
 */
class EchoNotificationsHandlersTest extends MediaWikiTestCase {

	/**
	 * @var RepoLinker
	 */
	private $repoLinker;

	/**
	 * @var NamespaceChecker
	 */
	private $namespaceChecker;

	protected function setUp() {
		parent::setUp();
		// if Echo is not loaded, skip this test
		if ( !class_exists( EchoEvent::class ) ) {
			$this->markTestSkipped( "Echo not loaded" );
		}

		$this->repoLinker = $this->getMockBuilder( RepoLinker::class )
			->disableOriginalConstructor()
			->getMock();
		$this->repoLinker
			->expects( $this->any() )
			->method( 'getEntityUrl' )
			->will( $this->returnValue( 'foo' ) );

		$this->namespaceChecker = $this->getMockBuilder( NamespaceChecker::class )
			->disableOriginalConstructor()
			->getMock();
		$this->namespaceChecker
			->expects( $this->any() )
			->method( 'isWikibaseEnabled' )
			->will( $this->returnValue( true ) );
	}

	/**
	 * @param SettingsArray $settings
	 *
	 * @return EchoNotificationsHandlers
	 */
	private function getHandlers( SettingsArray $settings ) {
		return new EchoNotificationsHandlers(
			$this->repoLinker,
			$this->namespaceChecker,
			$settings->getSetting( 'siteGlobalID' ),
			$settings->getSetting( 'sendEchoNotification' ),
			'repoSiteName'
		);
	}

	public function testWikibaseHandleChange() {
		/** @var ChangeRow[] $changes */
		$changes = TestChanges::getChanges();

		$settings = new SettingsArray();
		$settings->setSetting( 'siteGlobalID', 'enwiki' );
		$settings->setSetting( 'sendEchoNotification', true );
		$settings->setSetting( 'echoIcon', false );

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

		Title::newFromText( 'Emmy' )->resetArticleID( 0 );
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

		Title::newFromText( 'Emmy2' )->resetArticleID( 0 );
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

		Title::newFromText( 'Duummy2' )->resetArticleID( 0 );
		$this->assertFalse(
			$handlers->doWikibaseHandleChange( $changeDe ),
			"Failed asserting that 'Duummy' does not create an event"
		);

		$this->insertPage( 'Duummy2' );
		$this->assertFalse(
			$handlers->doWikibaseHandleChange( $changeDe ),
			"Failed asserting that 'Duummy2' does not create an event"
		);

		$this->insertPage( 'Duummy', '#REDIRECT [[Duummy2]]' );
		$this->assertFalse(
			$handlers->doWikibaseHandleChange( $changeDe ),
			"Failed asserting that 'Duummy2' redirected to by 'Duummy' does not create an event"
		);

		$this->insertPage( 'Duummy' );
		$this->assertTrue(
			$handlers->doWikibaseHandleChange( $changeDe ),
			"Failed asserting that 'Duummy2' creates an event"
		);
	}

	public function localUserCreatedProvider() {
		return [
			'disabled no auto' => [
				'enabled' => false,
				'times' => 0,
				'auto' => false,
			],
			'disabled auto' => [
				'enabled' => false,
				'times' => 0,
				'auto' => true,
			],
			'enabled no auto' => [
				'enabled' => true,
				'times' => 1,
				'auto' => false,
			],
			'enabled auto' => [
				'enabled' => true,
				'times' => 1,
				'auto' => true,
			]
		];
	}

	/**
	 * @dataProvider localUserCreatedProvider
	 */
	public function testLocalUserCreated( $enabled, $times, $auto ) {
		$handlers = new EchoNotificationsHandlers(
			$this->repoLinker,
			$this->namespaceChecker,
			'enwiki',
			$enabled,
			'repoSiteName'
		);

		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->exactly( $times ) )
			->method( 'setOption' );
		$user->expects( $this->exactly( $times ) )
			->method( 'saveSettings' );

		$handlers->doLocalUserCreated( $user, $auto );
	}

}
