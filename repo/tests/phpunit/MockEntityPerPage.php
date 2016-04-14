<?php

namespace Wikibase\Repo\Tests;

use BadMethodCallException;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Repo\Store\EntityPerPage;

/**
 * @author Addshore
 */
class MockEntityPerPage implements EntityPerPage {

	/**
	 * @var array
	 */
	private $pageIdToEntityId = array();

	/**
	 * @var array
	 */
	private $redirects = array();

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
	public function addEntityPage( EntityId $entityId, $pageId ) {
		$this->pageIdToEntityId[$pageId] = $entityId;
	}

	/**
	 * Adds a new link between an entity redirect and a page
	 *
	 * @since 0.5
	 *
	 * @param EntityId $entityId
	 * @param int $pageId
	 * @param EntityId $targetId
	 */
	public function addRedirectPage( EntityId $entityId, $pageId, EntityId $targetId ) {
		$this->addEntityPage( $entityId, $pageId );
		$this->redirects[$pageId] = $entityId->getSerialization();
	}

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
	public function listEntities(
		$entityType,
		$limit,
		EntityId $after = null,
		$redirects = self::NO_REDIRECTS
	) {
		/** @var EntityId[] $entityIds */
		$entityIds = $this->pageIdToEntityId;
		$entityIds = array_values( $entityIds );

		// Act on $entityType
		if ( is_string( $entityType ) ) {
			foreach ( $entityIds as $key => $entityId ) {
				if ( $entityId->getEntityType() !== $entityType ) {
					unset( $entityIds[$key] );
				}
			}
		}

		// Act on $redirects
		foreach ( $entityIds as $key => $entityId ) {
			$entityIdString = $entityId->getSerialization();
			if (
				( $redirects === self::NO_REDIRECTS && in_array( $entityIdString, $this->redirects ) ) ||
				( $redirects === self::ONLY_REDIRECTS && !in_array( $entityIdString, $this->redirects ) )
			) {
				unset( $entityIds[$key] );
			}
		}

		// Act on $after
		if ( $after !== null ) {
			foreach ( $entityIds as $key => $entityId ) {
				if ( $entityId->getSerialization() <= $after->getSerialization() ) {
					unset( $entityIds[$key] );
				}
			}
		}

		// Act on $limit
		$entityIds = array_slice( array_values( $entityIds ), 0, $limit );

		return array_values( $entityIds );
	}

	/**
	 * Removes a link between an entity (or entity redirect) and a page
	 *
	 * @since 0.5
	 *
	 * @param EntityId $entityId
	 * @param int $pageId
	 *
	 * @throws BadMethodCallException always
	 * @return boolean Success indicator
	 */
	public function deleteEntityPage( EntityId $entityId, $pageId ) {
		throw new BadMethodCallException( 'Mock method not yet implemented' );
	}

	/**
	 * Removes all associations of the given entity (or entity redirect).
	 *
	 * @since 0.5
	 *
	 * @param EntityId $entityId
	 *
	 * @throws BadMethodCallException always
	 * @return boolean Success indicator
	 */
	public function deleteEntity( EntityId $entityId ) {
		throw new BadMethodCallException( 'Mock method not yet implemented' );
	}

	/**
	 * Clears the table
	 *
	 * @since 0.2
	 *
	 * @return boolean Success indicator
	 */
	public function clear() {
		$this->pageIdToEntityId = array();
		$this->redirects = array();
	}

	/**
	 * Return all entities without a specify term
	 *
	 * @since 0.2
	 *
	 * @todo: move this to the TermIndex service
	 *
	 * @param string $termType Can be any member of the TermIndexEntry::TYPE_ enum
	 * @param string|null $language Restrict the search for one language. By default the search is done for all languages.
	 * @param string|null $entityType Can be "item", "property" or "query". By default the search is done for all entities.
	 * @param integer $limit Limit of the query.
	 * @param integer $offset Offset of the query.
	 *
	 * @throws BadMethodCallException always
	 * @return EntityId[]
	 */
	public function getEntitiesWithoutTerm(
		$termType,
		$language = null,
		$entityType = null,
		$limit = 50,
		$offset = 0
	) {
		throw new BadMethodCallException( 'Mock method not yet implemented' );
	}

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
	 * @throws BadMethodCallException always
	 * @return EntityId[]
	 */
	public function getItemsWithoutSitelinks( $siteId = null, $limit = 50, $offset = 0 ) {
		throw new BadMethodCallException( 'Mock method not yet implemented' );
	}

}
