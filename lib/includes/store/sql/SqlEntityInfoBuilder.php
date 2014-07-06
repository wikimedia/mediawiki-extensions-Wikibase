<?php

namespace Wikibase\Lib\Store\Sql;

use InvalidArgumentException;
use OutOfBoundsException;
use ResultWrapper;
use RuntimeException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel;
use Wikibase\EntityId;
use Wikibase\Lib\Store\EntityInfoBuilder;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\UnresolvedRedirectException;
use Wikibase\Property;

/**
 * Class EntityInfoBuilder implementation relying on database access.
 *
 * @since 0.5
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class SqlEntityInfoBuilder extends \DBAccessBase implements EntityInfoBuilder {

	/**
	 * Maps term types to fields used for lists of these terms in entity serializations.
	 *
	 * @var array
	 */
	static $termTypeFields = array(
		'label' => 'labels',
		'description' => 'descriptions',
		'alias' => 'aliases',
	);

	/**
	 * @var string The name of the database table holding terms.
	 */
	protected $termTable;

	/**
	 * @var string The name of the database table holding property info.
	 */
	protected $propertyInfoTable;

	/**
	 * @var string The name of the database table connecting entities to pages.
	 */
	protected $entityPerPageTable;

	/**
	 * @var EntityRevisionLookup High level lookup used to resolve redirects.
	 */
	protected $entityRevisionLookup;

	/**
	 * EntityId objects indexed by serialized ID. This allows us to re-use
	 * the original EntityId object and avoids parsing the string again.
	 *
	 * @see getEntityId()
	 *
	 * @var EntityId[] id-string -> EntityId
	 */
	private $entityIds = null;

	/**
	 * The entity info data structure. This data structure is exposed via getEntityInfo().
	 *
	 * @see EntityInfoBuilder::getEntityInfo()
	 *
	 * @var array[] id-string -> entity-record-array
	 */
	private $entityInfo = null;

	/**
	 * Maps of id strings to numeric ids, grouped by entity type.
	 * Used to build database queries on tables that use separate
	 * fields for type and numeric id.
	 *
	 * @var array[] type -> id-string -> int
	 */
	private $numericIdsByType = null;

	/**
	 * Maps of id strings to numeric ids, grouped by entity type,
	 * of entity IDs with no corresponding Entity in the database.
	 * This uses the same structure as $this->numericIdsByType.
	 *
	 * Initialized lazily by getMissingIdsByType().
	 *
	 * @var array[] type -> id-string -> int
	 */
	private $missingIdsByType = null;

	/**
	 * A map of entity id strings to EntityId objects, representing any
	 * redirects present in the list of entities provided to the constructor.
	 *
	 * Initialized lazily by resolveRedirects().
	 *
	 * @var string[] id-string -> EntityId
	 */
	private $redirects = null;

	/**
	 * @param EntityId[] $ids
	 * @param EntityRevisionLookup $entityRevisionLookup
	 * @param string|bool $wiki The wiki's database to connect to.
	 *        Must be a value LBFactory understands. Defaults to false, which is the local wiki.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $ids, EntityRevisionLookup $entityRevisionLookup, $wiki = false ) {
		if ( !is_string( $wiki ) && $wiki !== false ) {
			throw new InvalidArgumentException( '$wiki must be a string or false.' );
		}

		parent::__construct( $wiki );

		$this->termTable = 'wb_terms';
		$this->propertyInfoTable = 'wb_property_info';
		$this->entityPerPageTable = 'wb_entity_per_page';

		$this->entityRevisionLookup = $entityRevisionLookup;

		$this->setEntityIds( $ids );
	}

	/**
	 * @param EntityId[] $ids
	 *
	 * @throws RuntimeException If called more than once.
	 */
	private function setEntityIds( $ids ) {
		if ( $this->entityIds !== null ) {
			throw new \RuntimeException( 'EntityIds have already been initialized' );
		}

		$this->entityIds = array();
		$this->entityInfo = array();
		$this->numericIdsByType = array();

		foreach ( $ids as $id ) {
			$key = $id->getSerialization();
			$type = $id->getEntityType();

			$this->entityInfo[$key] = array(
				'id' => $key,
				'type' => $type,
			);

			$this->numericIdsByType[$type][$key] = $id->getNumericId();
			$this->entityIds[$key] = $id;
		}
	}

	/**
	 * @see EntityInfoBuilder::getEntityInfo
	 *
	 * @return array[]
	 */
	public function getEntityInfo() {
		return $this->entityInfo;
	}

	/**
	 * @see EntityInfoBuilder::resolveRedirects
	 */
	public function resolveRedirects() {
		if ( $this->redirects !== null ) {
			// already done
			return;
		}

		$this->redirects = $this->findRedirects();

		foreach ( $this->redirects as $key => $targetId ) {
			$this->applyRedirect( $key, $targetId );
		}
	}

	/**
	 * Applied the given redirect to the internal data structure
	 *
	 * @param string $idString The redirected entity id
	 * @param EntityId $targetId The redirect target
	 */
	private function applyRedirect( $idString, EntityId $targetId) {
		$redirectedId = $this->getEntityId( $idString );
		$type = $redirectedId->getEntityType();

		$targetKey = $targetId->getSerialization();

		if ( $idString === $targetKey ) {
			// Sanity check: self-redirect, nothing to do.
			return;
		}

		// If the redirect target doesn't have a record yet, copy the old record.
		// Since two IDs may be redirected to the same target, this may already have
		// happened.
		if ( !isset( $this->entityInfo[$targetKey] ) ) {
			$this->entityInfo[$targetKey] = $this->entityInfo[$idString]; // copy
			$this->entityInfo[$targetKey]['id'] = $targetKey; // update id
		}

		// Make the redirected key a reference to the target record.
		unset( $this->entityInfo[$idString] ); // just to be sure not to cause a mess
		$this->entityInfo[$idString] = & $this->entityInfo[$targetKey];

		// Remove the numeric id of the redirect, since we don't want to
		// use it in database queries.
		unset( $this->numericIdsByType[$type][$idString] );

		// Record the id of the target.
		$this->numericIdsByType[$type][$targetKey] = $targetId->getNumericId();
		$this->entityIds[$targetKey] = $targetId;
	}

	/**
	 * Applies a default value to the given field in each entity record.
	 *
	 * @param string $field the field to assign the default value to
	 * @param mixed $default the default value
	 * @param callable|null $filter A filter callback; if given, only records that match
	 *        the filter will be updated. The callback gets the entity record as the only
	 *        parameter, and must return a boolean.
	 */
	private function setDefaultValue( $field, $default, $filter = null ) {
		foreach ( $this->entityInfo as &$entity ) {
			if ( $filter !== null ) {
				$match = call_user_func( $filter, $entity );

				if ( !$match ) {
					continue;
				}
			}

			if ( !isset( $entity[$field] ) ) {
				$entity[$field] = $default;
			}
		}
	}

	/**
	 * @see EntityInfoBuilder::collectTerms
	 *
	 * @param array $termTypes Which types of terms to include (e.g. "label", "description", "aliases").
	 * @param array $languages Which languages to include
	 */
	public function collectTerms( array $termTypes = null, array $languages = null ) {
		if ( $termTypes === array() || $languages === array() ) {
			// nothing to do
			return;
		}

		wfProfileIn( __METHOD__ );

		//NOTE: we make one DB query per entity type, so we can take advantage of the
		//      database index on the term_entity_type field.
		foreach ( array_keys( $this->numericIdsByType ) as $type ) {
			$this->collectTermsForEntities( $type, $termTypes, $languages );
		}

		if ( $termTypes === null ) {
			$termTypes = array_keys( self::$termTypeFields );
		}

		foreach ( $termTypes as $type ) {
			$this->setDefaultValue( self::$termTypeFields[$type], array() );
		}

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Collects the terms for a number of entities (of the given types, in the given languages)
	 *
	 * @param $entityType
	 * @param array $termTypes
	 * @param array $languages
	 */
	private function collectTermsForEntities( $entityType, array $termTypes = null, array $languages = null ) {
		wfProfileIn( __METHOD__ );

		$entityIds = $this->numericIdsByType[$entityType];

		$where = array(
			'term_entity_type' => $entityType,
			'term_entity_id' => $entityIds,
		);

		if ( $termTypes ) {
			$where['term_type'] = $termTypes;
		}

		if ( $languages ) {
			$where['term_language'] = $languages;
		}

		$dbw = $this->getConnection( DB_SLAVE );

		$res = $dbw->select(
			$this->termTable,
			array( 'term_entity_type', 'term_entity_id', 'term_type', 'term_language', 'term_text' ),
			$where,
			__METHOD__
		);

		$this->injectTerms( $res );

		$this->releaseConnection( $dbw );

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Injects terms from a DB result into the $entityInfo structure.
	 *
	 * @note: Keep in sync with EntitySerializer!
	 *
	 * @param ResultWrapper $dbResult
	 *
	 * @throws \InvalidArgumentException
	 */
	private function injectTerms( $dbResult ) {
		foreach ( $dbResult as $row ) {
			// this is deprecated, but I don't see an alternative.
			$id = new EntityId( $row->term_entity_type, (int)$row->term_entity_id );
			$key = $id->getPrefixedId();

			if ( !isset( $this->entityInfo[$key] ) ) {
				continue;
			}

			$field = self::$termTypeFields[$row->term_type];

			switch ( $row->term_type ) {
				case 'label':
					$this->injectLabel( $this->entityInfo[$key][$field], $row->term_language, $row->term_text );
					break;
				case 'description':
					$this->injectDescription( $this->entityInfo[$key][$field], $row->term_language, $row->term_text );
					break;
				case 'alias':
					$this->injectAlias( $this->entityInfo[$key][$field], $row->term_language, $row->term_text );
					break;
				default:
					wfDebugLog( __CLASS__, __FUNCTION__ . ': unknown term type: ' . $row->term_type );
			}
		}
	}

	private function injectLabel( &$termList, $language, $text ) {
		$termList[$language] = array(
			'language' => $language,
			'value' => $text,
		);
	}

	private function injectDescription( &$termList, $language, $text ) {
		$termList[$language] = array(
			'language' => $language,
			'value' => $text,
		);
	}

	private function injectAlias( &$termList, $language, $text ) {
		$termList[$language][] = array( // note that we are appending here.
			'language' => $language,
			'value' => $text,
		);
	}

	/**
	 * @see EntityInfoBuilder::collectDataTypes
	 */
	public function collectDataTypes() {
		//TODO: use PropertyDataTypeLookup service to make use of caching!

		wfProfileIn( __METHOD__ );

		if ( empty( $this->numericIdsByType[Property::ENTITY_TYPE] ) ) {
			// there are no Property entities, so there is nothing to do.
			return;
		}

		$numericPropertyIds = $this->numericIdsByType[Property::ENTITY_TYPE];

		$dbw = $this->getConnection( DB_SLAVE );

		$res = $dbw->select(
			$this->propertyInfoTable,
			array( 'pi_property_id', 'pi_type' ),
			array( 'pi_property_id' => $numericPropertyIds ),
			__METHOD__
		);

		$this->injectDataTypes( $res );
		$this->setDefaultValue( 'datatype', null, function( $entity ) {
			return $entity['type'] === Property::ENTITY_TYPE;
		} );

		$this->releaseConnection( $dbw );

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Injects data types from a DB result into the $entityInfo structure.
	 *
	 * @note: Keep in sync with ItemSerializer!
	 *
	 * @param ResultWrapper $dbResult
	 *
	 * @throws InvalidArgumentException
	 */
	private function injectDataTypes( $dbResult ) {
		foreach ( $dbResult as $row ) {
			$id = PropertyId::newFromNumber( (int)$row->pi_property_id );
			$key = $id->getPrefixedId();

			if ( !isset( $this->entityInfo[$key] ) ) {
				continue;
			}

			$this->entityInfo[$key]['datatype'] = $row->pi_type;
		}
	}

	/**
	 * @see EntityInfoBuilder::removeMissing
	 */
	public function removeMissing() {
		wfProfileIn( __METHOD__ );

		$missingIds = $this->getMissingIds();
		$missingPrefixedIds = array_keys( $missingIds );

		if ( !empty( $this->redirects ) ) {
			// keep any redirects
			$missingPrefixedIds = array_diff_key( $missingPrefixedIds, array_keys( $this->redirects ) );
		}

		if ( !empty( $missingPrefixedIds ) ) {
			$this->unsetEntityInfo( $missingPrefixedIds );
		}

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Lists IDs of missing entities, grouped by type. These ids represent a subset of the
	 * entity ids provided to the constructor that do not correspond to actual entities.
	 * The corresponding entities may have been deleted, may have never existed, or may
	 * be redirects.
	 *
	 * @return array[] Maps entity types to arrays that associate the id string of non-existing
	 * entities with the respective numeric id.
	 *
	 */
	private function getMissingIdsByType() {
		if ( $this->missingIdsByType !== null ) {
			return $this->missingIdsByType;
		}

		wfProfileIn( __METHOD__ );

		$this->missingIdsByType = array();

		//NOTE: we make one DB query per entity type, so we can take advantage of the
		//      database index on the epp_entity_type field.
		foreach ( $this->numericIdsByType as $type => $idsForType ) {
			$pageIds = $this->getPageIdsForEntities( $type, $idsForType );
			$this->missingIdsByType[$type] = array_diff( $idsForType, array_keys( $pageIds ) );
		}

		wfProfileOut( __METHOD__ );
		return $this->missingIdsByType;
	}

	/**
	 * Removes the given list of IDs from all internal data structures.
	 *
	 * @param string[] $ids
	 */
	private function unsetEntityInfo( $ids ) {
		$this->entityInfo = array_diff_key( $this->entityInfo, array_flip( $ids ) );

		foreach ( $this->numericIdsByType as &$numeridIds ) {
			$numeridIds = array_diff_key( $numeridIds, array_flip( $ids ) );
		}

		// remove empty entries
		$this->numericIdsByType = array_filter( $this->numericIdsByType );
	}

	/**
	 * Creates a mapping from the given entity IDs to the corresponding page IDs.
	 *
	 * @param string $entityType
	 * @param array $entityIds
	 *
	 * @return array A map of (numeric) entity IDs to page ids.
	 */
	private function getPageIdsForEntities( $entityType, $entityIds ) {
		wfProfileIn( __METHOD__ );

		$pageIds = array();

		$dbw = $this->getConnection( DB_SLAVE );

		$res = $dbw->select(
			$this->entityPerPageTable,
			array( 'epp_entity_type', 'epp_entity_id', 'epp_page_id' ),
			array(
				'epp_entity_type' => $entityType,
				'epp_entity_id' => $entityIds,
			),
			__METHOD__
		);

		foreach ( $res as $row ) {
			$pageIds[$row->epp_entity_id] = $row->epp_page_id;
		}

		$this->releaseConnection( $dbw );

		wfProfileOut( __METHOD__ );

		return $pageIds;
	}

	/**
	 * Returns an EntityId object for the given serialized ID.
	 * This is implemented as a lookup of the original EntityId object supplied
	 * to the constructor (or found during redirect resolution).
	 *
	 * @param string $idString the serialized id
	 *
	 * @return EntityId
	 * @throws OutOfBoundsException If the ID string does not correspond to any ID
	 * supplied to the constructor (or found during redirect resolution).
	 */
	private function getEntityId( $idString ) {
		if ( !isset( $this->entityIds[$idString] ) ) {
			throw new OutOfBoundsException( 'Unknown ID: ' . $idString );
		}

		return $this->entityIds[$idString];
	}

	/**
	 * @return EntityId[] The subset of EntityIds supplied to the constructor that
	 * do not represent actual entities. They may have been deleted, have never existed,
	 * or be redirects.
	 */
	private function getMissingIds() {
		// find redirects based on missing ids,
		// because the current logic for findRedirects is slow.
		$missingIdsbyType = $this->getMissingIdsByType();

		// flip and flatten to get a list of id strings
		$missingIdStrings = array_reduce(
			$missingIdsbyType,
			function ( $acc, $next ) {
				return array_merge( $acc, array_keys( $next ) );
			},
			array()
		);

		$missingIds = array();

		foreach ( $missingIdStrings as $idString ) {
			$missingIds[$idString] = $this->getEntityId( $idString );
		}

		return $missingIds;
	}

	/**
	 * Finds and returns any redirects from the set of entities suppied to the constructor.
	 *
	 * @note: The current implementation is rather inefficient in cases where there are lots of
	 * redirects or missing entities. It first finds uses getMissingIds() to find all ids not
	 * present in the entity_per_page table. These are potential redirects (but may also be
	 * deleted or otherwise missing entities). It then tries to load each of the potential
	 * redirects from the full serialized blob. This could be sped up by recording the redirects
	 * in a separate database table.
	 * Assuming that entities and redirects are relatively rare in a well maintained
	 * Wikibase instance, the present implementation should be ok for now.
	 *
	 * @return EntityId[] An associative array mapping id strings to EntityIds representing
	 * the redirect targets.
	 */
	private function findRedirects() {
		// find redirects based on missing ids,
		// because the current logic for findRedirects is slow.
		$missingIds = $this->getMissingIds();

		$redirects = array();

		foreach ( $missingIds as $key => $id ) {
			// NOTE: We are really only interested in the exception.
			try {
				$this->entityRevisionLookup->getEntityRevision( $id );
			} catch ( UnresolvedRedirectException $ex ) {
				$redirects[$key] = $ex->getRedirectTargetId();
			}
		}

		return $redirects;
	}
}
