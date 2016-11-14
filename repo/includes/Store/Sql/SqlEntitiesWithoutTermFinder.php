<?php

namespace Wikibase\Repo\Store\Sql;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\EntityIdComposer;
use Wikibase\Repo\Store\EntitiesWithoutTermFinder;

/**
 * Service for getting entities without terms.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Thomas Pellissier Tanon
 * @author Daniel Kinzler
 */
class SqlEntitiesWithoutTermFinder implements EntitiesWithoutTermFinder {

	/**
	 * @var EntityIdComposer
	 */
	private $entityIdComposer;

	/**
	 * @param EntityIdComposer $entityIdComposer
	 */
	public function __construct( EntityIdComposer $entityIdComposer ) {
		$this->entityIdComposer = $entityIdComposer;
	}

	/**
	 * @see EntitiesWithoutTermFinder::getEntitiesWithoutTerm
	 *
	 * @since 0.2
	 *
	 * @param string $termType Can be any member of the TermIndexEntry::TYPE_ enum
	 * @param string|null $language Restrict the search for one language. By default the search is done for all languages.
	 * @param string|null $entityType Can be "item", "property" or "query". By default the search is done for all entities.
	 * @param integer $limit Limit of the query.
	 * @param integer $offset Offset of the query.
	 *
	 * @return EntityId[]
	 */
	public function getEntitiesWithoutTerm( $termType, $language = null, $entityType = null, $limit = 50, $offset = 0 ) {
		$dbr = wfGetDB( DB_REPLICA );
		$conditions = [
			'term_entity_type IS NULL'
		];

		$joinConditions = 'term_entity_id = epp_entity_id' .
			' AND term_entity_type = epp_entity_type' .
			' AND term_type = ' . $dbr->addQuotes( $termType ) .
			' AND epp_redirect_target IS NULL';

		if ( $language !== null ) {
			$joinConditions .= ' AND term_language = ' . $dbr->addQuotes( $language );
		}

		if ( $entityType !== null ) {
			$conditions[] = 'epp_entity_type = ' . $dbr->addQuotes( $entityType );
		}

		$rows = $dbr->select(
			[ 'wb_entity_per_page', 'wb_terms' ],
			[
				'entity_id' => 'epp_entity_id',
				'entity_type' => 'epp_entity_type',
			],
			$conditions,
			__METHOD__,
			[
				'OFFSET' => $offset,
				'LIMIT' => $limit,
				'ORDER BY' => 'epp_page_id DESC'
			],
			[ 'wb_terms' => [ 'LEFT JOIN', $joinConditions ] ]
		);

		return $this->getEntityIdsFromRows( $rows );
	}

	private function getEntityIdsFromRows( $rows ) {
		$entities = [];

		foreach ( $rows as $row ) {
			try {
				$entities[] = $this->entityIdComposer->composeEntityId( $row->entity_type, $row->entity_id );
			} catch ( InvalidArgumentException $ex ) {
				wfLogWarning( 'Unsupported entity type "' . $row->entity_type . '"' );
			}
		}

		return $entities;
	}

}
