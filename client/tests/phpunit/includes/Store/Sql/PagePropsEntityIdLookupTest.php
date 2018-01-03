<?php

namespace Wikibase\Client\Tests\Store\Sql;

use Title;
use Wikibase\Client\Store\Sql\PagePropsEntityIdLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikimedia\Rdbms\IDatabase;

/**
 * @covers Wikibase\Client\Store\Sql\PagePropsEntityIdLookup
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseStore
 * @group Database
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class PagePropsEntityIdLookupTest extends \MediaWikiTestCase {

	protected function setUp() {
		$this->tablesUsed[] = 'page_props';
		parent::setUp();
	}

	private function makeTitle( $pageId ) {
		$title = Title::makeTitle( NS_MAIN, 'No' . $pageId );
		$title->resetArticleID( $pageId );

		return $title;
	}

	private function insertPageProps( IDatabase $db, $pageId, EntityId $entityId ) {
		$db->insert(
			'page_props',
			[
				'pp_page' => $pageId,
				'pp_propname' => 'wikibase_item',
				'pp_value' => $entityId->getSerialization(),
			]
		);
	}

	public function testGetEntityIdForTitle() {
		$db = wfGetDB( DB_MASTER );

		$title22 = $this->makeTitle( 22 );
		$title99 = $this->makeTitle( 99 );

		$q22 = new ItemId( 'Q22' );
		$this->insertPageProps( $db, 22, $q22 );

		$lookup = new PagePropsEntityIdLookup( wfGetLB(), new ItemIdParser() );
		$this->assertEquals( $q22, $lookup->getEntityIdForTitle( $title22 ) );
		$this->assertNull( $lookup->getEntityIdForTitle( $title99 ) );
	}

	public function testGetEntityIds() {
		$db = wfGetDB( DB_MASTER );

		$title11 = $this->makeTitle( 11 );
		$title22 = $this->makeTitle( 22 );
		$title99 = $this->makeTitle( 99 );

		$q11 = new ItemId( 'Q11' );
		$q22 = new ItemId( 'Q22' );

		$this->insertPageProps( $db, 11, $q11 );
		$this->insertPageProps( $db, 22, $q22 );

		$expected = [
			11 => $q11,
			22 => $q22
		];

		$lookup = new PagePropsEntityIdLookup( wfGetLB(), new ItemIdParser() );
		$actual = $lookup->getEntityIds( [ $title22, $title99, $title11 ] );
		ksort( $actual );

		$this->assertEquals( $expected, $actual );
	}

}
