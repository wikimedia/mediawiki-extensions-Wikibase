<?php

namespace Wikibase\Lib\Tests;

use Wikibase\Lib\DataTypeDefinitions;

/**
 * @covers Wikibase\Lib\DataTypeDefinitions
 *
 * @group DataValueExtensions
 * @group WikibaseLib
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class DataTypeDefinitionsTest extends \MediaWikiTestCase {

	private function getDataTypeDefinitions() {
		$definitions = array(
			'foo' => array(
				'value-type' => 'FOO',
				'validator-factory-callback' => 'DataTypeDefinitionsTest::getFooValidators',
				'parser-factory-callback' => 'DataTypeDefinitionsTest::getFooParser',
				'formatter-factory-callback' => 'DataTypeDefinitionsTest::getFooFormatter',
			),
			'bar' => array(
				'value-type' => 'BAR',
				'formatter-factory-callback' => 'DataTypeDefinitionsTest::getBarFormatter',
			)
		);

		return new DataTypeDefinitions( $definitions );
	}

	public function testTypeIds() {
		$defs = $this->getDataTypeDefinitions();
		$this->assertEquals( array( 'foo', 'bar' ), $defs->getTypeIds() );
	}

	public function testGetValueTypes() {
		$defs = $this->getDataTypeDefinitions();
		$this->assertEquals( array( 'foo' => 'FOO', 'bar' => 'BAR' ), $defs->getValueTypes() );
	}

	public function testGetValidatorFactoryCallbacks() {
		$defs = $this->getDataTypeDefinitions();
		$this->assertEquals( array( 'foo' => 'DataTypeDefinitionsTest::getFooValidators' ), $defs->getValidatorFactoryCallbacks() );
	}

	public function testGetParserFactoryCallbacks() {
		$defs = $this->getDataTypeDefinitions();
		$this->assertEquals( array( 'foo' => 'DataTypeDefinitionsTest::getFooParser' ), $defs->getParserFactoryCallbacks() );
	}

	public function testRegisterDataTypes() {
		$defs = $this->getDataTypeDefinitions();

		$extraTypes = array(
			'bar' => array(
				'validator-factory-callback' => 'DataTypeDefinitionsTest::getBarValidators',
				'parser-factory-callback' => 'DataTypeDefinitionsTest::getBarParser',
			),
			'fuzz' => array(
				'value-type' => 'FUZZ',
				'validator-factory-callback' => 'DataTypeDefinitionsTest::getFuzzValidators',
			)
		);

		$defs->registerDataTypes( $extraTypes );

		$this->assertEquals( array( 'foo', 'bar', 'fuzz' ), $defs->getTypeIds() );
		$this->assertEquals( array( 'foo' => 'FOO', 'bar' => 'BAR', 'fuzz' => 'FUZZ' ), $defs->getValueTypes() );

		$this->assertEquals(
			array( 'foo' => 'DataTypeDefinitionsTest::getFooValidators',
				'bar' => 'DataTypeDefinitionsTest::getBarValidators',
				'fuzz' => 'DataTypeDefinitionsTest::getFuzzValidators' ),
			$defs->getValidatorFactoryCallbacks()
		);
	}

}
