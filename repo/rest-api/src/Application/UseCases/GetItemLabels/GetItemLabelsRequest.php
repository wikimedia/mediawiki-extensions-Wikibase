<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemLabels;

use Wikibase\Repo\RestApi\Application\UseCases\ItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseRequest;

/**
 * @license GPL-2.0-or-later
 */
class GetItemLabelsRequest implements UseCaseRequest, ItemIdRequest {

	private string $itemId;

	public function __construct( string $itemId ) {
		$this->itemId = $itemId;
	}

	public function getItemId(): string {
		return $this->itemId;
	}

}
