<?php

namespace Wikibase\Repo\Store;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Interface to a table that join wiki pages and entities.
 *
 * @since 0.2
 *
 * @license GPL-2.0+
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
