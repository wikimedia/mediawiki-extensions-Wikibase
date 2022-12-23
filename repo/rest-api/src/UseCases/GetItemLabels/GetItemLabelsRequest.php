<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemLabels;

/**
 * @license GPL-2.0-or-later
 */
class GetItemLabelsRequest {

	private string $itemId;

	public function __construct( string $itemId ) {
		$this->itemId = $itemId;
	}

	public function getItemId(): string {
		return $this->itemId;
	}

}
