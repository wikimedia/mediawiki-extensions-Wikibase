<?php

namespace Wikibase\Test;

use DatabaseBase;
use Title;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Client\Store\Sql\PagePropsEntityIdLookup;

/**
 * @covers Wikibase\Client\Store\Sql\PagePropsEntityIdLookup
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseStore
 * @group Database
 *
 * @licence GNU GPL v2+
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

	private function insertPageProps( DatabaseBase $db, $pageId, EntityId $entityId ) {
		$db->insert(
			'page_props',
			array(
				'pp_page' => $pageId,
				'pp_propname' => 'wikibase_item',
				'pp_value' => $entityId->getSerialization(),
			)
		);
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

		$expected = array(
			11 => $q11,
			22 => $q22
		);

		$lookup = new PagePropsEntityIdLookup( wfGetLB(), new BasicEntityIdParser() );
		$actual = $lookup->getEntityIds( array( $title22, $title99, $title11 ) );
		ksort( $actual );

		$this->assertEquals( $expected, $actual );
	}

}
