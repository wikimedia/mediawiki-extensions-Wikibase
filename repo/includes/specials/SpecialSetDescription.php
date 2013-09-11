<?php

namespace Wikibase\Repo\Specials;

use Wikibase\ChangeOpDescription;
use Wikibase\EntityContent;
use Wikibase\Summary;

/**
 * Special page for setting the description of a Wikibase entity.
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@googlemail.com >
 */
class SpecialSetDescription extends SpecialSetEntity {

	/**
	 * Constructor
	 *
	 * @since 0.4
	 */
	public function __construct() {
		parent::__construct( 'SetDescription', 'description-update' );
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
	 * @param EntityContent $entityContent
	 * @param string $language
	 *
	 * @return string
	 */
	protected function getValue( $entityContent, $language ) {
		return $entityContent === null ? '' : $entityContent->getEntity()->getDescription( $language );
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
		$summary = $this->getSummary( 'wbsetdescription' );
		$changeOp = new ChangeOpDescription( $language, $value );
		$changeOp->apply( $entityContent->getEntity(), $summary );

		return $summary;
	}
}