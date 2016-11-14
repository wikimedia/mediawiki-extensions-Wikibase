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
		return [
			'VT:FOO' => [
				'formatter-factory-callback' => 'DataTypeDefinitionsTest::getFooValueFormatter',
				'parser-factory-callback' => 'DataTypeDefinitionsTest::getFooValueParser',
				'rdf-builder-factory-callback' => 'DataTypeDefinitionsTest::getFooRdfBuilder',
			],
			'PT:foo' => [
				'value-type' => 'FOO',
				'rdf-uri' => 'http://acme.test/vocab/Foo',
				'validator-factory-callback' => 'DataTypeDefinitionsTest::getFooValidators',
				'parser-factory-callback' => 'DataTypeDefinitionsTest::getFooParser',
				'snak-formatter-factory-callback' => 'DataTypeDefinitionsTest::getFooSnakFormatter',
			],
			'PT:bar' => [
				'value-type' => 'BAR',
				'formatter-factory-callback' => 'DataTypeDefinitionsTest::getBarFormatter',
			]
		];
	}

	private function getDataTypeDefinitions() {
		return new DataTypeDefinitions( $this->getDefinitions() );
	}

	public function testTypeIds() {
		$defs = $this->getDataTypeDefinitions();
		$this->assertEquals( [ 'foo', 'bar' ], $defs->getTypeIds() );
	}

	public function testGetValueTypes() {
		$defs = $this->getDataTypeDefinitions();
		$this->assertEquals( [ 'foo' => 'FOO', 'bar' => 'BAR' ], $defs->getValueTypes() );
	}

	public function testGetRdfTypeUris() {
		$defs = $this->getDataTypeDefinitions();
		$this->assertEquals( [ 'foo' => 'http://acme.test/vocab/Foo' ], $defs->getRdfTypeUris() );
	}

	public function testGetValidatorFactoryCallbacks() {
		$defs = $this->getDataTypeDefinitions();

		$expected = [ 'foo' => 'DataTypeDefinitionsTest::getFooValidators' ];
		$this->assertEquals( $expected, $defs->getValidatorFactoryCallbacks() );

		$expected = [ 'PT:foo' => 'DataTypeDefinitionsTest::getFooValidators' ];
		$this->assertEquals( $expected, $defs->getValidatorFactoryCallbacks( DataTypeDefinitions::PREFIXED_MODE ) );
	}

	public function testGetParserFactoryCallbacks() {
		$defs = $this->getDataTypeDefinitions();

		$expected = [ 'foo' => 'DataTypeDefinitionsTest::getFooParser' ];
		$this->assertEquals( $expected, $defs->getParserFactoryCallbacks() );

		$expected = [
			'PT:foo' => 'DataTypeDefinitionsTest::getFooParser',
			'VT:FOO' => 'DataTypeDefinitionsTest::getFooValueParser',
		];
		$this->assertEquals( $expected, $defs->getParserFactoryCallbacks( DataTypeDefinitions::PREFIXED_MODE ) );
	}

	public function testGetFormatterFactoryCallbacks() {
		$defs = $this->getDataTypeDefinitions();

		$expected = [
			'foo' => 'DataTypeDefinitionsTest::getFooValueFormatter',
			'bar' => 'DataTypeDefinitionsTest::getBarFormatter',
		];
		$this->assertEquals( $expected, $defs->getFormatterFactoryCallbacks() );

		$expected = [
			'VT:FOO' => 'DataTypeDefinitionsTest::getFooValueFormatter',
			'PT:bar' => 'DataTypeDefinitionsTest::getBarFormatter',
		];
		$this->assertEquals( $expected, $defs->getFormatterFactoryCallbacks( DataTypeDefinitions::PREFIXED_MODE ) );
	}

	public function testRegisterDataTypes() {
		$defs = $this->getDataTypeDefinitions();

		$extraTypes = [
			'VT:FOO' => [
				'validator-factory-callback' => 'DataTypeDefinitionsTest::getFooValueValidator',
			],
			'PT:bar' => [
				'validator-factory-callback' => 'DataTypeDefinitionsTest::getBarValidators',
				'parser-factory-callback' => 'DataTypeDefinitionsTest::getBarParser',
			],
			'PT:fuzz' => [
				'value-type' => 'FOO',
			],
		];

		$defs->registerDataTypes( $extraTypes );

		$this->assertEquals( [ 'foo', 'bar', 'fuzz' ], $defs->getTypeIds() );
		$this->assertEquals( [ 'foo' => 'FOO', 'bar' => 'BAR', 'fuzz' => 'FOO' ], $defs->getValueTypes() );

		$actual = $defs->getValidatorFactoryCallbacks();
		$this->assertEquals(
			[ 'foo' => 'DataTypeDefinitionsTest::getFooValidators',
				'bar' => 'DataTypeDefinitionsTest::getBarValidators',
				'fuzz' => 'DataTypeDefinitionsTest::getFooValueValidator' ],
			$actual
		);
	}

	public function testGetRdfBuilderFactoryCallbacks() {
		$defs = $this->getDataTypeDefinitions();
		$this->assertEquals(
			[ 'foo' => 'DataTypeDefinitionsTest::getFooRdfBuilder' ],
			$defs->getRdfBuilderFactoryCallbacks()
		);
		$this->assertEquals(
			[ 'VT:FOO' => 'DataTypeDefinitionsTest::getFooRdfBuilder' ],
			$defs->getRdfBuilderFactoryCallbacks( DataTypeDefinitions::PREFIXED_MODE )
		);
	}

	public function testDataTypeDefinitions_onlySomeDataTypesEnabled() {
		$definitions = $this->getDefinitions();
		$defs = new DataTypeDefinitions( $definitions, [ 'bar' ] );

		$this->assertSame(
			[ 'foo' ],
			$defs->getTypeIds(),
			'data type ids'
		);

		$this->assertSame(
			[ 'VT:FOO' => 'DataTypeDefinitionsTest::getFooValueFormatter' ],
			$defs->getFormatterFactoryCallbacks( DataTypeDefinitions::PREFIXED_MODE ),
			'formatter factory callbacks, prefixed mode'
		);
	}

}
