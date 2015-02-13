<?php

namespace Wikibase\Test;

use DataTypes\DataTypeFactory;
use PHPUnit_Framework_TestCase;
use Wikibase\Lib\WikibaseDataTypeBuilders;

/**
 * @covers Wikibase\Lib\WikibaseDataTypeBuilders
 *
 * @group WikibaseLib
 * @group Wikibase
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class WikibaseDataTypeBuildersTest extends PHPUnit_Framework_TestCase {

	private function newTypeFactory() {
		$builders = new WikibaseDataTypeBuilders();
		$dataTypeFactory = new DataTypeFactory( $builders->getDataTypeBuilders() );

		return $dataTypeFactory;
	}

	public function provideDataTypes() {
		$cases = array(
			array( 'wikibase-item' ),
			array( 'wikibase-property' ),
			array( 'commonsMedia' ),
			array( 'string' ),
			array( 'time' ),
			array( 'globe-coordinate' ),
			array( 'url' ),
			array( 'quantity' ),
			array( 'monolingualtext' ),
		);

		if ( defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES ) {
			$cases = array_merge( $cases, array(

				// ....
			) );
		}

		return $cases;
	}

	/**
	 * @dataProvider provideDataTypes
	 */
	public function testDataTypes( $typeId ) {
		$typeFactory = $this->newTypeFactory();
		$type = $typeFactory->getType( $typeId );

		$this->assertInstanceOf( 'DataTypes\DataType', $type );
	}

}
