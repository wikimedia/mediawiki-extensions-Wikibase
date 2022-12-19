<?php

namespace Wikibase\Repo\Tests;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Store\PropertyInfoStore;
use Wikibase\Repo\PropertyInfoBuilder;

/**
 * @covers \Wikibase\Repo\PropertyInfoBuilder
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class PropertyInfoBuilderTest extends \PHPUnit\Framework\TestCase {

	private function getPropertyInfoBuilder() {
		return new PropertyInfoBuilder( [
			PropertyInfoLookup::KEY_FORMATTER_URL => new NumericPropertyId( 'P42' ),
			PropertyInfoStore::KEY_CANONICAL_URI => new NumericPropertyId( 'P142' ),
		] );
	}

	public function provideBuildPropertyInfo() {
		$cases = [];

		$cases[] = [
			Property::newFromType( 'foo' ),
			[
				'type' => 'foo',
			],
		];

		$property = Property::newFromType( 'foo' );
		$snak = new PropertyValueSnak( new NumericPropertyId( 'P42' ), new StringValue( 'test' ) );
		$property->getStatements()->addNewStatement( $snak );

		$snak = new PropertyValueSnak( new NumericPropertyId( 'P142' ), new StringValue( 'Heya' ) );
		$property->getStatements()->addNewStatement( $snak );

		$cases[] = [
			$property,
			[
				'type' => 'foo',
				'formatterURL' => 'test',
				'canonicalURI' => 'Heya',
			],
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
