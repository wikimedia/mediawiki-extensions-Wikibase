<?php

namespace Wikibase\Repo\Tests;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\PropertyInfoBuilder;
use Wikibase\PropertyInfoStore;

/**
 * @covers Wikibase\PropertyInfoBuilder
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class PropertyInfoBuilderTest extends \PHPUnit_Framework_TestCase {

	private function getPropertyInfoBuilder() {
		return new PropertyInfoBuilder( [
			PropertyInfoStore::KEY_FORMATTER_URL => new PropertyId( 'P42' )
		] );
	}

	public function provideBuildPropertyInfo() {
		$cases = [];

		$cases[] = [
			Property::newFromType( 'foo' ),
			[
				'type' => 'foo'
			]
		];

		$property = Property::newFromType( 'foo' );
		$snak = new PropertyValueSnak( new PropertyId( 'P42' ), new StringValue( 'test' ) );
		$property->getStatements()->addNewStatement( $snak );

		$cases[] = [
			$property,
			[
				'type' => 'foo',
				'formatterURL' => 'test'
			]
		];

		return $cases;
	}

	/**
	 * @dataProvider provideBuildPropertyInfo
	 */
	public function testBuildPropertyInfo( Property $property, array $expected ) {
		$propertyInfoBuilder = $this->getPropertyInfoBuilder();
		$this->assertEquals( $expected, $propertyInfoBuilder->buildPropertyInfo( $property ) );
	}

}
