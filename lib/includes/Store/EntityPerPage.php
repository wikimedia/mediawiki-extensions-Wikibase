<?php

namespace Wikibase\Lib\Store;

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
 * @license GPL-2.0+
 * @author Thomas Pellissier Tanon
 */
interface EntityPerPage {

	/**
	 * Omit redirects from entity listing.
	 */
	const NO_REDIRECTS = 'no';

	/**
	 * Include redirects in entity listing.
	 */
	const INCLUDE_REDIRECTS = 'include';

	/**
	 * Include only redirects in listing.
	 */
	const ONLY_REDIRECTS = 'only';

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
	 * Lists entities of the given type (optionally including redirects).
	 *
	 * @since 0.5
	 *
	 * @param null|string $entityType The entity type to look for.
	 * @param int $limit The maximum number of IDs to return.
	 * @param EntityId|null $after Only return entities with IDs greater than this.
	 * @param string $redirects A XXX_REDIRECTS constant (default is NO_REDIRECTS).
	 *
	 * @return EntityId[]
	 */
	public function listEntities( $entityType, $limit, EntityId $after = null, $redirects = self::NO_REDIRECTS );

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

}
