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
		$callable1 = function () {
		};
		$callable2 = function () {
		};
		$this->mockService( 'WikibaseRepo.EntityTypeDefinitions',
			new EntityTypeDefinitions( [
				'type1' => [
					EntityTypeDefinitions::ENTITY_SEARCH_CALLBACK => $callable1,
				],
				'type2' => [
					EntityTypeDefinitions::ENTITY_SEARCH_CALLBACK => $callable2,
				],
			] ) );

		$this->assertSame( [
			'type1' => $callable1,
			'type2' => $callable2,
		], $this->getService( 'WikibaseRepo.EntitySearchHelperCallbacks' ) );
	}

}
