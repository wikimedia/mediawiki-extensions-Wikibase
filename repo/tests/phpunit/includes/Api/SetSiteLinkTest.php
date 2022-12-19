<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Api;

use ApiUsageException;
use MediaWiki\MediaWikiServices;
use User;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\SiteLinkPageNormalizer;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Api\SetSiteLink
 * @covers \Wikibase\Repo\Api\ModifyEntity
 *
 * @license GPL-2.0-or-later
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group BreakingTheSlownessBarrier
 *
 * The database group has as a side effect that temporal database tables are created. This makes
 * it possible to test without poisoning a production database.
 * @group Database
 *
 * Some of the tests takes more time, and needs therefore longer time before they can be aborted
 * as non-functional. The reason why tests are aborted is assumed to be set up of temporal databases
 * that hold the first tests in a pending state awaiting access to the database.
 * @group medium
 */
class SetSiteLinkTest extends WikibaseApiTestCase {

	/**
	 * @var bool
	 */
	private static $hasSetup;

	/** @var ItemId */
	private static $gaItemId;
	/** @var ItemId */
	private static $faItemId;
	/** @var ItemId */
	private static $otherItemId;

	public function provideData() {
		return [
			'set new link using id' => [
				'p' => [
					'handle' => 'Leipzig',
					'linksite' => 'dewiki',
					'linktitle' => 'leipzig',
					'badges' => '{gaItem}|{faItem}',
				],
				'e' => [
					'value' => [ 'dewiki' => [
						'title' => 'Leipzig',
						'badges' => [ '{gaItem}', '{faItem}' ],
					] ],
				],
			],
			'set new link using sitelink' => [
				'p' => [
					'site' => 'dewiki',
					'title' => 'Berlin',
					'linksite' => 'nowiki',
					'linktitle' => 'berlin',
				],
				'e' => [
					'value' => [ 'nowiki' => [
						'title' => 'Berlin',
						'badges' => [],
					] ],
					'indb' => 5,
				],
			],
			'modify link using id' => [
				'p' => [
					'handle' => 'Leipzig',
					'linksite' => 'dewiki',
					'linktitle' => 'Leipzig_Two',
					'badges' => '',
				],
				'e' => [
					'value' => [ 'dewiki' => [
						'title' => 'Leipzig Two',
						'badges' => [],
					] ],
				],
			],
			'modify link using sitelink' => [
				'p' => [
					'site' => 'dewiki',
					'title' => 'Berlin',
					'linksite' => 'nowiki',
					'linktitle' => 'Berlin_Two',
				],
				'e' => [
					'value' => [ 'nowiki' => [
						'title' => 'Berlin Two',
						'badges' => [],
					] ],
					'indb' => 5,
				],
			],
			'remove link using id (with a summary)' => [
				'p' => [
					'handle' => 'Leipzig',
					'linksite' => 'dewiki',
					'linktitle' => '',
					'summary' => 'WooSummary',
				],
				'e' => [ 'value' => [] ] ],
			'remove link using sitelink' => [
				'p' => [
					'site' => 'dewiki',
					'title' => 'Berlin',
					'linksite' => 'nowiki',
					'linktitle' => '',
				],
				'e' => [ 'value' => [], 'indb' => 4 ] ],
			'add badges to existing sitelink' => [
				'p' => [
					'site' => 'dewiki',
					'title' => 'Berlin',
					'linksite' => 'dewiki',
					'linktitle' => 'Berlin',
					'badges' => '{faItem}|{gaItem}',
				],
				'e' => [
					'value' => [ 'dewiki' => [
						'title' => 'Berlin',
						'badges' => [ '{faItem}', '{gaItem}' ],
					] ],
					'indb' => 4,
				],
			],
			'add duplicate badges to existing sitelink' => [
				'p' => [
					'site' => 'dewiki',
					'title' => 'Berlin',
					'linksite' => 'dewiki',
					'linktitle' => 'Berlin',
					'badges' => '{gaItem}|{gaItem}|{faItem}|{gaItem}',
				],
				'e' => [
					'value' => [ 'dewiki' => [
						'title' => 'Berlin',
						'badges' => [ '{gaItem}', '{faItem}' ],
					] ],
					'indb' => 4,
				],
			],
			'no change' => [
				'p' => [
					'site' => 'dewiki',
					'title' => 'Berlin',
					'linksite' => 'dewiki',
					'linktitle' => 'Berlin',
					'badges' => '{gaItem}|{faItem}',
				],
				'e' => [
					'value' => [ 'dewiki' => [
						'title' => 'Berlin',
						'badges' => [ '{gaItem}', '{faItem}' ],
					] ],
					'indb' => 4,
				],
			],
			'change only title, badges should be intact' => [
				'p' => [
					'site' => 'dewiki',
					'title' => 'Berlin',
					'linksite' => 'dewiki',
					'linktitle' => 'Berlin_Two',
				],
				'e' => [
					'value' => [ 'dewiki' => [
						'title' => 'Berlin Two',
						'badges' => [ '{gaItem}', '{faItem}' ],
					] ],
					'indb' => 4,
				],
			],
			'change both title and badges' => [
				'p' => [
					'site' => 'dewiki',
					'title' => 'Berlin Two',
					'linksite' => 'dewiki',
					'linktitle' => 'Berlin',
					'badges' => '{gaItem}',
				],
				'e' => [
					'value' => [ 'dewiki' => [
						'title' => 'Berlin',
						'badges' => [ '{gaItem}' ],
					] ],
					'indb' => 4,
				],
			],
			'change only badges, title intact' => [
				'p' => [
					'site' => 'dewiki',
					'title' => 'Berlin',
					'linksite' => 'dewiki',
					'badges' => '{gaItem}|{faItem}',
				],
				'e' => [
					'value' => [ 'dewiki' => [
						'title' => 'Berlin',
						'badges' => [ '{gaItem}', '{faItem}' ],
					] ],
					'indb' => 4,
				],
			],
			'set new link using id (without badges)' => [
				'p' => [
					'handle' => 'Berlin',
					'linksite' => 'svwiki',
					'linktitle' => 'Berlin',
				],
				'e' => [
					'value' => [ 'svwiki' => [
						'title' => 'Berlin',
						'badges' => [],
					] ],
					'indb' => 5,
				],
			],
			'delete link by not providing neither title nor badges' => [
				'p' => [ 'handle' => 'Berlin', 'linksite' => 'svwiki' ],
				'e' => [ 'value' => [], 'indb' => 4 ],
			],
		];
	}

