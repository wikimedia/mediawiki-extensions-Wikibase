<?php

namespace Wikibase\Repo\Specials;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Repo\WikibaseRepo;

/**
 * Special page for listing Items without sitelinks.
 *
 * @since 0.4
 *
 * @license GPL-2.0+
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
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		$this->showQuery();
	}

	/**
	 * @see SpecialWikibaseQueryPage::getResult
	 *
	 * @since 0.4
	 *
	 * @param int $offset
	 * @param int $limit
	 *
	 * @return EntityId[]
	 */
	protected function getResult( $offset = 0, $limit = 0 ) {
		$itemsWithoutSitelinks = WikibaseRepo::getDefaultInstance()->getStore()->newItemsWithoutSitelinksFinder();
		return $itemsWithoutSitelinks->getItemsWithoutSitelinks( $limit, $offset );
	}

}
