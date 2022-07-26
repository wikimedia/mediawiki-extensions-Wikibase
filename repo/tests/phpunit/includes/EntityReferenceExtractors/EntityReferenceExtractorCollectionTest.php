<?php

namespace Wikibase\Repo\Tests\EntityReferenceExtractors;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractor;
use Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractorCollection;
use Wikibase\Repo\EntityReferenceExtractors\SiteLinkBadgeItemReferenceExtractor;

/**
 * @covers \Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractorCollection
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityReferenceExtractorCollectionTest extends TestCase {

	/**
	 * @dataProvider nonEntityReferenceExtractorsProvider
	 */
	public function testGivenNonEntityReferenceExtractorArray_throwsException( $extractors ) {
		$this->expectException( InvalidArgumentException::class );
		new EntityReferenceExtractorCollection( $extractors );
	}

	public function nonEntityReferenceExtractorsProvider() {
		return [
			[ [ 1, 2, 3 ] ],
			[ [ 'foo' ] ],
			[ [ new SiteLinkBadgeItemReferenceExtractor(), null ] ],
		];
	}

	public function testGivenNoReferenceExtractors_returnsEmptyArray() {
		$extractor = new EntityReferenceExtractorCollection( [] );

		$this->assertEquals( [], $extractor->extractEntityIds( new Item() ) );
	}

	public function testGivenEntityReferenceExtractor_returnsIds() {
		$expected = [ new ItemId( 'Q42' ), new NumericPropertyId( 'P23' ) ];
		$item = new Item();
		$mockExtractor = $this->getMockEntityReferenceExtractor( $item, $expected );
		$extractor = new EntityReferenceExtractorCollection( [ $mockExtractor ] );

		$this->assertEquals( $expected, $extractor->extractEntityIds( $item ) );
	}

	public function testGivenMultipleEntityReferenceExtractors_returnsIdsMergedAndUnique() {
		$item = new Item();
		$mockExtractor1 = $this->getMockEntityReferenceExtractor(
			$item,
			[ new NumericPropertyId( 'P666' ), new ItemId( 'Q123' ) ]
		);
		$mockExtractor2 = $this->getMockEntityReferenceExtractor(
			$item,
			[ new NumericPropertyId( 'P666' ), new ItemId( 'Q456' ) ]
		);
		$extractor = new EntityReferenceExtractorCollection( [ $mockExtractor1, $mockExtractor2 ] );

		$this->assertEquals(
			[ new NumericPropertyId( 'P666' ), new ItemId( 'Q123' ), new ItemId( 'Q456' ) ],
			$extractor->extractEntityIds( $item )
		);
	}

	private function getMockEntityReferenceExtractor( $entity, $extracted ) {
		$mockEntityReferenceExtractor = $this->createMock( EntityReferenceExtractor::class );
		$mockEntityReferenceExtractor->expects( $this->once() )
			->method( 'extractEntityIds' )
			->with( $entity )
			->willReturn( $extracted );

		return $mockEntityReferenceExtractor;
	}

}
