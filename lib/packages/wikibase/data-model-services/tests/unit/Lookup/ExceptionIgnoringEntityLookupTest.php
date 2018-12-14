<?php

namespace Wikibase\DataModel\Services\Tests\Lookup;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\UnresolvedEntityRedirectException;
use Wikibase\DataModel\Services\Lookup\ExceptionIgnoringEntityLookup;

/**
 * @covers Wikibase\DataModel\Services\Lookup\ExceptionIgnoringEntityLookup
 *
 * @license GPL-2.0-or-later
 */
class ExceptionIgnoringEntityLookupTest extends \PHPUnit_Framework_TestCase {

	public function testGetEntity_returnsEntity() {
		$entity = new Item( new ItemId( 'Q1' ) );
		$entityId = $entity->getId();
		$innerLookup = $this->createMock( EntityLookup::class );
		$innerLookup->expects( $this->once() )
			->method( 'getEntity' )
			->with( $entityId )
			->willReturn( $entity );
		$outerLookup = new ExceptionIgnoringEntityLookup( $innerLookup );

		$actual = $outerLookup->getEntity( $entityId );

		$this->assertSame( $entity, $actual );
	}

	public function testGetEntity_returnsNull() {
		$entityId = new ItemId( 'Q999999999' );
		$innerLookup = $this->createMock( EntityLookup::class );
		$innerLookup->expects( $this->once() )
			->method( 'getEntity' )
			->with( $entityId )
			->willReturn( null );
		$outerLookup = new ExceptionIgnoringEntityLookup( $innerLookup );

		$actual = $outerLookup->getEntity( $entityId );

		$this->assertNull( $actual );
	}

	public function testGetEntity_catchesUnresolvedEntityRedirectException() {
		$entityId = new ItemId( 'Q2' );
		$innerLookup = $this->createMock( EntityLookup::class );
		$innerLookup->expects( $this->once() )
			->method( 'getEntity' )
			->with( $entityId )
			->willThrowException( new UnresolvedEntityRedirectException(
				$entityId,
				new ItemId( 'Q1' )
			) );
		$outerLookup = new ExceptionIgnoringEntityLookup( $innerLookup );

		$actual = $outerLookup->getEntity( $entityId );

		$this->assertNull( $actual );
	}

	/**
	 * @dataProvider provideBooleans
	 */
	public function testHasEntity( $expected ) {
		$entityId = new ItemId( 'Q1' );
		$innerLookup = $this->createMock( EntityLookup::class );
		$innerLookup->expects( $this->once() )
			->method( 'hasEntity' )
			->with( $entityId )
			->willReturn( $expected );
		$outerLookup = new ExceptionIgnoringEntityLookup( $innerLookup );

		$actual = $outerLookup->hasEntity( $entityId );

		$this->assertSame( $expected, $actual );
	}

	public function provideBooleans() {
		return [
			[ true ],
			[ false ],
		];
	}

}
