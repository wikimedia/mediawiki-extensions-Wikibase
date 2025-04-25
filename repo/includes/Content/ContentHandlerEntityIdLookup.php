<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Content;

use MediaWiki\Title\Title;
use OutOfBoundsException;
use Wikibase\DataAccess\Hooks\GetEntityContentModelForTitleHook;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\Store\StorageException;

/**
 * Implementation of EntityIdLookup that uses content handler.
 *
 * @license GPL-2.0-or-later
 */
class ContentHandlerEntityIdLookup implements EntityIdLookup {
	private EntityContentFactory $entityContentFactory;
	private GetEntityContentModelForTitleHook $hookRunner;

	public function __construct(
		EntityContentFactory $entityContentFactory,
		GetEntityContentModelForTitleHook $hookRunner
	) {
		$this->entityContentFactory = $entityContentFactory;
		$this->hookRunner = $hookRunner;
	}

	/**
	 * Returns the ID of the entity associated with the given page title.
	 *
	 * @note There is no guarantee that the EntityId returned by this method refers to
	 * an existing entity.
	 */
	public function getEntityIdForTitle( Title $title ): ?EntityId {
		$contentModel = $title->getContentModel();

		$this->hookRunner->onGetEntityContentModelForTitle( $title, $contentModel );

		try {
			$handler = $this->entityContentFactory->getEntityHandlerForContentModel( $contentModel );
			return $handler->getIdForTitle( $title );
		} catch ( OutOfBoundsException $ex ) {
			// Not an entity content model
		} catch ( EntityIdParsingException $ex ) {
			// @phan-suppress-previous-line PhanPluginDuplicateCatchStatementBody
			// Not a valid entity page title.
		}

		return null;
	}

	/**
	 * @see EntityIdLookup::getEntityIds
	 *
	 * @note the current implementation skips non-existing entities, but there is no guarantee
	 * that this will always be the case.
	 *
	 * @param Title[] $titles
	 *
	 * @throws StorageException
	 * @return EntityId[] Entity IDs, keyed by page IDs.
	 */
	public function getEntityIds( array $titles ): array {
		$entityIds = [];

		foreach ( $titles as $title ) {
			$pageId = $title->getArticleID();

			if ( $pageId > 0 ) {
				$entityId = $this->getEntityIdForTitle( $title );

				if ( $entityId !== null ) {
					$entityIds[$pageId] = $entityId;
				}
			}
		}

		return $entityIds;
	}

}
