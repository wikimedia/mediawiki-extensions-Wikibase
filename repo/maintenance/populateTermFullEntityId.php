<?php

namespace Wikibase;

use LoggedUpdateMaintenance;
use MediaWiki\MediaWikiServices;
use Wikibase\Lib\Reporting\ObservableMessageReporter;
use Wikibase\Repo\Store\Sql\TermFullEntityIdBuilder;
use Wikibase\Repo\WikibaseRepo;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class PopulateTermFullEntityId extends LoggedUpdateMaintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Populate the term_full_entity_id column in the wb terms table.' );

		$this->addOption( 'batch-size', "Number of rows to update per batch (Default: 1000)",
			false, true );
		$this->addOption( 'start-id', "First row (id) to start updating", false, true );
		$this->addOption( 'rebuild-all', "Repopulate the column for all rows" );
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

		$termIndex = WikibaseRepo::getDefaultInstance()->getStore()->getTermIndex();
		$idParser = WikibaseRepo::getDefaultInstance()->getEntityIdParser();

		$batchSize = (int)$this->getOption( 'batch-size', 1000 );
		$rebuildAll = $this->getOption( 'rebuild-all', false );
		$selectFromId = $this->getOption( 'start-id', null );

		$builder = new TermFullEntityIdBuilder(
			MediaWikiServices::getInstance()->getDBLoadBalancerFactory(),
			$termIndex,
			WikibaseRepo::getDefaultInstance()->getEntityIdComposer(),
			$idParser,
			$this->getReporter(),
			$batchSize,
			$rebuildAll
		);

		if ( $selectFromId !== null ) {
			$id = $idParser->parse( $selectFromId );
			$builder->rebuild( $id );
		} else {
			$builder->rebuild();
		}

		$this->output( "Done.\n" );

		return true;
	}

	/**
	 * @return ObservableMessageReporter
	 */
	private function getReporter() {
		$reporter = new ObservableMessageReporter();
		$reporter->registerReporterCallback(
			array( $this, 'report' )
		);

		return $reporter;
	}

	/**
	 * @see LoggedUpdateMaintenance::getUpdateKey
	 *
	 * @return string
	 */
	public function getUpdateKey() {
		return 'Wikibase\PopulateTermFullEntityId';
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

$maintClass = PopulateTermFullEntityId::class;
require_once RUN_MAINTENANCE_IF_MAIN;
