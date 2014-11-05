<?php

namespace Wikibase\Test\Entity;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Entity\PropertyNotFoundException;

/**
 * @covers Wikibase\DataModel\Entity\PropertyNotFoundException
 * @uses Wikibase\DataModel\Entity\PropertyId
 *
 * @group WikibaseDataModel
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyNotFoundExceptionTest extends \PHPUnit_Framework_TestCase {

	public function testConstructorWithJustATable() {
		$propertyId = new PropertyId( 'p42' );
		$exception = new PropertyNotFoundException( $propertyId );

		$this->assertEquals( $propertyId, $exception->getPropertyId() );
	}

}
