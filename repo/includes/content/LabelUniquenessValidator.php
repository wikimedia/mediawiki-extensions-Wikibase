<?php

namespace Wikibase\content;

use Status;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\Term;
use Wikibase\TermIndex;

/**
 * Validator for checking that entity labels are unique (per language).
 * This is used to make sure that Properties have unique labels.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class LabelUniquenessValidator implements EntityValidator {

	/**
	 * @var TermIndex
	 */
	protected $termIndex;

	/**
	 * @param TermIndex $termIndex
	 */
	function __construct( TermIndex $termIndex ) {
		$this->termIndex = $termIndex;
	}

	/**
	 * @see OnSaveValidator::validate()
	 *
	 * @param Entity $entity
	 *
	 * @return Status
	 */
	public function validateEntity( Entity $entity ) {
		$labels = array();

		foreach ( $entity->getLabels() as $langCode => $labelText ) {
			$label = new Term( array(
				'termLanguage' => $langCode,
				'termText' => $labelText,
			) );

			$labels[] = $label;
		}

		$foundLabels = $this->termIndex->getMatchingTerms(
			$labels,
			Term::TYPE_LABEL,
			$entity->getType()
		);

		$status = Status::newGood();

		/**
		 * @var Term $foundLabel
		 */
		foreach ( $foundLabels as $foundLabel ) {
			$foundId = $foundLabel->getEntityId();

			if ( $entity->getId() === null || !$entity->getId()->equals( $foundId ) ) {
				// Messages: wikibase-error-label-not-unique-wikibase-property,
				// wikibase-error-label-not-unique-wikibase-query
				$status->fatal(
					'wikibase-error-label-not-unique-wikibase-' . $entity->getType(),
					$foundLabel->getText(),
					$foundLabel->getLanguage(),
					$foundId !== null ? $foundId : ''
				);
			}
		}

		return $status;
	}

}