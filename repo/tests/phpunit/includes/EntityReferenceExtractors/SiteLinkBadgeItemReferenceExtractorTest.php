<?php

namespace Wikibase\Repo\Tests\EntityReferenceExtractors;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Repo\EntityReferenceExtractors\SiteLinkBadgeItemReferenceExtractor;

/**
 * @covers \Wikibase\Repo\EntityReferenceExtractors\SiteLinkBadgeItemReferenceExtractor
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SiteLinkBadgeItemReferenceExtractorTest extends TestCase {

	/**
	 * @dataProvider siteLinksProvider
	 */
	public function testExtractEntityIds( $siteLinks, $expected ) {
		$item = new Item( null, null, new SiteLinkList( $siteLinks ) );

		$instance = new SiteLinkBadgeItemReferenceExtractor();
		$this->assertEquals(
			$expected,
			$instance->extractEntityIds( $item )
		);
	}

	/**
	 * @dataProvider nonItemProvider
	 */
	public function testGivenNotAnItem_extractEntityIdsThrows( $entity ) {
		$instance = new SiteLinkBadgeItemReferenceExtractor();
		$this->expectException( InvalidArgumentException::class );
		$instance->extractEntityIds( $entity );
	}

	public function siteLinksProvider() {
		yield 'no sitelinks' => [
			[],
			[],
		];

		yield '3 sitelinks with 1 recurring badge' => [
			[
				new SiteLink( 'dewiki', 'Bla', [] ),
				new SiteLink( 'enwiki', 'Bli', [ new ItemId( 'Q123' ), new ItemId( 'Q321' ) ] ),
				new SiteLink( 'frwiki', 'Blu', [ new ItemId( 'Q123' ), new ItemId( 'Q234' ) ] ),
			],
			[ new ItemId( 'Q123' ), new ItemId( 'Q321' ), new ItemId( 'Q234' ) ],
		];
	}

	public function nonItemProvider() {
		yield 'property' => [ new Property( null, null, 'string' ) ];
	}

}
