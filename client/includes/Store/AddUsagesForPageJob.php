<?php

namespace Wikibase\Client\Store;

use Job;
use JobSpecification;
use Title;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikimedia\Assert\Assert;

/**
 * Job for scheduled invocation of UsageUpdater::addUsagesForPage
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class AddUsagesForPageJob extends Job {

	/**
	 * @var UsageUpdater
	 */
	private $usageUpdater;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * Spec constructor, for creating JobSpecifications to be pushed to the job queue.
	 *
	 * @param Title $title
	 * @param EntityUsage[] $usages
	 *
	 * @return JobSpecification
	 */
	public static function newSpec( Title $title, array $usages ) {
		// NOTE: Map EntityUsage objects to scalar arrays, for JSON serialization in the job queue.
		$usages = array_map( function ( EntityUsage $usage ) {
			return $usage->asArray();
		}, $usages );

		return new JobSpecification(
			'wikibase-addUsagesForPage',
			[
				'namespace' => $title->getNamespace(),
				'title' => $title->getDBkey(),
				'pageId' => $title->getArticleID(),
				'usages' => $usages,
			],
			[ 'removeDuplicates' => true ]
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

		Assert::parameterElementType(
			'array',
			$params['usages'],
			'$params["usages"]' );

		$usageUpdater = WikibaseClient::getStore()->getUsageUpdater();
		$idParser = WikibaseClient::getEntityIdParser();
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
		}, $this->params['usages'] );

		return $usages;
	}

	/**
	 * @see Job::run
	 *
	 * @return bool
	 */
	public function run() {
		$this->usageUpdater->addUsagesForPage(
			$this->params['pageId'],
			$this->getUsages()
		);

		return true;
	}

}
