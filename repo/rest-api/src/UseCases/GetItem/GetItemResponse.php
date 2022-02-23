<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItem;

/**
 * @license GPL-2.0-or-later
 */
class GetItemResponse {

	private $itemSerialization;

	public function __construct( array $itemSerialization ) {
		$this->itemSerialization = $itemSerialization;
	}

	public function getItem(): array {
		return $this->itemSerialization;
	}
}
