<?php

declare( strict_types = 1 );

namespace Wikibase\Client\ChangeModification;

use MediaWiki\MediaWikiServices;
use Title;
use Wikimedia\Assert\Assert;
use Wikimedia\Rdbms\ILBFactory;

/**
 * Job for notifying a client wiki of a batch of revision visibility changes on the repository.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class ChangeVisibilityNotificationJob extends ChangeModificationNotificationJob {

	/** @var int */
	private $batchSize;

	/**
	 * Constructs a ChangeVisibilityNotificationJob for the repo revisions given.
	 *
	 * @param ILBFactory $lbFactory
	 * @param int $batchSize
	 * @param array $params Contains the name of the repo, revisionIdentifiersJson to redact
	 *   and the visibilityBitFlag to set.
	 */
	public function __construct( ILBFactory $lbFactory, int $batchSize, array $params = [] ) {
		parent::__construct( 'ChangeVisibilityNotification', $lbFactory, $params );

		Assert::parameter(
			isset( $params['visibilityBitFlag'] ),
			'$params',
			'$params[\'visibilityBitFlag\'] not set.'
		);

		$this->batchSize = $batchSize;
	}

	public static function newFromGlobalState( Title $unused, array $params ) {
		$mwServices = MediaWikiServices::getInstance();

		return new self(
			$mwServices->getDBLoadBalancerFactory(),
			$mwServices->getMainConfig()->get( 'UpdateRowsPerQuery' ),
			$params
		);
	}

	/**
	 * @param int[] $relevantChanges
	 */
	protected function modifyChanges( array $relevantChanges ): void {
		$visibilityBitFlag = $this->params['visibilityBitFlag'];

		$dbw = $this->lbFactory->getMainLB()->getConnection( DB_MASTER );

		foreach ( array_chunk( $relevantChanges, $this->batchSize ) as $rcIdBatch ) {
			$dbw->update(
				'recentchanges',
				[ 'rc_deleted' => $visibilityBitFlag ],
				[ 'rc_id' => $rcIdBatch ],
				__METHOD__
			);

			$this->lbFactory->waitForReplication();
		}
	}

}
