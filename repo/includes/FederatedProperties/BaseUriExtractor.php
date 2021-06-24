<?php
declare( strict_types=1 );

namespace Wikibase\Repo\FederatedProperties;

use Wikibase\DataModel\Entity\EntityIdParsingException;

/**
 * @license GPL-2.0-or-later
 */
class BaseUriExtractor {

	public function getBaseUriFromSerialization( string $idSerialization ): string {
		$urlPath = parse_url( $idSerialization, PHP_URL_PATH );
		$urlQuery = parse_url( $idSerialization, PHP_URL_QUERY );
		$urlFragment = parse_url( $idSerialization, PHP_URL_FRAGMENT );
		if ( is_string( $urlPath ) && $urlQuery === null && $urlFragment === null ) {
			return $this->removeLastPartOfUrl( $idSerialization );
		} else {
			throw new EntityIdParsingException(
				'Entity serialization is a URI but does not appear to look like a Wikibase Concept URI: ' .
				$idSerialization
			);
		}
	}

	private function removeLastPartOfUrl( string $idSerialization ): string {
		$url = explode(
			'/',
			$idSerialization
		);
		array_pop( $url );
		return implode( '/', $url ) . '/';
	}

}
