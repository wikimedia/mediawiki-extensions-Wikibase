<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ContentModelMappingsTest extends ServiceWiringTestCase {

	public function testUsesEntityTypeDefinitions(): void {
		$this->mockService( 'WikibaseRepo.EntityTypeDefinitions',
			new EntityTypeDefinitions( [
				'test1' => [
					EntityTypeDefinitions::CONTENT_MODEL_ID => 'test1-content-model',
				],
			] ) );

		$contentModelMappings = $this->getService( 'WikibaseRepo.ContentModelMappings' );

		$expected = [
			'test1' => 'test1-content-model',
		];
		$this->assertSame( $expected, $contentModelMappings );
	}

	public function testRunsHook(): void {
		$this->mockService( 'WikibaseRepo.EntityTypeDefinitions',
			new EntityTypeDefinitions( [] ) );
		$this->configureHookContainer( [
			'WikibaseContentModelMapping' => [ function ( array &$map ) {
				$map['test2'] = 'test2-content-model';
			} ],
		] );

		$contentModelMappings = $this->getService( 'WikibaseRepo.ContentModelMappings' );

		$expected = [
			'test2' => 'test2-content-model',
		];
		$this->assertSame( $expected, $contentModelMappings );
	}

}
