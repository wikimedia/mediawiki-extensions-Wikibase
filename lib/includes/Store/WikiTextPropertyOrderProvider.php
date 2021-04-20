<?php

namespace Wikibase\Lib\Store;

/**
 * Base class for PropertyOrderProviders, that parse the property order from a
 * wikitext page.
 *
 * @license GPL-2.0-or-later
 * @author Lucie-Aimée Kaffee
 * @author Marius Hoch
 */
abstract class WikiTextPropertyOrderProvider implements PropertyOrderProvider {

	/**
	 * @see parent::getPropertyOrder()
	 * @return null|int[] null if page doesn't exist
	 * @throws PropertyOrderProviderException
	 */
	public function getPropertyOrder() {
		$pageContent = $this->getPropertyOrderWikitext();
		if ( $pageContent === null ) {
			return null;
		}
		$parsedList = $this->parseList( $pageContent );

		return array_flip( $parsedList );
	}

	/**
	 * Get the wikitext of the property order list.
	 *
	 * @return string|null
	 * @throws PropertyOrderProviderException
	 */
	abstract protected function getPropertyOrderWikitext();

	/**
	 * @param string $pageContent
	 *
	 * @return string[]
	 */
	private function parseList( $pageContent ) {
		$pageContent = preg_replace( '@<!--.*?-->@s', '', $pageContent );

		preg_match_all(
			'@^[*#]+\h*(?:\[\[(?:d:)?Property:)?(?:{{[a-z]+\|)?(P\d+\b)@im',
			$pageContent,
			$orderedPropertiesMatches,
			PREG_PATTERN_ORDER
		);
		$orderedProperties = array_map( 'strtoupper', $orderedPropertiesMatches[1] );

		return $orderedProperties;
	}

}
