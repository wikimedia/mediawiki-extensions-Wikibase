<?php

namespace Wikibase;

use ResultWrapper;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\LegacyIdInterpreter;

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
	 */
	protected $idParser;

	/**
	 * @param DataModel\Entity\EntityIdParser $idParser
	 * @param string|bool $wiki The wiki's database to connect to.
	 *        Must be a value LBFactory understands. Defaults to false, which is the local wiki.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct( EntityIdParser $idParser, $wiki = false ) {
		if ( !is_string( $wiki ) && $wiki !== false ) {
			throw new \InvalidArgumentException( '$wiki must be a string or false.' );
		}

		parent::__construct( $wiki );

		$this->idParser = $idParser;

		$this->termTable = 'wb_terms';
		$this->propertyInfoTable = 'wb_property_info';
		$this->entityPerPageTable = 'wb_entity_per_page';
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
	 * @param array $entityInfo An array with entity IDs for keys.
	 *
	 * @return array A two-level map, mapping each entity type to a map
	 *         of prefixed entity IDs to numeric IDs.
	 */
	protected function getNumericEntityIds( array $entityInfo ) {
		$ids = array();

		foreach ( $entityInfo as $prefixedId => $entityRecord ) {
			//TODO: we could avoid constructing EntityId objects be taking them, from
			//      a magic field in $entityRecord, e.g. $entityRecord['__entityId'] or some such.

			/* @var EntityId $id */
			$id = $this->idParser->parse( $prefixedId );
			$type = $id->getEntityType();
			$ids[$type][$prefixedId] = $id->getNumericId();
		}

		return $ids;
	}

	/**
	 * Applies a default value to the given field in each entity record.
	 *
	 * @param array $entityInfo a map of strings to arrays, each array representing an entity,
	 *        with the key being the entity's ID. NOTE: This array will be updated!
	 * @param string $field the field to assign the default value to
	 * @param mixed $default the default value
	 * @param callable|null $filter A filter callback; if given, only records that match
	 *        the filter will be updated. The callback gets the entity record as the only
	 *        parameter, and must return a boolean.
	 */
	public function setDefaultValue( array &$entityInfo, $field, $default, $filter = null ) {
		foreach ( $entityInfo as &$entity ) {
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
	 * @see EntityInfoBuilder::addTerms()
	 *
	 * @param array $entityInfo a map of strings to arrays, each array representing an entity,
	 *        with the key being the entity's ID. NOTE: This array will be updated!
	 * @param array $types Which types of terms to include (e.g. "label", "description", "aliases").
	 * @param array $languages Which languages to include
	 */
	public function addTerms( array &$entityInfo, array $types = null, array $languages = null ) {
		if ( $types === array() || $languages === array() ) {
			// nothing to do
			return;
		}

		wfProfileIn( __METHOD__ );
		$entityIdsByType = $this->getNumericEntityIds( $entityInfo );

		//NOTE: we make one DB query per entity type, so we can take advantage of the
		//      database index on the term_entity_type field.
		foreach ( $entityIdsByType as $type => $idsForType ) {
			$this->collectTermsForEntities( $entityInfo, $type, $idsForType, $types, $languages );
		}

		if ( $types === null ) {
			$types = array_keys( self::$termTypeFields );
		}

		foreach ( $types as $type ) {
			$this->setDefaultValue( $entityInfo, self::$termTypeFields[$type], array() );
		}

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Collects the terms for a number of entities (of the given types, in the given languages)
	 *
	 * @param array $entityInfo
	 * @param $entityType
	 * @param array $entityIds
	 * @param array $termTypes
	 * @param array $languages
	 */
	private function collectTermsForEntities( array &$entityInfo, $entityType, array $entityIds, array $termTypes = null, array $languages = null ) {
		wfProfileIn( __METHOD__ );

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

		$this->injectTerms( $res, $entityInfo );

		$this->releaseConnection( $dbw );

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Injects terms from a DB result into the $entityInfo structure.
	 *
	 * @note: Keep in sync with EntitySerializer!
	 *
	 * @param ResultWrapper $dbResult
	 * @param array $entityInfo
	 *
	 * @throws \InvalidArgumentException
	 */
	private function injectTerms( $dbResult, array &$entityInfo ) {
		$idParser = new LegacyIdInterpreter();

		foreach ( $dbResult as $row ) {
			// FIXME: this only works for items and properties
			$id = $idParser->newIdFromTypeAndNumber( $row->term_entity_type, (int)$row->term_entity_id );
			$key = $id->getSerialization();

			if ( !isset( $entityInfo[$key] ) ) {
				continue;
			}

			$field = self::$termTypeFields[$row->term_type];

			switch ( $row->term_type ) {
				case 'label':
					$this->injectLabel( $entityInfo[$key][$field], $row->term_language, $row->term_text );
					break;
				case 'description':
					$this->injectDescription( $entityInfo[$key][$field], $row->term_language, $row->term_text );
					break;
				case 'alias':
					$this->injectAlias( $entityInfo[$key][$field], $row->term_language, $row->term_text );
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
	 * @see EntityInfoBuilder::addDataTypes()
	 *
	 * @param array $entityInfo a map of strings to arrays, each array representing an entity,
	 *        with the key being the entity's ID. NOTE: This array will be updated!
	 */
	public function addDataTypes( array &$entityInfo ) {
		//TODO: use PropertyDataTypeLookup service to make use of caching!

		wfProfileIn( __METHOD__ );

		$entityIds = $this->getNumericEntityIds( $entityInfo );

		if ( empty( $entityIds[Property::ENTITY_TYPE] ) ) {
			// there are no properties in the list, so there is nothing to do.
			return;
		}

		$dbw = $this->getConnection( DB_SLAVE );

		$res = $dbw->select(
			$this->propertyInfoTable,
			array( 'pi_property_id', 'pi_type' ),
			array( 'pi_property_id' => $entityIds[Property::ENTITY_TYPE] ),
			__METHOD__
		);

		$this->injectDataTypes( $res, $entityInfo );
		$this->setDefaultValue( $entityInfo, 'datatype', null, function( $entity ) {
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
	 * @param array $entityInfo
	 *
	 * @throws \InvalidArgumentException
	 */
	private function injectDataTypes( $dbResult, array &$entityInfo ) {
		foreach ( $dbResult as $row ) {
			$id = PropertyId::newFromNumber( (int)$row->pi_property_id );
			$key = $id->getPrefixedId();

			if ( !isset( $entityInfo[$key] ) ) {
				continue;
			}

			$entityInfo[$key]['datatype'] = $row->pi_type;
		}
	}

	/**
	 * Adds property data types to the entries in $entityInfo. Missing Properties
	 * will have their datatype field set to null. Other entities remain unchanged.
	 *
	 * @param array $entityInfo a map of strings to arrays, each array representing an entity,
	 *        with the key being the entity's ID. NOTE: This array will be updated!
	 */
	public function removeMissing( array &$entityInfo ) {
		wfProfileIn( __METHOD__ );

		$entityIdsByType = $this->getNumericEntityIds( $entityInfo );

		//NOTE: we make one DB query per entity type, so we can take advantage of the
		//      database index on the epp_entity_type field.
		foreach ( $entityIdsByType as $type => $idsForType ) {
			$pageIds = $this->getPageIdsForEntities( $type, $idsForType );
			$missingNumericIds = array_diff( $idsForType, array_keys( $pageIds ) );

			// get the missing prefixed ids based on the missing numeric ids
			$numericToPrefixed = array_flip( $idsForType );
			$missingPrefixedIds = array_intersect_key( $numericToPrefixed, array_flip( array_values( $missingNumericIds ) ) );

			// strip missing stuff from $entityInfo
			$entityInfo = array_diff_key( $entityInfo, array_flip( $missingPrefixedIds ) );
		}

		wfProfileOut( __METHOD__ );
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
}
