<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DataTypeDefinitionsTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->configureHookRunner();
		$this->mockService(
			'WikibaseRepo.Settings',
			new SettingsArray( [
				'disabledDataTypes' => [],
			] )
		);

		$this->assertInstanceOf(
			DataTypeDefinitions::class,
			$this->getService( 'WikibaseRepo.DataTypeDefinitions' )
		);
	}

	public function testRunsHook(): void {
		$this->configureHookRunner( [
			'WikibaseRepoDataTypes' => [ function ( array &$dataTypes ) {
				$dataTypes['PT:test'] = [ 'value-type' => 'string' ];
			} ],
		] );
		$this->mockService(
			'WikibaseRepo.Settings',
			new SettingsArray( [
				'disabledDataTypes' => [],
			] )
		);

		/** @var DataTypeDefinitions $dataTypeDefinitions */
		$dataTypeDefinitions = $this->getService( 'WikibaseRepo.DataTypeDefinitions' );

		$valueTypes = $dataTypeDefinitions->getValueTypes();
		$this->assertArrayHasKey( 'test', $valueTypes );
		$this->assertSame( 'string', $valueTypes['test'] );
	}

}
