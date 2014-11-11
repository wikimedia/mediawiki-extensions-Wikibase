<?php

namespace Wikibase\Client\Usage\Sql;

use DatabaseBase;
use InvalidArgumentException;
use LoadBalancer;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;

/**
 * Implements initial population (priming) for the wbc_entity_usage table,
 * based on "wikibase_item" entries in the page_props table.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class UsageTablePrimer {


	/**
	 * @var LoadBalancer
	 */
	private $loadBalancer;

	/**
	 * @var string
	 */
	private $usageTableName;

	/**
	 * @var int
	 */
	private $batchSize;

	/**
	 * @param LoadBalancer $loadBalancer
	 * @param string $usageTableName
	 * @param int $batchSize
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( LoadBalancer $loadBalancer, $usageTableName, $batchSize ) {
		if ( !is_string( $usageTableName ) ) {
			throw new InvalidArgumentException( '$usageTableName must be a string' );
		}

		if ( !is_int( $batchSize ) || $batchSize < 1 ) {
			throw new InvalidArgumentException( '$batchSize must be an integer >= 1' );
		}

		$this->loadBalancer = $loadBalancer;
		$this->usageTableName = $usageTableName;
		$this->batchSize = $batchSize;
	}

	/**
	 * @param array $continuation
	 *
	 * @return array|null A continuation, or null if there are no more rows to insert.
	 */
	public function insertUsageRows( $continuation = null ) {
		$db = $this->loadBalancer->getConnection( DB_MASTER );

		list( $fromId, $toId, $continuation ) = $this->getPageIdRange( $db, $continuation );

		if ( $fromId === $toId ) {
			// Nothing to do
			return null;
		}

		$sql = $this->getInsertSql( $db, $fromId, $toId );

		$db->startAtomic( __METHOD__ );
		$db->query( $sql );
		$db->endAtomic( __METHOD__ );

		return $continuation;
	}

	private function getInsertSql( DatabaseBase $db, $fromId, $toId ) {
		$db = $this->loadBalancer->getConnection( DB_MASTER );

		$ppTableName = $db->tableName( 'page_props' );
		$euTableName = $db->tableName( $this->usageTableName );

		$entityTypeExpression = 'IF ( SUBSTR( pp_value, 1, 1 ) = "Q", ' . Item::ENTITY_TYPE . ', ' .
			'IF ( SUBSTR( pp_value, 1, 1 ) = "P", ' . Property::ENTITY_TYPE . ', ' .
				'NULL' .
			' ) ' .
		' ) ';

		$fields = array(
			'eu_entity_type' => $entityTypeExpression,
			'eu_entity_id' => 'UPPERCASE( pp_value )',
			'eu_aspect' => $db->addQuotes( EntityUsage::ALL_USAGE ),
			'eu_page_id' => 'pp_page',
		);

		$propname = 'wikibase_entity';

		$sql = 'INSERT INTO ' . $euTableName .
			' ( ' . $db->makeList( array_keys( $fields ) ) . ' ) ' .
			'SELECT ' . implode( ',', array_values( $fields ) ) .
			'FROM ' . $ppTableName . ' ' .
			'WHERE pp_propname = ' . $db->addQuotes( $propname );

		if ( $fromId > 0 ) {
			$sql .= ' AND pp_page >= ' . $fromId;
		}

		if ( $toId > 0 ) {
			$sql .= ' AND pp_page < ' . $toId;
		}

		return $sql;
	}

	private function getPageIdRange( DatabaseBase $db, $fromId, $toId ) {
		$db = $this->loadBalancer->getConnection( DB_MASTER );

		$ppTableName = $db->tableName( 'page_props' );

		$db->select( 'page_props', 'pp_page' );

		$entityTypeExpression = 'IF ( SUBSTR( pp_value, 1, 1 ) = "Q", ' . Item::ENTITY_TYPE . ', ' .
			'IF ( SUBSTR( pp_value, 1, 1 ) = "P", ' . Property::ENTITY_TYPE . ', ' .
			'NULL' .
			' ) ' .
			' ) ';

		$fields = array(
			'eu_entity_type' => $entityTypeExpression,
			'eu_entity_id' => 'UPPERCASE( pp_value )',
			'eu_aspect' => $db->addQuotes( EntityUsage::ALL_USAGE ),
			'eu_page_id' => 'pp_page',
		);

		$propname = 'wikibase_entity';

		$sql = 'INSERT INTO ' . $euTableName .
			' ( ' . $db->makeList( array_keys( $fields ) ) . ' ) ' .
			'SELECT ' . implode( ',', array_values( $fields ) ) .
			'FROM ' . $ppTableName . ' ' .
			'WHERE pp_propname = ' . $db->addQuotes( $propname );

		if ( $fromId > 0 ) {
			$sql .= ' AND pp_page >= ' . $fromId;
		}

		if ( $toId > 0 ) {
			$sql .= ' AND pp_page < ' . $toId;
		}

		return $sql;
	}

}
