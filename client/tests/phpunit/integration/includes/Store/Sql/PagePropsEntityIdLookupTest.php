<?php

declare( strict_types=1 );

namespace Wikibase\Client\Tests\Integration\Store\Sql;

use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use PageProps;
use Title;
use Wikibase\Client\Store\Sql\PagePropsEntityIdLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;

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

	private function makeTitle( int $pageId, int $ns = NS_MAIN ): Title {
		$title = Title::makeTitle( $ns, 'No' . $pageId );
		if ( $title->canExist() ) {
			$title->resetArticleID( $pageId );
		}

		return $title;
	}

	private function insertPageProps( int $pageId, EntityId $entityId ): void {
		$this->db->insert(
			'page_props',
			[
				'pp_page' => $pageId,
				'pp_propname' => 'wikibase_item',
				'pp_value' => $entityId->getSerialization(),
			]
		);
	}

	public function testGetEntityIdForTitle(): void {
		$title22 = $this->makeTitle( 22 );
		$title99 = $this->makeTitle( 99 );

		$q22 = new ItemId( 'Q22' );
		$this->insertPageProps( 22, $q22 );

		$lookup = new PagePropsEntityIdLookup(
			MediaWikiServices::getInstance()->getPageProps(),
			new ItemIdParser()
		);
		$this->assertEquals( $q22, $lookup->getEntityIdForTitle( $title22 ) );
		$this->assertNull( $lookup->getEntityIdForTitle( $title99 ) );
	}

	public function testGetEntityIds(): void {
		$title11 = $this->makeTitle( 11 );
		$title22 = $this->makeTitle( 22 );
		$title99 = $this->makeTitle( 99 );

		$q11 = new ItemId( 'Q11' );
		$q22 = new ItemId( 'Q22' );

		$this->insertPageProps( 11, $q11 );
		$this->insertPageProps( 22, $q22 );

		$expected = [
			11 => $q11,
			22 => $q22,
		];

		$lookup = new PagePropsEntityIdLookup(
			MediaWikiServices::getInstance()->getPageProps(),
			new ItemIdParser()
		);
		$actual = $lookup->getEntityIds( [ $title22, $title99, $title11 ] );
		ksort( $actual );

		$this->assertEquals( $expected, $actual );
	}

	public function testIntegratesWithPageProps(): void {
		$page = $this->makeTitle( 11 );
		$special = $this->makeTitle( 22, NS_SPECIAL );

		$mockPageProps = $this->createMock( PageProps::class );
		$mockPageProps
			->expects( $this->once() )
			->method( 'getProperties' )
			->with( [ $page ] )
			->willReturn( [] );

		$lookup = new PagePropsEntityIdLookup(
			$mockPageProps,
			new ItemIdParser()
		);

		$lookup->getEntityIds( [ $page, $special ] );
	}

}
