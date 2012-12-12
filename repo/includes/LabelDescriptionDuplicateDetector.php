<?php

namespace Wikibase;
use Status;
use Diff\MapDiff;

/**
 * Detector of label+description uniqueness constraint violations.
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
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LabelDescriptionDuplicateDetector {

	/**
	 * Looks for label+description violations in the provided Entity using
	 * the provided TermCache. If there is no such conflict, an empty array is returned.
	 * If there is, an array with first label and then description is returned,
	 * both objects being a Term.
	 *
	 * @since 0.4
	 *
	 * @param Entity $entity
	 * @param TermCombinationMatchFinder $termCache
	 *
	 * @return Term[]
	 */
	public function getConflictingTerms( Entity $entity, TermCombinationMatchFinder $termCache ) {
		$terms = array();

		foreach ( $entity->getLabels() as $langCode => $labelText ) {
			$description = $entity->getDescription( $langCode );

			if ( $description !== false ) {
				$label = new Term( array(
					'termLanguage' => $langCode,
					'termText' => $labelText,
					'termType' => Term::TYPE_LABEL,
				) );

				$description = new Term( array(
					'termLanguage' => $langCode,
					'termText' => $description,
					'termType' => Term::TYPE_DESCRIPTION,
				) );

				$terms[] = array( $label, $description );
			}
		}

		if ( empty( $terms ) ) {
			return array();
		}

		$foundTerms = $termCache->getMatchingTermCombination(
			$terms,
			null,
			$entity->getType(),
			$entity->getId() === null ? null : $entity->getId()->getNumericId(),
			$entity->getType()
		);

		return $foundTerms;
	}

	/**
	 * Looks for label+description violations in the provided Entity using the provided TermCache.
	 * If there is a conflict affected by the provided label and description diffs, a fatal error
	 * will be added to the provided status. If both diffs are not provided, any conflict will
	 * result in a fatal error being added.
	 *
	 * @since 0.4
	 *
	 * @param Entity $entity The Entity for which to check if it has any non-unique label+description pairs
	 * @param Status $status The status to which to add an error if there is a violation
	 * @param TermCombinationMatchFinder $termCache The TermCache to use for conflict detection
	 * @param MapDiff|null $labelsDiff
	 * @param MapDiff|null $descriptionDiff
	 */
	public function addLabelDescriptionConflicts( Entity $entity, Status $status, TermCombinationMatchFinder $termCache,
												  MapDiff $labelsDiff = null, MapDiff $descriptionDiff = null ) {

		$foundTerms = $this->getConflictingTerms( $entity, $termCache );

		if ( !empty( $foundTerms ) ) {
			/**
			 * @var Term $label
			 * @var Term $description
			 */
			list( $label, $description ) = $foundTerms;

			if ( ( $labelsDiff === null && $descriptionDiff === null )
				|| $this->languageAffectedByDiff( $label->getLanguage(), $labelsDiff, $descriptionDiff ) ) {

				$status->fatal(
					'wikibase-error-label-not-unique-item',
					$label->getText(),
					$label->getLanguage(),
					$label->getEntityId(),
					$description->getText()
				);
			}
		}
	}

	/**
	 * Returns if either of the provided label and description diffs affect a certain language.
	 *
	 * @since 0.4
	 *
	 * @param string $languageCode
	 * @param MapDiff|null $labelsDiff
	 * @param MapDiff|null $descriptionDiff
	 *
	 * @return boolean
	 */
	protected function languageAffectedByDiff( $languageCode, MapDiff $labelsDiff = null, MapDiff $descriptionDiff = null ) {
		$c = $labelsDiff->getOperations();

		if ( $labelsDiff !== null && array_key_exists( $languageCode, $c ) ) {
			return true;
		}

		if ( $descriptionDiff !== null && array_key_exists( $languageCode, $descriptionDiff->getOperations() ) ) {
			return true;
		}

		return false;
	}

}