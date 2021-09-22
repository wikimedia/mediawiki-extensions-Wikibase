<?php

namespace Wikibase\DataModel\Services\Tests\Lookup;

use Exception;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;

/**
 * @covers \Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class PropertyDataTypeLookupExceptionTest extends TestCase {

	public function testConstructorWithOnlyRequiredArguments() {
		$propertyId = new NumericPropertyId( 'P1' );
		$exception = new PropertyDataTypeLookupException( $propertyId );

		$this->assertSame( $propertyId, $exception->getPropertyId() );
		$this->assertSame( 'Property data type lookup failed for: P1', $exception->getMessage() );
		$this->assertSame( 0, $exception->getCode() );
		$this->assertNull( $exception->getPrevious() );
	}

	public function testConstructorWithAllArguments() {
		$propertyId = new NumericPropertyId( 'P1' );
		$previous = new Exception( 'previous' );
		$exception = new PropertyDataTypeLookupException( $propertyId, 'customMessage', $previous );

		$this->assertSame( $propertyId, $exception->getPropertyId() );
		$this->assertSame( 'customMessage', $exception->getMessage() );
		$this->assertSame( 0, $exception->getCode() );
		$this->assertSame( $previous, $exception->getPrevious() );
	}

}
