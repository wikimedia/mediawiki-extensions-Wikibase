<?php

namespace Wikibase;

use Maintenance;
use MediaWiki\MediaWikiServices;
use Wikibase\Lib\Reporting\ObservableMessageReporter;
use Wikibase\Repo\Store\Sql\TermSqlIndexSearchFieldsClearer;
use Wikibase\Repo\WikibaseRepo;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * @license GPL-2.0-or-later
 * @author Lucas Werkmeister
 */
class ClearTermSqlIndexSearchFields extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription(
			'Remove all values from the search-related fields of the wb_terms table ' .
			'(term_search_key and term_weight).'
		);

		$this->addOption(
			'batch-size',
			'Number of rows to update per batch (default: 1000).',
			false,
			true
		);

		$this->addOption(
			'from-id',
			'First row (term_row_id) to start removing from.',
			false,
			true
		);

		$this->addOption(
			'sleep',
			'Sleep time (in seconds) between every batch (default: 10).',
			false,
			true
		);

		$this->addOption(
			'skip-term-weight',
			'Skip clearing the term_weight column. ' .
			'term_weight is a fixed-size field, so clearing it has no benefit ' .
			'other than ensuring consistency across the table ' .
			'(the field is cleared in all new rows). ' .
			'Add this option to reduce the UPDATE load.',
			false,
			false
		);
	}

	public function execute() {
		if ( !defined( 'WB_VERSION' ) ) {
			$this->fatalError(
				'You need to have Wikibase enabled in order to use this maintenance script!'
			);
		}

		if ( $this->searchFieldsWritten() ) {
			$this->fatalError(
				'Wikibase is still writing to these fields, ' .
				'it does not make sense to clear them!'
			);
		}

		$clearer = $this->getTermSqlIndexSearchFieldsClearer();
		$clearer->clear();

		$this->output( "Done.\n" );
	}

	/**
	 * Determine whether the search-related fields of the wb_terms table
	 * are being written to in normal operation.
	 *
	 * @return bool
	 */
	private function searchFieldsWritten() {
		$settings = WikibaseRepo::getDefaultInstance()->getSettings();

		if ( $settings->getSetting( 'useTermsTableSearchFields' ) ) {
			// fields are actively used
			return true;
		}

		if ( $settings->getSetting( 'forceWriteTermsTableSearchFields' ) ) {
			// fields are not used, but still written to
			return true;
		}

		return false;
	}

	private function getTermSqlIndexSearchFieldsClearer() {
		$sleep = $this->getOption( 'sleep', 10 );
		$batchSize = (int)$this->getOption( 'batch-size', 1000 );
		$fromId = $this->getOption( 'from-id', null );
		$clearTermWeight = !(bool)$this->getOption( 'skip-term-weight', false );

		$clearer = new TermSqlIndexSearchFieldsClearer(
			MediaWikiServices::getInstance()->getDBLoadBalancerFactory(),
			$sleep
		);
		$clearer->setProgressReporter( $this->getReporter() );
		$clearer->setErrorReporter( $this->getErrorReporter() );
		$clearer->setBatchSize( $batchSize );
		if ( $fromId !== null ) {
			$clearer->setFromId( (int)$fromId );
		}
		$clearer->setClearTermWeight( $clearTermWeight );

		return $clearer;
	}

	/**
	 * @return ObservableMessageReporter
	 */
	private function getReporter() {
		$reporter = new ObservableMessageReporter();
		$reporter->registerReporterCallback( function( $message ) {
			$this->output( "$message\n" );
		} );

		return $reporter;
	}

	/**
	 * @return ObservableMessageReporter
	 */
	private function getErrorReporter() {
		$reporter = new ObservableMessageReporter();
		$reporter->registerReporterCallback( function( $message ) {
			$this->error( "[ERROR] $message" );
		} );

		return $reporter;
	}

}

$maintClass = ClearTermSqlIndexSearchFields::class;
require_once RUN_MAINTENANCE_IF_MAIN;
