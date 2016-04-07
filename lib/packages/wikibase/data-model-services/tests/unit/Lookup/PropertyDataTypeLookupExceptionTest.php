<?php

namespace Wikibase\DataModel\Services\Tests\Lookup;

use Exception;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;

/**
 * @covers Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class PropertyDataTypeLookupExceptionTest extends PHPUnit_Framework_TestCase {

	public function testConstructorWithOnlyRequiredArguments() {
		$propertyId = new PropertyId( 'P1' );
		$exception = new PropertyDataTypeLookupException( $propertyId );

		$this->assertSame( $propertyId, $exception->getPropertyId() );
		$this->assertSame( 'Property data type lookup failed for: P1', $exception->getMessage() );
		$this->assertSame( 0, $exception->getCode() );
		$this->assertNull( $exception->getPrevious() );
	}

	public function testConstructorWithAllArguments() {
		$propertyId = new PropertyId( 'P1' );
		$previous = new Exception( 'previous' );
		$exception = new PropertyDataTypeLookupException( $propertyId, 'customMessage', $previous );

		$this->assertSame( $propertyId, $exception->getPropertyId() );
		$this->assertSame( 'customMessage', $exception->getMessage() );
		$this->assertSame( 0, $exception->getCode() );
		$this->assertSame( $previous, $exception->getPrevious() );
	}

}
