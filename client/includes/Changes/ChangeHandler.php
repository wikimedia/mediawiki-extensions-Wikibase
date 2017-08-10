<?php

namespace Wikibase\Client\Changes;

use Exception;
use Hooks;
use InvalidArgumentException;
use MWException;
use SiteLookup;
use Title;
use Traversable;
use Wikibase\Change;
use Wikibase\Client\Store\TitleFactory;
use Wikibase\EntityChange;

/**
 * Interface for change handling. Whenever a change is detected,
 * it should be fed to this service which then takes care handling it.
 *
 * @see docs/change-propagation.wiki for an overview of the change propagation mechanism.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class ChangeHandler {

	/**
	 * @var AffectedPagesFinder
	 */
	private $affectedPagesFinder;

	/**
	 * @var TitleFactory
	 */
	private $titleFactory;

	/**
	 * @var PageUpdater
	 */
	private $updater;

	/**
	 * @var ChangeRunCoalescer
	 */
	private $changeRunCoalescer;

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	/**
	 * @param AffectedPagesFinder $affectedPagesFinder
	 * @param TitleFactory $titleFactory
	 * @param PageUpdater $updater
	 * @param ChangeRunCoalescer $changeRunCoalescer
	 * @param SiteLookup $siteLookup
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		AffectedPagesFinder $affectedPagesFinder,
		TitleFactory $titleFactory,
		PageUpdater $updater,
		ChangeRunCoalescer $changeRunCoalescer,
		SiteLookup $siteLookup
	) {

		$this->affectedPagesFinder = $affectedPagesFinder;
		$this->titleFactory = $titleFactory;
		$this->updater = $updater;
		$this->changeRunCoalescer = $changeRunCoalescer;
		$this->siteLookup = $siteLookup;
	}

	/**
	 * @param EntityChange[] $changes
	 */
	public function handleChanges( array $changes ) {
		$changes = $this->changeRunCoalescer->transformChangeList( $changes );

		if ( !Hooks::run( 'WikibaseHandleChanges', [ $changes ] ) ) {
			return;
		}

		foreach ( $changes as $change ) {
			if ( !Hooks::run( 'WikibaseHandleChange', [ $change ] ) ) {
				continue;
			}

			$this->handleChange( $change );
		}
	}

	/**
	 * Main entry point for handling changes
	 *
	 * @todo: process multiple changes at once!
	 *
	 * @param EntityChange $change
	 *
	 * @throws MWException
	 */
	public function handleChange( EntityChange $change ) {
		$changeId = $this->getChangeIdForLog( $change );
		wfDebugLog( __CLASS__, __FUNCTION__ . ": handling change #$changeId"
			. ' (' . $change->getType() . ')' );

		$usagesPerPage = $this->affectedPagesFinder->getAffectedUsagesByPage( $change );

		wfDebugLog( __CLASS__, __FUNCTION__ . ': updating ' . count( $usagesPerPage )
			. " page(s) for change #$changeId." );

		// Run all updates on all affected pages
		$titlesToUpdate = $this->getTitlesForUsages( $usagesPerPage );

		$this->updater->purgeWebCache( $titlesToUpdate );
		$this->updater->scheduleRefreshLinks( $titlesToUpdate );
		$this->updater->injectRCRecords( $titlesToUpdate, $change );
		// TODO: inject dummy revisions
	}

	/**
	 * @param Traversable $usagesPerPage A sequence of PageEntityUsages objects
	 *
	 * @return Title[]
	 */
	private function getTitlesForUsages( $usagesPerPage ) {
		$titles = [];

		foreach ( $usagesPerPage as $usages ) {
			try {
				$title = $this->titleFactory->newFromID( $usages->getPageId() );
				$titles[] = $title;
			} catch ( Exception $ex ) {
				// No title for that ID, maybe the page got deleted just now.
			}
		}

		return $titles;
	}

	/**
	 * Returns a human readable change ID, containing multiple IDs in case of a
	 * coalesced change.
	 *
	 * @param Change $change
	 *
	 * @return string
	 */
	private function getChangeIdForLog( Change $change ) {
		if ( $change instanceof EntityChange ) {
			$info = $change->getInfo();

			if ( isset( $info['change-ids'] ) ) {
				return implode( '|', $info['change-ids'] );
			}
		}

		return $change->getId();
	}

}
