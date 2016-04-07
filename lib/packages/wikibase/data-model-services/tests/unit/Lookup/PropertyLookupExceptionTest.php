<?php

namespace Wikibase\DataModel\Services\Tests\Lookup;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyLookupException;

/**
 * @covers Wikibase\DataModel\Services\Lookup\PropertyLookupException
 *
 * @license GPL-2.0+
 * @author Addshore
 */
class PropertyLookupExceptionTest extends \PHPUnit_Framework_TestCase {

	public function testConstructorWithJustAnId() {
		$propertyId = new PropertyId( 'P123' );
		$exception = new PropertyLookupException( $propertyId );

		$this->assertEquals( $propertyId, $exception->getEntityId() );
		$this->assertEquals( $propertyId, $exception->getPropertyId() );
	}

}
