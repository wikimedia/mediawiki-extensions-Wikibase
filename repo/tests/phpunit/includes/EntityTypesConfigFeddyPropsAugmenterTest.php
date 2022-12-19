<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\EntityTypesConfigFeddyPropsAugmenter;

/**
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @covers \Wikibase\Repo\EntityTypesConfigFeddyPropsAugmenter
 */
class EntityTypesConfigFeddyPropsAugmenterTest extends TestCase {

	public function testGivenFedPropsSettingEnabled_overridesDefinedServices() {
		$augmenter = new EntityTypesConfigFeddyPropsAugmenter(
			[
				'property' => [
					'some-service' => 'foo',
				],
			]
		);
		$entityTypeDefinitions = [
			'property' => [
				'some-service' => 'bar',
				'some-other-service' => 'baz',
			],
		];

		$entityTypeDefinitions = $augmenter->override( $entityTypeDefinitions );

		$this->assertEquals( [ 'property' => [
			'some-service' => 'foo',
			'some-other-service' => 'baz',
		] ], $entityTypeDefinitions );
	}

	public function testFactoryReturnsObjectOfCorrectType() {
		$augmenter = EntityTypesConfigFeddyPropsAugmenter::factory();
		$this->assertInstanceOf( EntityTypesConfigFeddyPropsAugmenter::class, $augmenter );
	}

}
