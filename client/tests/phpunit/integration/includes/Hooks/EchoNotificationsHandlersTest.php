<?php

namespace Wikibase\Client\Tests\Integration\Hooks;

use MediaWiki\Page\RedirectLookup;
use MediaWiki\Title\Title;
use MediaWiki\User\Options\UserOptionsManager;
use MediaWiki\User\User;
use MediaWiki\User\UserIdentityLookup;
use MediaWikiIntegrationTestCase;
use Wikibase\Client\Hooks\EchoNotificationsHandlers;
use Wikibase\Client\NamespaceChecker;
use Wikibase\Client\RepoLinker;
use Wikibase\Client\WikibaseClient;
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
	}

	private function getHandlers( SettingsArray $settings ): EchoNotificationsHandlers {
		return new EchoNotificationsHandlers(
			$this->repoLinker,
			$this->namespaceChecker,
			$this->getServiceContainer()->getRedirectLookup(),
			$this->getServiceContainer()->getUserIdentityLookup(),
			$this->createMock( UserOptionsManager::class ),
			$settings->getSetting( 'siteGlobalID' ),
			$settings->getSetting( 'sendEchoNotification' ),
			'repoSiteName'
		);
	}

	public function testWikibaseHandleChange_unrelatedChanges() {
		$settings = new SettingsArray( [
			'siteGlobalID' => 'enwiki',
			'sendEchoNotification' => true,
		] );
		$handlers = $this->getHandlers( $settings );

		/** @var ChangeRow[] $changes */
		$changes = array_diff_key(
			TestChanges::getChanges(),
			[
				'change-dewiki-sitelink' => true,
				'change-enwiki-sitelink' => true,
				'set-enwiki-sitelink' => true,
			]
		);
		foreach ( $changes as $key => $change ) {
			$this->assertFalse(
				$handlers->doWikibaseHandleChange( $change ),
				"Failed asserting that '$key' does not create an event"
			);
		}
	}

	public static function provideWikibaseHandleChange() {
		return [
			// add 'Emmy' as enwiki sitelink
			[ true, 'set-enwiki-sitelink', true, 'enwiki', [ 'Emmy' ] ],
			[ false, 'set-enwiki-sitelink', true, 'enwiki' ],
			[ false, 'set-enwiki-sitelink', false, 'enwiki', [ 'Emmy' ] ],
			[ false, 'set-enwiki-sitelink', true, 'dewiki', [ 'Emmy' ] ],

			// change enwiki sitelink from 'Emmy' to 'Emmy2'
			[ true, 'change-enwiki-sitelink', true, 'enwiki', [ 'Emmy', 'Emmy2' ] ],
			[ false, 'change-enwiki-sitelink', true, 'enwiki', [ 'Emmy' ] ],
			[ false, 'change-enwiki-sitelink', true, 'enwiki', [ 'Emmy2' ] ],

			// change dewiki sitelink from 'Duummy' to 'Duummy2'
			[
				true,
				'change-dewiki-sitelink',
				true,
				'dewiki',
				[ 'Duummy', 'Duummy2' ],
			],
			[
				false,
				'change-dewiki-sitelink',
				true,
				'dewiki',
				[
					'Duummy' => '#REDIRECT [[Duummy2]]',
					'Duummy2',
				],
			],
		];
	}

	/**
	 * @dataProvider provideWikibaseHandleChange
	 */
	public function testWikibaseHandleChange(
		bool $expected,
		string $key,
		bool $sendEchoNotification,
		string $siteGlobalID,
		array $createPages = []
	) {
		$settings = clone WikibaseClient::getSettings();
		$settings->setSetting( 'siteGlobalID', $siteGlobalID );
		$settings->setSetting( 'sendEchoNotification', $sendEchoNotification );
		$settings->setSetting( 'propagateChangesToRepo', false );
		$this->setService( 'WikibaseClient.Settings', $settings );

		$handlers = $this->getHandlers( $settings );

		/** @var ChangeRow[] $changes */
		$changes = TestChanges::getChanges();
		$change = $changes[$key];

		$pages = [];
		foreach ( $createPages as $key => $value ) {
			if ( is_int( $key ) ) {
				[ 'title' => $title ] = $this->insertPage( $value, 'This page is not a redirect' );
				$pages[] = $this->getExistingTestPage( $title );
			} else {
				[ 'title' => $title ] = $this->insertPage( $key, $value );
				$pages[] = $this->getExistingTestPage( $title );
			}
		}
		$this->assertSame( $expected, $handlers->doWikibaseHandleChange( $change ) );
		foreach ( $pages as $page ) {
			$this->deletePage( $page );
			$page->getTitle()->resetArticleID( false );
		}
		Title::clearCaches();
	}

	public static function localUserCreatedProvider() {
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

		$userOptionsManager = $this->createMock( UserOptionsManager::class );
		$userOptionsManager->expects( $this->exactly( $times ) )
			->method( 'setOption' );

		$handlers = new EchoNotificationsHandlers(
			$this->repoLinker,
			$this->namespaceChecker,
			$this->createNoopMock( RedirectLookup::class ),
			$this->createNoopMock( UserIdentityLookup::class ),
			$userOptionsManager,
			'enwiki',
			$enabled,
			'repoSiteName'
		);

		$handlers->doLocalUserCreated( $user, $auto );
	}

}
