<?php

namespace Wikibase\Lib\Tests;

use PHPUnit_Framework_TestCase;
use Wikibase\Lib\DataTypeDefinitions;

/**
 * @covers Wikibase\Lib\DataTypeDefinitions
 *
 * @group DataValueExtensions
 * @group WikibaseLib
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class DataTypeDefinitionsTest extends PHPUnit_Framework_TestCase {

	private function getDefinitions() {
		return array(
			'VT:FOO' => array(
				'formatter-factory-callback' => 'DataTypeDefinitionsTest::getFooValueFormatter',
				'parser-factory-callback' => 'DataTypeDefinitionsTest::getFooValueParser',
				'rdf-builder-factory-callback' => 'DataTypeDefinitionsTest::getFooRdfBuilder',
			),
			'PT:foo' => array(
				'value-type' => 'FOO',
				'rdf-uri' => 'http://acme.test/vocab/Foo',
				'validator-factory-callback' => 'DataTypeDefinitionsTest::getFooValidators',
				'parser-factory-callback' => 'DataTypeDefinitionsTest::getFooParser',
				'snak-formatter-factory-callback' => 'DataTypeDefinitionsTest::getFooSnakFormatter',
			),
			'PT:bar' => array(
				'value-type' => 'BAR',
				'formatter-factory-callback' => 'DataTypeDefinitionsTest::getBarFormatter',
			)
		);
	}

	private function getDataTypeDefinitions() {
		return new DataTypeDefinitions( $this->getDefinitions() );
	}

	public function testTypeIds() {
		$defs = $this->getDataTypeDefinitions();
		$this->assertEquals( array( 'foo', 'bar' ), $defs->getTypeIds() );
	}

	public function testGetValueTypes() {
		$defs = $this->getDataTypeDefinitions();
		$this->assertEquals( array( 'foo' => 'FOO', 'bar' => 'BAR' ), $defs->getValueTypes() );
	}

	public function testGetRdfTypeUris() {
		$defs = $this->getDataTypeDefinitions();
		$this->assertEquals( array( 'foo' => 'http://acme.test/vocab/Foo' ), $defs->getRdfTypeUris() );
	}

	public function testGetValidatorFactoryCallbacks() {
		$defs = $this->getDataTypeDefinitions();

		$expected = array( 'foo' => 'DataTypeDefinitionsTest::getFooValidators' );
		$this->assertEquals( $expected, $defs->getValidatorFactoryCallbacks() );

		$expected = array( 'PT:foo' => 'DataTypeDefinitionsTest::getFooValidators' );
		$this->assertEquals( $expected, $defs->getValidatorFactoryCallbacks( DataTypeDefinitions::PREFIXED_MODE ) );
	}

	public function testGetParserFactoryCallbacks() {
		$defs = $this->getDataTypeDefinitions();

		$expected = array( 'foo' => 'DataTypeDefinitionsTest::getFooParser' );
		$this->assertEquals( $expected, $defs->getParserFactoryCallbacks() );

		$expected = array(
			'PT:foo' => 'DataTypeDefinitionsTest::getFooParser',
			'VT:FOO' => 'DataTypeDefinitionsTest::getFooValueParser',
		);
		$this->assertEquals( $expected, $defs->getParserFactoryCallbacks( DataTypeDefinitions::PREFIXED_MODE ) );
	}

	public function testGetFormatterFactoryCallbacks() {
		$defs = $this->getDataTypeDefinitions();

		$expected = array(
			'foo' => 'DataTypeDefinitionsTest::getFooValueFormatter',
			'bar' => 'DataTypeDefinitionsTest::getBarFormatter',
		);
		$this->assertEquals( $expected, $defs->getFormatterFactoryCallbacks() );

		$expected = array(
			'VT:FOO' => 'DataTypeDefinitionsTest::getFooValueFormatter',
			'PT:bar' => 'DataTypeDefinitionsTest::getBarFormatter',
		);
		$this->assertEquals( $expected, $defs->getFormatterFactoryCallbacks( DataTypeDefinitions::PREFIXED_MODE ) );
	}

	public function testRegisterDataTypes() {
		$defs = $this->getDataTypeDefinitions();

		$extraTypes = array(
			'VT:FOO' => array(
				'validator-factory-callback' => 'DataTypeDefinitionsTest::getFooValueValidator',
			),
			'PT:bar' => array(
				'validator-factory-callback' => 'DataTypeDefinitionsTest::getBarValidators',
				'parser-factory-callback' => 'DataTypeDefinitionsTest::getBarParser',
			),
			'PT:fuzz' => array(
				'value-type' => 'FOO',
			),
		);

		$defs->registerDataTypes( $extraTypes );

		$this->assertEquals( array( 'foo', 'bar', 'fuzz' ), $defs->getTypeIds() );
		$this->assertEquals( array( 'foo' => 'FOO', 'bar' => 'BAR', 'fuzz' => 'FOO' ), $defs->getValueTypes() );

		$actual = $defs->getValidatorFactoryCallbacks();
		$this->assertEquals(
			array( 'foo' => 'DataTypeDefinitionsTest::getFooValidators',
				'bar' => 'DataTypeDefinitionsTest::getBarValidators',
				'fuzz' => 'DataTypeDefinitionsTest::getFooValueValidator' ),
			$actual
		);
	}

	public function testGetRdfBuilderFactoryCallbacks() {
		$defs = $this->getDataTypeDefinitions();
		$this->assertEquals(
			array( 'foo' => 'DataTypeDefinitionsTest::getFooRdfBuilder' ),
			$defs->getRdfBuilderFactoryCallbacks()
		);
		$this->assertEquals(
			array( 'VT:FOO' => 'DataTypeDefinitionsTest::getFooRdfBuilder' ),
			$defs->getRdfBuilderFactoryCallbacks( DataTypeDefinitions::PREFIXED_MODE )
		);
	}

	public function testDataTypeDefinitions_onlySomeDataTypesEnabled() {
		$definitions = $this->getDefinitions();
		$defs = new DataTypeDefinitions( $definitions, array( 'bar' ) );

		$this->assertSame(
			array( 'foo' ),
			$defs->getTypeIds(),
			'data type ids'
		);

		$this->assertSame(
			array( 'VT:FOO' => 'DataTypeDefinitionsTest::getFooValueFormatter' ),
			$defs->getFormatterFactoryCallbacks( DataTypeDefinitions::PREFIXED_MODE ),
			'formatter factory callbacks, prefixed mode'
		);
	}

}
