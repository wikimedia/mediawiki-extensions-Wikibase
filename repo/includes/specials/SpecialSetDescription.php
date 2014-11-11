<?php

namespace Wikibase\Repo\Specials;

use Wikibase\DataModel\Entity\Entity;
use Wikibase\Summary;

/**
 * Special page for setting the description of a Wikibase entity.
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SpecialSetDescription extends SpecialModifyTerm {

	/**
	 * @since 0.4
	 */
	public function __construct() {
		parent::__construct( 'SetDescription' );
	}

	/**
	 * @see SpecialSetEntity::getPostedValue()
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	protected function getPostedValue() {
		return $this->getRequest()->getVal( 'description' );
	}

	/**
	 * @see SpecialSetEntity::getValue()
	 *
	 * @since 0.4
	 *
	 * @param Entity $entity
	 * @param string $languageCode
	 *
	 * @return string
	 */
	protected function getValue( $entity, $languageCode ) {
		return $entity === null ? '' : $entity->getDescription( $languageCode );
	}

	/**
	 * @see SpecialSetEntity::setValue()
	 *
	 * @since 0.4
	 *
	 * @param Entity $entity
	 * @param string $languageCode
	 * @param string $value
	 *
	 * @return Summary
	 */
	protected function setValue( $entity, $languageCode, $value ) {
		$value = $value === '' ? null : $value;
		$summary = $this->getSummary( 'wbsetdescription' );

		if ( $value === null ) {
			$changeOp = $this->termChangeOpFactory->newRemoveDescriptionOp( $languageCode );
		} else {
			$changeOp = $this->termChangeOpFactory->newSetDescriptionOp( $languageCode, $value );
		}

		$this->applyChangeOp( $changeOp, $entity, $summary );

		return $summary;
	}

}
