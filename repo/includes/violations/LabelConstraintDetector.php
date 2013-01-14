<?php

namespace Wikibase;
use Status;
use Diff\Diff;

/**
 * Detector for label constraint violations.
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
class LabelConstraintDetector extends MultiLangConstraintDetector {

	/**
	 * @see MultiLangConstraintDetector::checkConstraints
	 */
	public function checkConstraints( Entity $entity, Status $status, Diff $diff = null, array $limits = null ) {
		global $wgLang;

		$truncateLength = Settings::get( 'multilang-truncate-length' );

		if ( !isset( $limits ) ) {
			$limits = Settings::get( 'multilang-limits' );
		}

		$foundSets = array();

		if ( wfRunHooks( 'WikibaseCheckConstraintsForLabel',
			array( &$foundSets, $entity->getLabels(), $limits, $status ) ) ) {

			// default constraints in addition to the ones checked inside the hook
			$foundSets[] = static::findLengthConstraintViolations( $entity->getLabels(), $limits['length'], $status );
		}

		$failedLangs = $this->inDiff( $foundSets, $diff === null ? null : $diff->getLabelsDiff() );
		$failedCodes = static::formatLangCodes( array_keys( $failedLangs ) );
		$failedValues = static::formatLangValues( array_values( $failedLangs ), $truncateLength );

		if ( !empty( $failedLangs ) ) {
			// At this point it should be possible to remove messages for other languages,
			// but unfortunatly there is no method to remove registered but outdated warnings.
			// We add a tatal error message before we leave.
			$status->fatal(
				'wikibase-error-constraint-violation-label',
				count( $failedCodes ),
				$failedCodes,
				$failedValues
			);
		}
	}

}