<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Serializers;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Serializers\ItemSerializer as LegacyItemSerializer;

/**
 * @license GPL-2.0-or-later
 */
class ItemSerializer {

	private $legacyItemSerializer;

	public function __construct( LegacyItemSerializer $legacyItemSerializer ) {
		$this->legacyItemSerializer = $legacyItemSerializer;
	}

	public function serialize( Item $item ): array {
		$serialization = $this->legacyItemSerializer->serialize( $item );

		$serialization['statements'] = $serialization['claims'];
		unset( $serialization['claims'] );

		return $serialization;
	}
}
