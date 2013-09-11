<?php

namespace Wikibase\Repo\Specials;

/**
 * Page for listing entities without description.
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Bene*
 */
class SpecialEntitiesWithoutDescription extends SpecialEntitiesWithoutPage {

	public function __construct() {
		parent::__construct( 'EntitiesWithoutDescription' );
	}

	/**
	 * @see SpecialEntitiesWithoutPage::getTermType
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	protected function getTermType() {
		return \Wikibase\Term::TYPE_DESCRIPTION;
	}

	/**
	 * @see SpecialEntitiesWithoutPage::getLegend
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	protected function getLegend() {
		return $this->msg( 'wikibase-entitieswithoutdescription-legend' )->text();
	}
}
