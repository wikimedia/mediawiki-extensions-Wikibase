<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\SettingsArray;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DataTypeDefinitionsTest extends ServiceWiringTestCase {
	protected function setUp(): void {
		parent::setUp();

		$this->mockService(
			'WikibaseClient.Settings',
			new SettingsArray( [
				'disabledDataTypes' => [],
			] )
		);
	}

	public function testConstruction(): void {
		$this->assertInstanceOf(
			DataTypeDefinitions::class,
			$this->getService( 'WikibaseClient.DataTypeDefinitions' )
		);
	}

	public function testRunsHook(): void {
		$this->configureHookContainer( [
			'WikibaseClientDataTypes' => [ function ( array &$dataTypes ) {
				$dataTypes['PT:test'] = [ 'value-type' => 'string' ];
			} ],
		] );

		/** @var DataTypeDefinitions $dataTypeDefinitions */
		$dataTypeDefinitions = $this->getService( 'WikibaseClient.DataTypeDefinitions' );

		$valueTypes = $dataTypeDefinitions->getValueTypes();
		$this->assertArrayHasKey( 'test', $valueTypes );
		$this->assertSame( 'string', $valueTypes['test'] );
	}

}
