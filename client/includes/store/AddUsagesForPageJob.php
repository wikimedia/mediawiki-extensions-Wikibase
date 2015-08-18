<?php

namespace Wikibase\Client\Store;

use Job;
use JobSpecification;
use Title;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Services\EntityId\EntityIdParser;
use Wikimedia\Assert\Assert;

/**
 * Job for scheduled invocation of UsageUpdater::addUsagesForPage
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class AddUsagesForPageJob extends Job {

	/**
	 * @var integer
	 */
	private $pageId;

	/**
	 * @var EntityUsage[]
	 */
	private $usages;

	/**
	 * @var string timestamp
	 */
	private $touched;

	/**
	 * @var UsageUpdater
	 */
	private $usageUpdater;

	/**
	 * @var EntityIdParser $idParser
	 */
	private $idParser;

	/**
	 * Spec constructor, for creating JobSpecifications to be pushed to the job queue.
	 *
	 * @param Title $title
	 * @param EntityUsage[] $usages
	 * @param string $touched
	 *
	 * @return JobSpecification
	 */
	public static function newSpec( Title $title, array $usages, $touched ) {
		// NOTE: Map EntityUsage objects to scalar arrays, for JSON serialization in the job queue.
		$usages = array_map( function ( EntityUsage $usage ) {
			return $usage->asArray();
		}, $usages );

		$jobParams = array(
			'pageId' => $title->getArticleId(),
			'usages' => $usages,
			'touched' => $touched
		);

		return new JobSpecification(
			'wikibase-addUsagesForPage',
			$jobParams,
			array( 'removeDuplicates' => true ),
			$title
		);
	}

	/**
	 * @param Title $title
	 * @param array $params
	 */
	public function __construct( Title $title, array $params ) {
		parent::__construct( 'wikibase-addUsagesForPage', $title, $params );

		Assert::parameter(
			isset( $params['pageId'] ) && is_int( $params['pageId'] ) && $params['pageId'] > 0,
			'$params["pageId"]',
			'must be a positive integer' );

		Assert::parameter(
			isset( $params['usages'] ) && is_array( $params['usages'] ) && !empty( $params['usages'] ),
			'$params["usages"]',
			'must be a non-empty array' );

		Assert::parameter(
			isset( $params['touched'] ) && is_string( $params['touched'] ) && $params['touched'] !== '',
			'$params["touched"]',
			'must be a timestamp string' );

		Assert::parameterElementType(
			'array',
			$params['usages'],
			'$params["usages"]' );

		$this->pageId = $params['pageId'];
		$this->usages = $params['usages'];
		$this->touched = $params['touched'];

		$usageUpdater = WikibaseClient::getDefaultInstance()->getStore()->getUsageUpdater();
		$idParser = WikibaseClient::getDefaultInstance()->getEntityIdParser();
		$this->overrideServices( $usageUpdater, $idParser );
	}

	/**
	 * Service override for testing
	 *
	 * @param UsageUpdater $usageUpdater
	 * @param EntityIdParser $idParser
	 */
	public function overrideServices( UsageUpdater $usageUpdater, EntityIdParser $idParser ) {
		$this->usageUpdater = $usageUpdater;
		$this->idParser = $idParser;
	}

	/**
	 * @see Job::getDeduplicationInfo
	 *
	 * @return array Job params array, with touched omitted.
	 */
	public function getDeduplicationInfo() {
		// parent Job class returns an array with 'params' key
		$info = parent::getDeduplicationInfo();

		// If this job is not yet processed and a new one (e.g. from a more recent
		// edit) is created with same page id and usages, then the job queue can
		// disregard this one and avoid duplicate, excess database updates.
		if ( is_array( $info['params'] ) ) {
			unset( $info['params']['touched'] );
		}

		return $info;
	}

	/**
	 * @return EntityUsage[]
	 */
	private function getUsages() {
		// Turn serialized usage info into EntityUsage objects
		$idParser = $this->idParser;
		$usages = array_map( function ( array $usageArray ) use ( $idParser ) {
			// This is the inverse of EntityUsage::asArray()
			return new EntityUsage(
				$idParser->parse( $usageArray['entityId'] ),
				$usageArray['aspect'],
				$usageArray['modifier']
			);
		}, $this->usages );

		return $usages;
	}

	/**
	 * Call UsageUpdater::addUsagesForPage
	 *
	 * @return bool Success
	 */
	public function run() {
		$this->usageUpdater->addUsagesForPage(
			$this->pageId,
			$this->getUsages(),
			$this->touched
		);
	}

}
