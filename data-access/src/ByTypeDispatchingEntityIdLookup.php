<?php

namespace Wikibase\DataAccess;

use Hooks;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Store\EntityIdLookup;
use Wikimedia\Assert\Assert;

/**
 * An EntityIdLookup that dispatches by Title content model to inner EntityIdLookups.
 * If no lookup is registered for the content model, then the lookup will fall back to
 * a default lookup.
 *
 * @license GPL-2.0-or-later
 */
class ByTypeDispatchingEntityIdLookup implements EntityIdLookup {

	/**
	 * @var string[] Entity type ID to content model ID mapping.
	 */
	private $entityContentModels;

	/**
	 * EntityIdLookup[]
	 */
	private $lookups;

	/**
	 * EntityIdLookup
	 */
	private $defaultLookup;

	public function __construct( array $entityContentModels, array $lookups, EntityIdLookup $defaultLookup ) {
		Assert::parameterElementType( 'string', $entityContentModels, '$entityContentModels' );
		Assert::parameterElementType( 'string', array_keys( $entityContentModels ), 'keys of $entityContentModels' );
		Assert::parameterElementType( 'callable', $lookups, '$lookups' );
		Assert::parameterElementType( 'string', array_keys( $lookups ), 'keys of $lookups' );

		$this->entityContentModels = $entityContentModels;
		$this->lookups = $lookups;
		$this->defaultLookup = $defaultLookup;
	}

	public function getEntityIds( array $titles ) {
		$pageIds = [];
		$contentModels = [];
		foreach ( $titles as $title ) {
			$contentModel = $this->getContentModelForTitle( $title );
			$contentModels[$contentModel][] = $title;

			$pageIds[] = $title->getArticleID();
		}

		$results = array_fill_keys( $pageIds, null );
		foreach ( $contentModels as $contentModel => $contentModelTitles ) {
			$lookup = $this->getLookupForContentModel( $contentModel );
			$entityIds = $lookup->getEntityIds( $contentModelTitles );
			$results = array_replace( $results, $entityIds );
		}

		return array_filter( $results, function ( $id ) {
			return $id instanceof EntityId;
		} );
	}

	public function getEntityIdForTitle( Title $title ) {
		$contentModel = $this->getContentModelForTitle( $title );
		$lookup = $this->getLookupForContentModel( $contentModel );
		return $lookup->getEntityIdForTitle( $title );
	}

	/**
	 * @param Title $title
	 * @return string
	 */
	private function getContentModelForTitle( Title $title ) {
		$contentModel = $title->getContentModel();
		Hooks::run( 'GetEntityContentModelForTitle', [ $title, &$contentModel ] );
		return $contentModel;
	}

	/**
	 * @param string $contentModel
	 * @return EntityIdLookup
	 */
	private function getLookupForContentModel( $contentModel ) {
		$entityType = array_search( $contentModel, $this->entityContentModels, true );
		if ( $entityType === false || !isset( $this->lookups[$entityType] ) ) {
			return $this->defaultLookup;
		}

		$lookup = call_user_func( $this->lookups[$entityType] );
		Assert::postcondition(
			$lookup instanceof EntityIdLookup,
			'Callback must return an instance of EntityIdLookup'
		);

		return $lookup;
	}

}
