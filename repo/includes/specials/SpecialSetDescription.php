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
	 * @param string $language
	 *
	 * @return string
	 */
	protected function getValue( $entity, $language ) {
		return $entity === null ? '' : $entity->getDescription( $language );
	}

	/**
	 * @see SpecialSetEntity::setValue()
	 *
	 * @since 0.4
	 *
	 * @param Entity $entity
	 * @param string $language
	 * @param string $value
	 *
	 * @return Summary
	 */
	protected function setValue( $entity, $language, $value ) {
		$value = $value === '' ? null : $value;
		$summary = $this->getSummary( 'wbsetdescription' );

		if ( $value === null ) {
			$changeOp = $this->termChangeOpFactory->newRemoveDescriptionOp( $language );
		} else {
			$changeOp = $this->termChangeOpFactory->newSetDescriptionOp( $language, $value );
		}

		$this->applyChangeOp( $changeOp, $entity, $summary );

		return $summary;
	}

}
