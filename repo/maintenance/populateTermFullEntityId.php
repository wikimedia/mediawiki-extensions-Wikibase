<?php

namespace Wikibase\Repo\Maintenance;

use LoggedUpdateMaintenance;
use MediaWiki\MediaWikiServices;
use Wikibase\WikibaseSettings;
use Wikimedia\Rdbms\IDatabase;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script for populating data in term_full_entity_id column in wb_terms table.
 * To be used with MediaWiki's update.php script.
 *
 * Note: This script does not work for custom entity types registered by extensions (e.g. mediainfo)
 * Use rebuildTermSqlIndex maintenance script (with --no-deduplication option) for more
 * sophisticated populating of term_full_entity_id column, including handling custom
 * entity types, batching with continuation, etc.
 *
 * @license GPL-2.0+
 */
class PopulateTermFullEntityId extends LoggedUpdateMaintenance {

	private $tableName = 'wb_terms';

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Populates term_full_entity_id column of wb_terms table.' );
	}

	/**
	 * @see LoggedUpdateMaintenance::doDBUpdates
	 *
	 * @return bool
	 */
	public function doDBUpdates() {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->error( "You need to have Wikibase enabled in order to use this maintenance script!", 1 );
		}

		$loadBalancer = MediaWikiServices::getInstance()->getDBLoadBalancerFactory()->getMainLB();

		$dbr = $loadBalancer->getConnection( DB_REPLICA );
		$dbw = $loadBalancer->getConnection( DB_MASTER );

		$success = $this->populateFullEntityIdColumn( $dbr, $dbw );

		$loadBalancer->reuseConnection( $dbw );
		$loadBalancer->reuseConnection( $dbr );

		if ( $success ) {
			$this->output( "Done populating term_full_entity_id column in wb_terms.\n" );
		} else {
			$this->error( "There were errors in populating term_full_entity_id column in wb_terms.\n" );
		}

		return $success;
	}

	/**
	 * @param IDatabase $dbr
	 * @param IDatabase $dbw
	 *
	 * @return bool
	 */
	private function populateFullEntityIdColumn( IDatabase $dbr, IDatabase $dbw ) {
		if ( $this->termsTableHasTermsForCustomEntityTypes( $dbr ) ) {
			$this->error(
				"There are entries for entities other than items and properties in wb_terms which cannot be automatically updated.\n" .
				"Run rebuildTermSqlIndex (consider --no-deduplication option) maintenance script to have terms of all entities updated.\n" .
				"You should set Wikibase 'readFullEntityIdColumn' setting to false until all terms have been updated.\n",
				0
			);
		}

		$hasNullFullIdFields = (bool)$dbr->selectField(
			$this->tableName,
			'1',
			[ 'term_full_entity_id IS NULL' ],
			__METHOD__
		);

		if ( !$hasNullFullIdFields ) {
			$this->output( "...term_full_entity_id already populated and complete.\n" );
			return true;
		}

		return $this->runColumnPopulationQueries( $dbw );
	}

	/**
	 * @param IDatabase $db
	 *
	 * @return bool
	 */
	private function termsTableHasTermsForCustomEntityTypes( IDatabase $db ) {
		return (bool)$db->selectField(
			$this->tableName,
			'1',
			[
				$db->makeList(
					[
						'term_entity_type != ' . $db->addQuotes( 'item' ),
						'term_entity_type != ' . $db->addQuotes( 'property' ),
					],
					LIST_AND
				),
				'term_full_entity_id IS NULL'
			],
			__METHOD__
		);
	}

	/**
	 * @param IDatabase $db
	 *
	 * @return bool
	 */
	private function runColumnPopulationQueries( IDatabase $db ) {
		$query = $this->getUpdateQuery( $db, 'item', 'Q' );
		if ( !$db->query( $query, __METHOD__, true ) ) {
			return false;
		}

		$query = $this->getUpdateQuery( $db, 'property', 'P' );
		if ( !$db->query( $query, __METHOD__, true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @param IDatabase $db
	 * @param string $entityType
	 * @param string $idPrefix
	 *
	 * @return string
	 */
	private function getUpdateQuery( IDatabase $db, $entityType, $idPrefix ) {
		$fullEntityId = $db->buildConcat(
			[
				$db->addQuotes( $idPrefix ),
				'term_entity_id'
			]
		);

		return 'UPDATE /*_*/' . $this->tableName . ' ' .
			'SET term_full_entity_id = ' . $fullEntityId . ' ' .
			'WHERE term_entity_type=' . $db->addQuotes( $entityType );
	}

	/**
	 * @see LoggedUpdateMaintenance::getUpdateKey
	 *
	 * @return string
	 */
	public function getUpdateKey() {
		return 'Wikibase\Repo\Maintenance\PopulateTermFullEntityId';
	}

}

$maintClass = PopulateTermFullEntityId::class;
require_once RUN_MAINTENANCE_IF_MAIN;
