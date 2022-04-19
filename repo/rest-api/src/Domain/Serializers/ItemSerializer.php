<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Serializers;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Serializers\ItemSerializer as LegacyItemSerializer;

/**
 * @license GPL-2.0-or-later
 */
class ItemSerializer {

	private $legacyItemSerializer;
	private $mapToTermValue;

	public function __construct( LegacyItemSerializer $legacyItemSerializer ) {
		$this->legacyItemSerializer = $legacyItemSerializer;
		$this->mapToTermValue = function( $term ) {
			return $term['value'];
		};
	}

	public function serialize( Item $item ): array {
		$serialization = $this->legacyItemSerializer->serialize( $item );

		$serialization['labels'] = $this->flattenTerms( $serialization['labels'] );
		$serialization['descriptions'] = $this->flattenTerms( $serialization['descriptions'] );
		$serialization['aliases'] = $this->flattenTermArrays( $serialization['aliases'] );

		$serialization['statements'] = $serialization['claims'];
		unset( $serialization['claims'] );

		return $serialization;
	}

	private function flattenTerms( array $section ): array {
		return array_map( $this->mapToTermValue, $section );
	}

	private function flattenTermArrays( array $section ): array {
		// loop through arrays inside the term arrays
		return array_map( function( $termArray ) {
			return array_map( $this->mapToTermValue, $termArray );
		}, $section );
	}
}
