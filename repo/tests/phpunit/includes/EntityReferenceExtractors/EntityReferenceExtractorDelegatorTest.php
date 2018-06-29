<?php

namespace Wikibase\Repo\Tests\EntityReferenceExtractors;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractor;
use Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractorDelegator;

/**
 * @covers Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractorDelegator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityReferenceExtractorDelegatorTest extends TestCase {

	/**
	 * @dataProvider nonCallableArrayProvider
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function testGivenNonCallables_throwsException( $nonCallables ) {
		new EntityReferenceExtractorDelegator( $nonCallables );
	}

	public function testGivenEntityReferenceExtractorsForEntityType_extractEntityIdsDelegates() {
		$entity = new Item();
		$expected = [ new ItemId( 'Q123' ) ];
		$mockEntityReferenceExtractor = $this->getMockBuilder( EntityReferenceExtractor::class )->getMock();
		$mockEntityReferenceExtractor->expects( $this->once() )
			->method( 'extractEntityIds' )
			->with( $entity )
			->willReturn( $expected );
		$delegator = new EntityReferenceExtractorDelegator( [
			'property' => function () {
				// not called
			},
			'item' => function () use ( $mockEntityReferenceExtractor ) {
				return $mockEntityReferenceExtractor;
			},
		] );

		$this->assertSame(
			$expected,
			$delegator->extractEntityIds( $entity )
		);
	}

	public function nonCallableArrayProvider() {
		return [
			[ [ 'string', 'string' ] ],
			[ [ 1, 2, 3 ] ],
			[ [ function () {
			}, null ] ],
		];
	}

}
