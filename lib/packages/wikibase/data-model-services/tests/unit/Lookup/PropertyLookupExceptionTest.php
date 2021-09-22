<?php

namespace Wikibase\DataModel\Services\Tests\Lookup;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyLookupException;

/**
 * @covers \Wikibase\DataModel\Services\Lookup\PropertyLookupException
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class PropertyLookupExceptionTest extends TestCase {

	public function testConstructorWithJustAnId() {
		$propertyId = new NumericPropertyId( 'P123' );
		$exception = new PropertyLookupException( $propertyId );

		$this->assertEquals( $propertyId, $exception->getEntityId() );
		$this->assertEquals( $propertyId, $exception->getPropertyId() );
	}

}
