<?php
declare( strict_types=1 );

namespace Wikibase\Lib\Store;

use TextContent;
use Title;
use WikiPage;

/**
 * Base class for ItemOrderProviders, that parse the item order from a
 * wikitext page.
 *
 * @license GPL-2.0-or-later
 * @author Noa Rave
 */
class WikiPageItemOrderProvider implements ItemOrderProvider {

	/**
	 * @var Title
	 */
	private $pageTitle;

	/**
	 * @param Title $pageTitle page name the ordered item list is on
	 */
	public function __construct( Title $pageTitle ) {
		$this->pageTitle = $pageTitle;
	}

	/**
	 * @see parent::getItemOrder()
	 * @return null|int[] null if page doesn't exist
	 * @throws ItemOrderProviderException
	 */
	public function getItemOrder(): ?array {
		$pageContent = $this->getItemOrderWikitext();
		if ( $pageContent === null ) {
			return null;
		}
		$parsedList = $this->parseList( $pageContent );

		return array_flip( $parsedList );
	}

	/**
	 * Get Content of the wiki page
	 *
	 * @return string|null
	 * @throws ItemOrderProviderException
	 */
	protected function getItemOrderWikitext(): ?string {
		$wikiPage = WikiPage::factory( $this->pageTitle );

		$pageContent = $wikiPage->getContent();

		if ( $pageContent === null ) {
			return null;
		}

		if ( !( $pageContent instanceof TextContent ) ) {
			throw new ItemOrderProviderException(
				'The page content of ' . $this->pageTitle->getText() . ' is not TextContent'
			);
		}

		return strval( $pageContent->getText() );
	}

	/**
	 * @param string $pageContent
	 *
	 * @return string[]
	 */
	private function parseList( string $pageContent ): array {
		$pageContent = preg_replace( '@<!--.*?-->@s', '', $pageContent );

		preg_match_all(
			'@^[*#]+\h*(?:\[\[(?:d:)?(?:Item:)?)?(?:{{[a-z]+\|)?(Q\d+\b)@im',
			$pageContent,
			$orderedItems,
			PREG_PATTERN_ORDER
		);
		$orderedItems = array_map( 'strtoupper', $orderedItems[1] );

		return $orderedItems;
	}
}
