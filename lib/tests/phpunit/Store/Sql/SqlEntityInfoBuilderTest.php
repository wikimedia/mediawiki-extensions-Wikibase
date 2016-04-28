<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\Store\Sql\SqlEntityInfoBuilder;

/**
 * @covers Wikibase\Lib\Store\Sql\SqlEntityInfoBuilder
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseStore
 * @group WikibasePropertyInfo
 * @group Database
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class SqlEntityInfoBuilderTest extends EntityInfoBuilderTest {

	protected function setUp() {
		parent::setUp();

		if ( !defined( 'WB_VERSION' ) ) {
			$this->markTestSkipped( 'Entity info tables are not available locally on the client' );
		}

		$this->tablesUsed[] = 'wb_property_info';
		$this->tablesUsed[] = 'wb_terms';
		$this->tablesUsed[] = 'wb_entity_per_page';

		$termRows = array();
		$infoRows = array();
		$eppRows = array();

		$pageId = 1000;

		foreach ( $this->getKnownEntities() as $entity ) {
			$eppRows[] = array(
				$entity->getType(),
				$entity->getId()->getNumericId(),
				$pageId++,
				null
			);

			$labels = $entity->getFingerprint()->getLabels()->toTextArray();
			$descriptions = $entity->getFingerprint()->getDescriptions()->toTextArray();
			$aliases = $entity->getFingerprint()->getAliasGroups()->toTextArray();

			$termRows = array_merge( $termRows, $this->getTermRows( $entity->getId(), 'label', $labels ) );
			$termRows = array_merge( $termRows, $this->getTermRows( $entity->getId(), 'description', $descriptions ) );
			$termRows = array_merge( $termRows, $this->getTermRows( $entity->getId(), 'alias', $aliases ) );

			if ( $entity instanceof Property ) {
				$infoRows[] = array(
					$entity->getId()->getNumericId(),
					$entity->getDataTypeId(),
					'{"type":"' . $entity->getDataTypeId() . '"}'
				);
			}
		}

		foreach ( $this->getKnownRedirects() as $from => $toId ) {
			$fromId = new ItemId( $from );

			$eppRows[] = array(
				$fromId->getEntityType(),
				$fromId->getNumericId(),
				$pageId++,
				$toId->getSerialization()
			);
		}

		$this->insertRows(
			'wb_terms',
			array( 'term_entity_type', 'term_entity_id', 'term_type', 'term_language', 'term_text', 'term_search_key' ),
			$termRows );

		$this->insertRows(
			'wb_property_info',
			array( 'pi_property_id', 'pi_type', 'pi_info' ),
			$infoRows );

		$eppColumns = array( 'epp_entity_type', 'epp_entity_id', 'epp_page_id', 'epp_redirect_target' );

		$this->insertRows(
			'wb_entity_per_page',
			$eppColumns,
			$eppRows );
	}

	private function getTermRows( EntityId $id, $termType, $terms ) {
		$rows = array();

		foreach ( $terms as $lang => $langTerms ) {
			$langTerms = (array)$langTerms;

			foreach ( $langTerms as $term ) {
				$rows[] = array(
					$id->getEntityType(),
					$id->getNumericId(),
					$termType,
					$lang,
					$term,
					$term );
			}
		}

		return $rows;
	}

	private function insertRows( $table, array $fields, array $rows ) {
		$dbw = wfGetDB( DB_MASTER );

		foreach ( $rows as $row ) {
			$row = array_slice( $row, 0, count( $fields ) );

			$dbw->insert(
				$table,
				array_combine( $fields, $row ),
				__METHOD__,
				// Just ignore insertation errors... if similar data already is in the DB
				// it's probably good enough for the tests (as this is only testing for UNIQUE
				// fields anyway).
				array( 'IGNORE' )
			);
		}
	}

	/**
	 * @param EntityId[] $ids
	 *
	 * @return SqlEntityInfoBuilder
	 */
	protected function newEntityInfoBuilder( array $ids ) {
		return new SqlEntityInfoBuilder( new BasicEntityIdParser(), $ids );
	}

}
