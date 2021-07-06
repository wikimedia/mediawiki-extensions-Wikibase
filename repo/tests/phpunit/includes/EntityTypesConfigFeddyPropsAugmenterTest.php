<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\EntityTypesConfigFeddyPropsAugmenter;

/**
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityTypesConfigFeddyPropsAugmenterTest extends TestCase {

	public function testGivenFedPropsSettingDisabled_doesNothing() {
		$augmenter = new EntityTypesConfigFeddyPropsAugmenter(
			[
				'property' => [
					'some-service' => 'foo'
				]
			],
			false
		);
		$originalEntityTypeDefinitions = [
			'property' => [
				'some-service' => 'bar'
			]
		];

		$newEntityTypeDefinitions = $augmenter->override( $originalEntityTypeDefinitions );

		$this->assertEquals( $originalEntityTypeDefinitions, $newEntityTypeDefinitions );
	}

	public function testGivenFedPropsSettingEnabled_overridesDefinedServices() {
		$augmenter = new EntityTypesConfigFeddyPropsAugmenter(
			[
				'property' => [
					'some-service' => 'foo'
				]
			],
			true
		);
		$entityTypeDefinitions = [
			'property' => [
				'some-service' => 'bar',
				'some-other-service' => 'baz'
			]
		];

		$entityTypeDefinitions = $augmenter->override( $entityTypeDefinitions );

		$this->assertEquals( [ 'property' => [
			'some-service' => 'foo',
			'some-other-service' => 'baz'
		] ], $entityTypeDefinitions );
	}

	public function testFactoryReturnsObjectOfCorrectType() {
		$augmenter = EntityTypesConfigFeddyPropsAugmenter::factory( true );
		$this->assertInstanceOf( EntityTypesConfigFeddyPropsAugmenter::class, $augmenter );
	}

}
