<?php

namespace Wikibase;

use LoggedUpdateMaintenance;
use Wikibase\Lib\Reporting\ObservableMessageReporter;
use Wikibase\Repo\WikibaseRepo;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script for rebuilding the search key of the TermSQLCache.
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class RebuildTermsSearchKey extends LoggedUpdateMaintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Rebuild the search key of the TermSQLCache' );

		$this->addOption( 'only-missing', "Update only missing keys (per default, all keys are updated)" );
		$this->addOption( 'start-row', "The ID of the first row to update (useful for continuing aborted runs)", false, true );
		$this->addOption( 'batch-size', "Number of rows to update per database transaction (100 per default)", false, true );
	}

	/**
	 * @see LoggedUpdateMaintenance::doDBUpdates
	 *
	 * @return bool
	 */
	public function doDBUpdates() {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->output( "You need to have Wikibase enabled in order to use this maintenance script!\n\n" );
			exit;
		}

		$reporter = new ObservableMessageReporter();
		$reporter->registerReporterCallback(
			[ $this, 'report' ]
		);

		$table = WikibaseRepo::getDefaultInstance()->getStore()->getTermIndex();
		$builder = new TermSearchKeyBuilder( $table );
		$builder->setReporter( $reporter );

		$builder->setBatchSize( (int)$this->getOption( 'batch-size', 100 ) );
		$builder->setRebuildAll( !$this->getOption( 'only-missing', false ) );
		$builder->setFromId( (int)$this->getOption( 'start-row', 1 ) );

		$n = $builder->rebuildSearchKey();

		$this->output( "Done. Updated $n search keys.\n" );

		return true;
	}

	/**
	 * @see LoggedUpdateMaintenance::getUpdateKey
	 *
	 * @return string
	 */
	public function getUpdateKey() {
		return 'Wikibase\RebuildTermsSearchKey';
	}

	/**
	 * Outputs a message vis the output() method.
	 *
	 * @param string $msg
	 */
	public function report( $msg ) {
		$this->output( "$msg\n" );
	}

}

$maintClass = RebuildTermsSearchKey::class;
require_once RUN_MAINTENANCE_IF_MAIN;
