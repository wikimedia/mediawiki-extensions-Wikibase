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
class WikiPagePropertyOrderProvider implements PropertyOrderProvider {

	/**
	 * @var Title
	 */
	private $pageTitle;

	/**
	 * Constructor of the WikiPageOrderProvider
	 * @param Title $pageTitle page name the ordered property list is on
	 */
	public function __construct( Title $pageTitle ) {
		$this->pageTitle = $pageTitle;
	}

	/**
	 * @see parent::getPropertyOrder()
	 * @return null|int[] null if page doesn't exist
	 * @throws PropertyOrderProviderException
	 */
	public function getPropertyOrder() {
		$pageContent = $this->getOrderedPropertiesPageContent();
		if ( $pageContent === null ) {
			return null;
		}
		$parsedList = $this->parseList( $pageContent );
		return array_flip( $parsedList );
	}

	/**
	 * Get Content of MediaWiki:Wikibase-SortedProperties
	 * @return string|null
	 * @throws PropertyOrderProviderException
	 */
	private function getOrderedPropertiesPageContent() {
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

	/**
	 * @param string $pageContent
	 * @return string[]
	 */
	private function parseList( $pageContent ) {
		$pageContent = preg_replace( '@<!--.*?-->@s', '', $pageContent );

		preg_match_all(
			'@^\*\s*(?:\[\[Property:)?(P\d+)@im',
			$pageContent,
			$orderedPropertiesMatches,
			PREG_PATTERN_ORDER
		);
		$orderedProperties = array_map( 'strtoupper', $orderedPropertiesMatches[1] );

		return $orderedProperties;
	}

}
