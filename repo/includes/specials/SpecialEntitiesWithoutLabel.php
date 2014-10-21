<?php

namespace Wikibase\Repo\Specials;

use Wikibase\Term;

/**
 * Page for listing entities without label.
 *
 * @since 0.2
 * @licence GNU GPL v2+
 * @author Bene*
 */
class SpecialEntitiesWithoutLabel extends SpecialEntitiesWithoutPage {

	public function __construct() {
		parent::__construct( 'EntitiesWithoutLabel' );
	}

	/**
	 * @see SpecialEntitiesWithoutPage::getTermType
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	protected function getTermType() {
		return Term::TYPE_LABEL;
	}

	/**
	 * @see SpecialEntitiesWithoutPage::getLegend
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	protected function getLegend() {
		return $this->msg( 'wikibase-entitieswithoutlabel-legend' )->text();
	}

}
