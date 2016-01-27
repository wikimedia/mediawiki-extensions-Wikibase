<?php

namespace Wikibase\DataModel\Services\Tests\Lookup;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;

/**
 * @covers Wikibase\DataModel\Services\Lookup\EntityLookupException
 *
 * @licence GNU GPL v2+
 * @author Addshore
 */
class EntityLookupExceptionTest extends \PHPUnit_Framework_TestCase {

	public function testConstructorWithJustAnId() {
		$propertyId = new PropertyId( 'P42' );
		$exception = new EntityLookupException( $propertyId );

		$this->assertEquals( $propertyId, $exception->getEntityId() );
	}

}
