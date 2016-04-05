<?php

namespace Wikibase\Test\Repo\Api;

use UsageException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Api\SetSiteLink
 * @covers Wikibase\Repo\Api\ModifyEntity
 *
 * @license GPL-2.0+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 * @author Addshore
 * @author Michał Łazowik
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group SetSiteLinkTest
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

	private static $hasSetup;

	/* @var ItemId */
	private static $gaItemId;
	/* @var ItemId */
	private static $faItemId;
	/* @var ItemId */
	private static $otherItemId;

	public function provideData() {
		return array(
			array( //0 set new link using id
				'p' => array(
					'handle' => 'Leipzig',
					'linksite' => 'dewiki',
					'linktitle' => 'leipzig',
					'badges' => '{gaItem}|{faItem}'
				),
				'e' => array(
					'value' => array( 'dewiki' => array(
						'title' => 'Leipzig',
						'badges' => array( '{gaItem}', '{faItem}' )
					) )
				)
			),
			array( //1 set new link using sitelink
				'p' => array(
					'site' => 'dewiki',
					'title' => 'Berlin',
					'linksite' => 'nowiki',
					'linktitle' => 'berlin'
				),
				'e' => array(
					'value' => array( 'nowiki' => array(
						'title' => 'Berlin',
						'badges' => []
					) ),
					'indb' => 5
				)
			),
			array( //2 modify link using id
				'p' => array(
					'handle' => 'Leipzig',
					'linksite' => 'dewiki',
					'linktitle' => 'Leipzig_Two',
					'badges' => ''
				),
				'e' => array(
					'value' => array( 'dewiki' => array(
						'title' => 'Leipzig Two',
						'badges' => []
					) )
				)
			),
			array( //3 modify link using sitelink
				'p' => array(
					'site' => 'dewiki',
					'title' => 'Berlin',
					'linksite' => 'nowiki',
					'linktitle' => 'Berlin_Two'
				),
				'e' => array(
					'value' => array( 'nowiki' => array(
						'title' => 'Berlin Two',
						'badges' => []
					) ),
					'indb' => 5
				)
			),
			array( //4 remove link using id (with a summary)
				'p' => array(
					'handle' => 'Leipzig',
					'linksite' => 'dewiki',
					'linktitle' => '',
					'summary' => 'WooSummary'
				),
				'e' => array( 'value' => [] ) ),
			array( //5 remove link using sitelink
				'p' => array(
					'site' => 'dewiki',
					'title' => 'Berlin',
					'linksite' => 'nowiki',
					'linktitle' => ''
				),
				'e' => array( 'value' => [], 'indb' => 4 ) ),
			array( //6 add badges to existing sitelink
				'p' => array(
					'site' => 'dewiki',
					'title' => 'Berlin',
					'linksite' => 'dewiki',
					'linktitle' => 'Berlin',
					'badges' => '{faItem}|{gaItem}'
				),
				'e' => array(
					'value' => array( 'dewiki' => array(
						'title' => 'Berlin',
						'badges' => array( '{faItem}', '{gaItem}' )
					) ),
					'indb' => 4
				)
			),
			array( //7 add duplicate badges to existing sitelink
				'p' => array(
					'site' => 'dewiki',
					'title' => 'Berlin',
					'linksite' => 'dewiki',
					'linktitle' => 'Berlin',
					'badges' => '{gaItem}|{gaItem}|{faItem}|{gaItem}'
				),
				'e' => array(
					'value' => array( 'dewiki' => array(
						'title' => 'Berlin',
						'badges' => array( '{gaItem}', '{faItem}' )
					) ),
					'indb' => 4
				)
			),
			array( //8 no change
				'p' => array(
					'site' => 'dewiki',
					'title' => 'Berlin',
					'linksite' => 'dewiki',
					'linktitle' => 'Berlin',
					'badges' => '{gaItem}|{faItem}'
				),
				'e' => array(
					'value' => array( 'dewiki' => array(
						'title' => 'Berlin',
						'badges' => array( '{gaItem}', '{faItem}' )
					) ),
					'indb' => 4
				)
			),
			array( //9 change only title, badges should be intact
				'p' => array(
					'site' => 'dewiki',
					'title' => 'Berlin',
					'linksite' => 'dewiki',
					'linktitle' => 'Berlin_Two'
				),
				'e' => array(
					'value' => array( 'dewiki' => array(
						'title' => 'Berlin Two',
						'badges' => array( '{gaItem}', '{faItem}' )
					) ),
					'indb' => 4
				)
			),
			array( //10 change both title and badges
				'p' => array(
					'site' => 'dewiki',
					'title' => 'Berlin Two',
					'linksite' => 'dewiki',
					'linktitle' => 'Berlin',
					'badges' => '{gaItem}'
				),
				'e' => array(
					'value' => array( 'dewiki' => array(
						'title' => 'Berlin',
						'badges' => array( '{gaItem}' )
					) ),
					'indb' => 4
				)
			),
			array( //11 change only badges, title intact
				'p' => array(
					'site' => 'dewiki',
					'title' => 'Berlin',
					'linksite' => 'dewiki',
					'badges' => '{gaItem}|{faItem}'
				),
				'e' => array(
					'value' => array( 'dewiki' => array(
						'title' => 'Berlin',
						'badges' => array( '{gaItem}', '{faItem}' )
					) ),
					'indb' => 4
				)
			),
			array( //12 set new link using id (without badges)
				'p' => array(
					'handle' => 'Berlin',
					'linksite' => 'svwiki',
					'linktitle' => 'Berlin'
				),
				'e' => array(
					'value' => array( 'svwiki' => array(
						'title' => 'Berlin',
						'badges' => []
					) ),
					'indb' => 5
				)
			),
			array( //13 delete link by not providing neither title nor badges
				'p' => array( 'handle' => 'Berlin', 'linksite' => 'svwiki' ),
				'e' => array( 'value' => [], 'indb' => 4 )
			),
		);
	}

	public function provideExceptionData() {
		return array(
			array( //0 badtoken
				'p' => array(
					'site' => 'dewiki',
					'title' => 'Berlin',
					'linksite' => 'svwiki',
					'linktitle' => 'testSetSiteLinkWithNoToken'
				),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'notoken',
					'message' => 'The token parameter must be set'
				) )
			),
			array( //1
				'p' => array(
					'site' => 'dewiki',
					'title' => 'Berlin',
					'linksite' => 'svwiki',
					'linktitle' => 'testSetSiteLinkWithBadToken',
					'token' => '88888888888888888888888888888888+\\'
				),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'badtoken',
					'message' => 'Invalid token'
				) )
			),
			array( //2 testSetSiteLinkWithNoId
				'p' => array(
					'linksite' => 'enwiki',
					'linktitle' => 'testSetSiteLinkWithNoId'
				),
				'e' => array( 'exception' => array( 'type' => UsageException::class ) ) ),
			array( //3 testSetSiteLinkWithBadId
				'p' => array(
					'id' => 123456789,
					'linksite' => 'enwiki',
					'linktitle' => 'testSetSiteLinkWithNoId'
				),
				'e' => array( 'exception' => array( 'type' => UsageException::class ) ) ),
			array( //4 testSetSiteLinkWithBadSite
				'p' => array(
					'site' => 'dewiktionary',
					'title' => 'Berlin',
					'linksite' => 'enwiki',
					'linktitle' => 'Berlin'
				),
				'e' => array( 'exception' => array( 'type' => UsageException::class ) ) ),
			array( //5 testSetSiteLinkWithBadTitle
				'p' => array(
					'site' => 'dewiki',
					'title' => 'BadTitle_de',
					'linksite' => 'enwiki',
					'linktitle' => 'BadTitle_en'
				),
				'e' => array( 'exception' => array( 'type' => UsageException::class ) ) ),
			array( //6 testSetSiteLinkWithBadTargetSite
				'p' => array(
					'site' => 'dewiki',
					'title' => 'Berlin',
					'linksite' => 'enwiktionary',
					'linktitle' => 'Berlin'
				),
				'e' => array( 'exception' => array( 'type' => UsageException::class ) ) ),
			array( //7 badge item does not exist
				'p' => array(
					'site' => 'enwiki',
					'title' => 'Berlin',
					'linksite' => 'enwiki',
					'linktitle' => 'Berlin',
					'badges' => 'Q99999|{faItem}'
				),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'no-such-entity'
				) )
			),
			array( //8 no sitelink - cannot change badges
				'p' => array(
					'site' => 'enwiki',
					'title' => 'Berlin',
					'linksite' => 'svwiki',
					'badges' => '{gaItem}|{faItem}'
				),
				'e' => array( 'exception' => array(
					'type' => UsageException::class,
					'code' => 'no-such-sitelink'
				) )
			),
		);
	}

	public function provideBadBadgeData() {
		return array(
			array( //0 bad badge id
				array( 'site' => 'enwiki',
					'title' => 'Berlin',
					'linksite' => 'enwiki',
					'linktitle' => 'Berlin',
					'badges' => 'abc|{faItem}'
				),
			),
			array( //1 badge id is not an item id
				array( 'site' => 'enwiki',
					'title' => 'Berlin',
					'linksite' => 'enwiki',
					'linktitle' => 'Berlin',
					'badges' => 'P2|{faItem}'
				),
			),
			array( //2 badge id is not specified
				array( 'site' => 'enwiki',
					'title' => 'Berlin',
					'linksite' => 'enwiki',
					'linktitle' => 'Berlin',
					'badges' => '{faItem}|{otherItem}'
				)
			)
		);
	}

	protected function setUp() {
		parent::setUp();

		if ( !isset( self::$hasSetup ) ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$store = $wikibaseRepo->getEntityStore();

			$this->initTestEntities( array( 'StringProp', 'Leipzig', 'Berlin' ) );

			$badge = new Item();
			$store->saveEntity( $badge, 'SetSiteLinkTestGA', $GLOBALS['wgUser'], EDIT_NEW );
			self::$gaItemId = $badge->getId();

			$badge = new Item();
			$store->saveEntity( $badge, 'SetSiteLinkTestFA', $GLOBALS['wgUser'], EDIT_NEW );
			self::$faItemId = $badge->getId();

			$badge = new Item();
			$store->saveEntity( $badge, 'SetSiteLinkTestOther', $GLOBALS['wgUser'], EDIT_NEW );
			self::$otherItemId = $badge->getId();

			$wikibaseRepo->getSettings()->setSetting( 'badgeItems', array(
				self::$gaItemId->getSerialization() => '',
				self::$faItemId->getSerialization() => '',
				'Q99999' => '', // Just in case we have a wrong config
			) );
		}
		self::$hasSetup = true;
	}

	/**
	 * Replace badge item id placeholders in expected results
	 *
	 * @param array $value
	 * @return array
	 */
	private function expectionPlaceholder( array $value ) {
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
				array( '{gaItem}', '{faItem}', '{otherItem}' ),
				array( self::$gaItemId->getSerialization(), self::$faItemId->getSerialization(), self::$otherItemId->getSerialization() ),
				$params['badges']
			);
		}

		// -- do the request --------------------------------------------------
		list( $result, , ) = $this->doApiRequestWithToken( $params );

		//@todo all of the below is very similar to the code in ModifyTermTestCase
		//This might be able to go in the same place

		// Replace the placeholder item ids in the expected results... this sucks
		if ( is_array( $expected['value'] ) ) {
			$expected['value'] = $this->expectionPlaceholder( $expected['value'] );
		}

		// -- check the result ------------------------------------------------
		$this->assertArrayHasKey( 'success', $result, "Missing 'success' marker in response." );
		$this->assertResultHasEntityType( $result );
		$this->assertArrayHasKey( 'entity', $result, "Missing 'entity' section in response." );
		$this->assertArrayHasKey( 'lastrevid', $result['entity'], 'entity should contain lastrevid key' );

		// -- check the result only has our changed data (if any)  ------------
		$linkSite = $params['linksite'];
		$siteLinks = $result['entity']['sitelinks'];

		$this->assertEquals( 1, count( $siteLinks ),
			"Entity return contained more than a single site"
		);

		$this->assertArrayHasKey( $linkSite, $siteLinks,
			"Entity doesn't return expected site"
		);

		$siteLink = $siteLinks[$linkSite];

		$this->assertEquals( $linkSite, $siteLink['site'],
			"Returned incorrect site"
		);

		if ( array_key_exists( $linkSite, $expected['value'] ) ) {
			$expectedSiteLink = $expected['value'][$linkSite];

			$this->assertArrayHasKey( 'url', $siteLink );
			$this->assertEquals( $expectedSiteLink['title'], $siteLink['title'],
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
			$this->assertEquals( $expected['warning'], $result['warnings']['messages']['0']['name'] );
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
		if ( ! array_key_exists( 'warning', $expected ) || $expected['warning'] != 'edit-no-change' ) {
			$this->assertRevisionSummary( array( 'wbsetsitelink', $params['linksite'] ), $result['entity']['lastrevid'] );
			if ( array_key_exists( 'summary', $params ) ) {
				$this->assertRevisionSummary( "/{$params['summary']}/", $result['entity']['lastrevid'] );
			}
		}
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
			$this->assertInternalType( 'string', $dbSiteLink['title'] );
			$this->assertSame( $expectedSiteLink['title'], $dbSiteLink['title'] );

			$this->assertArrayHasKey( 'badges', $dbSiteLink );
			$this->assertInternalType( 'array', $dbSiteLink['badges'] );
			$this->assertArrayEquals( $expectedSiteLink['badges'], $dbSiteLink['badges'] );
		}
	}

	/**
	 * @dataProvider provideExceptionData
	 */
	public function testSetSiteLinkExceptions( array $params, array $expected ) {
		// -- set any defaults ------------------------------------
		$params['action'] = 'wbsetsitelink';

		// Replace the placeholder item ids in the API params
		if ( isset( $params['badges'] ) ) {
			$params['badges'] = str_replace(
				array( '{gaItem}', '{faItem}', '{otherItem}' ),
				array( self::$gaItemId->getSerialization(), self::$faItemId->getSerialization(), self::$otherItemId->getSerialization() ),
				$params['badges']
			);
		}

		$this->doTestQueryExceptions( $params, $expected['exception'] );
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
				array( '{gaItem}', '{faItem}', '{otherItem}' ),
				array( self::$gaItemId->getSerialization(), self::$faItemId->getSerialization(), self::$otherItemId->getSerialization() ),
				$params['badges']
			);
		}

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$warning = $result['warnings']['wbsetsitelink']['warnings'];
		$this->assertContains( 'Unrecognized value for parameter \'badges\'', $warning );
	}

}
