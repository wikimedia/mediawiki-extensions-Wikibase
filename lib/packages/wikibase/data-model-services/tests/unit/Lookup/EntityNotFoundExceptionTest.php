<?php

namespace Wikibase\DataModel\Services\Tests\Lookup;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\EntityNotFoundException;

/**
 * @covers Wikibase\DataModel\Services\Lookup\EntityNotFoundException
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class EntityNotFoundExceptionTest extends \PHPUnit_Framework_TestCase {

	public function testConstructorWithJustATable() {
		$propertyId = new PropertyId( 'P42' );
		$exception = new EntityNotFoundException( $propertyId );

		$this->assertEquals( $propertyId, $exception->getEntityId() );
	}

}
