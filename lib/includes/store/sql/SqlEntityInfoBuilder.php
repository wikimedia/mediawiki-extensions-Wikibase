<?php

namespace Wikibase\Lib\Store\Sql;

use InvalidArgumentException;
use ResultWrapper;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel;
use Wikibase\EntityId;
use Wikibase\Lib\Store\EntityInfoBuilder;
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
	 * @var string[]
	 */
	static $termTypeFields = array(
		'label' => 'labels',
		'description' => 'descriptions',
		'alias' => 'aliases',
	);

	/**
	 * @var string
	 */
	protected $termTable;

	/**
	 * @var string
	 */
	protected $propertyInfoTable;

	/**
	 * @var string
	 */
	protected $entityPerPageTable;

	/**
	 * @var EntityIdParser
	 *
	 * @note: currently not used, but we will need it once the database contains
	 * full string IDs instead of numeric ids.
	 */
	protected $idParser;

	/**
	 * @var EntityId[] id-string -> EntityId
	 */
	private $entityIds = null;

	/**
	 * @var array[] id-string -> entity-record-array
	 */
	private $entityInfo = null;

	/**
	 * @var array[] type -> id-key -> int
	 */
	private $numericIdsByType = null;

	/**
	 * @param EntityId[] $ids
	 * @param EntityIdParser $idParser
	 * @param string|bool $wiki The wiki's database to connect to.
	 *        Must be a value LBFactory understands. Defaults to false, which is the local wiki.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $ids, EntityIdParser $idParser, $wiki = false ) {
		if ( !is_string( $wiki ) && $wiki !== false ) {
			throw new InvalidArgumentException( '$wiki must be a string or false.' );
		}

		parent::__construct( $wiki );

		$this->idParser = $idParser;

		$this->termTable = 'wb_terms';
		$this->propertyInfoTable = 'wb_property_info';
		$this->entityPerPageTable = 'wb_entity_per_page';

		$this->setEntityIds( $ids );
	}

	/**
	 * @param EntityId[] $ids
	 */
	private function setEntityIds( array $ids ) {
		$this->entityIds = array();
		$this->entityInfo = array();
		$this->numericIdsByType = array();

		foreach ( $ids as $id ) {
			$key = $id->getSerialization();
			$type = $id->getEntityType();

			$this->entityIds[$key] = $id;

			$this->entityInfo[$key] = array(
				'id' => $key,
				'type' => $type,
			);

			$this->numericIdsByType[$type][$key] = $id->getNumericId();
		}
	}

	/**
	 * Returns an entity info data structure. The entity info is represented
	 * by a nested array structure. On the top level, entity id strings are used as
	 * keys that refer to entity "records". Each record is an associative array with
	 * at least the fields "id" and "type". Which other fields are present depends on
	 * which methods have been called on the EntityInfoBuilder in order to gather
	 * information about the entities.
	 *
	 * @return array[]
	 */
	public function getEntityInfo() {
		return $this->entityInfo;
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
	 * @throws \InvalidArgumentException
	 */
	private function injectTerms( ResultWrapper $dbResult ) {
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

		//NOTE: we make one DB query per entity type, so we can take advantage of the
		//      database index on the epp_entity_type field.
		foreach ( $this->numericIdsByType as $type => $idsForType ) {
			$pageIds = $this->getPageIdsForEntities( $type, $idsForType );
			$missingNumericIds = array_diff( $idsForType, array_keys( $pageIds ) );

			// get the missing prefixed ids based on the missing numeric ids
			$numericToPrefixed = array_flip( $idsForType );
			$missingPrefixedIds = array_intersect_key( $numericToPrefixed, array_flip( array_values( $missingNumericIds ) ) );

			$this->unsetEntityInfo( $missingPrefixedIds );
		}

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
	 * @param int[] $entityIds
	 *
	 * @return array A map of (numeric) entity IDs to page ids.
	 */
	private function getPageIdsForEntities( $entityType, array $entityIds ) {
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
}
