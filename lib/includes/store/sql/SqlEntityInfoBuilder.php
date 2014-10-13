<?php

namespace Wikibase\Lib\Store\Sql;

use DBAccessBase;
use InvalidArgumentException;
use ResultWrapper;
use RuntimeException;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\LegacyIdInterpreter;
use Wikibase\Lib\Store\EntityInfoBuilder;

/**
 * Class EntityInfoBuilder implementation relying on database access.
 *
 * @since 0.5
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class SqlEntityInfoBuilder extends DBAccessBase implements EntityInfoBuilder {

	/**
	 * Maps term types to fields used for lists of these terms in entity serializations.
	 *
	 * @var string[]
	 */
	static $termTypeFields = array(
		'label' => 'labels',
		'description' => 'descriptions',
		'alias' => 'aliases',
	);

	/**
	 * @var string The name of the database table holding terms.
	 */
	private $termTable;

	/**
	 * @var string The name of the database table holding property info.
	 */
	private $propertyInfoTable;

	/**
	 * @var string The name of the database table connecting entities to pages.
	 */
	private $entityPerPageTable;

	/**
	 * @var bool
	 */
	private $useRedirectTargetColumn;

	/**
	 * EntityId objects indexed by serialized ID. This allows us to re-use
	 * the original EntityId object and avoids parsing the string again.
	 *
	 * @see getEntityId()
	 *
	 * @var EntityId[] map of id-strings to EntityId objects: id-string => EntityId
	 */
	private $entityIds = null;

	/**
	 * The entity info data structure. This data structure is exposed via getEntityInfo().
	 * After resolveRedirects() is called, this will contain entries for the redirect targets
	 * in addition to the entries for the redirected IDs. Entries for the redirected IDs
	 * will be php references to the entries that use the actual (target) IDs as keys.
	 *
	 * @see EntityInfoBuilder::getEntityInfo()
	 *
	 * @var array[] map of id-strings to entity-record arrays:
	 *      id-string => record
	 */
	private $entityInfo = null;

	/**
	 * Maps of id strings to numeric ids, grouped by entity type.
	 * Used to build database queries on tables that use separate
	 * fields for type and numeric id.
	 *
	 * @var array[] map of entity types to maps of id-strings to numeric ids:
	 *      type => id-string => id-int
	 */
	private $numericIdsByType = array();

	/**
	 * Maps of id strings to page info records, grouped by entity type.
	 * This uses the same basic structure as $this->numericIdsByType.
	 * Each page info record is an associative array with keys page_id
	 * and redirect_target.
	 *
	 * Initialized lazily by getPageInfoIdsByType().
	 *
	 * @var array[] map of entity type to maps of id-strings to numeric ids:
	 *      type => id-string => id-int
	 */
	private $pageInfoByType = null;

	/**
	 * A map of entity id strings to EntityId objects, representing any
	 * redirects present in the list of entities provided to the constructor.
	 *
	 * Initialized lazily by resolveRedirects().
	 *
	 * @var string[] map of id-string to EntityId objects:
	 *      id-string => EntityId
	 */
	private $redirects = null;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @param EntityId[] $ids
	 * @param bool $useRedirectTargetColumn
	 * @param string|bool $wiki The wiki's database to connect to.
	 *        Must be a value LBFactory understands. Defaults to false, which is the local wiki.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct( array $ids, $useRedirectTargetColumn = true, $wiki = false ) {
		if ( !is_string( $wiki ) && $wiki !== false ) {
			throw new InvalidArgumentException( '$wiki must be a string or false.' );
		}

		parent::__construct( $wiki );

		$this->termTable = 'wb_terms';
		$this->propertyInfoTable = 'wb_property_info';
		$this->entityPerPageTable = 'wb_entity_per_page';
		$this->useRedirectTargetColumn = $useRedirectTargetColumn;

		$this->idParser = new BasicEntityIdParser();

		$this->setEntityIds( $ids );
	}

	/**
	 * @param EntityId[] $ids
	 *
	 * @throws RuntimeException If called more than once.
	 */
	private function setEntityIds( array $ids ) {
		if ( $this->entityIds !== null ) {
			throw new RuntimeException( 'EntityIds have already been initialized' );
		}

		$this->entityIds = array();
		$this->entityInfo = array();
		$this->numericIdsByType = array();

		foreach ( $ids as $id ) {
			$this->updateEntityInfo( $id );
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
	 * Applied the given redirect to the internal data structure.
	 *
	 * After this method returns, the old ID will have been replaced by the target ID
	 * in the $entityInfo as well as the $numericIdsByType structures. In $entityInfo,
	 * the old key will remain as a reference to the entry under the new (target) key.
	 *
	 * @param string $idString The redirected entity id
	 * @param EntityId $targetId The redirect target
	 */
	private function applyRedirect( $idString, EntityId $targetId ) {
		$targetKey = $targetId->getSerialization();

		if ( $idString === $targetKey ) {
			// Sanity check: self-redirect, nothing to do.
			return;
		}

		// Copy the record for the old key to the target key.
		$this->initEntityInfo( $targetKey, $this->entityInfo[$idString] );

		// Remove the original entry for the old key.
		$this->unsetKey( $idString );

		// Make the redirected key a reference to the target record.
		$this->createEntityInfoReference( $idString, $this->entityInfo[$targetKey] );

		// From now on, use the target ID in the record and for database queries.
		$this->updateEntityInfo( $targetId );
	}

	/**
	 * Sets the given key in the $entityInfo data structure to a reference
	 * to the given record. This allows the same record to be accessed
	 * under multiple different keys.
	 *
	 * @param string $key
	 * @param array $record
	 */
	private function createEntityInfoReference( $key, array &$record ) {
		$this->entityInfo[$key] = &$record;
	}

	/**
	 * Removes any references to the given entity from the $entityInfo data
	 * structure as well as the $numericIdsByType cache, but not from
	 * the $entityIds cache.
	 *
	 * @param string $idString
	 */
	private function unsetKey( $idString ) {
		$id = $this->getEntityId( $idString );

		$type = $id->getEntityType();

		unset( $this->entityInfo[$idString] );
		unset( $this->numericIdsByType[$type][$idString] );
	}

	/**
	 * Sets the given key in the $entityInfo data structure to
	 * the given record if that key is not already set.
	 *
	 * @param string $key
	 * @param array $record
	 */
	private function initEntityInfo( $key, array $record ) {
		if ( !isset( $this->entityInfo[$key] ) ) {
			$this->entityInfo[$key] = $record;
		}
	}

	/**
	 * Updates the $entityInfo structure and makes the ID
	 * available via the $numericIdsByType and $entityIds caches.
	 *
	 * @param EntityId $id
	 */
	private function updateEntityInfo( EntityId $id ) {
		$type = $id->getEntityType();
		$key = $id->getSerialization();

		// NOTE: we assume that the type of entity never changes.
		$this->initEntityInfo( $key, array( 'type' => $type ) );

		$this->entityIds[$key] = $id;
		$this->entityInfo[$key]['id'] = $key;
		$this->numericIdsByType[$type][$key] = $id->getNumericId();
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
	 * @param string[]|null $termTypes Which types of terms to include (e.g. "label", "description", "aliases").
	 * @param string[]|null $languages Which languages to include
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
	 * @param string $entityType
	 * @param string[]|null $termTypes
	 * @param string[]|null $languages
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
	 * @throws InvalidArgumentException
	 */
	private function injectTerms( ResultWrapper $dbResult ) {
		foreach ( $dbResult as $row ) {
			// FIXME: this only works for items and properties
			$entityId = LegacyIdInterpreter::newIdFromTypeAndNumber( $row->term_entity_type, (int)$row->term_entity_id );
			$key = $entityId->getSerialization();

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

	/**
	 * @param string[]|null $termList
	 * @param string $language
	 * @param string $text
	 */
	private function injectLabel( &$termList, $language, $text ) {
		$termList[$language] = array(
			'language' => $language,
			'value' => $text,
		);
	}

	/**
	 * @param string[]|null $termList
	 * @param string $language
	 * @param string $text
	 */
	private function injectDescription( &$termList, $language, $text ) {
		$termList[$language] = array(
			'language' => $language,
			'value' => $text,
		);
	}

	/**
	 * @param array[]|null $termGroupList
	 * @param string $language
	 * @param string $text
	 */
	private function injectAlias( &$termGroupList, $language, $text ) {
		$termGroupList[$language][] = array( // note that we are appending here.
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
	private function injectDataTypes( ResultWrapper $dbResult ) {
		foreach ( $dbResult as $row ) {
			$id = PropertyId::newFromNumber( (int)$row->pi_property_id );
			$key = $id->getSerialization();

			if ( !isset( $this->entityInfo[$key] ) ) {
				continue;
			}

			$this->entityInfo[$key]['datatype'] = $row->pi_type;
		}
	}

	/**
	 * @see EntityInfoBuilder::removeMissing
	 */
	public function removeMissing( $redirects = 'keep-redirects' ) {
		wfProfileIn( __METHOD__ );

		$missingIds = $this->getMissingIds( $redirects !== 'keep-redirects' );

		$this->unsetEntityInfo( $missingIds );
		wfProfileOut( __METHOD__ );
	}

	/**
	 * Removes the given list of IDs from all internal data structures.
	 *
	 * @param string[] $ids
	 */
	private function unsetEntityInfo( array $ids ) {
		$this->entityInfo = array_diff_key( $this->entityInfo, array_flip( $ids ) );
		$this->entityIds = array_diff_key( $this->entityIds, array_flip( $ids ) );

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
	 *
	 * @return array A map of (numeric) entity IDs to page info record.
	 *         Each page info record is an associative array with the fields
	 *         page_id and redirect_target. Redirects are included.
	 */
	private function getPageInfoForType( $entityType ) {
		if ( isset( $this->pageInfoByType[$entityType] ) ) {
			return $this->pageInfoByType[$entityType];
		}

		wfProfileIn( __METHOD__ );

		$entityIds = $this->numericIdsByType[$entityType];

		$dbw = $this->getConnection( DB_SLAVE );

		$fields = array(
			'epp_entity_type',
			'epp_entity_id',
			'epp_page_id',
			$this->useRedirectTargetColumn
				? 'epp_redirect_target'
				: 'NULL AS epp_redirect_target'
		);

		$res = $dbw->select(
			$this->entityPerPageTable,
			$fields,
			array(
				'epp_entity_type' => $entityType,
				'epp_entity_id' => $entityIds,
			),
			__METHOD__
		);

		$idStrings = array_flip( $entityIds );

		$this->pageInfoByType[$entityType] = array();

		foreach ( $res as $row ) {
			$key = $idStrings[$row->epp_entity_id];

			$this->pageInfoByType[$entityType][$key] = array(
				'page_id' => $row->epp_page_id,
				'redirect_target' => $row->epp_redirect_target,
			);
		}

		$this->releaseConnection( $dbw );

		wfProfileOut( __METHOD__ );

		return $this->pageInfoByType[$entityType];
	}

	/**
	 * @return array[] Associative array containing a page info record for each entity ID.
	 *         Each page info record is an associative array with the fields
	 *         page_id and redirect_target. Redirects are included.
	 */
	private function getPageInfo() {
		$info = array();

		foreach ( $this->numericIdsByType as $type => $ids ) {
			$info[$type] = $this->getPageInfoForType( $type );
		}

		return $this->ungroup( $info );
	}

	/**
	 * Returns an EntityId object for the given serialized ID.
	 * This is implemented as a lookup of the original EntityId object supplied
	 * to the constructor (or found during redirect resolution).
	 *
	 * @param string $idString the serialized id
	 *
	 * @return EntityId
	 * @throws EntityIdParsingException If the ID is malformed.
	 */
	private function getEntityId( $idString ) {
		if ( !isset( $this->entityIds[$idString] ) ) {
			$this->entityIds[$idString] = $this->idParser->parse( $idString );
		}

		return $this->entityIds[$idString];
	}

	/**
	 * Flattens a grouped array structure into a flat array.
	 * Useful e.g. to convert "by type" structures into flat arrays
	 * with ID strings as keys.
	 *
	 * @param array[] $groupedArrays
	 *
	 * @return array
	 */
	private function ungroup( $groupedArrays ) {
		$merged = array_reduce(
			$groupedArrays,
			function ( $acc, $next ) {
				return array_merge( $acc, $next );
			},
			array()
		);

		return $merged;
	}

	/**
	 * @param bool $includeRedirects Whether redirects should be included in the list of missing ids.
	 *
	 * @return string[] The subset of entity ids supplied to the constructor that
	 * do not represent actual entities.
	 */
	private function getMissingIds( $includeRedirects = false ) {
		$pageInfo = $this->getPageInfo();
		$missingIds = array();

		foreach ( $this->entityInfo as $key => $info ) {
			if ( isset( $pageInfo[$key] ) ) {
				// ID found. If we don't want to include redirects, or it's not a redirect, skip it.
				if ( !$includeRedirects || $pageInfo[$key]['redirect_target'] === null ) {
					continue;
				}
			}

			$missingIds[] = $key;
		}

		return $missingIds;
	}

	/**
	 * Finds and returns any redirects from the set of entities supplied to the constructor.
	 *
	 * @return EntityId[] An associative array mapping id strings to EntityIds representing
	 * the redirect targets.
	 */
	private function findRedirects() {
		$pageInfo = $this->getPageInfo();
		$redirects = array();

		foreach ( $pageInfo as $key => $pageRecord ) {
			if ( $pageInfo[$key]['redirect_target'] !== null ) {
				$redirects[$key] = $this->getEntityId( $pageInfo[$key]['redirect_target'] );
			}
		}

		return $redirects;
	}

	/**
	 * @param EntityId[] $ids
	 *
	 * @return string[]
	 */
	private function convertEntityIdsToStrings( array $ids ) {
		return array_map( function ( EntityId $id ) {
			return $id->getSerialization();
		}, $ids );
	}

	/**
	 * Remove info records for the given EntityIds.
	 *
	 * @param EntityId[] $ids
	 */
	public function removeEntityInfo( array $ids ) {
		$remove = $this->convertEntityIdsToStrings( $ids );
		$this->unsetEntityInfo( $remove );
	}

	/**
	 * Retain only info records for the given EntityIds.
	 * Useful e.g. after resolveRedirects(), to remove explicit entries for
	 * redirect targets not present in the original input.
	 *
	 * @param EntityId[] $ids
	 */
	public function retainEntityInfo( array $ids ) {
		$retain = $this->convertEntityIdsToStrings( $ids );
		$remove = array_diff( array_keys( $this->entityInfo ), $retain );
		$this->unsetEntityInfo( $remove );
	}

}
