<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\Services\Diff\EntityDifferStrategy;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityDifferTest extends ServiceWiringTestCase {

	public function testRegistersStrategiesFromBuilders() {
		$calls1 = 0;
		$strategy1 = $this->createMock( EntityDifferStrategy::class );
		$calls2 = 0;
		$strategy2 = $this->createMock( EntityDifferStrategy::class );
		$this->mockService( 'WikibaseRepo.EntityTypeDefinitions',
			new EntityTypeDefinitions( [
				'test1' => [
					EntityTypeDefinitions::ENTITY_DIFFER_STRATEGY_BUILDER => function () use ( &$calls1, $strategy1 ) {
						$calls1++;
						return $strategy1;
					},
				],
				'test2' => [
					EntityTypeDefinitions::ENTITY_DIFFER_STRATEGY_BUILDER => function () use ( &$calls2, $strategy2 ) {
						$calls2++;
						return $strategy2;
					},
				],
				'test3' => [
					// undefined builder should not be an error
				],
			] ) );

		$entityDiffer = $this->getService( 'WikibaseRepo.EntityDiffer' );

		$this->assertInstanceOf( EntityDiffer::class, $entityDiffer );
		$this->assertSame( 1, $calls1, 'builder should only be called once' );
		$this->assertSame( 1, $calls2, 'builder should only be called once' );
	}

}
