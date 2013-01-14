<?php

namespace Wikibase;
use Status;
use Diff\Diff;

/**
 * Detector for multilang constraint violations.
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
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 */
abstract class MultiLangConstraintDetector {

	/**
	 * Looks for multilang length violations in the provided entries. If there
	 * is no such conflict, an empty array is returned. If there is to long entries,
	 * an array with multilang strings is returned.
	 *
	 * @since 0.4
	 *
	 * @param Entity $entity
	 *
	 * @return Term[]
	 */
	public static function findLengthConstraintViolations( array $entries, $limit, \Status $status ) {
		$foundEntries = array();

		foreach ( $entries as $langCode => $langValue ) {
			$toLong = false;
			if ( is_string( $langValue ) ) {
				$toLong = strlen( $langValue ) > $limit;
			}
			elseif ( is_array( $langValue ) ) {
				array_map(
					function( $entry ) use ( &$toLong, $limit ) {
						$toLong |= is_string( $entry ) && ( mb_strlen( $entry ) > $limit );
					},
					$langValue
				);
			}
			if ( $toLong ) {
				$foundEntries[$langCode] = $langValue;
				$status->warning( 'wikibase-warning-constraint-violation-length', $langCode );
			}
		}

		return $foundEntries;
	}

	/**
	 * Check for multilang constraint violations in the provided Entity.
	 * If there is a constraint affected by the provided multilang diffs, a fatal error
	 * will be added to the provided status.
	 *
	 * This could be split out in individual calls, but then the mess show up in
	 * EditEntity and that class should not know to much of the internals.
	 *
	 * @since 0.4
	 *
	 * @param Entity $entity The Entity for which to check if there is any conflict
	 * @param Status $status The status to which to add an error if there is a violation
	 * @param Diff|null $diff
	 */
	abstract public function checkConstraints( Entity $entity, Status $status, Diff $diff = null, array $limits = null );

	/**
	 * Check the sets and figure out which one of them intersects the diff.
	 * @param array $foundSets Pair of language and value
	 * @param Diff $diff A specific diff for the values to be analyzed
	 * @return array the pairs that also intersects the diff
	 */
	protected function inDiff( array $foundSets, $diff ) {
		$failedLangs = array();
		foreach ( $foundSets as $entry ) {
			foreach ( $entry as $langCode => $langValue) {
				if ( $diff === null || $this->languageAffectedByDiff( $langCode, $diff ) ) {
					$failedLangs[$langCode] = $langValue;
				}
			}
		}
		return $failedLangs;
	}

	protected static function formatLangCodes( array $langCodes ) {
		global $wgLang;

		return $wgLang->semicolonList( $langCodes );
	}

	protected static function formatLangValues( array $langValues, $truncateLength ) {
		global $wgLang;

		$res = array_walk_recursive(
			$langValues,
			function ( &$value ) use ( $truncateLength ) {
				global $wgLang;
				$value = is_string( $value ) ? $wgLang->truncate( $value, $truncateLength ) : $value;
			}
		);

		$res = array_walk(
			$langValues,
			function ( &$value ) use ( $truncateLength ) {
				global $wgLang;
				$value = is_array( $value ) ? $wgLang->commaList( $value ) : $value;
			}
		);

		return $wgLang->semicolonList( $langValues );
	}

	/**
	 * Returns if either of the provided multilang diffs affect a certain language.
	 *
	 * @since 0.4
	 *
	 * @param string $languageCode
	 * @param Diff|null $diff
	 *
	 * @return boolean
	 */
	protected function languageAffectedByDiff( $languageCode, Diff $diff = null ) {
		$c = $diff->getOperations();

		if ( $diff !== null && array_key_exists( $languageCode, $c ) ) {
			return true;
		}

		return false;
	}

}