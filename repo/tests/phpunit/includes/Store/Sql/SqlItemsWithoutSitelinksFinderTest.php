<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Tests\Rdbms\LocalRepoDbTestHelper;
use Wikibase\Repo\Store\Sql\SqlItemsWithoutSitelinksFinder;

/**
 * @covers \Wikibase\Repo\Store\Sql\SqlItemsWithoutSitelinksFinder
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group Database
 *
 * @group Medium
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class SqlItemsWithoutSitelinksFinderTest extends MediaWikiIntegrationTestCase {

	use LocalRepoDbTestHelper;

	protected function setUp(): void {
		parent::setUp();

		static $setUp = false;
		if ( !$setUp ) {
			$setUp = true;

			$dbw = $this->db;

			$dbw->delete( 'page', '*' );
			$dbw->delete( 'page_props', '*' );

			// Create page row for Q100, ..., Q106
			$pages = [];
			for ( $i = 0; $i < 7; $i++ ) {
				$n = 100 + $i;

				$pages[] = [
					'page_namespace' => 123,
					'page_title' => "Q$n",
					'page_random' => 0,
					'page_latest' => 0,
					'page_len' => 1,
					'page_is_redirect' => 0,
					'page_touched' => $dbw->timestamp(),
				];

				// Make Q105 a redirect
				if ( $i === 5 ) {
					$pages[$i]['page_is_redirect'] = 1;
				}
			}

			// Wrong namespace (will still get the page prop entry)
			$pages[] = [
				'page_namespace' => 4,
				'page_title' => 'Q5',
				'page_random' => 0,
				'page_latest' => 0,
				'page_len' => 1,
				'page_is_redirect' => 0,
				'page_touched' => $dbw->timestamp(),
			];

			$dbw->insert(
				'page',
				$pages,
				__METHOD__
			);

			// Add wb-sitelinks = 0 for Items with id <= Q105
			$dbw->insertSelect(
				'page_props',
				'page',
				[
					'pp_page' => 'page_id',
					'pp_propname' => $dbw->addQuotes( 'wb-sitelinks' ),
					'pp_value' => 0,
				],
				'page_title <= ' . $dbw->addQuotes( 'Q105' )
			);
			// Add wb-sitelinks = 12 for Item Q106
			$dbw->insertSelect(
				'page_props',
				'page',
				[
					'pp_page' => 'page_id',
					'pp_propname' => $dbw->addQuotes( 'wb-sitelinks' ),
					'pp_value' => 12,
				],
				[
					'page_title' => 'Q106',
				]
			);
		}
	}

	private function getSqlItemsWithoutSitelinksFinder() {
		return new SqlItemsWithoutSitelinksFinder(
			new EntityNamespaceLookup( [ Item::ENTITY_TYPE => 123 ] ),
			$this->getRepoDomainDb( $this->db )
		);
	}

	public function testGetItemsWithoutSitelinks_getAll() {
		$itemsWithoutSitelinksFinder = $this->getSqlItemsWithoutSitelinksFinder();

		$itemIds = $itemsWithoutSitelinksFinder->getItemsWithoutSitelinks( 10e6 );

		$this->assertCount( 5, $itemIds );
		$this->assertContainsOnlyInstancesOf( ItemId::class, $itemIds );
		// This is in descending order
		for ( $i = 0; $i < 5; $i++ ) {
			$id = 'Q10' . ( 4 - $i );
			$this->assertSame( $id, $itemIds[$i]->getSerialization() );
		}
	}

	public function testGetItemsWithoutSitelinks_limit() {
		$itemsWithoutSitelinksFinder = $this->getSqlItemsWithoutSitelinksFinder();

		$itemIds = $itemsWithoutSitelinksFinder->getItemsWithoutSitelinks( 2 );

		$this->assertCount( 2, $itemIds );
		$this->assertContainsOnlyInstancesOf( ItemId::class, $itemIds );
		// This is in descending order
		$this->assertSame( 'Q104', $itemIds[0]->getSerialization() );
		$this->assertSame( 'Q103', $itemIds[1]->getSerialization() );
	}

	public function testGetItemsWithoutSitelinks_offset() {
		$itemsWithoutSitelinksFinder = $this->getSqlItemsWithoutSitelinksFinder();

		$itemIds = $itemsWithoutSitelinksFinder->getItemsWithoutSitelinks( 2, 2 );

		$this->assertCount( 2, $itemIds );
		$this->assertContainsOnlyInstancesOf( ItemId::class, $itemIds );
		// This is in descending order
		$this->assertSame( 'Q102', $itemIds[0]->getSerialization() );
		$this->assertSame( 'Q101', $itemIds[1]->getSerialization() );
	}

	public function testGetItemsWithoutSitelinks_offsetNothingSelected() {
		$itemsWithoutSitelinksFinder = $this->getSqlItemsWithoutSitelinksFinder();

		$itemIds = $itemsWithoutSitelinksFinder->getItemsWithoutSitelinks( 2, 200 );

		$this->assertSame( [], $itemIds );
	}

}
