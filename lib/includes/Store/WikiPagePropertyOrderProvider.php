<?php

namespace Wikibase\Lib\Store;

use MediaWiki\Page\WikiPageFactory;
use TextContent;
use Title;

/**
 * Provides a list of ordered Property numbers
 *
 * @license GPL-2.0-or-later
 * @author Lucie-Aimée Kaffee
 */
class WikiPagePropertyOrderProvider extends WikiTextPropertyOrderProvider implements PropertyOrderProvider {

	/** @var WikiPageFactory */
	private $wikiPageFactory;

	/**
	 * @var Title
	 */
	private $pageTitle;

	/**
	 * @param WikiPageFactory $wikiPageFactory
	 * @param Title $pageTitle page name the ordered property list is on
	 */
	public function __construct(
		WikiPageFactory $wikiPageFactory,
		Title $pageTitle
	) {
		$this->wikiPageFactory = $wikiPageFactory;
		$this->pageTitle = $pageTitle;
	}

	/**
	 * Get Content of MediaWiki:Wikibase-SortedProperties
	 *
	 * @return string|null
	 * @throws PropertyOrderProviderException
	 */
	protected function getPropertyOrderWikitext() {
		$wikiPage = $this->wikiPageFactory->newFromTitle( $this->pageTitle );

		$pageContent = $wikiPage->getContent();

		if ( $pageContent === null ) {
			return null;
		}

		if ( !( $pageContent instanceof TextContent ) ) {
			throw new PropertyOrderProviderException( 'The page content of ' . $this->pageTitle->getText() . ' is not TextContent' );
		}

		return strval( $pageContent->getText() );
	}

}
