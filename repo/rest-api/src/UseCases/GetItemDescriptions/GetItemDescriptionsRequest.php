<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemDescriptions;

/**
 * @license GPL-2.0-or-later
 */
class GetItemDescriptionsRequest {

	private string $itemId;

	public function __construct( string $itemId ) {
		$this->itemId = $itemId;
	}

	public function getItemId(): string {
		return $this->itemId;
	}

}
