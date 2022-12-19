<?php

namespace Wikibase\DataModel\Services\Tests\Lookup;

use Exception;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\ReferencedEntityIdLookupException;

/**
 * @covers \Wikibase\DataModel\Services\Lookup\ReferencedEntityIdLookupException
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class ReferencedEntityIdLookupExceptionTest extends TestCase {

	public function testConstructorWithOnlyRequiredArguments() {
		$entityId = new ItemId( 'Q1' );
		$propertyId = new NumericPropertyId( 'P12' );
		$toIds = [
			new ItemId( 'Q5' ),
			new ItemId( 'Q2013' ),
		];
		$exception = new ReferencedEntityIdLookupException( $entityId, $propertyId, $toIds );

		$this->assertSame(
			'Referenced entity id lookup failed. ' .
			'Tried to find a referenced entity out of Q5, Q2013 linked from Q1 via P12',
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

		$exception = new ReferencedEntityIdLookupException(
			$entityId,
			$propertyId,
			$toIds,
			'blah blah',
			$previous
		);

		$this->assertSame( 'blah blah', $exception->getMessage() );
		$this->assertSame( 0, $exception->getCode() );
		$this->assertSame( $previous, $exception->getPrevious() );
	}

}
