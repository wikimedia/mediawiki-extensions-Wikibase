<?php

namespace Wikibase\Test\Api;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Api\SetSiteLink
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 * @author Adam Shorland
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

	public static function provideData() {
		$badgesCases1 =  array(
			array( //0 set new link using id
				'p' => array( 'handle' => 'Leipzig', 'linksite' => 'dewiki', 'linktitle' => 'leipzig', 'badges' => '{gaItem}|{faItem}' ),
				'e' => array( 'value' => array( 'dewiki' => array( 'title' => 'Leipzig', 'badges' => array( '{gaItem}', '{faItem}' ) ) ) ) ),
		);
		$basicCases = array(
			array( //1 set new link using sitelink
				'p' => array( 'site' => 'dewiki', 'title' => 'Berlin', 'linksite' => 'nowiki', 'linktitle' => 'berlin' ),
				'e' => array( 'value' => array( 'nowiki' => array( 'title' => 'Berlin', 'badges' => array() ) ), 'indb' => 5 ) ),
			array( //2 modify link using id
				'p' => array( 'handle' => 'Leipzig', 'linksite' => 'dewiki', 'linktitle' => 'Leipzig_Two', 'badges' => '' ),
				'e' => array( 'value' => array( 'dewiki' => array( 'title' => 'Leipzig Two', 'badges' => array() ) ) ) ),
			array( //3 modify link using sitelink
				'p' => array( 'site' => 'dewiki', 'title' => 'Berlin', 'linksite' => 'nowiki', 'linktitle' => 'Berlin_Two' ),
				'e' => array( 'value' => array( 'nowiki' => array( 'title' => 'Berlin Two', 'badges' => array() ) ), 'indb' => 5 ) ),
			array( //4 remove link using id (with a summary)
				'p' => array( 'handle' => 'Leipzig', 'linksite' => 'dewiki', 'linktitle' => '', 'summary' => 'WooSummary' ),
				'e' => array( 'value' => array() ) ),
			array( //5 remove link using sitelink
				'p' => array( 'site' => 'dewiki', 'title' => 'Berlin', 'linksite' => 'nowiki', 'linktitle' => '' ),
				'e' => array( 'value' => array(), 'indb' => 4 ) ),
		);
		$badgesCases2 = array(
			array( //6 add badges to existing sitelink
				'p' => array( 'site' => 'dewiki', 'title' => 'Berlin', 'linksite' => 'dewiki', 'linktitle' => 'Berlin', 'badges' => '{faItem}|{gaItem}' ),
				'e' => array( 'value' => array( 'dewiki' => array( 'title' => 'Berlin', 'badges' => array( '{faItem}', '{gaItem}' ) ) ), 'indb' => 4 ) ),
			array( //7 add duplicate badges to existing sitelink
				'p' => array( 'site' => 'dewiki', 'title' => 'Berlin', 'linksite' => 'dewiki', 'linktitle' => 'Berlin', 'badges' => '{gaItem}|{gaItem}|{faItem}|{gaItem}' ),
				'e' => array( 'value' => array( 'dewiki' => array( 'title' => 'Berlin', 'badges' => array( '{gaItem}', '{faItem}' ) ) ), 'indb' => 4 ) ),
			array( //8 no change
				'p' => array( 'site' => 'dewiki', 'title' => 'Berlin', 'linksite' => 'dewiki', 'linktitle' => 'Berlin', 'badges' => '{gaItem}|{faItem}' ),
				'e' => array( 'value' => array( 'dewiki' => array( 'title' => 'Berlin', 'badges' => array( '{gaItem}', '{faItem}' ) ) ), 'indb' => 4 ) ),
			array( //9 change only title, badges should be intact
				'p' => array( 'site' => 'dewiki', 'title' => 'Berlin', 'linksite' => 'dewiki', 'linktitle' => 'Berlin_Two' ),
				'e' => array( 'value' => array( 'dewiki' => array( 'title' => 'Berlin Two', 'badges' => array( '{gaItem}', '{faItem}' ) ) ), 'indb' => 4 ) ),
			array( //10 change both title and badges
				'p' => array( 'site' => 'dewiki', 'title' => 'Berlin Two', 'linksite' => 'dewiki', 'linktitle' => 'Berlin', 'badges' => '{gaItem}' ),
				'e' => array( 'value' => array( 'dewiki' => array( 'title' => 'Berlin', 'badges' => array( '{gaItem}' ) ) ), 'indb' => 4 ) ),
			array( //11 change only badges, title intact
				'p' => array( 'site' => 'dewiki', 'title' => 'Berlin', 'linksite' => 'dewiki', 'badges' => '{gaItem}|{faItem}' ),
				'e' => array( 'value' => array( 'dewiki' => array( 'title' => 'Berlin', 'badges' => array( '{gaItem}', '{faItem}' ) ) ), 'indb' => 4 ) ),
			array( //12 set new link using id (without badges)
				'p' => array( 'handle' => 'Berlin', 'linksite' => 'svwiki', 'linktitle' => 'Berlin' ),
				'e' => array( 'value' => array( 'svwiki' => array( 'title' => 'Berlin', 'badges' => array() ) ), 'indb' => 5 ) ),
			array( //13 delete link by not providing neither title nor badges
				'p' => array( 'handle' => 'Berlin', 'linksite' => 'svwiki' ),
				'e' => array( 'value' => array(), 'indb' => 4 ) ),
		);

		// Experimental tests for setting of badges in api
		// @todo remove experimental once enabled remove this and return all cases
		if ( defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES ) {
			return array_merge( $badgesCases1, $basicCases, $badgesCases2 );
		} else {
			return $basicCases;
		}
	}

	public static function provideExceptionData() {
		$basicCases = array(
			array( //0 badtoken
				'p' => array( 'site' => 'dewiki', 'title' => 'Berlin', 'linksite' => 'svwiki', 'linktitle' => 'testSetLiteLinkWithNoToken' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'badtoken', 'message' => 'loss of session data' ) ) ),
			array( //1 testSetLiteLinkWithNoId
				'p' => array( 'linksite' => 'enwiki', 'linktitle' => 'testSetLiteLinkWithNoId' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException' ) ) ),
			array( //2 testSetLiteLinkWithBadId
				'p' => array( 'id' => 123456789, 'linksite' => 'enwiki', 'linktitle' => 'testSetLiteLinkWithNoId' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException' ) ) ),
			array( //3 testSetLiteLinkWithBadSite
				'p' => array( 'site' => 'dewiktionary', 'title' => 'Berlin', 'linksite' => 'enwiki', 'linktitle' => 'Berlin' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException' ) ) ),
			array( //4 testSetLiteLinkWithBadTitle
				'p' => array( 'site' => 'dewiki', 'title' => 'BadTitle_de', 'linksite' => 'enwiki', 'linktitle' => 'BadTitle_en' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException' ) ) ),
			array( //5 testSetLiteLinkWithBadTargetSite
				'p' => array( 'site' => 'dewiki', 'title' => 'Berlin', 'linksite' => 'enwiktionary', 'linktitle' => 'Berlin' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException' ) ) ),
		);
		$badgesCases = array(
			array( //6 bad badge id
				'p' => array( 'site' => 'enwiki', 'title' => 'Berlin', 'linksite' => 'enwiki', 'linktitle' => 'Berlin', 'badges' => 'abc|{faItem}' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'no-such-entity-id' ) ) ),
			array( //7 badge id is not an item id
				'p' => array( 'site' => 'enwiki', 'title' => 'Berlin', 'linksite' => 'enwiki', 'linktitle' => 'Berlin', 'badges' => 'P2|{faItem}' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'not-item' ) ) ),
			array( //8 badge item does not exist
				'p' => array( 'site' => 'enwiki', 'title' => 'Berlin', 'linksite' => 'enwiki', 'linktitle' => 'Berlin', 'badges' => 'Q99999|{faItem}' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'no-such-entity' ) ) ),
			array( //9 badge id is not specified
				'p' => array( 'site' => 'enwiki', 'title' => 'Berlin', 'linksite' => 'enwiki', 'linktitle' => 'Berlin', 'badges' => '{faItem}|{otherItem}' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'not-badge' ) ) ),
			array( //10 no sitelink - cannot change badges
				'p' => array( 'site' => 'enwiki', 'title' => 'Berlin', 'linksite' => 'svwiki', 'badges' => '{gaItem}|{faItem}' ),
				'e' => array( 'exception' => array( 'type' => 'UsageException', 'code' => 'no-such-sitelink' ) ) ),
		);

		// Experimental tests for setting of badges in api
		// @todo remove experimental once enabled remove this and return all cases
		if ( defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES ) {
			return array_merge( $basicCases, $badgesCases );
		} else {
			return $basicCases;
		}
	}

	public function setup() {
		parent::setup();

		if ( !isset( self::$hasSetup ) ) {
			$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

			$this->initTestEntities( array( 'Leipzig', 'Berlin' ) );

			$badge = Item::newEmpty();
			$store->saveEntity( $badge, 'SetSiteLinkTestGA', $GLOBALS['wgUser'], EDIT_NEW );
			self::$gaItemId = $badge->getId();

			$badge = Item::newEmpty();
			$store->saveEntity( $badge, 'SetSiteLinkTestFA', $GLOBALS['wgUser'], EDIT_NEW );
			self::$faItemId = $badge->getId();

			$badge = Item::newEmpty();
			$store->saveEntity( $badge, 'SetSiteLinkTestOther', $GLOBALS['wgUser'], EDIT_NEW );
			self::$otherItemId = $badge->getId();

			WikibaseRepo::getDefaultInstance()->getSettings()->setSetting( 'badgeItems', array(
				self::$gaItemId->getPrefixedId() => '',
				self::$faItemId->getPrefixedId() => '',
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
	private function expectionPlaceholder( $value ) {
		foreach( $value as &$site ) {
			if ( !isset( $site['badges'] ) ) {
					continue;
			}
			foreach( $site['badges'] as &$dummy ) {
				if ( $dummy === '{gaItem}' ) {
					$dummy = self::$gaItemId->getPrefixedId();
				} elseif ( $dummy === '{faItem}' ) {
					$dummy = self::$faItemId->getPrefixedId();
				} elseif ( $dummy === '{otherItem}' ) {
					$dummy = self::$otherItemId->getPrefixedId();
				}
			}
		}
		return $value;
	}

	/**
	 * @dataProvider provideData
	 */
	public function testSetLiteLink( $params, $expected ) {
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
				array( self::$gaItemId->getPrefixedId(), self::$faItemId->getPrefixedId(), self::$otherItemId->getPrefixedId() ),
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
		$sitelinks = $result['entity']['sitelinks'];

		$this->assertEquals( 1, count( $sitelinks ),
			"Entity return contained more than a single site"
		);

		$this->assertArrayHasKey( $linkSite, $sitelinks,
			"Entity doesn't return expected site"
		);

		$sitelink = $sitelinks[$linkSite];

		$this->assertEquals( $linkSite, $sitelink['site'],
			"Returned incorrect site"
		);

		if ( array_key_exists( $linkSite, $expected['value'] ) ) {
			$expSitelink = $expected['value'][ $linkSite ];

			$this->assertArrayHasKey( 'url', $sitelink );
			$this->assertEquals( $expSitelink['title'], $sitelink['title'],
				"Returned incorrect title"
			);

			$this->assertArrayHasKey( 'badges', $sitelink );
			$this->assertEquals( $expSitelink['badges'], $sitelink['badges'],
				"Returned incorrect badges"
			);
		} else if ( empty( $expected['value'] ) ) {
			$this->assertArrayHasKey( 'removed', $sitelink,
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
		if ( $expectedInDb ) {
			$this->assertArrayHasKey( 'sitelinks', $dbEntity );

			foreach ( array( 'title', 'badges' ) as $prop ) {
				$dbSitelinks = self::flattenArray( $dbEntity['sitelinks'], 'site', $prop );
				$this->assertEquals( $expectedInDb, count( $dbSitelinks ) );
				foreach ( $expected['value'] as $valueSite => $value ) {
					$this->assertArrayHasKey( $valueSite, $dbSitelinks );
					$this->assertEquals( $value[$prop], $dbSitelinks[$valueSite],
						"'$prop' value is not correct"
					);
				}
			}
		} else {
			$this->assertArrayNotHasKey( 'sitelinks', $dbEntity );
		}

		// -- check the edit summary --------------------------------------------
		if ( ! array_key_exists( 'warning', $expected ) || $expected['warning'] != 'edit-no-change' ) {
			$this->assertRevisionSummary( array( 'wbsetsitelink', $params['linksite'] ), $result['entity']['lastrevid'] );
			if ( array_key_exists( 'summary', $params ) ) {
				$this->assertRevisionSummary( "/{$params['summary']}/", $result['entity']['lastrevid'] );
			}
		}
	}

	/**
	 * @dataProvider provideExceptionData
	 */
	public function testSetSiteLinkExceptions( $params, $expected ) {
		// -- set any defaults ------------------------------------
		$params['action'] = 'wbsetsitelink';

		// Replace the placeholder item ids in the API params
		if ( isset( $params['badges'] ) ) {
			$params['badges'] = str_replace(
				array( '{gaItem}', '{faItem}', '{otherItem}' ),
				array( self::$gaItemId->getPrefixedId(), self::$faItemId->getPrefixedId(), self::$otherItemId->getPrefixedId() ),
				$params['badges']
			);
		}

		$this->doTestQueryExceptions( $params, $expected['exception'] );
	}
}

