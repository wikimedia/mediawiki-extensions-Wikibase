<?php

namespace Wikibase\Lib\Test;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\PropertyNotFoundException;

/**
 * @covers Wikibase\Lib\PropertyNotFoundException
 *
 * @group Wikibase
 * @group WikibaseLib
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
