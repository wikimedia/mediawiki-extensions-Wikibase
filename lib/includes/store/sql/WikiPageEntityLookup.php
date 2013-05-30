<?php

namespace Wikibase;
use MWException;
use Wikibase\Lib\EntityRetrievingDataTypeLookup;

/**
 * Implements an entity repo based on blobs stored in wiki pages on a locally reachable
 * database server. This class also supports memcached (or accellerator) based caching
 * of entities.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.3
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class WikiPageEntityLookup extends \DBAccessBase implements EntityLookup, EntityRevisionLookup {

	/**
	 * The cache type to use for caching entities in memory. Use false to disable caching.
	 * Note that only the latest revision of an entity is cached.
	 *
	 * @var string $cacheType
	 */
	protected $cacheType;

	/**
	 * The key prefix to use when caching entities in memory.
	 *
	 * @var $cacheKeyPrefix
	 */
	protected $cacheKeyPrefix;

	/**
	 * @var int $cacheTimeout
	 */
	protected $cacheTimeout = 3600;

	/**
	 * @param String|bool $wiki           The name of thw wiki database to use, in a form
	 *                                    that wfGetLB() understands. Use false to indicate the local wiki.
	 * @param bool|int|null $cacheType      The cache type ID for the cache to use for
	 *                                    caching entities in memory. Defaults to $wgMainCacheType.
	 *                                    Set it to false to disable caching, or specify a different
	 *                                    cache type using the CACHE_XXX constants. Set to false to
	 *                                    disable caching.
	 *                                    Note that the $wiki parameter determines the cache compartment,
	 *                                    so multiple wikis loading entities from the same repository
	 *                                    will share the cache.
	 * @param string      $cacheKeyPrefix The key prefix to use for constructing cache keys.
	 *                                    Defaults to "wbentity". There should be no reason to change this.
	 *
	 * @return \Wikibase\WikiPageEntityLookup
	 */
	public function __construct( $wiki = false, $cacheType = null, $cacheKeyPrefix = "wbentity" ) {
		parent::__construct( $wiki );

		if ( $cacheType === null ) {
			$cacheType = $GLOBALS[ 'wgMainCacheType' ];
		}

		$this->cacheType = $cacheType; //FIXME: take from Settings: defaultEntityCache
		$this->cacheKeyPrefix = $cacheKeyPrefix;
	}

	/**
	 * Returns a cache key suitable for the given entity
	 *
	 * @param EntityId $entityId
	 *
	 * @return String
	 */
	protected function getEntityCacheKey( EntityId $entityId ) {
		global $wgCachePrefix;

		if ( $this->wiki === false ) {
			$keyDB = $wgCachePrefix === false ? wfWikiID() : $wgCachePrefix;
		} else {
			$keyDB = $this->wiki;
		}

		$cacheKey = wfForeignMemcKey( $keyDB, $this->cacheKeyPrefix, $entityId->getPrefixedId() );
		return $cacheKey;
	}

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
	public function getEntity( EntityID $entityId, $revision = 0 ) {
		$entityRev = $this->getEntityRevision( $entityId, $revision );
		return $entityRev === null ? null : $entityRev->getEntity();
	}

	/**
	 * @since 0.4
	 * @see EntityRevisionLookup::getEntityRevision
	 *
	 * @param EntityID $entityId
	 * @param int $revision The desired revision id, 0 means "current".
	 *
	 * @return EntityRevision|null
	 * @throw StorageException
	 */
	public function getEntityRevision( EntityID $entityId, $revision = 0 ) {
		wfProfileIn( __METHOD__ );
		wfDebugLog( __CLASS__, __FUNCTION__ . ": Looking up entity " . $entityId
				. " (rev $revision)" );

		if ( $revision === false ) { // default changed from false to 0
			wfWarn( 'getEntityRevision() called with $revision = false, use 0 instead.' );
			$revision = 0;
		}

		$cache = null;
		$cacheKey = false;
		$cachedEntityRev = null;
		$cachedRev = false;

		if ( $this->cacheType !== false ) {
			$cacheKey = $this->getEntityCacheKey( $entityId );
			$cache = wfGetCache( $this->cacheType );
			$cached = $cache->get( $cacheKey );

			if ( !( $cached instanceof EntityRevision ) ) {
				wfDebugLog( __CLASS__, __FUNCTION__ . ": Found something strange in the cache for (key $cacheKey)." );
				$cached = false;
			}

			if ( $cached ) {
				wfDebugLog( __CLASS__, __FUNCTION__ . ": Found entity in cache (key $cacheKey)" );
				$cachedEntityRev = $cached;
				$cachedRev = $cachedEntityRev->getRevision();

				if ( $revision === $cachedRev ) {
					wfDebugLog( __CLASS__, __FUNCTION__ . ": Using cached entity (rev $cachedRev)" );
					wfProfileOut( __METHOD__ );
					return $cachedEntityRev;
				}

				// NOTE: if $revision is false, we first check whether the cached
				// revision is still the latest.
			}
		}

		$db = $this->getConnection( DB_READ );

		$opt = array();

		$tables = array(
			'page',
			'revision',
			'text'
		);

		$pageTable = $db->tableName( 'page' );
		$revisionTable = $db->tableName( 'revision' );
		$textTable = $db->tableName( 'text' );

		$vars = "$pageTable.*, $revisionTable.*, $textTable.*";

		$where = array();
		$join = array();

		if ( $revision > 0 ) {
			// pick revision by id
			$where['rev_id'] = $revision;

			// pick page via rev_page
			$join['page'] = array( 'INNER JOIN', 'page_id=rev_page' );

			// pick text via rev_text_id
			$join['text'] = array( 'INNER JOIN', 'old_id=rev_text_id' );

			wfDebugLog( __CLASS__, __FUNCTION__ . ": Looking up revision $revision of " . $entityId );
		} else {
			// entity to page mapping
			$tables[] = 'wb_entity_per_page';

			// pick entity by id
			$where['epp_entity_id'] = $entityId->getNumericId();
			$where['epp_entity_type'] = $entityId->getEntityType();

			// pick page via epp_page_id
			$join['page'] = array( 'INNER JOIN', 'epp_page_id=page_id' );

			// pick latest revision via page_latest
			$join['revision'] = array( 'INNER JOIN', 'page_latest=rev_id' );

			// pick text via rev_text_id
			$join['text'] = array( 'INNER JOIN', 'old_id=rev_text_id' );

			wfDebugLog( __CLASS__, __FUNCTION__ . ": Looking up latest revision of " . $entityId );
		}

		$res = $db->select( $tables, $vars, $where, __METHOD__, $opt, $join );

		if ( !$res ) {
			// this can only happen if the DB is set to ignore errors, which shouldn't be the case...
			$error = $db->lastError();
			$enum = $db->lastErrno();
			wfWarn( "Database Error in " . __METHOD__ . " #$enum: $error" );
		}

		$entityRev = null;

		if ( $row = $res->fetchObject() ) {

			if ( $cachedRev !== false && intval( $row->rev_id ) === intval( $cachedRev ) ) {
				// the revision we loaded is the cached one, use the cached entity
				wfDebugLog( __CLASS__, __FUNCTION__ . ": Using cached entity (rev $cachedRev is latest)" );
				wfProfileOut( __METHOD__ );
				return $cachedEntityRev;
			}

			$entityRev = $this->loadEntity( $entityId->getEntityType(), $row );
		} else {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": Entity not loaded for " . $entityId );
		}

		$this->releaseConnection( $db );

		if ( $entityRev && !$entityId->equals( $entityRev->getEntity()->getId() ) ) {
			// This can happen when giving a revision ID that doesn't belong to the given entity
			wfDebugLog( __CLASS__, __FUNCTION__ . ": Loaded wrong entity: expected " . $entityId
							. ", got " . $entityRev->getEntity()->getId());

			$entityRev = null;
		}

		if ( $entityRev === null && $revision > 0 ) {
			// If a revision was specified, that revision doesn't exist or doesn't belong to
			// the given entity. Throw an error.
			throw new StorageException( "No such revision found for $entityId: $revision" );
		}

		// cacheable if it's the latest revision.
		if ( $cache && $row && $entityRev
			&& $row->page_latest === $row->rev_id ) {

			if ( $cachedRev !== false ) {
				wfDebugLog( __CLASS__, __FUNCTION__ . ": Updating cached version of " . $entityId );
				$cache->replace( $cacheKey, $entityRev, $this->cacheTimeout );
			} else {
				wfDebugLog( __CLASS__, __FUNCTION__ . ": Adding cached version of " . $entityId );
				$cache->add( $cacheKey, $entityRev, $this->cacheTimeout );
			}
		} else if ( $cachedRev !== false ) {
			// no longer the latest version
			wfDebugLog( __CLASS__, __FUNCTION__ . ": Removing cached version of " . $entityId );
			$cache->delete( $cacheKey );
		}

		wfProfileOut( __METHOD__ );
		return $entityRev;
	}

	/**
	 * @see EntityLookup::getEntities
	 *
	 * @since 0.4
	 *
	 * @param EntityID[] $entityIds
	 *
	 * @return Entity|null[]
	 */
	public function getEntities( array $entityIds ) {
		$entities = array();

		// TODO: we really want batch lookup here :)
		foreach ( $entityIds as $key => $entityId ) {

			$entities[$entityId->getPrefixedId()] = $this->getEntity( $entityId );
		}

		return $entities;
	}

	/**
	 * Construct an EntityRevision object from a database row from the revision and text tables.
	 *
	 * This calls Revision::getRevisionText to resolve any additional indirections in getting
	 * to the actual blob data, like the "External Store" mechanism used by Wikipedia & co.
	 *
	 * @param Object $row a row object as expected \Revision::getRevisionText(), that is, it
	 *        should contain the relevant fields from the revision and/or text table.
	 * @param String $entityType The entity type ID, determines what kind of object is constructed
	 *        from the blob in the database.
	 *
	 * @return EntityRevision|null
	 */
	protected function loadEntity( $entityType, $row ) {
		wfProfileIn( __METHOD__ );

		wfDebugLog( __CLASS__, __FUNCTION__ . ": calling getRevisionText() on rev " . $row->rev_id );

		//NOTE: $row contains revision fields from another wiki. This SHOULD not
		//      cause any problems, since getRevisionText should only look at the old_flags
		//      and old_text fields. But be aware.
		$blob = \Revision::getRevisionText( $row, 'old_', $this->wiki );

		if ( $blob === false ) {
			// oops. something went wrong.
			wfWarn( "Unable to load raw content blob for rev " . $row->rev_id );
			wfProfileOut( __METHOD__ );
			return null;
		}

		$format = $row->rev_content_format;
		$entity = EntityFactory::singleton()->newFromBlob( $entityType, $blob, $format );
		$entityRev = new EntityRevision( $entity, (int)$row->rev_id, $row->rev_timestamp );

		wfDebugLog( __CLASS__, __FUNCTION__ . ": Created entity object from revision blob: "
			. $entity->getId() );

		wfProfileOut( __METHOD__ );
		return $entityRev;
	}
}
