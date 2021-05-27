<?php

declare( strict_types=1 );

namespace Wikibase\Client\Tests\Integration\Store\Sql;

use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use Title;
use Wikibase\Client\Store\Sql\PagePropsEntityIdLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikimedia\Rdbms\IDatabase;

/**
 * @covers \Wikibase\Client\Store\Sql\PagePropsEntityIdLookup
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseStore
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class PagePropsEntityIdLookupTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		$this->tablesUsed[] = 'page_props';
		parent::setUp();
	}

	private function makeTitle( int $pageId ): Title {
		$title = Title::makeTitle( NS_MAIN, 'No' . $pageId );
		$title->resetArticleID( $pageId );

		return $title;
	}

	private function insertPageProps( IDatabase $db, int $pageId, EntityId $entityId ): void {
		$db->insert(
			'page_props',
			[
				'pp_page' => $pageId,
				'pp_propname' => 'wikibase_item',
				'pp_value' => $entityId->getSerialization(),
			]
		);
	}

	public function testGetEntityIdForTitle(): void {
		$db = wfGetDB( DB_PRIMARY );

		$title22 = $this->makeTitle( 22 );
		$title99 = $this->makeTitle( 99 );

		$q22 = new ItemId( 'Q22' );
		$this->insertPageProps( $db, 22, $q22 );

		$lookup = new PagePropsEntityIdLookup(
			MediaWikiServices::getInstance()->getPageProps(),
			new ItemIdParser()
		);
		$this->assertEquals( $q22, $lookup->getEntityIdForTitle( $title22 ) );
		$this->assertNull( $lookup->getEntityIdForTitle( $title99 ) );
	}

	public function testGetEntityIds(): void {
		$db = wfGetDB( DB_PRIMARY );

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

		$lookup = new PagePropsEntityIdLookup(
			MediaWikiServices::getInstance()->getPageProps(),
			new ItemIdParser()
		);
		$actual = $lookup->getEntityIds( [ $title22, $title99, $title11 ] );
		ksort( $actual );

		$this->assertEquals( $expected, $actual );
	}

}
