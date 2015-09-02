<?php


namespace Wikibase\Test;
use Wikibase\DataModel\Entity\Property;
use Wikibase\PropertyInfoBuilder;

/**
 * @covers Wikibase\Repo\PropertyInfoBuilder
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class PropertyInfoBuilderTest extends \PHPUnit_Framework_TestCase {

	public function testBuildPropertyInfo() {
		$property = Property::newFromType( 'foo' );
		$propertyInfoBuilder = new PropertyInfoBuilder();

		$this->assertEquals( array( 'type' => 'foo' ), $propertyInfoBuilder->buildPropertyInfo( $property ) );
	}

}
