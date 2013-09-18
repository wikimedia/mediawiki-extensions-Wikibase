<?php

namespace Wikibase;

use Iterator;

/**
 * Interface to a table that join wiki pages and entities.
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
	 * @since 0.2
	 *
	 * @param EntityContent $entityContent
	 *
	 * @return boolean Success indicator
	 */
	public function addEntityContent( EntityContent $entityContent );

	/**
	 * Removes the new link between an entity and a page
	 *
	 * @since 0.2
	 *
	 * @param EntityContent $entityContent
	 *
	 * @return boolean Success indicator
	 */
	public function deleteEntityContent( EntityContent $entityContent );

	/**
	 * Clears the table
	 *
	 * @since 0.2
	 *
	 * @return boolean Success indicator
	 */
	public function clear();

	/**
	 * Rebuilds the table
	 *
	 * @since 0.2
	 *
	 * @return boolean success indicator
	 */
	public function rebuild();

	/**
	 * Return all entities without a specify term
	 *
	 * @since 0.2
	 *
	 * @param string $termType Can be any member of the Term::TYPE_ enum
	 * @param string|null $language Restrict the search for one language. By default the search is done for all languages.
	 * @param string|null $entityType Can be "item", "property" or "query". By default the search is done for all entities.
	 * @param integer $limit Limit of the query.
	 * @param integer $offset Offset of the query.
	 * @return EntityId[]
	 */
	public function getEntitiesWithoutTerm( $termType, $language = null, $entityType = null, $limit = 50, $offset = 0 );


	/**
	 * Return all items without sitelinks
	 *
	 * @since 0.4
	 *
	 * @param string|null $siteId Restrict the request to a specific site.
	 * @param integer $limit Limit of the query.
	 * @param integer $offset Offset of the query.
	 * @return EntityId[]
	 */
	public function getItemsWithoutSitelinks( $siteId = null, $limit = 50, $offset = 0 );

	/**
	 * Returns an iterator providing an EntityId object for each entity.
	 *
	 * @param string $entityType The type of entity to return, or null for any type.
	 *
	 * @return Iterator
	 */
	public function getEntities( $entityType = null );
}
