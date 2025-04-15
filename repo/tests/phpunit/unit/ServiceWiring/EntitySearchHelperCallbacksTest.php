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
class EntitySearchHelperCallbacksTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$callable1 = fn () => null;
		$callable2 = fn () => null;
		$callable3 = fn () => null;
		$this->mockService( 'WikibaseRepo.EntityTypeDefinitions',
			new EntityTypeDefinitions( [
				'type1' => [
					EntityTypeDefinitions::ENTITY_SEARCH_CALLBACK => $callable1,
				],
				'type2' => [
					EntityTypeDefinitions::ENTITY_SEARCH_CALLBACK => $callable2,
				],
			] ) );
		$this->configureHookRunner( [
			'WikibaseRepoEntitySearchHelperCallbacks' => [
				function ( &$callbacks ) use ( $callable3 ) {
					$callbacks['type3'] = $callable3;
				},
			],
		] );

		$this->assertSame( [
			'type1' => $callable1,
			'type2' => $callable2,
			'type3' => $callable3,
		], $this->getService( 'WikibaseRepo.EntitySearchHelperCallbacks' ) );
	}

}
