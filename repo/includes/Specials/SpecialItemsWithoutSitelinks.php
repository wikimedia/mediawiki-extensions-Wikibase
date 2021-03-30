<?php

namespace Wikibase\Repo\Specials;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Repo\WikibaseRepo;

/**
 * Special page for listing Items without sitelinks.
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class SpecialItemsWithoutSitelinks extends SpecialWikibaseQueryPage {

	public function __construct() {
		parent::__construct( 'ItemsWithoutSitelinks' );
	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		$this->showQuery();
	}

	/**
	 * @see SpecialWikibaseQueryPage::getResult
	 *
	 * @param int $offset
	 * @param int $limit
	 *
	 * @return EntityId[]
	 */
	protected function getResult( $offset = 0, $limit = 0 ) {
		// TODO inject ItemsWithoutSitelinksFinder
		$itemsWithoutSitelinks = WikibaseRepo::getStore()->newItemsWithoutSitelinksFinder();
		return $itemsWithoutSitelinks->getItemsWithoutSitelinks( $limit, $offset );
	}

}
