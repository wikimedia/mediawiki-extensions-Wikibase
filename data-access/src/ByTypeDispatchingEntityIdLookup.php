<?php

namespace Wikibase\DataAccess;

use Hooks;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\ServiceByTypeDispatcher;
use Wikibase\Lib\Store\EntityIdLookup;
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

	/** @var ServiceByTypeDispatcher */
	private $serviceDispatcher;

	public function __construct( array $entityContentModels, array $lookups, EntityIdLookup $defaultLookup ) {
		Assert::parameterElementType( 'string', $entityContentModels, '$entityContentModels' );
		Assert::parameterElementType( 'string', array_keys( $entityContentModels ), 'keys of $entityContentModels' );

		$this->entityContentModels = $entityContentModels;
		$this->serviceDispatcher = new ServiceByTypeDispatcher( EntityIdLookup::class, $lookups, $defaultLookup );
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

	private function getContentModelForTitle( Title $title ): string {
		$contentModel = $title->getContentModel();
		Hooks::run( 'GetEntityContentModelForTitle', [ $title, &$contentModel ] );
		return $contentModel;
	}

	private function getLookupForContentModel( string $contentModel ): EntityIdLookup {
		$entityType = array_search( $contentModel, $this->entityContentModels, true );

		return $this->serviceDispatcher->getServiceForType( $entityType );
	}

}
