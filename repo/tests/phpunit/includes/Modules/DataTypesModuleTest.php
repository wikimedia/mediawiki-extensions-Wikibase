<?php

namespace Wikibase\Repo\Tests\Modules;

use Wikibase\Lib\DataTypeFactory;
use Exception;
use ResourceLoaderContext;
use Wikibase\Repo\Modules\DataTypesModule;

/**
 * @covers WikibaseRepo\Modules\DataTypesModule
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class DataTypesModuleTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return array [instance, resource definition]
	 */
	public function provideDataTypesModuleAndResourceDefinition() {
		$dataTypeFactory = new DataTypeFactory( [ 'url' => 'string' ] );

		$validResourceDefinitions = [
			[
				'datatypesconfigvarname' => 'foo',
				'datatypefactory' => function() {
					return new DataTypeFactory( [] );
				}
			],
			[
				'datatypesconfigvarname' => 'bar123',
				'datatypefactory' => $dataTypeFactory
			],
		];

		$cases = [];

		foreach ( $validResourceDefinitions as $definition ) {
			$instance = new DataTypesModule( $definition );
			$cases[] = [ $instance, $definition ];
		}

		return $cases;
	}

	/**
	 * @dataProvider provideDataTypesModuleAndResourceDefinition
	 *
	 * @param DataTypesModule $module
	 */
	public function testGetDataTypeFactory( DataTypesModule $module ) {
		$this->assertInstanceOf( DataTypeFactory::class, $module->getDataTypeFactory() );
	}

	/**
	 * @return array [invalid resource definition, case description]
	 */
	public function provideInvalidResourceDefinition() {
		$dataTypeFactory = new DataTypeFactory( [] );

		$validDefinition = [
			'datatypesconfigvarname' => 'foo',
			'datatypefactory' => function() {
				return new DataTypeFactory( [] );
			}
		];

		return [
			[
				[
					'datatypesconfigvarname' => 'foo'
				],
				'missing "datatypefactory" field'
			],
			[
				[
					'datatypefactory' => $dataTypeFactory
				],
				'missing "datatypesconfigvarname" field'
			],
			[
				[],
				'all fields missing'
			],
			[
				array_merge(
					$validDefinition,
					[
						'datatypefactory' => 123
					]
				),
				'"datatypefactory" field has value of wrong type'
			],
			[
				array_merge(
					$validDefinition,
					[
						'datatypefactory' => function() {
							return null;
						}
					]
				),
				'"datatypefactory" callback does not return a DataTypeFactory instance'
			],
		];
	}

	/**
	 * @dataProvider provideDataTypesModuleAndResourceDefinition
	 *
	 * @param DataTypesModule $module
	 * @param array $definition
	 */
	public function testGetConfigVarName( DataTypesModule $module, array $definition ) {
		$configVarName = $module->getConfigVarName();

		$this->assertInternalType( 'string', $configVarName );

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
		$this->setExpectedException( Exception::class );

		new DataTypesModule( $definition );
	}

	public function testGetDefinitionSummary() {
		$definition = $this->makeDefinition(
			[ 'foo' => 'string' ]
		);

		$module = new DataTypesModule( $definition );
		$summary = $module->getDefinitionSummary( $this->getContext() );

		$this->assertInternalType( 'array', $summary );
		$this->assertArrayHasKey( 0, $summary );
		$this->assertArrayHasKey( 'dataHash', $summary[0] );
	}

	public function testGetDefinitionSummary_notEqualForDifferentDataTypes() {
		$definition1 = $this->makeDefinition( [
			'foo' => 'string'
		] );

		$definition2 = $this->makeDefinition( [
			'foo' => 'string',
			'bar' => 'string'
		] );

		$module1 = new DataTypesModule( $definition1 );
		$module2 = new DataTypesModule( $definition2 );

		$context = $this->getContext();

		$summary1 = $module1->getDefinitionSummary( $context );
		$summary2 = $module2->getDefinitionSummary( $context );

		$this->assertNotEquals( $summary1[0]['dataHash'], $summary2[0]['dataHash'] );
	}

	private function makeDefinition( array $dataTypes ) {
		return [
			'datatypesconfigvarname' => 'foo123',
			'datatypefactory' => new DataTypeFactory( $dataTypes )
		];
	}

	/**
	 * @return ResourceLoaderContext
	 */
	private function getContext() {
		return $this->getMockBuilder( ResourceLoaderContext::class )
			->disableOriginalConstructor()
			->getMock();
	}

}
