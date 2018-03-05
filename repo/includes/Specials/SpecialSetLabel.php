<?php

namespace Wikibase\Repo\Specials;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\EditEntityFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Summary;
use Wikibase\SummaryFormatter;

/**
 * Special page for setting the label of a Wikibase entity.
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SpecialSetLabel extends SpecialModifyTerm {

	public function __construct(
		SpecialPageCopyrightView $copyrightView,
		SummaryFormatter $summaryFormatter,
		EntityTitleLookup $entityTitleLookup,
		EditEntityFactory $editEntityFactory,
		EntityPermissionChecker $entityPermissionChecker
	) {
		parent::__construct(
			'SetLabel',
			$copyrightView,
			$summaryFormatter,
			$entityTitleLookup,
			$editEntityFactory,
			$entityPermissionChecker
		);
	}

	public function doesWrites() {
		return true;
	}

	/**
	 * @see SpecialModifyTerm::validateInput
	 *
	 * @return bool
	 */
	protected function validateInput() {
		if ( !parent::validateInput() ) {
			return false;
		}

		return $this->getBaseRevision()->getEntity() instanceof LabelsProvider;
	}

	/**
	 * @see SpecialModifyTerm::getPostedValue()
	 *
	 * @return string|null
	 */
	protected function getPostedValue() {
		return $this->getRequest()->getVal( 'label' );
	}

	/**
	 * @see SpecialModifyTerm::getValue()
	 *
	 * @param EntityDocument $entity
	 * @param string $languageCode
	 *
	 * @throws InvalidArgumentException
	 * @return string
	 */
	protected function getValue( EntityDocument $entity, $languageCode ) {
		if ( !( $entity instanceof LabelsProvider ) ) {
			throw new InvalidArgumentException( '$entity must be a LabelsProvider' );
		}

		$labels = $entity->getLabels();

		if ( $labels->hasTermForLanguage( $languageCode ) ) {
			return $labels->getByLanguage( $languageCode )->getText();
		}

		return '';
	}

	/**
	 * @see SpecialModifyTerm::setValue()
	 *
	 * @param EntityDocument $entity
	 * @param string $languageCode
	 * @param string $value
	 *
	 * @return Summary
	 */
	protected function setValue( EntityDocument $entity, $languageCode, $value ) {
		$value = $value === '' ? null : $value;
		$summary = new Summary( 'wbsetlabel' );

		if ( $value === null ) {
			$changeOp = $this->termChangeOpFactory->newRemoveLabelOp( $languageCode );
		} else {
			$changeOp = $this->termChangeOpFactory->newSetLabelOp( $languageCode, $value );
		}

		$this->applyChangeOp( $changeOp, $entity, $summary );

		return $summary;
	}

}
