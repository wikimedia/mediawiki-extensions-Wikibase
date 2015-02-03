<?php

namespace Wikibase\Repo;

use Wikibase\DataModel\Entity\Item;

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

		foreach ( $item->getSiteLinks() as $siteLink ) {
			$text .= "\n" . $siteLink->getPageName();
		}

		return trim( $text );
	}

}
