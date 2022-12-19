<?php

namespace Wikibase\Lib\Tests;

use UnexpectedValueException;
use Wikibase\Lib\DataTypeDefinitions;

/**
 * @covers \Wikibase\Lib\DataTypeDefinitions
 *
 * @group DataValueExtensions
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class DataTypeDefinitionsTest extends \PHPUnit\Framework\TestCase {

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
				'rdf-data-type' => 'acme-test',
			],
			'VT:BAR' => [
				'validator-factory-callback' => 'DataTypeDefinitionsTest::getBarValueValidator',
				'parser-factory-callback' => 'DataTypeDefinitionsTest::getBarValueParser',
				'rdf-builder-factory-callback' => 'DataTypeDefinitionsTest::getBarValueRdfBuilder',
			],
			'PT:bar' => [
				'value-type' => 'BAR',
				'formatter-factory-callback' => 'DataTypeDefinitionsTest::getBarFormatter',
				'rdf-data-type' => function () {
					return 'acme-test-2';
				},
			],
		];
	}

	private function getDataTypeDefinitions() {
		return new DataTypeDefinitions( $this->getDefinitions() );
	}

	public function testTypeIds() {
		$defs = $this->getDataTypeDefinitions();
		$this->assertSame( [ 'foo', 'bar' ], $defs->getTypeIds() );
	}

	public function testGetValueTypes() {
		$defs = $this->getDataTypeDefinitions();
		$this->assertSame( [ 'foo' => 'FOO', 'bar' => 'BAR' ], $defs->getValueTypes() );
	}

	public function testGetRdfTypeUris() {
		$defs = $this->getDataTypeDefinitions();
		$this->assertSame( [ 'foo' => 'http://acme.test/vocab/Foo' ], $defs->getRdfTypeUris() );
	}

	public function testGetRdfDataTypes() {
		$defs = $this->getDataTypeDefinitions();
		$this->assertSame( [ 'foo' => 'acme-test', 'bar' => 'acme-test-2' ], $defs->getRdfDataTypes() );
	}

	public function testGetValidatorFactoryCallbacks() {
		$defs = $this->getDataTypeDefinitions();

		$expected = [
			'foo' => 'DataTypeDefinitionsTest::getFooValidators',
			'bar' => 'DataTypeDefinitionsTest::getBarValueValidator',
		];
		$this->assertSame( $expected, $defs->getValidatorFactoryCallbacks() );

		$expected = [
			'PT:foo' => 'DataTypeDefinitionsTest::getFooValidators',
			'VT:BAR' => 'DataTypeDefinitionsTest::getBarValueValidator',
		];
		$this->assertSame( $expected, $defs->getValidatorFactoryCallbacks( DataTypeDefinitions::PREFIXED_MODE ) );
	}

	public function testGetParserFactoryCallbacks() {
		$defs = $this->getDataTypeDefinitions();

		$expected = [
			'foo' => 'DataTypeDefinitionsTest::getFooParser',
			'bar' => 'DataTypeDefinitionsTest::getBarValueParser',
		];
		$this->assertSame( $expected, $defs->getParserFactoryCallbacks() );

		$expected = [
			'VT:FOO' => 'DataTypeDefinitionsTest::getFooValueParser',
			'PT:foo' => 'DataTypeDefinitionsTest::getFooParser',
			'VT:BAR' => 'DataTypeDefinitionsTest::getBarValueParser',
		];
		$this->assertSame( $expected, $defs->getParserFactoryCallbacks( DataTypeDefinitions::PREFIXED_MODE ) );
	}

	public function testGetFormatterFactoryCallbacks() {
		$defs = $this->getDataTypeDefinitions();

		$expected = [
			'foo' => 'DataTypeDefinitionsTest::getFooValueFormatter',
			'bar' => 'DataTypeDefinitionsTest::getBarFormatter',
		];
		$this->assertSame( $expected, $defs->getFormatterFactoryCallbacks() );

		$expected = [
			'VT:FOO' => 'DataTypeDefinitionsTest::getFooValueFormatter',
			'PT:bar' => 'DataTypeDefinitionsTest::getBarFormatter',
		];
		$this->assertSame( $expected, $defs->getFormatterFactoryCallbacks( DataTypeDefinitions::PREFIXED_MODE ) );
	}

	public function testRegisterDataTypes() {
		$defs = $this->getDataTypeDefinitions();

		$extraTypes = [
			'VT:FOO' => [
				'validator-factory-callback' => 'DataTypeDefinitionsTest::getFooValueValidator',
			],
			'PT:bar' => [
				'validator-factory-callback' => 'DataTypeDefinitionsTest::getBarValueValidators',
				'parser-factory-callback' => 'DataTypeDefinitionsTest::getBarValueParser',
			],
			'PT:fuzz' => [
				'value-type' => 'FOO',
			],
		];

		$defs->registerDataTypes( $extraTypes );

		$this->assertSame( [ 'foo', 'bar', 'fuzz' ], $defs->getTypeIds() );
		$this->assertSame( [ 'foo' => 'FOO', 'bar' => 'BAR', 'fuzz' => 'FOO' ], $defs->getValueTypes() );

		$actual = $defs->getValidatorFactoryCallbacks();
		$this->assertSame(
			[
				'foo' => 'DataTypeDefinitionsTest::getFooValidators',
				'bar' => 'DataTypeDefinitionsTest::getBarValueValidators',
				'fuzz' => 'DataTypeDefinitionsTest::getFooValueValidator',
			],
			$actual
		);
	}

	public function testGetRdfBuilderFactoryCallbacks() {
		$defs = $this->getDataTypeDefinitions();
		$this->assertSame(
			[
				'foo' => 'DataTypeDefinitionsTest::getFooRdfBuilder',
				'bar' => 'DataTypeDefinitionsTest::getBarValueRdfBuilder',
			],
			$defs->getRdfBuilderFactoryCallbacks()
		);
		$this->assertSame(
			[
				'VT:FOO' => 'DataTypeDefinitionsTest::getFooRdfBuilder',
				'VT:BAR' => 'DataTypeDefinitionsTest::getBarValueRdfBuilder',
			],
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

	public function test_getExpertModules_GivenPropertyType_ReturnsMapFromTypeIdToExpertModule() {
		$definitions = [
			'PT:some-type' => [
				'value-type' => 'some-value-type',
				'expert-module' => 'some-expert-module',
			],
		];
		$dataTypeDefinitions = new DataTypeDefinitions( $definitions );

		$this->assertSame(
			[ 'some-type' => 'some-expert-module' ],
			$dataTypeDefinitions->getExpertModules()
		);
	}

	public function test_getExpertModules_GivenPropertyTypeWithoutExpertModule_ThrowsAnException() {
		$definitions = [
			'PT:some-type' => [
				'value-type' => 'some-value-type',
				'expert-module' => '',
			],
		];
		$dataTypeDefinitions = new DataTypeDefinitions( $definitions );

		$this->expectException( UnexpectedValueException::class );
		$dataTypeDefinitions->getExpertModules();
	}

	public function test_getExpertModules_GivenValueTypeWithExpertModuleProperty_IgnoresIt() {
		$definitions = [
			'VT:some-type' => [
				'expert-module' => '',
			],
		];
		$dataTypeDefinitions = new DataTypeDefinitions( $definitions );

		$this->assertSame( [], $dataTypeDefinitions->getExpertModules() );
	}

	public function test_getNormalizerFactoryCallbacks(): void {
		$definitions = [
			'PT:some-type' => [
				'value-type' => 'some-type',
				'normalizer-factory-callback' => 'callable 1',
			],
			'VT:some-type' => [
				'normalizer-factory-callback' => 'callable 2',
			],
			'PT:other-type' => [ 'value-type' => 'other-type' ],
			'VT:other-type' => [],
		];
		$dataTypeDefinitions = new DataTypeDefinitions( $definitions );

		$this->assertSame(
			[ 'PT:some-type' => 'callable 1', 'VT:some-type' => 'callable 2' ],
			$dataTypeDefinitions->getNormalizerFactoryCallbacks()
		);
	}

}
