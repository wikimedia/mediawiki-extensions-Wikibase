<?php

namespace Wikibase;

use LoggedUpdateMaintenance;
use Wikibase\Lib\Reporting\ObservableMessageReporter;
use Wikibase\Repo\WikibaseRepo;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script for rebuilding the property info table.
 *
 * @since 0.4
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class RebuildPropertyInfo extends LoggedUpdateMaintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Rebuild the property info table.' );

		$this->addOption( 'rebuild-all', "Update property info for all properties (per default, only missing entries are created)" );
		$this->addOption( 'start-row', "The ID of the first row to update (useful for continuing aborted runs)", false, true );
		$this->addOption( 'batch-size', "Number of rows to update per database transaction (100 per default)", false, true );
	}

	/**
	 * @see LoggedUpdateMaintenance::doDBUpdates
	 *
	 * @return bool
	 */
	public function doDBUpdates() {
		if ( !defined( 'WB_VERSION' ) ) {
			$this->output( "You need to have Wikibase enabled in order to use this maintenance script!\n\n" );
			exit;
		}

		$reporter = new ObservableMessageReporter();
		$reporter->registerReporterCallback(
			array( $this, 'report' )
		);

		$table = new PropertyInfoTable( false );
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$entityLookup = $wikibaseRepo->getEntityLookup();
		$propertyInfoBuilder = $wikibaseRepo->newPropertyInfoBuilder();

		$builder = new PropertyInfoTableBuilder( $table, $entityLookup, $propertyInfoBuilder );
		$builder->setReporter( $reporter );

		$builder->setBatchSize( (int)$this->getOption( 'batch-size', 100 ) );
		$builder->setRebuildAll( $this->getOption( 'rebuild-all', false ) );
		$builder->setFromId( (int)$this->getOption( 'start-row', 1 ) );

		$n = $builder->rebuildPropertyInfo();

		$this->output( "Done. Updated $n property info entries.\n" );

		return true;
	}

	/**
	 * @see LoggedUpdateMaintenance::getUpdateKey
	 *
	 * @return string
	 */
	public function getUpdateKey() {
		return self::class;
	}

	/**
	 * Outputs a message vis the output() method.
	 *
	 * @since 0.4
	 *
	 * @param string $msg
	 */
	public function report( $msg ) {
		$this->output( "$msg\n" );
	}

}

$maintClass = RebuildPropertyInfo::class;
require_once RUN_MAINTENANCE_IF_MAIN;
