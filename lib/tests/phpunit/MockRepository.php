<?php

namespace Wikibase\Test;

use DatabaseBase;
use Wikibase\Claims;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SimpleSiteLink;
use Wikibase\Entity;
use Wikibase\EntityId;
use Wikibase\EntityInfoBuilder;
use Wikibase\EntityLookup;
use Wikibase\EntityRevision;
use Wikibase\EntityRevisionLookup;
use Wikibase\Item;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\Lib\PropertyNotFoundException;
use Wikibase\SiteLink;
use Wikibase\SiteLinkLookup;
use Wikibase\Property;
use Wikibase\StorageException;

/**
 * Mock repository for use in tests.
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class MockRepository implements SiteLinkLookup, EntityLookup, EntityRevisionLookup, EntityInfoBuilder, PropertyDataTypeLookup {

	/**
	 * Entity id serialization => array of EntityRevision
	 *
	 * @var array[]
	 */
	protected $entities = array();

	/**
	 * "$globalSiteId:$pageTitle" => item id integer
	 *
	 * @var string[]
	 */
	protected $itemByLink = array();

	private $maxId = 0;

	/**
	 * @see EntityLookup::getEntity
	 *
	 * @param EntityID $entityId
	 * @param int $revision The desired revision id, 0 means "current".
	 *
	 * @return Entity|null
	 *
	 * @throw StorageException
	 */
	public function getEntity( EntityId $entityId, $revision = 0 ) {
		$rev = $this->getEntityRevision( $entityId, $revision );

		return $rev === null ? null : $rev->getEntity();
	}

	/**
	 * @since 0.4
	 * @see EntityRevisionLookup::getEntityRevision
	 *
	 * @param EntityID $entityId
	 * @param int $revision The desired revision id, 0 means "current".
	 *
	 * @throws StorageException
	 * @return EntityRevision|null
	 * @throw StorageException
	 */
	public function getEntityRevision( EntityId $entityId, $revision = 0 ) {
		$key = $entityId->getPrefixedId();

		if ( !isset( $this->entities[$key] ) || empty( $this->entities[$key] ) ) {
			return null;
		}

		if ( $revision === false ) { // default changed from false to 0
			wfWarn( 'getEntityRevision() called with $revision = false, use 0 instead.' );
			$revision = 0;
		}

		/* @var EntityRevision[] $revisions */
		$revisions = $this->entities[$key];

		if ( $revision === 0 ) { // note: be robust and accept false too.
			$revIds = array_keys( $revisions );
			$n = count( $revIds );

			$revision = $revIds[$n-1];
		} else if ( !isset( $revisions[$revision] ) ) {
			throw new StorageException( "no such revision for entity $key: $revision" );
		}

		$entityRev = $revisions[$revision];
		$entityRev = new EntityRevision( // return a copy!
			$entityRev->getEntity()->copy(), // return a copy!
			$entityRev->getRevision(),
			$entityRev->getTimestamp()
		);

		return $entityRev;
	}

	/**
	 * See EntityLookup::hasEntity()
	 *
	 * @since 0.4
	 *
	 * @param EntityID $entityId
	 *
	 * @return bool
	 */
	public function hasEntity( EntityId $entityId ) {
		return $this->getEntity( $entityId ) !== null;
	}

	/**
	 * Returns an array with the conflicts between the item and the sitelinks
	 * currently in the store. The array is empty if there are no such conflicts.
	 *
	 * The items in the return array are arrays with the following elements:
	 * - integer itemId
	 * - string siteId
	 * - string sitePage
	 *
	 * @param Item               $item
	 * @param DatabaseBase|null $db The database object to use (optional).
	 *                               If conflict checking is performed as part of a save operation,
	 *                               this should be used to provide the master DB connection that will
	 *                               also be used for saving. This will preserve transactional integrity
	 *                               and avoid race conditions.
	 *
	 * @return array of array
	 */
	public function getConflictsForItem( Item $item, DatabaseBase $db = null ) {
		$newLinks = array();

		foreach ( $item->getSiteLinks() as $siteLink ) {
			$newLinks[$siteLink->getSiteId()] = $siteLink->getPageName();
		}

		$conflicts = array();

		foreach ( array_keys( $this->entities ) as $id ) {
			$id = EntityId::newFromPrefixedId( $id );

			if ( $id->getEntityType() !== Item::ENTITY_TYPE ) {
				continue;
			}

			$oldLinks = $this->getLinks( array( $id->getNumericId() ) );

			foreach ( $oldLinks as $link ) {
				list( $wiki, $page, $itemId ) = $link;

				if ( $item->getId() && $itemId === $item->getId()->getNumericId() ) {
					continue;
				}

				if ( isset( $newLinks[$wiki] ) ) {
					if ( $page == $newLinks[$wiki] ) {
						$conflicts[] = $link;
					}
				}
			}
		}

		return $conflicts;
	}

	/**
	 * Returns the id of the item that is equivalent to the
	 * provided page, or false if there is none.
	 *
	 * @since 0.1
	 *
	 * @param string $globalSiteId
	 * @param string $pageTitle
	 *
	 * @return integer|boolean
	 */
	public function getItemIdForLink( $globalSiteId, $pageTitle ) {
		// We store page titles with spaces instead of underscores
		$pageTitle = str_replace( '_', ' ', $pageTitle );

		$key = "$globalSiteId:$pageTitle";

		if ( isset( $this->itemByLink[$key] ) ) {
			return $this->itemByLink[$key];
		} else {
			return false;
		}
	}

	/**
	 * @see SiteLinkLookup::getEntityIdForSiteLink
	 *
	 * @since 0.4
	 *
	 * @param SimpleSiteLink $siteLink
	 *
	 * @return ItemId|null
	 */
	public function getEntityIdForSiteLink( SimpleSiteLink $siteLink ) {
		if ( $siteLink instanceof SiteLink ) {
			$globalSiteId = $siteLink->getSite()->getGlobalId();
			$pageName = $siteLink->getPage();
		}
		else {
			$globalSiteId = $siteLink->getSiteId();
			$pageName = $siteLink->getPageName();
		}

		// @todo: fix test data to use titles with underscores, like the site link table does it
		$title = \Title::newFromText( $pageName );
		$pageTitle = $title->getDBkey();

		$numericItemId = $this->getItemIdForLink( $globalSiteId, $pageTitle );
		return is_int( $numericItemId ) ? new ItemId( 'Q' . $numericItemId ) : null;
	}

	/**
	 * Registers the sitelinsk of the given Item so they can later be found with getLinks, etc
	 *
	 * @param Item $item
	 */
	protected function registerSiteLinks( Item $item ) {
		$this->unregisterSiteLinks( $item );

		$numId = $item->getId()->getNumericId();

		foreach ( $item->getSiteLinks() as $siteLink ) {
			$key = $siteLink->getSiteId() . ':' . $siteLink->getPageName();
			$this->itemByLink[$key] = $numId;
		}
	}

	/**
	 * Unregisters the sitelinsk of the given Item so they are no longer found with getLinks, etc
	 *
	 * @param Item $item
	 */
	protected function unregisterSiteLinks( Item $item ) {
		// clean up old sitelinks

		$numId = $item->getId()->getNumericId();

		foreach ( $this->itemByLink as $key => $n ) {
			if ( $n === $numId ) {
				unset( $this->itemByLink[$key] );
			}
		}
	}

	/**
	 * Puts an entity into the mock repository. If there already is an entity with the same ID
	 * in the mock repository, it is not removed, but replaced as the current one. If a revision
	 * ID is given, the entity with the highest revision ID is considered the current one.
	 *
	 * @param Entity $entity
	 * @param int              $revision
	 * @param int|string       $timestamp
	 *
	 * @return EntityRevision
	 */
	public function putEntity( Entity $entity, $revision = 0, $timestamp = 0 ) {
		if ( $entity->getId() === null ) {
			//NOTE: assign ID to original object, not clone
			$entity->setId( $this->maxId +1 );
		}

		$oldEntity = $this->getEntity( $entity->getId() );

		if ( $oldEntity && ( $oldEntity instanceof Item ) ) {
			// clean up old sitelinks
			$this->unregisterSiteLinks( $oldEntity );
		}

		if ( $entity instanceof Item ) {
			// add new sitelinks
			$this->registerSiteLinks( $entity );
		}

		$key = $entity->getId()->getPrefixedId();

		if ( !array_key_exists( $key, $this->entities ) ) {
			$this->entities[$key] = array();
		}

		if ( $revision === 0 ) {
			$revision = count( $this->entities[$key] ) +1;
		}

		$this->maxId = max( $this->maxId, $entity->getId()->getNumericId() );

		$rev = new EntityRevision(
			$entity->copy(), // note: always clone
			$revision,
			wfTimestamp( TS_MW, $timestamp )
		);

		$this->entities[$key][$revision] = $rev;
		ksort( $this->entities[$key] );

		return $rev;
	}

	/**
	 * Removes an entity from the mock repository.
	 *
	 * @param EntityId $id
	 *
	 * @return Entity
	 */
	public function removeEntity( EntityId $id ) {
		$oldEntity = $this->getEntity( $id );

		if ( $oldEntity && ( $oldEntity instanceof Item ) ) {
			// clean up old sitelinks
			$this->unregisterSiteLinks( $oldEntity );
		}

		$key = $id->getPrefixedId();
		unset( $this->entities[$key] );

		return $oldEntity;
	}

	/**
	 * Returns how many links match the provided conditions.
	 *
	 * Note: this is an exact count which is expensive if the result set is big.
	 * This means you probably do not want to call this method without any conditions.
	 *
	 * @since 0.3
	 *
	 * @param array $itemIds
	 * @param array $siteIds
	 * @param array $pageNames
	 *
	 * @return integer
	 */
	public function countLinks( array $itemIds, array $siteIds = array(), array $pageNames = array() ) {
		return count( $this->getLinks( $itemIds, $siteIds, $pageNames ) );
	}

	/**
	 * Returns the links that match the provided conditions.
	 * The links are returned as arrays with the following elements in specified order:
	 * - siteId
	 * - pageName
	 * - itemId (unprefixed)
	 *
	 * Note: if the conditions are not very selective the result set can be very big.
	 * Thus the caller is responsible for not executing to expensive queries in it's context.
	 *
	 * @since 0.3
	 *
	 * @param array $itemIds
	 * @param array $siteIds
	 * @param array $pageNames
	 *
	 * @return array[]
	 */
	public function getLinks( array $itemIds, array $siteIds = array(), array $pageNames = array() ) {
		$links = array();

		/* @var Entity $entity */
		foreach ( array_keys( $this->entities ) as $id ) {
			$id = EntityId::newFromPrefixedId( $id );

			if ( $id->getEntityType() !== Item::ENTITY_TYPE ) {
				continue;
			}

			if ( !empty( $itemIds ) && !in_array( $id->getNumericId(), $itemIds ) ) {
				continue;
			}

			/**
			 * @var Item $entity
			 */
			$entity = $this->getEntity( $id );

			foreach ( $entity->getSiteLinks() as $link ) {
				if ( $this->linkMatches( $entity, $link, $itemIds, $siteIds, $pageNames ) ) {
					$links[] = array(
						$link->getSiteId(),
						$link->getPageName(),
						$entity->getId()->getNumericId(),
					);
				}
			}
		}

		return $links;
	}

	/**
	 * Returns true if the link matches the given conditions.
	 *
	 * @param Item     $item
	 * @param SimpleSiteLink $link
	 * @param array              $itemIds
	 * @param array              $siteIds
	 * @param array              $pageNames
	 *
	 * @return bool
	 */
	protected function linkMatches( Item $item, SimpleSiteLink $link,
		array $itemIds, array $siteIds = array(), array $pageNames = array() ) {

		if ( !empty( $itemIds ) ) {
			if ( !in_array( $item->getId()->getNumericId(), $itemIds ) ) {
				return false;
			}
		}

		if ( !empty( $siteIds ) ) {
			if ( !in_array( $link->getSiteId(), $siteIds ) ) {
				return false;
			}
		}

		if ( !empty( $pageNames ) ) {
			if ( !in_array( $link->getPageName(), $pageNames ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Fetches the entities with provided ids and returns them.
	 * The result array contains the prefixed entity ids as keys.
	 * The values are either an Entity or null, if there is no entity with the associated id.
	 *
	 * The revisions can be specified as an array holding an integer element for each
	 * id in the $entityIds array or false for latest. If all should be latest, false
	 * can be provided instead of an array.
	 *
	 * @since 0.4
	 *
	 * @param EntityID[] $entityIds
	 *
	 * @return Entity|null[]
	 */
	public function getEntities( array $entityIds ) {
		$entities = array();

		foreach ( $entityIds as $key => $entityId ) {

			if ( is_string( $entityId ) ) {
				$entityId = EntityId::newFromPrefixedId( $entityId );
			}

			$entities[$entityId->getPrefixedId()] = $this->getEntity( $entityId );
		}

		return $entities;
	}

	/**
	 * @see SiteLinkLookup::getSiteLinksForItem
	 *
	 * Returns an array of SiteLink for an EntityId.
	 *
	 * If the entity isn't known or not an Item, an empty array is returned.
	 *
	 * @since 0.4
	 *
	 * @param ItemId $itemId
	 *
	 * @return SimpleSiteLink[]
	 */
	public function getSiteLinksForItem( ItemId $itemId ) {
		$entity = $this->getEntity( $itemId );

		if ( $entity instanceof Item ) {
			return $entity->getSiteLinks();
		}

		// FIXME: throw InvalidArgumentException rather then failing silently
		return array();
	}

	/**
	 * Returns these claims from the given entity that have a main Snak for the property
	 * identified by $propertyLabel in the language given by $langCode.
	 *
	 * @since    0.4
	 *
	 * @param Entity $entity
	 * @param string $propertyLabel
	 * @param string $langCode
	 *
	 * @return Claims
	 */
	public function getClaimsByPropertyLabel( Entity $entity, $propertyLabel, $langCode ) {
		$prop = $this->getPropertyByLabel( $propertyLabel, $langCode );

		if ( !$prop ) {
			return new Claims();
		}

		$allClaims = new Claims( $entity->getClaims() );
		$theClaims = $allClaims->getClaimsForProperty( $prop->getId()->getNumericId() );

		return $theClaims;
	}

	public function getPropertyByLabel( $propertyLabel, $langCode ) {
		$ids = array_keys( $this->entities );

		foreach ( $ids as $id ) {
			$id = EntityId::newFromPrefixedId( $id );
			$entity = $this->getEntity( $id );

			if ( $entity->getType() !== Property::ENTITY_TYPE ) {
				continue;
			}

			$labels = $entity->getLabels( array( $langCode) );

			if ( empty( $labels ) ) {
				continue;
			}

			$label = reset( $labels );
			if ( $label !== $propertyLabel ) {
				continue;
			}

			return $entity;
		}

		return null;
	}

	/**
	 * Builds basic stubs of entity info records based on the given list of entity IDs.
	 *
	 * @param EntityId[] $ids
	 *
	 * @return array A map of prefixed entity IDs to records representing an entity each.
	 */
	public function buildEntityInfo( array $ids ) {
		$entityInfo = array();

		foreach ( $ids as $id ) {
			$prefixedId = $id->getPrefixedId();

			$entityInfo[$prefixedId] = array(
				'id' => $prefixedId,
				'type' => $id->getEntityType(),
			);
		}

		return $entityInfo;
	}

	/**
	 * Adds terms (like labels and/or descriptions) to
	 *
	 * @param array $entityInfo a map of strings to arrays, each array representing an entity,
	 *        with the key being the entity's ID. NOTE: This array will be updated!
	 * @param array $types Which types of terms to include (e.g. "label", "description", "aliases").
	 * @param array $languages Which languages to include
	 */
	public function addTerms( array &$entityInfo, array $types = null, array $languages = null ) {
		foreach ( $entityInfo as $id => &$entityRecord ) {
			$id = EntityId::newFromPrefixedId( $id );
			$entity = $this->getEntity( $id );

			if ( !$entity ) {
				// hack: fake an empty entity, so the field get initialized
				$entity = Item::newEmpty();
			}

			if ( $types === null || in_array( 'label', $types ) ) {
				$this->injectLabels( $entityRecord, $entity, $languages );
			}

			if ( $types === null || in_array( 'description', $types ) ) {
				$this->injectDescriptions( $entityRecord, $entity, $languages );
			}

			if ( $types === null || in_array( 'alias', $types ) ) {
				$this->injectAliases( $entityRecord, $entity, $languages );
			}
		}
	}

	private function injectLabels( array &$entityRecord, Entity $entity, $languages ) {
		$labels = $entity->getLabels( $languages );

		if ( !isset( $entityRecord['labels'] ) ) {
			$entityRecord['labels'] = array();
		}

		foreach ( $labels as $lang => $text ) {
			$entityRecord['labels'][$lang] = array(
				'language' => $lang,
				'value' => $text,
			);
		}
	}

	private function injectDescriptions( array &$entityRecord, Entity $entity, $languages ) {
		$descriptions = $entity->getDescriptions( $languages );

		if ( !isset( $entityRecord['descriptions'] ) ) {
			$entityRecord['descriptions'] = array();
		}

		foreach ( $descriptions as $lang => $text ) {
			$entityRecord['descriptions'][$lang] = array(
				'language' => $lang,
				'value' => $text,
			);
		}
	}

	private function injectAliases( array &$entityRecord, Entity $entity, $languages ) {
		if ( $languages === null ) {
			$languages = array_keys( $entity->getAllAliases() );
		}

		if ( !isset( $entityRecord['aliases'] ) ) {
			$entityRecord['aliases'] = array();
		}

		foreach ( $languages as $lang ) {
			$aliases = $entity->getAliases( $lang );

			foreach ( $aliases as $text ) {
				$entityRecord['aliases'][$lang][] = array( // note: append
					'language' => $lang,
					'value' => $text,
				);
			}
		}
	}

	/**
	 * Adds property data types to the entries in $entityInfo. Entities that do not have a data type
	 * remain unchanged.
	 *
	 * @param array $entityInfo a map of strings to arrays, each array representing an entity,
	 *        with the key being the entity's ID. NOTE: This array will be updated!
	 */
	public function addDataTypes( array &$entityInfo ) {
		foreach ( $entityInfo as $id => &$entityRecord ) {
			$id = EntityId::newFromPrefixedId( $id );

			if ( $id->getEntityType() !== Property::ENTITY_TYPE ) {
				continue;
			}

			$entity = $this->getEntity( $id );

			if ( !$entity ) {
				$entityRecord['datatype'] = null;
			} elseif ( $entity instanceof Property ) {
				$entityRecord['datatype'] = $entity->getDataTypeId();
			}
		}
	}

	/**
	 * Adds property data types to the entries in $entityInfo. Entities that do not have a data type
	 * remain unchanged.
	 *
	 * @param array $entityInfo a map of strings to arrays, each array representing an entity,
	 *        with the key being the entity's ID. NOTE: This array will be updated!
	 */
	public function removeMissing( array &$entityInfo ) {
		foreach ( array_keys( $entityInfo ) as $key ) {
			$id = EntityId::newFromPrefixedId( $key );
			$entity = $this->getEntity( $id );

			if ( !$entity ) {
				unset( $entityInfo[$key] );
			}
		}
	}

	/**
	 * @see PropertyDataTypeLookup::getDataTypeIdForProperty()
	 *
	 * @since 0.5
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return string
	 * @throws PropertyNotFoundException
	 */
	public function getDataTypeIdForProperty( PropertyId $propertyId ) {
		/* @var Property $property */
		$property = $this->getEntity( $propertyId );

		if ( !$property ) {
			throw new PropertyNotFoundException( $propertyId );
		}

		return $property->getDataTypeId();
	}

	/**
	 * Returns the id of the latest revision of the given entity, or false if there is no such entity.
	 *
	 * @param EntityID $entityId
	 *
	 * @return int|false
	 */
	public function getLatestRevisionId( EntityId $entityId ) {
		$rev = $this->getEntityRevision( $entityId );

		return $rev === null ? false : $rev->getRevision();
	}
}
