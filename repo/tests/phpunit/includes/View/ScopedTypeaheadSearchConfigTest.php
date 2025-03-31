<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\View;

use MediaWiki\HookContainer\HookContainer;
use MediaWikiIntegrationTestCase;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\View\ScopedTypeaheadSearchConfig;

/**
 * @covers \Wikibase\Repo\View\ScopedTypeaheadSearchConfig
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ScopedTypeaheadSearchConfigTest extends MediaWikiIntegrationTestCase {

	private array $existingScopes;
	private EntityNamespaceLookup $entityNamespaceLookup;
	private array $enabledEntityTypesForSearch;

	public function setUp(): void {
		parent::setUp();
		$this->existingScopes = [
			'item' => 120,
			'property' => 122,
		];
		$this->entityNamespaceLookup = $this->createMock( EntityNamespaceLookup::class );
		$this->entityNamespaceLookup->method( 'getEntityNamespace' )->willReturnCallback(
			function ( $namespaceId ) {
				return $this->existingScopes[ $namespaceId ];
			}
		);
		$this->enabledEntityTypesForSearch = [ 'item', 'property' ];
	}

	public function testAddScope() {
		$hookContainer = $this->createHookContainer( [
			'WikibaseRepoSearchableEntityScopes' => static function ( &$scopes ) {
				$scopes['test'] = 123;
			},
			'WikibaseRepoSearchableEntityScopesMessages' => static function ( &$messages ) {
				$messages['test'] = 'test-message';
			},
		] );
		$configGenerator = new ScopedTypeaheadSearchConfig(
			$hookContainer, $this->entityNamespaceLookup, $this->enabledEntityTypesForSearch
		);

		$config = $configGenerator->getConfiguration();
		$expectedConfig = [
			'entityTypesConfig' => [
				'item' => [ 'namespace' => 120, 'message' => 'wikibase-scoped-search-item-scope-name' ],
				'property' => [ 'namespace' => 122, 'message' => 'wikibase-scoped-search-property-scope-name' ],
				'test' => [ 'namespace' => 123, 'message' => 'test-message' ],
			],
			'namespacesConfig' => [
				120 => 'item',
				122 => 'property',
				123 => 'test',
			],
		];
		$this->assertSame( $expectedConfig, $config );
	}

	public function testAddScopeAvoidsDuplicateHookInvocations() {
		$hookContainer = $this->createMock( HookContainer::class );
		$hookCount = 0;
		$hookContainer
			->method( 'run' )
			->willReturnCallback( function ( $hookName, $args ) use ( &$hookCount ) {
				if ( $hookName === 'WikibaseRepoSearchableEntityScopes' ) {
					$args[0]['test'] = 123;
					$hookCount++;
				} elseif ( $hookName === 'WikibaseRepoSearchableEntityScopesMessages' ) {
					$args[0]['test'] = 'test-message';
					$hookCount++;
				} else {
					$this->fail( 'Unexpected invocation count or invalid hook' );
				}
				return true;
			} );
		$configGenerator = new ScopedTypeaheadSearchConfig(
			$hookContainer,
			$this->entityNamespaceLookup,
			$this->enabledEntityTypesForSearch
		);

		$config = $configGenerator->getConfiguration();
		$expectedConfig = [
			'entityTypesConfig' => [
				'item' => [ 'namespace' => 120, 'message' => 'wikibase-scoped-search-item-scope-name' ],
				'property' => [ 'namespace' => 122, 'message' => 'wikibase-scoped-search-property-scope-name' ],
				'test' => [ 'namespace' => 123, 'message' => 'test-message' ],
			],
			'namespacesConfig' => [
				120 => 'item',
				122 => 'property',
				123 => 'test',
			],
		];
		$this->assertSame( $expectedConfig, $config );

		$config = $configGenerator->getConfiguration();
		$this->assertSame( $expectedConfig, $config );

		$this->assertEquals( 2, $hookCount, 'Expected only two hooks to be fired' );
	}

}
