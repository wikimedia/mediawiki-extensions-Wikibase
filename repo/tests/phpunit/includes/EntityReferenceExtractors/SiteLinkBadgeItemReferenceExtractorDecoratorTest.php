<?php

namespace Wikibase\Repo\Tests\EntityReferenceExtractors;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractor;
use Wikibase\Repo\EntityReferenceExtractors\SiteLinkBadgeItemReferenceExtractorDecorator;

/**
 * @covers Wikibase\Repo\EntityReferenceExtractors\SiteLinkBadgeItemReferenceExtractorDecorator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SiteLinkBadgeItemReferenceExtractorDecoratorTest extends TestCase {

	/**
	 * @dataProvider entityIdsAndItemWithSiteLinksProvider
	 */
	public function testExtractEntityIds( $decorateeEntityIds, $siteLinks, $expected ) {
		$mockReferenceExtractor = $this->getMockReferenceExtractor( $decorateeEntityIds );
		$item = new Item( null, null, new SiteLinkList( $siteLinks ) );

		$instance = new SiteLinkBadgeItemReferenceExtractorDecorator( $mockReferenceExtractor );
		$this->assertEquals(
			$expected,
			$instance->extractEntityIds( $item )
		);
	}

	/**
	 * @dataProvider nonItemProvider
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function testGivenNotAnItem_extractEntityIdsThrows( $entity ) {
		$instance = new SiteLinkBadgeItemReferenceExtractorDecorator( $this->getMockReferenceExtractor( [] ) );
		$instance->extractEntityIds( $entity );
	}

	/**
	 * @param array $returnedIds
	 * @return EntityReferenceExtractor|PHPUnit_Framework_MockObject_MockObject
	 */
	private function getMockReferenceExtractor( array $returnedIds ) {
		$mockReferenceExtractor = $this->getMockBuilder( EntityReferenceExtractor::class )
			->getMock();
		$mockReferenceExtractor->expects( $this->any() )
			->method( 'extractEntityIds' )
			->willReturn( $returnedIds );

		return $mockReferenceExtractor;
	}

	public function entityIdsAndItemWithSiteLinksProvider() {
		yield 'only decoratee entity ids' => [
			[ new ItemId( 'Q123' ), new PropertyId( 'P321' ) ],
			[],
			[ new ItemId( 'Q123' ), new PropertyId( 'P321' ) ],
		];

		yield 'only sitelinks badge items' => [
			[],
			[
				new SiteLink( 'dewiki', 'Bla', [] ),
				new SiteLink( 'enwiki', 'Bli', [ new ItemId( 'Q123' ), new ItemId( 'Q321' ) ] ),
				new SiteLink( 'frwiki', 'Blu', [ new ItemId( 'Q123' ), new ItemId( 'Q234' ) ] ),
			],
			[ new ItemId( 'Q123' ), new ItemId( 'Q321' ), new ItemId( 'Q234' ) ]
		];

		yield 'both sitelink badge item ids and decoratee ids' => [
			[ new ItemId( 'Q123' ), new PropertyId( 'P321' ) ],
			[
				new SiteLink( 'dewiki', 'Kartoffel', [ new ItemId( 'Q777' ) ] ),
			],
			[ new ItemId( 'Q123' ), new PropertyId( 'P321' ), new ItemId( 'Q777' ) ],
		];
	}

	public function nonItemProvider() {
		yield 'property' => [ new Property( null, null, 'string' ) ];
	}

}
