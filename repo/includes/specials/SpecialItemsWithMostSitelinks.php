<?php

namespace Wikibase\Repo\Specials;

use Wikibase\Lib\Specials\SpecialWikibaseQueryPage;
use Wikibase\StoreFactory;

/**
 * Page for listing items with most sitelinks.
 *
 * @since 0.5
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SpecialItemsWithMostSitelinks extends SpecialWikibaseQueryPage {

	public function __construct() {
		parent::__construct( 'ItemsWithMostSitelinks' );
	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @since 0.4
	 *
	 * @param string $subPage
	 * @return boolean
	 */
	public function execute( $subPage ) {
		if ( !parent::execute( $subPage ) ) {
			return false;
		}

		$this->showQuery();
	}

	/**
	 * @see SpecialWikibaseQueryPage::getResult
	 *
	 * @since 0.4
	 */
	protected function getResult( $offset = 0, $limit = 0 ) {
		$entityPerPage = StoreFactory::getStore( 'sqlstore' )->newEntityPerPage();
		return $entityPerPage->getItemsWithMostSitelinks( null, $limit, $offset );
	}

}
