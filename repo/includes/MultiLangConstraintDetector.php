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
class MultiLangConstraintDetector {

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
	public function getLengthConstraintViolations( array $entries, $limit ) {
		$foundEntries = array();

		foreach ( $entries as $langCode => $langValue ) {
			$toLong = false;
			if ( is_string( $langValue ) ) {
				$toLong = strlen( $langValue ) > $limit;
			}
			elseif ( is_array( $langValue ) ) {
				array_map(
					function( $entry ) use ( &$toLong, $limit ) {
						$toLong |= is_string( $entry ) && ( strlen( $entry ) > $limit );
					},
					$langValue
				);
			}
			if ( $toLong ) {
				$foundEntries[$langCode] = $langValue;
			}
		}

		return $foundEntries;
	}

	/**
	 * Looks for multilang constraint violations in the provided Entity.
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
	public function addConstraintChecks( Entity $entity, Status $status, Diff $diff = null, array $limits = null ) {

		if ( !isset( $limits ) ) {
			$limits = Settings::get( 'multilang-limits' );
		}

		$diffs = array(
			'label' => $diff === null ? null : $diff->getLabelsDiff(),
			'description' => $diff === null ? null : $diff->getDescriptionsDiff(),
			'aliases' => $diff === null ? null : $diff->getAliasesDiff()
		);

		$foundSets = array();

		if ( wfRunHooks( 'WikibaseAddConstraintChecksForLabel',
			array( &$foundSets['label'], $entity->getLabels(), $limits ) ) ) {

			// default constraints in addition to the ones checked inside the hook
			$foundSets['label'] = array(
				$this->getLengthConstraintViolations( $entity->getLabels(), $limits['length'] )
			);
		}

		if ( wfRunHooks( 'WikibaseAddConstraintChecksForDescription',
			array( &$foundSets['description'], $entity->getDescriptions(), $limits ) ) ) {

			// default constraints in addition to the ones checked inside the hook
			$foundSets['description'] = array(
				$this->getLengthConstraintViolations( $entity->getDescriptions(), $limits['length'] )
			);
		}

		if ( wfRunHooks( 'WikibaseAddConstraintChecksForAliases',
			array( &$foundSets['aliases'], $entity->getAllAliases(), $limits ) ) ) {

			// default constraints in addition to the ones checked inside the hook
			$foundSets['aliases'] = array(
				$this->getLengthConstraintViolations( $entity->getAllAliases(), $limits['length'] )
			);
		}

		foreach ( $foundSets as $section => $set ) {
			$failedLang = array();
			foreach ( $set as $key => $entry ) {
				if ( !empty( $entry ) ) {
					foreach ( $entry as $langCode => $langValue) {
						if ( $diffs[$section] === null || $this->languageAffectedByDiff( $langCode, $diffs[$section] ) ) {
							$failedLang[] = $langCode;
						}
					}
				}
			}
			if ( !empty( $failedLang ) ) {
				$status->fatal(
					'wikibase-error-constraint-violation-' . $section,
					implode(',', $failedLang)
				);
				return;
			}
		}
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