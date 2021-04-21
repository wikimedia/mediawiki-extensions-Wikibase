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
class EntityTypesConfigValueTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.EntityTypeDefinitions',
			new EntityTypeDefinitions( [
				'type' => [
					EntityTypeDefinitions::JS_DESERIALIZER_FACTORY_FUNCTION => 'func',
				],
			] ) );

		$this->assertSame( [
			'types' => [ 'type' ],
			'deserializer-factory-functions' => [
				'type' => 'func',
			],
		], $this->getService( 'WikibaseRepo.EntityTypesConfigValue' ) );
	}

}
