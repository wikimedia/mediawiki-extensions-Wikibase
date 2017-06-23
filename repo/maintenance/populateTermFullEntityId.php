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
 * Note:  This script does not work for custom entity types registered by extensions (e.g. mediainfo)
 * Use rebuildTermSqlIndex maintenance script (with --no-deduplication option) for more
 * sophisticated populating of term_full_entity_id column, including handling custom
 * entity types, batching with continuation, etc.
 *
 * @license GPL-2.0+
 */
class PopulateTermFullEntityId extends LoggedUpdateMaintenance {

	private $tableName = 'wb_terms';

	private $entityIdPrefixes = [
		'item' => 'Q',
		'property' => 'P',
	];

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Populates term_full_entity_id column of wb_terms table.' );

		// TODO: why this does not work?
		$this->setBatchSize( null );
		$this->deleteOption( 'batch-size' );
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

		$db = $loadBalancer->getConnection( DB_MASTER );

		$success = $this->populateFullEntityIdColumn( $db );

		$loadBalancer->reuseConnection( $db );

		if ( $success ) {
			$this->output( "Done.\n" );
		} else {
			$this->error( "There were errors in populating term_full_entity_id column in wb_terms.\n" );
		}

		return $success;
	}

	/**
	 * @param IDatabase $db
	 * @return bool
	 */
	private function populateFullEntityIdColumn( IDatabase $db ) {
		if ( $this->termsTableHasTermsForCustomEntityTypes( $db ) ) {
			$this->error(
				"There are entries for entities other than items and properties in wb_terms which cannot be automatically updated.\n" .
				"Run rebuildTermSqlIndex (consider --no-deduplication option) maintenance script to have terms of all entities updated.\n" .
				"You should set Wikibase 'readFullEntityIdColumn' setting to false until all terms have been updated.\n",
				0
			);
		}

		$hasNullFullIdFields = (bool)$db->selectField(
			'wb_terms',
			'1',
			[ 'term_full_entity_id IS NULL' ],
			__METHOD__
		);

		if ( !$hasNullFullIdFields ) {
			$this->output( "...term_full_entity_id already populated and complete.\n" );
			return true;
		}

		return $this->runColumnPopulationQueries( $db );
	}

	/**
	 * @param IDatabase $db
	 * @return bool
	 */
	private function termsTableHasTermsForCustomEntityTypes( IDatabase $db ) {
		return (bool)$db->selectField(
			'wb_terms',
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
	 * @return bool
	 */
	private function runColumnPopulationQueries( IDatabase $db ) {
		$query = $this->getQueryForItems( $db );
		if ( !$db->query( $query, __METHOD__ ) ) {
			return false;
		}

		$query = $this->getQueryForProperties( $db );
		if ( !$db->query( $query, __METHOD__ ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @param IDatabase $db
	 * @return string
	 */
	private function getQueryForItems( IDatabase $db ) {
		if ( $db->getType() === 'sqlite' ) {
			return "UPDATE /*_*/wb_terms " .
				"SET term_full_entity_id = 'Q' || term_entity_id " .
				"WHERE term_entity_type='item'";
		}

		return "UPDATE /*_*/wb_terms " .
			"SET term_full_entity_id = concat('Q', term_entity_id) " .
			"WHERE term_entity_type='item'";
	}

	/**
	 * @param IDatabase $db
	 * @return string
	 */
	private function getQueryForProperties( IDatabase $db ) {
		if ( $db->getType() === 'sqlite' ) {
			return "UPDATE /*_*/wb_terms " .
				"SET term_full_entity_id = 'P' || term_entity_id " .
				"WHERE term_entity_type='property'";
		}

		return "UPDATE /*_*/wb_terms " .
			"SET term_full_entity_id = concat('P', term_entity_id) " .
			"WHERE term_entity_type='property'";
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
