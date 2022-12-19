<?php

namespace Wikibase\Lib\Tests\Modules;

use Exception;
use MediaWiki\ResourceLoader\Context;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\Modules\DataTypesModule;

/**
 * @covers \WikibaseRepo\Modules\DataTypesModule
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class DataTypesModuleTest extends \PHPUnit\Framework\TestCase {

	public function provideDataTypesModuleAndResourceDefinition() {
		$validResourceDefinitions = [
			[
				'datatypesconfigvarname' => 'foo',
				'datatypefactory' => function() {
					return new DataTypeFactory( [] );
				},
			],
			[
				'datatypesconfigvarname' => 'bar123',
				'datatypefactory' => new DataTypeFactory( [ 'url' => 'string' ] ),
			],
		];

		foreach ( $validResourceDefinitions as $definition ) {
			yield [ new DataTypesModule( $definition ), $definition ];
		}
	}

	/**
	 * @dataProvider provideDataTypesModuleAndResourceDefinition
	 */
	public function testGetDataTypeFactory( DataTypesModule $module ) {
		$this->assertInstanceOf( DataTypeFactory::class, $module->getDataTypeFactory() );
	}

	public function provideInvalidResourceDefinition() {
		$dataTypeFactory = new DataTypeFactory( [] );

		$validDefinition = [
			'datatypesconfigvarname' => 'foo',
			'datatypefactory' => function() {
				return new DataTypeFactory( [] );
			},
		];

		return [
			[
				[
					'datatypesconfigvarname' => 'foo',
				],
				'missing "datatypefactory" field',
			],
			[
				[
					'datatypefactory' => $dataTypeFactory,
				],
				'missing "datatypesconfigvarname" field',
			],
			[
				[],
				'all fields missing',
			],
			[
				array_merge(
					$validDefinition,
					[
						'datatypefactory' => 123,
					]
				),
				'"datatypefactory" field has value of wrong type',
			],
			[
				array_merge(
					$validDefinition,
					[
						'datatypefactory' => function() {
							return null;
						},
					]
				),
				'"datatypefactory" callback does not return a DataTypeFactory instance',
			],
		];
	}

	/**
	 * @dataProvider provideDataTypesModuleAndResourceDefinition
	 */
	public function testGetConfigVarName( DataTypesModule $module, array $definition ) {
		$configVarName = $module->getConfigVarName();

		$this->assertIsString( $configVarName );

		$this->assertSame(
			$definition['datatypesconfigvarname'],
			$module->getConfigVarName()
		);
	}

	/**
	 * @dataProvider provideInvalidResourceDefinition
	 *
	 * @param array $definition
	 * @param string $caseDescription
	 */
	public function testConstructorErrors( array $definition, $caseDescription ) {
		$this->setName( 'Instantiation raises exception in case ' . $caseDescription );
		$this->expectException( Exception::class );

		new DataTypesModule( $definition );
	}

	public function testGetDefinitionSummary() {
		$definition = $this->makeDefinition(
			[ 'foo' => 'string' ]
		);

		$module = new DataTypesModule( $definition );
		$summary = $module->getDefinitionSummary( $this->createMock( Context::class ) );

		$this->assertIsArray( $summary );
		$this->assertArrayHasKey( 0, $summary );
		$this->assertArrayHasKey( 'dataHash', $summary[0] );
	}

	public function testGetDefinitionSummary_notEqualForDifferentDataTypes() {
		$definition1 = $this->makeDefinition( [
			'foo' => 'string',
		] );

		$definition2 = $this->makeDefinition( [
			'foo' => 'string',
			'bar' => 'string',
		] );

		$module1 = new DataTypesModule( $definition1 );
		$module2 = new DataTypesModule( $definition2 );

		$context = $this->createMock( Context::class );

		$summary1 = $module1->getDefinitionSummary( $context );
		$summary2 = $module2->getDefinitionSummary( $context );

		$this->assertNotEquals( $summary1[0]['dataHash'], $summary2[0]['dataHash'] );
	}

	private function makeDefinition( array $dataTypes ) {
		return [
			'datatypesconfigvarname' => 'foo123',
			'datatypefactory' => new DataTypeFactory( $dataTypes ),
		];
	}

}
