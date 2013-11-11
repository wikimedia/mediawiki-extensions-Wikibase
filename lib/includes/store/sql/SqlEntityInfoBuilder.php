<?php

namespace Wikibase;

use ResultWrapper;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\PropertyId;

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
	 * @var string
	 */
	protected $termTable;

	/**
	 * @var string
	 */
	protected $propertyInfoTable;

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
			$ids[$type][] = $id->getNumericId();
		}

		return $ids;
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
		wfProfileIn( __METHOD__ );
		$entityIdsByType = $this->getNumericEntityIds( $entityInfo );

		foreach ( $entityIdsByType as $type => $idsForType ) {
			$this->collectTermsForEntities( $entityInfo, $type, $idsForType, $types, $languages );
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
		foreach ( $dbResult as $row ) {
			// this is deprecated, but I don't see an alternative.
			$id = new EntityId( $row->term_entity_type, (int)$row->term_entity_id );
			$key = $id->getPrefixedId();

			if ( !isset( $entityInfo[$key] ) ) {
				continue;
			}

			switch ( $row->term_type ) {
				case 'label':
					$this->injectLabel( $entityInfo[$key], $row->term_language, $row->term_text );
					break;
				case 'description':
					$this->injectDescription( $entityInfo[$key], $row->term_language, $row->term_text );
					break;
				case 'alias':
					$this->injectAlias( $entityInfo[$key], $row->term_language, $row->term_text );
					break;
				default:
					wfDebugLog( __CLASS__, __FUNCTION__ . ': unknown term type: ' . $row->term_type );
			}
		}
	}

	private function injectLabel( &$entityRecord, $language, $text ) {
		$entityRecord['labels'][$language] = array(
			'language' => $language,
			'value' => $text,
		);
	}

	private function injectDescription( &$entityRecord, $language, $text ) {
		$entityRecord['descriptions'][$language] = array(
			'language' => $language,
			'value' => $text,
		);
	}

	private function injectAlias( &$entityRecord, $language, $text ) {
		$entityRecord['aliases'][$language][] = array( // note that we are appending here.
			'language' => $language,
			'value' => $text,
		);
	}

	/**
	 * Adds property data types to the entries in $entityInfo. Entities that do not have a data type
	 * remain unchanged.
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
}
