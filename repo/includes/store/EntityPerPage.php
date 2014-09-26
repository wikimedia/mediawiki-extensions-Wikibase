<?php

namespace Wikibase\Repo\Store;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Interface to a table that join wiki pages and entities.
 *
 * @todo: Combine with the EntityTitleLookup interface?
 * @todo: At least add a way to get page IDs!
 *
 * @since 0.2
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
interface EntityPerPage {

	/**
	 * Adds a new link between an entity and a page
	 *
	 * @since 0.5
	 *
	 * @param EntityId $entityId
	 * @param int $pageId
	 *
	 * @throws InvalidArgumentException
	 */
	public function addEntityPage( EntityId $entityId, $pageId );

	/**
	 * Adds a new link between an entity redirect and a page
	 *
	 * @since 0.5
	 *
	 * @param EntityId $entityId
	 * @param int $pageId
	 * @param EntityId $targetId
	 */
	public function addRedirectPage( EntityId $entityId, $pageId, EntityId $targetId );

	/**
	 * Lists entities of the given type.
	 * This does not include redirects.
	 *
	 * @since 0.5
	 *
	 * @param null|string $entityType The entity type to look for.
	 * @param int $limit The maximum number of IDs to return.
	 * @param EntityId $after Only return entities with IDs greater than this.
	 *
	 * @throws InvalidArgumentException
	 * @return EntityId[]
	 */
	public function listEntities( $entityType, $limit, EntityId $after = null );

	/**
	 * Removes a link between an entity (or entity redirect) and a page
	 *
	 * @since 0.5
	 *
	 * @param EntityId $entityId
	 * @param int $pageId
	 *
	 * @throws InvalidArgumentException
	 * @return boolean Success indicator
	 */
	public function deleteEntityPage( EntityId $entityId, $pageId );

	/**
	 * Removes all associations of the given entity (or entity redirect).
	 *
	 * @since 0.5
	 *
	 * @param EntityId $entityId
	 *
	 * @return boolean Success indicator
	 */
	public function deleteEntity( EntityId $entityId );

	/**
	 * Clears the table
	 *
	 * @since 0.2
	 *
	 * @return boolean Success indicator
	 */
	public function clear();

	/**
	 * Return all entities without a specify term
	 *
	 * @since 0.2
	 *
	 * @todo: move this to the TermIndex service
	 *
	 * @param string $termType Can be any member of the Term::TYPE_ enum
	 * @param string|null $language Restrict the search for one language. By default the search is done for all languages.
	 * @param string|null $entityType Can be "item", "property" or "query". By default the search is done for all entities.
	 * @param integer $limit Limit of the query.
	 * @param integer $offset Offset of the query.
	 *
	 * @return EntityId[]
	 */
	public function getEntitiesWithoutTerm( $termType, $language = null, $entityType = null, $limit = 50, $offset = 0 );


	/**
	 * Return all items without sitelinks
	 *
	 * @since 0.4
	 *
	 * @todo: move this to the SiteLinkLookup service
	 *
	 * @param string|null $siteId Restrict the request to a specific site.
	 * @param integer $limit Limit of the query.
	 * @param integer $offset Offset of the query.
	 *
	 * @return EntityId[]
	 */
	public function getItemsWithoutSitelinks( $siteId = null, $limit = 50, $offset = 0 );
}
