<?php

namespace Wikibase\DataModel\Services\Tests\Lookup;

use Exception;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\MaxReferencedEntityVisitsExhaustedException;

/**
 * @covers \Wikibase\DataModel\Services\Lookup\MaxReferencedEntityVisitsExhaustedException
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class MaxReferencedEntityVisitsExhaustedExceptionTest extends TestCase {

	public function testConstructorWithOnlyRequiredArguments() {
		$entityId = new ItemId( 'Q1' );
		$propertyId = new NumericPropertyId( 'P12' );
		$toIds = [
			new ItemId( 'Q5' ),
			new ItemId( 'Q2013' ),
		];
		$exception = new MaxReferencedEntityVisitsExhaustedException( $entityId, $propertyId, $toIds, 44 );

		$this->assertSame( 44, $exception->getMaxEntityVisits() );
		$this->assertSame(
			'Referenced entity id lookup failed: Maximum number of entity visits (44) exhausted.',
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

		$exception = new MaxReferencedEntityVisitsExhaustedException(
			$entityId,
			$propertyId,
			$toIds,
			123,
			'blah blah',
			$previous
		);

		$this->assertSame( 123, $exception->getMaxEntityVisits() );
		$this->assertSame( 'blah blah', $exception->getMessage() );
		$this->assertSame( 0, $exception->getCode() );
		$this->assertSame( $previous, $exception->getPrevious() );
	}

}
