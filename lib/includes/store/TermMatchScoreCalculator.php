<?php

namespace Wikibase;

/**
 * Calculates and stores score for term search
 *
 * @since 0.3
 *
 * @licence GNU GPL v2+
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */

class TermMatchScoreCalculator {

	protected $entry;
	protected $searchLength;

	/**
	 * Constructor
	 *
	 * @since 0.3
	 *
	 * @param array $entry
	 * @param string $search
	 */
	public function __construct( array $entry, $search ) {
		$this->entry = $entry;
		$this->searchLength = strlen( $search );
	}

	/**
	 * Calculate score
	 *
	 * @since 0.3
	 *
	 * @return integer $score
	 */
	public function calculateScore() {
		$score = 0;

		if ( isset( $this->entry['label'] ) ) {
			$score = $this->searchLength / strlen( $this->entry['label'] );
		}

		if ( isset( $this->entry['aliases'] ) ) {
			foreach ( $this->entry['aliases'] as $alias ) {
				$aliasScore = $this->searchLength / strlen( $alias );

				if ( $aliasScore > $score ) {
					$score = $aliasScore;
				}
			}
		}

		return $score;
	}

}
