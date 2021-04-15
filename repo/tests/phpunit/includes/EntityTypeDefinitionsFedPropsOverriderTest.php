<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\EntityTypeDefinitionsFedPropsOverrider;

/**
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityTypeDefinitionsFedPropsOverriderTest extends TestCase {

	public function testGivenFedPropsSettingDisabled_doesNothing() {
		$overrider = new EntityTypeDefinitionsFedPropsOverrider(
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

		$newEntityTypeDefinitions = $overrider->override( $originalEntityTypeDefinitions );

		$this->assertEquals( $originalEntityTypeDefinitions, $newEntityTypeDefinitions );
	}

	public function testGivenFedPropsSettingEnabled_overridesDefinedServices() {
		$overrider = new EntityTypeDefinitionsFedPropsOverrider(
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

		$entityTypeDefinitions = $overrider->override( $entityTypeDefinitions );

		$this->assertEquals( [ 'property' => [
			'some-service' => 'foo',
			'some-other-service' => 'baz'
		] ], $entityTypeDefinitions );
	}

	public function testFactoryReturnsObjectOfCorrectType() {
		$overrider = EntityTypeDefinitionsFedPropsOverrider::factory( true );
		$this->assertInstanceOf( EntityTypeDefinitionsFedPropsOverrider::class, $overrider );
	}

}