	public function provideExceptionData() {
		return [
			'missing token' => [
				'p' => [
					'site' => 'dewiki',
					'title' => 'Berlin',
					'linksite' => 'svwiki',
					'linktitle' => 'testSetSiteLinkWithNoToken',
				],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => $this->logicalOr(
						$this->equalTo( 'notoken' ),
						$this->equalTo( 'missingparam' )
					),
					'message' => 'The "token" parameter must be set',
				] ],
				'token' => false,
			],
			'invalid token' => [
				'p' => [
					'site' => 'dewiki',
					'title' => 'Berlin',
					'linksite' => 'svwiki',
					'linktitle' => 'testSetSiteLinkWithBadToken',
					'token' => '88888888888888888888888888888888+\\',
				],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'badtoken',
					'message' => 'Invalid CSRF token.',
				] ],
				'token' => false,
			],
			'Set SiteLink With No Id' => [
				'p' => [
					'linksite' => 'enwiki',
					'linktitle' => 'testSetSiteLinkWithNoId',
				],
				'e' => [ 'exception' => [ 'type' => ApiUsageException::class ] ] ],
			'Set SiteLink With Bad Id' => [
				'p' => [
					'id' => 123456789,
					'linksite' => 'enwiki',
					'linktitle' => 'testSetSiteLinkWithNoId',
				],
				'e' => [ 'exception' => [ 'type' => ApiUsageException::class ] ] ],
			'Set SiteLink With Bad Site' => [
				'p' => [
					'site' => 'dewiktionary',
					'title' => 'Berlin',
					'linksite' => 'enwiki',
					'linktitle' => 'Berlin',
				],
				'e' => [ 'exception' => [ 'type' => ApiUsageException::class ] ] ],
			'Set SiteLink With Bad Title' => [
				'p' => [
					'site' => 'dewiki',
					'title' => 'BadTitle_de',
					'linksite' => 'enwiki',
					'linktitle' => 'BadTitle_en',
				],
				'e' => [ 'exception' => [ 'type' => ApiUsageException::class ] ] ],
			'Set SiteLink With Bad Target Site' => [
				'p' => [
					'site' => 'dewiki',
					'title' => 'Berlin',
					'linksite' => 'enwiktionary',
					'linktitle' => 'Berlin',
				],
				'e' => [ 'exception' => [ 'type' => ApiUsageException::class ] ] ],
			'badge item does not exist' => [
				'p' => [
					'site' => 'enwiki',
					'title' => 'Berlin',
					'linksite' => 'enwiki',
					'linktitle' => 'Berlin',
					'badges' => 'Q99999|{faItem}',
				],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'no-such-entity',
				] ],
			],
			'no sitelink - cannot change badges' => [
				'p' => [
					'site' => 'enwiki',
					'title' => 'Berlin',
					'linksite' => 'svwiki',
					'badges' => '{gaItem}|{faItem}',
				],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'no-such-sitelink',
					'message' => wfMessage( 'wikibase-validator-no-such-sitelink', 'svwiki' )->inLanguage( 'en' )->text(),
				] ],
			],
		];
	}

	public function provideBadBadgeData() {
		return [
			'bad badge id' => [
				[ 'site' => 'enwiki',
					'title' => 'Berlin',
					'linksite' => 'enwiki',
					'linktitle' => 'Berlin',
					'badges' => 'abc|{faItem}',
				],
			],
			'badge id is not an item id' => [
				[ 'site' => 'enwiki',
					'title' => 'Berlin',
					'linksite' => 'enwiki',
					'linktitle' => 'Berlin',
					'badges' => 'P2|{faItem}',
				],
			],
			'badge id is not specified' => [
				[ 'site' => 'enwiki',
					'title' => 'Berlin',
					'linksite' => 'enwiki',
					'linktitle' => 'Berlin',
					'badges' => '{faItem}|{otherItem}',
				],
			],
		];
	}

	protected function setUp(): void {
		parent::setUp();

		// XXX: This test doesn't mark tablesUsed so things created here will remain through all tests in the class.
		if ( !isset( self::$hasSetup ) ) {
			$store = $this->getEntityStore();

			$this->initTestEntities( [ 'StringProp', 'Leipzig', 'Berlin' ] );

			$badge = new Item();
			$store->saveEntity( $badge, 'SetSiteLinkTestGA', $this->user, EDIT_NEW );
			self::$gaItemId = $badge->getId();

			$badge = new Item();
			$store->saveEntity( $badge, 'SetSiteLinkTestFA', $this->user, EDIT_NEW );
			self::$faItemId = $badge->getId();

			$badge = new Item();
			$store->saveEntity( $badge, 'SetSiteLinkTestOther', $this->user, EDIT_NEW );
			self::$otherItemId = $badge->getId();
		}

		$settings = clone WikibaseRepo::getSettings( $this->getServiceContainer() );
		$settings->setSetting( 'badgeItems', [
			self::$gaItemId->getSerialization() => '',
			self::$faItemId->getSerialization() => '',
			'Q99999' => '', // Just in case we have a wrong config
		] );
		$this->setService( 'WikibaseRepo.Settings', $settings );

		self::$hasSetup = true;
	}

	/**
	 * Replace badge item id placeholders in expected results
	 *
	 * @param array $value
	 * @return array
	 */
	private function exceptionPlaceholder( array $value ) {
		foreach ( $value as &$site ) {
			if ( !isset( $site['badges'] ) ) {
				continue;
			}
			foreach ( $site['badges'] as &$dummy ) {
				if ( $dummy === '{gaItem}' ) {
					$dummy = self::$gaItemId->getSerialization();
				} elseif ( $dummy === '{faItem}' ) {
					$dummy = self::$faItemId->getSerialization();
				} elseif ( $dummy === '{otherItem}' ) {
					$dummy = self::$otherItemId->getSerialization();
				}
			}
		}
		return $value;
	}

	public function testBadgePassedToNormalizer() {

		$params = [
			'action' => 'wbsetsitelink',
			'id' => EntityTestHelper::getId( 'Leipzig' ),
			'linksite' => 'dewiki',
			'linktitle' => 'leipzig',
			'badges' => self::$gaItemId->getSerialization(),
		];

		$pageNormalizerMock = $this->createMock( SiteLinkPageNormalizer::class );
		$pageNormalizerMock->expects( $this->once() )->method( 'normalize' )->with(
			$this->anything(),
			$this->equalTo( $params['linktitle'] ),
			$this->equalTo( [ $params['badges'] ] )
		)->willReturnArgument( 1 );
		$this->setService( 'WikibaseRepo.SiteLinkPageNormalizer', $pageNormalizerMock );

		$this->doApiRequestWithToken( $params );
	}

	/**
	 * @dataProvider provideData
	 */
	public function testSetSiteLink( array $params, array $expected ) {
		// -- set any defaults ------------------------------------
		if ( array_key_exists( 'handle', $params ) ) {
			$params['id'] = EntityTestHelper::getId( $params['handle'] );
			unset( $params['handle'] );
		}
		$params['action'] = 'wbsetsitelink';

		// Replace the placeholder item ids in the API params
		if ( isset( $params['badges'] ) ) {
			$params['badges'] = str_replace(
				[ '{gaItem}', '{faItem}', '{otherItem}' ],
				[ self::$gaItemId->getSerialization(), self::$faItemId->getSerialization(), self::$otherItemId->getSerialization() ],
				$params['badges']
			);
		}

		// -- do the request --------------------------------------------------
		[ $result ] = $this->doApiRequestWithToken( $params );

		//@todo all of the below is very similar to the code in ModifyTermTestCase
		//This might be able to go in the same place

		// Replace the placeholder item ids in the expected results... this sucks
		if ( is_array( $expected['value'] ) ) {
			$expected['value'] = $this->exceptionPlaceholder( $expected['value'] );
		}

		// -- check the result ------------------------------------------------
		$this->assertArrayHasKey( 'success', $result, "Missing 'success' marker in response." );
		$this->assertResultHasEntityType( $result );
		$this->assertArrayHasKey( 'entity', $result, "Missing 'entity' section in response." );
		$this->assertArrayHasKey( 'lastrevid', $result['entity'], 'entity should contain lastrevid key' );

		// -- check the result only has our changed data (if any)  ------------
		$linkSite = $params['linksite'];
		$siteLinks = $result['entity']['sitelinks'];

		$this->assertCount( 1, $siteLinks,
			"Entity return contained more than a single site"
		);

		$this->assertArrayHasKey( $linkSite, $siteLinks,
			"Entity doesn't return expected site"
		);

		$siteLink = $siteLinks[$linkSite];

		$this->assertSame( $linkSite, $siteLink['site'],
			"Returned incorrect site"
		);

		if ( array_key_exists( $linkSite, $expected['value'] ) ) {
			$expectedSiteLink = $expected['value'][$linkSite];

			$this->assertArrayHasKey( 'url', $siteLink );
			$this->assertSame( $expectedSiteLink['title'], $siteLink['title'],
				"Returned incorrect title"
			);

			$this->assertArrayHasKey( 'badges', $siteLink );
			$this->assertArrayEquals( $expectedSiteLink['badges'], $siteLink['badges'] );
		} elseif ( empty( $expected['value'] ) ) {
			$this->assertArrayHasKey( 'removed', $siteLink,
				"Entity doesn't return expected 'removed' marker"
			);
		}

		// -- check any warnings ----------------------------------------------
		if ( array_key_exists( 'warning', $expected ) ) {
			$this->assertArrayHasKey( 'warnings', $result, "Missing 'warnings' section in response." );
			$this->assertSame( $expected['warning'], $result['warnings']['messages']['0']['name'] );
			$this->assertArrayHasKey( 'html', $result['warnings']['messages'] );
		}

		// -- check item in database -------------------------------------------
		$dbEntity = $this->loadEntity( $result['entity']['id'] );
		$expectedInDb = count( $expected['value'] );
		if ( array_key_exists( 'indb', $expected ) ) {
			$expectedInDb = $expected['indb'];
		}
		$this->assertArrayHasKey( 'sitelinks', $dbEntity );
		$this->assertCount( $expectedInDb, $dbEntity['sitelinks'] );
		$this->assertContainsAllSiteLinks( $expected['value'], $dbEntity['sitelinks'] );

		// -- check the edit summary --------------------------------------------
		if ( !array_key_exists( 'warning', $expected ) || $expected['warning'] != 'edit-no-change' ) {
			$this->assertRevisionSummary( [ 'wbsetsitelink', $params['linksite'] ], $result['entity']['lastrevid'] );
			if ( array_key_exists( 'summary', $params ) ) {
				$this->assertRevisionSummary( "/{$params['summary']}/", $result['entity']['lastrevid'] );
			}
		}
	}

	public function testSetSiteLinkWithTag() {
		$this->assertCanTagSuccessfulRequest( [
			'action' => 'wbsetsitelink',
			'site' => 'dewiki',
			'title' => 'Berlin',
			'linksite' => 'nowiki',
			'linktitle' => 'berlin',
		] );
	}

	/**
	 * @param array[] $expectedSiteLinks
	 * @param array[] $dbSiteLinks
	 */
	private function assertContainsAllSiteLinks( array $expectedSiteLinks, array $dbSiteLinks ) {
		foreach ( $expectedSiteLinks as $site => $expectedSiteLink ) {
			$this->assertArrayHasKey( $site, $dbSiteLinks );
			$dbSiteLink = $dbSiteLinks[$site];

			$this->assertArrayHasKey( 'title', $dbSiteLink );
			$this->assertIsString( $dbSiteLink['title'] );
			$this->assertSame( $expectedSiteLink['title'], $dbSiteLink['title'] );

			$this->assertArrayHasKey( 'badges', $dbSiteLink );
			$this->assertIsArray( $dbSiteLink['badges'] );
			$this->assertArrayEquals( $expectedSiteLink['badges'], $dbSiteLink['badges'] );
		}
	}

	/**
	 * @dataProvider provideExceptionData
	 */
	public function testSetSiteLinkExceptions( array $params, array $expected, $token = true ) {
		// -- set any defaults ------------------------------------
		$params['action'] = 'wbsetsitelink';

		// Replace the placeholder item ids in the API params
		if ( isset( $params['badges'] ) ) {
			$params['badges'] = str_replace(
				[ '{gaItem}', '{faItem}', '{otherItem}' ],
				[ self::$gaItemId->getSerialization(), self::$faItemId->getSerialization(), self::$otherItemId->getSerialization() ],
				$params['badges']
			);
		}

		$this->doTestQueryExceptions( $params, $expected['exception'], null, $token );
	}

	/**
	 * @dataProvider provideBadBadgeData
	 */
	public function testBadBadges( array $params ) {
		// -- set any defaults ------------------------------------
		$params['action'] = 'wbsetsitelink';

		// Replace the placeholder item ids in the API params
		if ( isset( $params['badges'] ) ) {
			$params['badges'] = str_replace(
				[ '{gaItem}', '{faItem}', '{otherItem}' ],
				[ self::$gaItemId->getSerialization(), self::$faItemId->getSerialization(), self::$otherItemId->getSerialization() ],
				$params['badges']
			);
		}

		[ $result ] = $this->doApiRequestWithToken( $params );

		$warning = $result['warnings']['wbsetsitelink']['warnings'];
		$this->assertStringContainsString( 'Unrecognized value for parameter "badges"', $warning );
	}

	public function testUserCanSetSiteLinkWhenTheyHaveSufficientPermission() {
		$userWithAllPermissions = $this->createUserWithGroup( 'all-permission' );

		$this->setMwGlobals( 'wgGroupPermissions', [
			'all-permission' => [ 'edit' => true ],
			'*' => [ 'read' => true, 'writeapi' => true ],
		] );

		$newItem = $this->createItemUsing( $userWithAllPermissions );

		[ $result ] = $this->doApiRequestWithToken(
			$this->getSetSiteLinkRequestParams( $newItem->getId() ),
			null,
			$userWithAllPermissions
		);

		$this->assertSame( 1, $result['success'] );
	}

	public function testUserCannotSetSiteLinkWhenTheyLackPermission() {
		$userWithInsufficientPermissions = $this->createUserWithGroup( 'no-permission' );
		$userWithAllPermissions = $this->createUserWithGroup( 'all-permission' );

		$this->setMwGlobals( 'wgGroupPermissions', [
			'no-permission' => [ 'edit' => false ],
			'all-permission' => [ 'edit' => true ],
			'*' => [ 'read' => true, 'writeapi' => true ],
		] );

		MediaWikiServices::getInstance()->resetServiceForTesting( 'PermissionManager' );

		// And an item
		$newItem = $this->createItemUsing( $userWithAllPermissions );

		// Then the request is denied
		$expected = [
			'type' => ApiUsageException::class,
			'code' => 'permissiondenied',
		];

		MediaWikiServices::getInstance()->getPermissionManager()->invalidateUsersRightsCache(
			$userWithAllPermissions
		);
		MediaWikiServices::getInstance()->getPermissionManager()->invalidateUsersRightsCache(
			$userWithInsufficientPermissions
		);

		$this->doTestQueryExceptions(
			$this->getSetSiteLinkRequestParams( $newItem->getId() ),
			$expected,
			$userWithInsufficientPermissions
		);
	}

	public function testUserCanCreateItemWithSiteLinkWhenTheyHaveSufficientPermissions() {
		$userWithAllPermissions = $this->createUserWithGroup( 'all-permission' );

		$this->setMwGlobals( 'wgGroupPermissions', [
			'all-permission' => [ 'edit' => true, 'createpage' => true ],
			'*' => [ 'read' => true, 'writeapi' => true ],
		] );

		[ $result ] = $this->doApiRequestWithToken(
			$this->getCreateItemAndSetSiteLinkRequestParams(),
			null,
			$userWithAllPermissions
		);

		$this->assertSame( 1, $result['success'] );
		$this->assertSame( 'Another Cool Page', $result['entity']['sitelinks']['enwiki']['title'] );
	}

	public function testUserCannotCreateItemWhenTheyLackPermission() {
		$userWithInsufficientPermissions = $this->createUserWithGroup( 'no-permission' );

		$this->setMwGlobals( 'wgGroupPermissions', [
			'no-permission' => [ 'createpage' => false ],
			'*' => [ 'read' => true, 'edit' => true, 'writeapi' => true ],
		] );

		MediaWikiServices::getInstance()->resetServiceForTesting( 'PermissionManager' );

		// Then the request is denied
		$expected = [
			'type' => ApiUsageException::class,
			'code' => 'permissiondenied',
		];

		$this->doTestQueryExceptions(
			$this->getCreateItemAndSetSiteLinkRequestParams(),
			$expected,
			$userWithInsufficientPermissions
		);
	}

	/**
	 * @param User $user
	 *
	 * @return Item
	 */
	private function createItemUsing( User $user ) {
		$store = $this->getEntityStore();

		$itemRevision = $store->saveEntity( new Item(), 'SetSiteLinkTest', $user, EDIT_NEW );
		return $itemRevision->getEntity();
	}

	/**
	 * @param string $groupName
	 *
	 * @return User
	 */
	private function createUserWithGroup( $groupName ) {
		return $this->getTestUser( [ 'wbeditor', $groupName ] )->getUser();
	}

	private function getSetSiteLinkRequestParams( ItemId $id ) {
		return [
			'action' => 'wbsetsitelink',
			'id' => $id->getSerialization(),
			'linksite' => 'enwiki',
			'linktitle' => 'Some Cool Page',
		];
	}

	private function getCreateItemAndSetSiteLinkRequestParams() {
		return [
			'action' => 'wbsetsitelink',
			'new' => 'item',
			'linksite' => 'enwiki',
			'linktitle' => 'Another Cool Page',
		];
	}

}
