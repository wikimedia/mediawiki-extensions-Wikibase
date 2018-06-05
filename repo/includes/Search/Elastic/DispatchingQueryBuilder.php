<?php
namespace Wikibase\Repo\Search\Elastic;

use CirrusSearch\Profile\SearchProfileService;
use CirrusSearch\Query\FullTextQueryBuilder;
use CirrusSearch\Search\SearchContext;
use Wikibase\Lib\Store\EntityNamespaceLookup;

/**
 * Query builder helper that will instantiate and invoke specific builder
 * according to namespace configuration.
 */
class DispatchingQueryBuilder implements FullTextQueryBuilder {

	/**
	 * @var EntityNamespaceLookup
	 */
	private $entityNamespaceLookup;
	/***
	 * @var string[]
	 */
	private $searchContexts;

	/**
	 * @param array $searchContexts Map of entity type => search context
	 * @param EntityNamespaceLookup $entityNamespaceLookup
	 */
	public function __construct( array $searchContexts, EntityNamespaceLookup  $entityNamespaceLookup ) {
		$this->entityNamespaceLookup = $entityNamespaceLookup;
		$this->searchContexts = $searchContexts;
	}

	/**
	 * Search articles with provided term.
	 *
	 * If the search is requested for namespace(s) that have same builder type,
	 * will instantiate query builder of this type and call it on the context.
	 *
	 * @param SearchContext $searchContext
	 * @param string $term term to search
	 */
	public function build( SearchContext $searchContext, $term ) {
		$entityNs = $articleNs = [];
		$namespaces = $searchContext->getNamespaces();
		if ( !$namespaces ) {
			// Fallback if we don't have namespaces defined
			return;
		}
		// Look up search types for namespaces we're searching in
		foreach ( $namespaces as $ns ) {
			$type = $this->entityNamespaceLookup->getEntityType( (int)$ns );
			if ( $type && !empty( $this->searchContexts[$type] ) ) {
				$entityNs[$this->searchContexts[$type]][] = $ns;
			} else {
				$articleNs[] = $ns;
			}
		}
		if ( empty( $entityNs ) ) {
			// We're done, no need to build anything, context already has generic query
			return;
		}
		// Check if we only have one type. If yes, dispatch to that type.
		// If not, bail to generic search.
		if ( count( $entityNs ) > 1 ) {
			return;
		}
		// FIXME: if we have a mix here of article & entity namespaces, the search may not work
		// very well here. Right now we're just forcing it to entity space. We may want to look
		// for a better solution.
		if ( !empty( $articleNs ) ) {
			$searchContext->addWarning( 'wikibase-search-namespace-mix' );
		}
		reset( $entityNs );
		$searchType = key( $entityNs );

		$qbSettings = $searchContext->getConfig()->getProfileService()
			->loadProfile( SearchProfileService::FT_QUERY_BUILDER, $searchType );
		if ( !$qbSettings ) {
			$searchContext->addWarning( 'wikibase-search-config-notfound', $searchType );
			return;
		}
		$builderClass = $qbSettings['builder_class'];

		if ( !is_callable( [ $builderClass, 'newFromGlobals' ] ) ) {
			$searchContext->addWarning( 'wikibase-search-config-badclass', $builderClass );
			return;
		}

		$builder = $builderClass::newFromGlobals( $qbSettings['settings'] );
		if ( !( $builder instanceof FullTextQueryBuilder ) ) {
			$searchContext->addWarning( 'wikibase-search-config-badclass', $builderClass );
			return;
		}

		// Reset highlight query, we will define our own
		$searchContext->setHighlightQuery( null );
		// Reset suggest query
		$searchContext->setSuggest( [] );
		/**
		 * @var FullTextQueryBuilder $builder
		 */
		$builder->build( $searchContext, $term );
	}

	/**
	 * Attempt to build a degraded query from the query already built into $context. Must be
	 * called *after* self::build().
	 *
	 * @param SearchContext $searchContext
	 * @return bool True if a degraded query was built
	 */
	public function buildDegraded( SearchContext $searchContext ) {
		return false;
	}

}
