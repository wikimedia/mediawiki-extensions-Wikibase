<?php

namespace Wikibase\Repo\Specials;

use Html;
use Wikibase\DispatchStats;

/**
 * Page for displaying diagnostics about the dispatch process.
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class SpecialDispatchStats extends SpecialWikibasePage {

	/**
	 * Constructor.
	 *
	 * @since 0.2
	 */
	public function __construct() {
		parent::__construct( 'DispatchStats' );

	}

	protected function outputRow( $data, $tag = 'td', $attr = array() ) {
		$this->getOutput()->addHTML( Html::openElement( 'tr' ));

		foreach ( $data as $v ) {
			if ( !isset( $attr['align'] ) ) {
				if ( is_int( $v ) || is_float( $v ) ) {
					$attr['align'] = 'right';
				} else {
					$attr['align'] = 'right';
				}
			}

			$this->getOutput()->addHTML( Html::element( $tag, $attr, $v ));
		}

		$this->getOutput()->addHTML( Html::closeElement( 'tr' ));
	}

	protected function outputStateRow( $label, $state ) {
		$lang = $this->getContext()->getLanguage();

		$this->outputRow( array(
			$label,
			isset( $state->chd_site ) ? $state->chd_site : '-',
			isset( $state->chd_seen ) ? $state->chd_seen : '-',
			$lang->formatNum( $state->chd_pending ),
			$state->chd_lag === null
				? wfMessage( 'wikibase-dispatchstats-large-lag' )->text()
				: $lang->formatDuration( $state->chd_lag, array( 'days', 'hours', 'minutes' ) ),
			isset( $state->chd_touched )
				? $lang->timeanddate( $state->chd_touched, true )
				: '-',
		) );
	}

	public function execute( $subPage ) {
		parent::execute( $subPage );

		$lang = $this->getContext()->getLanguage();

		$stats = new DispatchStats();
		$stats->load();

		$this->getOutput()->addHTML( Html::rawElement( 'p', array(),
			$this->msg( 'wikibase-dispatchstats-intro' )->parse() ) );

		if ( !$stats->hasStats() ) {
			$this->getOutput()->addHTML( Html::rawElement( 'p', array(),
				$this->msg( 'wikibase-dispatchstats-no-stats' )->parse() ) );

			return;
		}

		// changes ------
		$this->getOutput()->addHTML( Html::rawElement( 'h2', array(), $this->msg( 'wikibase-dispatchstats-changes' )->parse() ));

		$this->getOutput()->addHTML( Html::openElement( 'table', array( 'class' => 'wikitable' ) ));

		$this->outputRow( array(
			'',
			$this->msg( 'wikibase-dispatchstats-change-id' )->text(),
			$this->msg( 'wikibase-dispatchstats-change-timestamp' )->text(),
		), 'th' );

		$this->outputRow( array(
			$this->msg( 'wikibase-dispatchstats-oldest-change' )->text(),
			$stats->getMinChangeId(),
			$lang->timeanddate( $stats->getMinChangeTimestamp(), true ),
		) );

		$this->outputRow( array(
			$this->msg( 'wikibase-dispatchstats-newest-change' )->text(),
			$stats->getMaxChangeId(),
			$lang->timeanddate( $stats->getMaxChangeTimestamp(), true ),
		) );

		$this->getOutput()->addHTML( Html::closeElement( 'table' ));

		// dispatch stats ------
		$this->getOutput()->addHTML( Html::rawElement( 'h2', array(), $this->msg( 'wikibase-dispatchstats-stats' )->parse() ));

		$this->getOutput()->addHTML( Html::openElement( 'table', array( 'class' => 'wikitable' ) ));

		$this->outputRow( array(
			'',
			$this->msg( 'wikibase-dispatchstats-site-id' )->text(),
			$this->msg( 'wikibase-dispatchstats-pos' )->text(),
			$this->msg( 'wikibase-dispatchstats-lag-num' )->text(),
			$this->msg( 'wikibase-dispatchstats-lag-time' )->text(),
			$this->msg( 'wikibase-dispatchstats-touched' )->text(),
		), 'th' );

		$this->outputStateRow(
			$this->msg( 'wikibase-dispatchstats-freshest' )->text(),
			$stats->getFreshest()
		);

		$this->outputStateRow(
			$this->msg( 'wikibase-dispatchstats-median' )->text(),
			$stats->getMedian()
		);

		$this->outputStateRow(
			$this->msg( 'wikibase-dispatchstats-stalest' )->text(),
			$stats->getStalest()
		);

		$this->outputStateRow(
			$this->msg( 'wikibase-dispatchstats-average' )->text(),
			$stats->getAverage()
		);

		$this->getOutput()->addHTML( Html::closeElement( 'table' ));
	}

}
