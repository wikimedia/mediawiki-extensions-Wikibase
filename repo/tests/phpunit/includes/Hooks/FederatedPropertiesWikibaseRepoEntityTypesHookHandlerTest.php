<?php

namespace Wikibase\Repo\Tests\Hooks;

use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Hooks\FederatedPropertiesWikibaseRepoEntityTypesHookHandler;

/**
 * @covers \Wikibase\Repo\Hooks\FederatedPropertiesWikibaseRepoEntityTypesHookHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class FederatedPropertiesWikibaseRepoEntityTypesHookHandlerTest extends TestCase {

	public function testGivenFedPropsSettingDisabled_doesNothing() {
		$hookHandler = new FederatedPropertiesWikibaseRepoEntityTypesHookHandler(
			false,
			[
				'property' => [
					'some-service' => 'foo'
				]
			]
		);
		$entityTypeDefinitions = [
			'property' => [
				'some-service' => 'bar'
			]
		];
		$originalEntityTypeDefs = $entityTypeDefinitions; // copy

		$hookHandler->doWikibaseRepoEntityTypes( $entityTypeDefinitions );

		$this->assertEquals( $originalEntityTypeDefs, $entityTypeDefinitions );
	}

	public function testGivenFedPropsSettingEnabled_overridesDefinedServices() {
		$hookHandler = new FederatedPropertiesWikibaseRepoEntityTypesHookHandler(
			true,
			[
				'property' => [
					'some-service' => 'foo'
				]
			]
		);
		$entityTypeDefinitions = [
			'property' => [
				'some-service' => 'bar',
				'some-other-service' => 'baz'
			]
		];

		$hookHandler->doWikibaseRepoEntityTypes( $entityTypeDefinitions );

		$this->assertEquals( [ 'property' => [
			'some-service' => 'foo',
			'some-other-service' => 'baz'
		] ], $entityTypeDefinitions );
	}

}
