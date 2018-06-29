<?php

namespace Wikibase\Repo\Tests\EntityReferenceExtractors;

use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractor;
use Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractorDelegator;
use Wikibase\Repo\EntityReferenceExtractors\StatementEntityReferenceExtractor;

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
		new EntityReferenceExtractorDelegator( $nonCallables, $this->getMockStatementEntityReferenceExtractor() );
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
				$this->fail( 'This should not be called' );
			},
			'item' => function () use ( $mockEntityReferenceExtractor ) {
				return $mockEntityReferenceExtractor;
			},
		], $this->getMockStatementEntityReferenceExtractor() );

		$this->assertSame(
			$expected,
			$delegator->extractEntityIds( $entity )
		);
	}

	public function testGivenUnknownStatementListProvidingEntityType_usesStatementEntityReferenceExtractor() {
		$entity = new Item();
		$expected = [ new PropertyId( 'P123' ), new ItemId( 'Q123' ) ];
		$statementEntityReferenceExtractor = $this->getMockStatementEntityReferenceExtractor();
		$statementEntityReferenceExtractor->expects( $this->once() )
			->method( 'extractEntityIds' )
			->with( $entity )
			->willReturn( $expected );
		$delegator = new EntityReferenceExtractorDelegator( [], $statementEntityReferenceExtractor );

		$this->assertSame( $expected, $delegator->extractEntityIds( $entity ) );
	}

	public function testGivenUnknownNonStatementListProvidingEntitytype_returnsEmptyArray() {
		$mockEntity = $this->getMockBuilder( EntityDocument::class )->getMock();
		$mockEntity->expects( $this->once() )
			->method( 'getType' )
			->willReturn( 'unknown-entity-type' );
		$statementEntityReferenceExtractor = $this->getMockStatementEntityReferenceExtractor();
		$statementEntityReferenceExtractor->expects( $this->never() )
			->method( 'extractEntityIds' );
		$delegator = new EntityReferenceExtractorDelegator( [], $statementEntityReferenceExtractor );

		$this->assertEquals( [], $delegator->extractEntityIds( $mockEntity ) );
	}

	public function nonCallableArrayProvider() {
		return [
			[ [ 'string', 'string' ] ],
			[ [ 1, 2, 3 ] ],
			[ [ function () {
			}, null ] ],
		];
	}

	/**
	 * @return StatementEntityReferenceExtractor|MockObject
	 */
	private function getMockStatementEntityReferenceExtractor() {
		return $this->getMockBuilder( StatementEntityReferenceExtractor::class )
			->disableOriginalConstructor()
			->getMock();
	}

}
