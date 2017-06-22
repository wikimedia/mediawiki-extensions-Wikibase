<?php

namespace Wikibase\Repo\Store\Sql;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\Store\EntitiesWithoutTermFinder;
use Wikimedia\Assert\Assert;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ResultWrapper;

/**
 * Service for getting entities without terms.
 *
 * @license GPL-2.0+
 * @author Thomas Pellissier Tanon
 * @author Daniel Kinzler
 * @author Marius Hoch
 */
class SqlEntitiesWithoutTermFinder implements EntitiesWithoutTermFinder {

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var EntityNamespaceLookup
	 */
	private $entityNamespaceLookup;

	/**
	 * Maps (supported) entity types to their prefix (before the numerical part).
	 *
	 * @var string[]
	 */
	private $entityTypeToPrefixMap;

	/**
	 * Whether it's possible to read from term_full_entity_id
	 *
	 * @var bool
	 */
	private $canReadFullEntityIdColumn;

	/**
	 * @param EntityIdParser $entityIdParser
	 * @param EntityNamespaceLookup $entityNamespaceLookup
	 * @param string[] $entityTypeToPrefixMap Maps (supported) entity types to their prefix (before the numerical part).
	 * @param bool $canReadFullEntityIdColumn Whether it can read term_entity_full_id or not
	 */
	public function __construct(
		EntityIdParser $entityIdParser,
		EntityNamespaceLookup $entityNamespaceLookup,
		array $entityTypeToPrefixMap,
		$canReadFullEntityIdColumn = false
	) {
		$this->entityIdParser = $entityIdParser;
		$this->entityNamespaceLookup = $entityNamespaceLookup;
		$this->entityTypeToPrefixMap = $entityTypeToPrefixMap;
		$this->canReadFullEntityIdColumn = $canReadFullEntityIdColumn;

		Assert::parameterElementType( 'string', $entityTypeToPrefixMap, '$entityTypeToPrefixMap' );
	}

	/**
	 * @see EntitiesWithoutTermFinder::getEntitiesWithoutTerm
	 *
	 * @param string $termType Can be any member of the TermIndexEntry::TYPE_ enum
	 * @param string|null $language Restrict the search for one language. By default the search is done for all languages.
	 * @param string[]|null $entityTypes Array containing the entity types to search for, typically
	 *  "item" and/or "property". Null includes all indexed entity types.
	 * @param integer $limit Limit of the query.
	 * @param integer $offset Offset of the query.
	 *
	 * @return EntityId[]
	 */
	public function getEntitiesWithoutTerm(
		$termType,
		$language = null,
		array $entityTypes = null,
		$limit = 50,
		$offset = 0
	) {
		$entityTypes = $this->normalizeEntityTypes( $entityTypes );

		$dbr = wfGetDB( DB_REPLICA );
		$conditions = [
			'term_entity_type IS NULL',
			'page_is_redirect' => 0,
			'page_namespace' => $this->getPageNamespaces( $entityTypes )
		];

		$joinConditions = [
			$this->getEntityTypeConditions( $dbr, $entityTypes ),
			'term_type' => $termType
		];

		if ( $language !== null ) {
			$joinConditions['term_language'] = $language;
		}

		$rows = $dbr->select(
			[ 'page', 'wb_terms' ],
			[ 'entity_id_serialization' => 'page_title' ],
			$conditions,
			__METHOD__,
			[
				'OFFSET' => $offset,
				'LIMIT' => $limit,
				'ORDER BY' => 'page_id DESC'
			],
			[ 'wb_terms' => [ 'LEFT JOIN', $joinConditions ] ]
		);

		return $this->getEntityIdsFromRows( $rows );
	}

	/**
	 * @param string[] $entityTypes
	 * @return int[]
	 */
	private function getPageNamespaces( array $entityTypes ) {
		$namespaces = [];
		foreach ( $entityTypes as $entityType ) {
			$namespaces[] = $this->entityNamespaceLookup->getEntityNamespace( $entityType );
		}

		return $namespaces;
	}

	/**
	 * Get join conditions for selecting (one of) the given entity type(s).
	 *
	 * @param IDatabase $dbr
	 * @param string[] $entityTypes
	 *
	 * @return string
	 */
	private function getEntityTypeConditions( IDatabase $dbr, array $entityTypes ) {
		$typeConditions = [];
		foreach ( $entityTypes as $entityType ) {
			$typeConditions[] = $this->getConditionsForEntityType( $dbr, $entityType );
		}

		return $dbr->makeList( $typeConditions, IDatabase::LIST_OR );
	}

	/**
	 * Get join condition for selecting the given entity type.
	 *
	 * @param IDatabase $dbr
	 * @param string $entityType
	 *
	 * @return string
	 */
	private function getConditionsForEntityType( IDatabase $dbr, $entityType ) {
		$conditions = [
			'term_entity_type' => $entityType,
			'page_namespace' => $this->entityNamespaceLookup->getEntityNamespace( $entityType )
		];
		if ( $this->canReadFullEntityIdColumn ) {
			$conditions[] = 'term_full_entity_id = page_title';
		} else {
			$prefix = $dbr->addQuotes( $this->entityTypeToPrefixMap[ $entityType ] );
			$conditions[] = 'term_entity_id = ' . $dbr->strreplace( 'page_title', "$prefix", "''" );
		}

		return $dbr->makeList( $conditions, IDatabase::LIST_AND );
	}

	/**
	 * @param ResultWrapper $rows
	 * @return EntityId[]
	 */
	private function getEntityIdsFromRows( ResultWrapper $rows ) {
		$entities = [];

		foreach ( $rows as $row ) {
			$entities[] = $this->entityIdParser->parse( $row->entity_id_serialization );
		}

		return $entities;
	}

	/**
	 * @param string[]|null $entityTypes
	 * @return string[]
	 *
	 * @throws InvalidArgumentException
	 */
	private function normalizeEntityTypes( array $entityTypes = null ) {
		$validTypes = array_keys( $this->entityTypeToPrefixMap );

		if ( $entityTypes === null ) {
			return $validTypes;
		}

		if ( array_diff( $entityTypes, $validTypes ) !== [] ) {
			throw new InvalidArgumentException(
				'$entityTypes can not contain unsupported entity types.'
			);
		}

		return $entityTypes;
	}

	/**
	 * @param bool $canReadFullEntityIdColumn
	 */
	public function setCanReadFullEntityIdColumn( $canReadFullEntityIdColumn ) {
		$this->canReadFullEntityIdColumn = $canReadFullEntityIdColumn;
	}

}
