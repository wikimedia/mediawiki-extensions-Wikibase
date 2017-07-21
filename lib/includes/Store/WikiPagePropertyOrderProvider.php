<?php

namespace Wikibase\Lib\Store;

use WikiPage;
use Title;
use TextContent;

/**
 * Provides a list of ordered Property numbers
 *
 * @license GNU GPL v2+
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
		if ( !$this->pageTitle ) {
			throw new PropertyOrderProviderException( 'Not able to get a title' );
		}

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
