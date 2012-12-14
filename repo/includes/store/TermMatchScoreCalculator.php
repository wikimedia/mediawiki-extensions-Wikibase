<?php

namespace Wikibase;

/**
 * Calculates and stores score for term search
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.3
 *
 * @file
 * @ingroup WikibaseRepo
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
	 * @returns integer $score
	 */
	public function calculateScore() {
		$score = $this->searchLength / strlen( $this->entry['label'] );

		if ( !isset( $this->entry['aliases'] ) ) {
			return $score;
		}

		foreach ( $this->entry['aliases'] as $alias ) {
			$aliasScore = $this->searchLength / strlen( $alias );

			if ( $aliasScore > $score ) {
				$score = $aliasScore;
			}
		}

		return $score;
	}

}
