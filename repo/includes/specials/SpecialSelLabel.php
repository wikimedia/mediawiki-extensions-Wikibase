<?php

/**
 * Special page for setting the label of a Wikibase entity.
 *
 * @since 0.2
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Denny Vrandecic < denny@vrandecic.de >
 */
class SpecialSetEntity extends SpecialWikibasePage {

	/**
	 * @var string
	 */
	protected $label = null;

	/**
	 * @var string
	 */
	protected $langCode = null;

	/**
	 * Constructor.
	 *
	 * @since 0.2
	 */
	public function __construct() {
		parent::__construct( 'SetLabel' );
	}

	/**
	 * @see SpecialCreateEntity::prepareArguments()
	 */
	protected function prepareArguments() {
		parent::prepareArguments();
		$this->langCode = $this->getRequest()->getVal( 'language', isset( $this->parts[1] ) ? $this->parts[1] : '' );
		$this->label = $this->getRequest()->getVal( 'label', isset( $this->parts[2] ) ? $this->parts[2] : '' );
		return true;
		
		return true;
	}

}