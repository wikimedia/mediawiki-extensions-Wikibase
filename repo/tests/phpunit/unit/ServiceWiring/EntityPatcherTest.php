<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataModel\Services\Diff\EntityPatcher;
use Wikibase\DataModel\Services\Diff\EntityPatcherStrategy;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityPatcherTest extends ServiceWiringTestCase {

	public function testRegistersStrategiesFromBuilders() {
		$calls1 = 0;
		$strategy1 = $this->createMock( EntityPatcherStrategy::class );
		$calls2 = 0;
		$strategy2 = $this->createMock( EntityPatcherStrategy::class );
		$this->mockService( 'WikibaseRepo.EntityTypeDefinitions',
			new EntityTypeDefinitions( [
				'test1' => [
					EntityTypeDefinitions::ENTITY_PATCHER_STRATEGY_BUILDER => function () use ( &$calls1, $strategy1 ) {
						$calls1++;
						return $strategy1;
					},
				],
				'test2' => [
					EntityTypeDefinitions::ENTITY_PATCHER_STRATEGY_BUILDER => function () use ( &$calls2, $strategy2 ) {
						$calls2++;
						return $strategy2;
					},
				],
				'test3' => [
					// undefined builder should not be an error
				],
			] ) );

		$entityPatcher = $this->getService( 'WikibaseRepo.EntityPatcher' );

		$this->assertInstanceOf( EntityPatcher::class, $entityPatcher );
		$this->assertSame( 1, $calls1, 'builder should only be called once' );
		$this->assertSame( 1, $calls2, 'builder should only be called once' );
	}

}
