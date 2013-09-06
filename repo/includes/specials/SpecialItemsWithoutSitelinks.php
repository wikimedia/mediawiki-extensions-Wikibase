<?php

use Wikibase\StoreFactory;
namespace Wikibase\Repo\Specials;

use Wikibase\Lib\Specials\SpecialWikibaseQueryPage;
use Wikibase\StoreFactory;

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
		return $entityPerPage->getItemsWithoutSitelinks( null, $limit, $offset );
	}

}
