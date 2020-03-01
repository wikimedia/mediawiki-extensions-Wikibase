<?php

namespace Wikibase\Lib\Store\Sql\Terms;

use Job;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use Title;

/**
 * A job to cleanup the wbt_* terms table rows when they may not be needed any more.
 * This job currently executes in a single transaction.
 *
 * @todo Execute the cleanup of each table in its own transaction
 *
 * @see @ref md_docs_storage_terms
 * @author Addshore
 * @license GPL-2.0-or-later
 */
class CleanTermsIfUnusedJob extends Job {

	/** @var TermStoreCleaner */
	private $termInLangIdsCleaner;

	const JOB_NAME = 'CleanTermsIfUnused';
	const TERM_IN_LANG_IDS = 'termInLangIds';

	/**
	 * @param Title $unused But required due to the code in Job::factory currently.
	 * @param array $params
	 * @return CleanTermsIfUnusedJob
	 */
	public static function getJobSpecification( Title $unused, array $params ): CleanTermsIfUnusedJob {
		return self::getJobSpecificationNoTitle( $params );
	}

	public static function getJobSpecificationNoTitle( array $params ): CleanTermsIfUnusedJob {
		$loadBalancer = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$logger = LoggerFactory::getInstance( 'Wikibase' );
		$innerTermStoreCleaner = new DatabaseInnerTermStoreCleaner( $logger );
		$cleaner = new DatabaseUsageCheckingTermStoreCleaner( $loadBalancer, $innerTermStoreCleaner );
		return new self( $cleaner, $params );
	}

	public function __construct( TermStoreCleaner $cleaner, $params ) {
		parent::__construct( self::JOB_NAME, $params );
		$this->termInLangIdsCleaner = $cleaner;
	}

	/**
	 * Of the given term in lang IDs, delete those that are not used by any other items or properties.
	 *
	 * @return bool
	 */
	public function run() {
		$termInLangIds = $this->params[self::TERM_IN_LANG_IDS];
		$this->termInLangIdsCleaner->cleanTermInLangIds( $termInLangIds );
		return true;
	}
}
