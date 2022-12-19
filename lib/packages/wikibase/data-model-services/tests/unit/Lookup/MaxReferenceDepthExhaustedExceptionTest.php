<?php

namespace Wikibase\DataModel\Services\Tests\Lookup;

use Exception;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\MaxReferenceDepthExhaustedException;

/**
 * @covers \Wikibase\DataModel\Services\Lookup\MaxReferenceDepthExhaustedException
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class MaxReferenceDepthExhaustedExceptionTest extends TestCase {

	public function testConstructorWithOnlyRequiredArguments() {
		$entityId = new ItemId( 'Q1' );
		$propertyId = new NumericPropertyId( 'P12' );
		$toIds = [
			new ItemId( 'Q5' ),
			new ItemId( 'Q2013' ),
		];
		$exception = new MaxReferenceDepthExhaustedException( $entityId, $propertyId, $toIds, 44 );

		$this->assertSame( 44, $exception->getMaxDepth() );
		$this->assertSame(
			'Referenced entity id lookup failed: Maximum depth of 44 exhausted.',
			$exception->getMessage()
		);
		$this->assertSame( 0, $exception->getCode() );
		$this->assertNull( $exception->getPrevious() );
	}

	public function testConstructorWithAllArguments() {
		$entityId = new ItemId( 'Q1' );
		$propertyId = new NumericPropertyId( 'P12' );
		$toIds = [
			new ItemId( 'Q5' ),
			new ItemId( 'Q2013' ),
		];
		$previous = new Exception( 'previous' );

		$exception = new MaxReferenceDepthExhaustedException(
			$entityId,
			$propertyId,
			$toIds,
			123,
			'blah blah',
			$previous
		);

		$this->assertSame( 123, $exception->getMaxDepth() );
		$this->assertSame( 'blah blah', $exception->getMessage() );
		$this->assertSame( 0, $exception->getCode() );
		$this->assertSame( $previous, $exception->getPrevious() );
	}

}
