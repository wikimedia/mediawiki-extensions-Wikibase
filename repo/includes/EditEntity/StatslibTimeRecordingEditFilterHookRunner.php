<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\EditEntity;

use MediaWiki\Context\IContextSource;
use MediaWiki\Status\Status;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\Repo\Content\EntityContent;
use Wikimedia\Stats\StatsFactory;

/**
 * EditFilterHookRunning that collects stats for edits.
 * @license GPL-2.0-or-later
 */
class StatslibTimeRecordingEditFilterHookRunner implements EditFilterHookRunner {

	private EditFilterHookRunner $hookRunner;
	private StatsFactory $statsFactory;

	public function __construct(
		EditFilterHookRunner $hookRunner,
		StatsFactory $statsFactory
	) {
		$this->hookRunner = $hookRunner;
		$this->statsFactory = $statsFactory->withComponent( 'WikibaseRepo' );
	}

	/**
	 * @param null|EntityDocument|EntityRedirect|EntityContent $new
	 * @param IContextSource $context
	 * @param string $summary
	 * @return Status
	 */
	public function run( $new, IContextSource $context, $summary ) {
		$attemptSaveFilterStart = microtime( true );
		$hookStatus = $this->hookRunner->run( $new, $context, $summary );
		$attemptSaveFilterEnd = microtime( true );

		if ( $new !== null ) {
			if ( $new instanceof EntityDocument ) {
				$entityType = $new->getType();
			} elseif ( $new instanceof EntityRedirect ) {
				$entityType = $new->getEntityId()->getEntityType();
			} elseif ( $new instanceof EntityContent ) {
				$entityType = $new->getEntityId()->getEntityType();
			} else {
				$entityType = 'UNKNOWN';
			}

			$this->statsFactory
			->getTiming( 'EditEntity_EditFilterHookRunner_run_duration_seconds' )
			->setLabel( 'type', $entityType )
			->copyToStatsdAt( "wikibase.repo.EditEntity.timing.EditFilterHookRunner.run.{$entityType}" )
			->observe( ( $attemptSaveFilterEnd - $attemptSaveFilterStart ) * 1000 );
		}

		return $hookStatus;
	}

}
