<?php

namespace Wikibase\Repo\Specials;

use Wikibase\ChangeOpLabel;
use Wikibase\EntityContent;
use Wikibase\Summary;

/**
 * Special page for setting the label of a Wikibase entity.
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@googlemail.com >
 */

class SpecialSetLabel extends SpecialSetEntity {

	/**
	 * Constructor
	 *
	 * @since 0.4
	 */
	public function __construct() {
		parent::__construct( 'SetLabel', 'label-update' );
	}

	/**
	 * @see SpecialSetEntity::getPostedValue()
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	protected function getPostedValue() {
		return $this->getRequest()->getVal( 'label' );
	}

	/**
	 * @see SpecialSetEntity::getValue()
	 *
	 * @since 0.4
	 *
	 * @param EntityContent $entityContent
	 * @param string $language
	 *
	 * @return string
	 */
	protected function getValue( $entityContent, $language ) {
		return $entityContent === null ? '' : $entityContent->getEntity()->getLabel( $language );
	}

	/**
	 * @see SpecialSetEntity::setValue()
	 *
	 * @since 0.4
	 *
	 * @param EntityContent $entityContent
	 * @param string $language
	 * @param string $value
	 *
	 * @return Summary
	 */
	protected function setValue( $entityContent, $language, $value ) {
		$value = $value === '' ? null : $value;
		$summary = $this->getSummary( 'wbsetlabel' );
		$changeOp = new ChangeOpLabel( $language, $value );
		$changeOp->apply( $entityContent->getEntity(), $summary );

		return $summary;
	}
}