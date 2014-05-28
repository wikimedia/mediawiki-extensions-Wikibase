<?php

namespace Wikibase\Repo\Specials;

use Wikibase\Lib\Specials\SpecialWikibaseQueryPage;
use Wikibase\Repo\WikibaseRepo;

/**
 * Page for listing entities without label.
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class SpecialItemsWithoutSitelinks extends SpecialWikibaseQueryPage {

	public function __construct() {
		parent::__construct( 'ItemsWithoutSitelinks' );
	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @since 0.4
	 *
	 * @param string $subPage
	 * @return bool
	 */
	public function execute( $subPage ) {
		if ( !parent::execute( $subPage ) ) {
			return false;
		}

		$this->showQuery();
		return true;
	}

	/**
	 * @see SpecialWikibaseQueryPage::getResult
	 *
	 * @since 0.4
	 */
	protected function getResult( $offset = 0, $limit = 0 ) {
		$entityPerPage = WikibaseRepo::getDefaultInstance()->getStore()->newEntityPerPage();
		return $entityPerPage->getItemsWithoutSitelinks( null, $limit, $offset );
	}

}
