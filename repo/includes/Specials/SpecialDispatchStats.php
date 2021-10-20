<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Specials;

use Html;
use Wikibase\Repo\Store\Sql\DispatchStats;

/**
 * @license GPL-2.0-or-later
 */
class SpecialDispatchStats extends SpecialWikibasePage {

	/**
	 * @var DispatchStats
	 */
	private $dispatchStats;

	public function __construct( DispatchStats $dispatchStats ) {
		parent::__construct( 'DispatchStats' );

		$this->dispatchStats = $dispatchStats;
	}

	public function execute( $subPage ): void {
		parent::execute( $subPage );

		$this->addIntroToPage();

		$stats = $this->dispatchStats->getDispatchStats();

		if ( isset( $stats['numberOfChanges'] ) && $stats['numberOfChanges'] === 0 ) {
			$this->addEmptyQueueMessageToPage();
			return;
		}

		$this->addChangeTimesToPage( $stats['freshestTime'], $stats['stalestTime'] );

		if ( isset( $stats['minimumNumberOfChanges'] ) ) {
			$this->addMinimumNumberOfChangesToPage( $stats['minimumNumberOfChanges'] );
			return;
		}

		if ( isset( $stats['estimatedNumberOfChanges'] ) ) {
			$this->addEstimatedStatsToPage( $stats['estimatedNumberOfChanges'] );
			return;
		}

		$this->addNumberOfChangesToPage( $stats['numberOfChanges'] );
		$this->addNumberOfEntitiesToPage( $stats['numberOfEntities'] );
	}

	private function addIntroToPage(): void {
		$this->getOutput()->addHTML( Html::rawElement( 'p', [],
			$this->msg( 'wikibase-dispatchstats-intro' )->parse() ) );
	}

	private function addChangeTimesToPage( string $freshestTime, string $stalestTime ): void {

		$this->getOutput()->addHTML( Html::rawElement( 'p', [],
			$this->msg( 'wikibase-dispatchstats-oldest' )->dateTimeParams( $stalestTime ) ) );
		$this->getOutput()->addHTML( Html::rawElement( 'p', [],
			$this->msg( 'wikibase-dispatchstats-newest' )->dateTimeParams( $freshestTime ) ) );
	}

	private function addMinimumNumberOfChangesToPage( int $minNumberOfChanges ): void {
		$this->getOutput()->addHTML( Html::rawElement( 'p', [],
			$this->msg( 'wikibase-dispatchstats-above' )->numParams( $minNumberOfChanges )->parse() ) );
	}

	private function addEstimatedStatsToPage( int $estimatedNumberOfChanges ): void {
		$this->getOutput()->addHTML( Html::rawElement( 'p', [],
			$this->msg( 'wikibase-dispatchstats-estimate' )->numParams( $estimatedNumberOfChanges )->parse() ) );
	}

	private function addEmptyQueueMessageToPage(): void {
		$this->getOutput()->addHTML( Html::rawElement( 'p', [],
			$this->msg( 'wikibase-dispatchstats-empty-queue' )->parse() ) );
	}

	private function addNumberOfChangesToPage( int $numberOfChanges ): void {
		$this->getOutput()->addHTML( Html::rawElement( 'p', [],
			$this->msg( 'wikibase-dispatchstats-number-of-changes-in-queue' )->numParams( $numberOfChanges )->parse() ) );
	}

	private function addNumberOfEntitiesToPage( int $numberOfEntities ): void {
		$this->getOutput()->addHTML( Html::rawElement( 'p', [],
			$this->msg( 'wikibase-dispatchstats-number-of-entities-in-queue' )->numParams( $numberOfEntities )->parse() ) );
	}
}
