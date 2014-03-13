<?php

namespace Wikibase\Repo;

use Wikibase\Item;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ItemSearchTextGenerator {

	/**
	 * @param Item $item
	 *
	 * @return string
	 */
	public function generate( Item $item ) {
		$entitySearchTextGenerator = new EntitySearchTextGenerator();
		$text = $entitySearchTextGenerator->generate( $item );

		$siteLinks = $item->getSiteLinks();
		$text .= $this->getSiteLinksText( $siteLinks );

		return $text;
	}

	/**
	 * @param array $siteLinks
	 *
	 * @return string
	 */
	protected function getSiteLinksText( array $siteLinks ) {
		$pages = array();

		foreach( $siteLinks as $siteLink ) {
			$pages[] = $siteLink->getPageName();
		}

		return "\n" . implode( "\n", $pages );
	}

}
