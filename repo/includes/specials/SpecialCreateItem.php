<?php

/**
 * Page for creating new Wikibase items.
 *
 * @since 0.1
 *
 * @file 
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 */
class SpecialCreateItem extends SpecialCreateEntity {

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 */
	public function __construct() {
		parent::__construct( 'CreateItem' );
	}

	/**
	 * @see SpecialCreateEntity::createEntity()
	 */
	protected function createEntity() {
		return \Wikibase\ItemContent::newEmpty();
	}

	/**
	 * @see SpecialCreateEntity::getLegend()
	 */
	protected function getLegend() {
		return $this->msg( 'wikibase-createitem-fieldset' );
	}

}
