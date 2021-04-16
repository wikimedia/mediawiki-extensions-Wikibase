<?php

namespace Wikibase\Lib\Store;

use TextContent;
use Title;
use WikiPage;

/**
 * Provides a list of ordered Property numbers
 *
 * @license GPL-2.0-or-later
 * @author Lucie-Aimée Kaffee
 */
class WikiPagePropertyOrderProvider extends WikiTextPropertyOrderProvider implements PropertyOrderProvider {

	/**
	 * @var Title
	 */
	private $pageTitle;

	/**
	 * @param Title $pageTitle page name the ordered property list is on
	 */
	public function __construct( Title $pageTitle ) {
		$this->pageTitle = $pageTitle;
	}

	/**
	 * Get Content of MediaWiki:Wikibase-SortedProperties
	 *
	 * @return string|null
	 * @throws PropertyOrderProviderException
	 */
	protected function getPropertyOrderWikitext() {
		$wikiPage = WikiPage::factory( $this->pageTitle );

		$pageContent = $wikiPage->getContent();

		if ( $pageContent === null ) {
			return null;
		}

		if ( !( $pageContent instanceof TextContent ) ) {
			throw new PropertyOrderProviderException( 'The page content of ' . $this->pageTitle->getText() . ' is not TextContent' );
		}

		return strval( $pageContent->getNativeData() );
	}

}
