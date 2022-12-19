<?php

namespace Wikibase\Client\Tests\Integration\Hooks;

use MediaWiki\User\UserOptionsManager;
use MediaWikiIntegrationTestCase;
use Title;
use User;
use Wikibase\Client\Hooks\EchoNotificationsHandlers;
use Wikibase\Client\NamespaceChecker;
use Wikibase\Client\RepoLinker;
use Wikibase\Lib\Changes\ChangeRow;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Tests\Changes\TestChanges;

/**
 * @covers \Wikibase\Client\Hooks\EchoNotificationsHandlers
 *
 * @group Database
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Matěj Suchánek
 */
class EchoNotificationsHandlersTest extends MediaWikiIntegrationTestCase {

	/**
	 * @var RepoLinker
	 */
	private $repoLinker;

	/**
	 * @var NamespaceChecker
	 */
	private $namespaceChecker;

	/**
	 * @var UserOptionsManager
	 */
	private $userOptionsManager;

	protected function setUp(): void {
		parent::setUp();
		$this->markTestSkippedIfExtensionNotLoaded( 'Echo' );

		$this->repoLinker = $this->createMock( RepoLinker::class );
		$this->repoLinker
			->method( 'getEntityUrl' )
			->willReturn( 'foo' );

		$this->namespaceChecker = $this->createMock( NamespaceChecker::class );
		$this->namespaceChecker
			->method( 'isWikibaseEnabled' )
			->willReturn( true );

		$this->userOptionsManager = $this->createMock( UserOptionsManager::class );
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
			$this->getServiceContainer()->getRedirectLookup(),
			$this->userOptionsManager,
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
			'set-enwiki-sitelink',
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

		Title::newFromTextThrow( 'Emmy' )->resetArticleID( 0 );
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

		Title::newFromTextThrow( 'Emmy2' )->resetArticleID( 0 );
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
			"Failed asserting that configuration suppresses creating an event"
		);

		$changeDe = $changes['change-dewiki-sitelink'];

		$settings->setSetting( 'siteGlobalID', 'dewiki' );
		$settings->setSetting( 'sendEchoNotification', true );
		$handlers = $this->getHandlers( $settings );

		Title::newFromTextThrow( 'Duummy2' )->resetArticleID( 0 );
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
			],
		];
	}

	/**
	 * @dataProvider localUserCreatedProvider
	 */
	public function testLocalUserCreated( $enabled, $times, $auto ) {
		$user = $this->createMock( User::class );

		$this->userOptionsManager->expects( $this->exactly( $times ) )
			->method( 'setOption' );

		$handlers = new EchoNotificationsHandlers(
			$this->repoLinker,
			$this->namespaceChecker,
			$this->getServiceContainer()->getRedirectLookup(),
			$this->userOptionsManager,
			'enwiki',
			$enabled,
			'repoSiteName'
		);

		$handlers->doLocalUserCreated( $user, $auto );
	}

}
