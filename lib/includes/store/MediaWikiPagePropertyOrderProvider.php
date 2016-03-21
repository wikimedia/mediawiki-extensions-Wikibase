<?php

namespace Wikibase\Lib\Store;

use Wikibase\Lib\Store\PropertyOrderProviderException;
use WikiPage;
use Title;
use TextContent;

/**
 * Provides a list of ordered Property numbers
 *
 * @license GNU GPL v2+
 * @author Lucie-Aimée Kaffee
 */
class MediaWikiPagePropertyOrderProvider {

	const PAGENAME = 'MediaWiki:Wikibase-SortedProperties';

	/**
	 * Get order of properties in the form [PropertyId] -> [Ordinal number]
	 * @return null|int[] null if page doesn't exist
	 * @throws PropertyOrderProviderException
	 */
	public function getPropertyOrder() {
		$pageContent = $this->getSortedPropertiesPageContent();
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
	private function getSortedPropertiesPageContent() {
		$title = Title::newFromText( self::PAGENAME );
		if ( !$title ) {
			throw new PropertyOrderProviderException( 'Not able to get a title' );
		}
		$wikiPage = WikiPage::factory( $title );
		if ( !$wikiPage ) {
			throw new PropertyOrderProviderException( 'Not able to get the Wikipage' );
		}
		$pageContent = $wikiPage->getContent();
		if ( $pageContent === null ) {
			return null;
		}
		if ( !( $pageContent instanceof TextContent ) ) {
			throw new PropertyOrderProviderException( 'The page content is not TextContent' );
		}

		return $pageContent->getNativeData();
	}

	/**
	 * @param string $pageContent
	 * @return string[]
	 */
	private function parseList( $pageContent ) {
		$orderedProperties = [];
		$orderedPropertiesMatches = [];

		$pageContent = preg_replace( '@<!--.*?-->@s', '', $pageContent );
		preg_match_all(
			'@^\*\s*([Pp]\d+)@m',
			$pageContent,
			$orderedPropertiesMatches,
			PREG_PATTERN_ORDER
		);
		$orderedProperties = array_map( 'strtoupper', $orderedPropertiesMatches[1] );

		return $orderedProperties;
	}

}
