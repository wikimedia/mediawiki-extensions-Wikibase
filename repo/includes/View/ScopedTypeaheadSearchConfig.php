<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\View;

use MediaWiki\HookContainer\HookContainer;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\Hooks\WikibaseRepoHookRunner;

/**
 * @license GPL-2.0-or-later
 */
class ScopedTypeaheadSearchConfig {

	private WikibaseRepoHookRunner $hookRunner;
	private EntityNamespaceLookup $entityNamespaceLookup;
	private array $enabledEntityTypesForSearch;
	private ?array $configuration = null;

	public function __construct(
		HookContainer $hookContainer,
		EntityNamespaceLookup $entityNamespaceLookup,
		array $enabledEntityTypesForSearch
	) {
		$this->hookRunner = new WikibaseRepoHookRunner( $hookContainer );
		$this->entityNamespaceLookup = $entityNamespaceLookup;
		$this->enabledEntityTypesForSearch = $enabledEntityTypesForSearch;
	}

	public function getConfiguration(): array {
		if ( $this->configuration !== null ) {
			return $this->configuration;
		}
		$messages = [
			'item' => 'wikibase-scoped-search-item-scope-name',
			'property' => 'wikibase-scoped-search-property-scope-name',
		];
		$this->hookRunner->onWikibaseRepoSearchableEntityScopesMessages( $messages );
		$entityTypes = [];
		$namespaces = [];
		foreach ( $this->enabledEntityTypesForSearch as $entityType ) {
			$namespaceId = $this->entityNamespaceLookup->getEntityNamespace( $entityType );
			if ( $namespaceId === null ) {
				continue;
			}
			$entityTypes[$entityType] = [ 'namespace' => $namespaceId, 'message' => $messages[$entityType] ];
			$namespaces[$namespaceId] = $entityType;
		}
		$additionalNamespaces = [];
		$this->hookRunner->onWikibaseRepoSearchableEntityScopes( $additionalNamespaces );
		foreach ( $additionalNamespaces as $entityType => $namespaceId ) {
			if ( array_key_exists( $entityType, $messages ) ) {
				$entityTypes[$entityType] = [ 'namespace' => $namespaceId, 'message' => $messages[$entityType] ];
				$namespaces[$namespaceId] = $entityType;
			}
		}
		$this->configuration = [ 'entityTypesConfig' => $entityTypes, 'namespacesConfig' => $namespaces ];
		return $this->configuration;
	}

}
