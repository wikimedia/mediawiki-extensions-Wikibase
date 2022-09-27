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
 * Callers can choose to supply a single termInLang id to clean per job or multiple.
 * Supplying multiple ids will naturally mean the Job and the transaction take longer
 * and therefor locks will also be held for a longer period of time possibly causing lock contention.
 * This started to show on wikidata.org in https://phabricator.wikimedia.org/T246898
 * and thus the default was switched for create a single job per termInLang id.
 * For smaller Wikibase installs with a lower edit rate it might make sense to instead optimize
 * for fewer jobs, where lock contention is less of an issue.
 *
 * @todo Execute the cleanup of each table in its own transaction to further reduce lock contention
 *
 * @see @ref docs_storage_terms
 * @author Addshore
 * @license GPL-2.0-or-later
 */
class CleanTermsIfUnusedJob extends Job {

	/** @var TermStoreCleaner */
	private $termInLangIdsCleaner;

	public const JOB_NAME = 'CleanTermsIfUnused';
	public const TERM_IN_LANG_IDS = 'termInLangIds';

	/**
	 * @param Title $unused But required due to the code in Job::factory currently.
	 * @param array $params
	 * @return CleanTermsIfUnusedJob
	 */
	public static function getJobSpecification( Title $unused, array $params ): CleanTermsIfUnusedJob {
		return self::getJobSpecificationNoTitle( $params );
	}

	public static function getJobSpecificationNoTitle( array $params ): CleanTermsIfUnusedJob {
		$repoDomainDb = MediaWikiServices::getInstance()
			->get( 'WikibaseRepo.RepoDomainDbFactory' )
			->newRepoDb();

		$logger = LoggerFactory::getInstance( 'Wikibase' );
		$innerTermStoreCleaner = new DatabaseInnerTermStoreCleaner( $logger );
		$cleaner = new DatabaseUsageCheckingTermStoreCleaner( $repoDomainDb, $innerTermStoreCleaner );
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
