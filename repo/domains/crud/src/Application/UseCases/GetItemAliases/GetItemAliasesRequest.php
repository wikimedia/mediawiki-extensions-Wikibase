<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemAliases;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\UseCaseRequest;

/**
 * @license GPL-2.0-or-later
 */
class GetItemAliasesRequest implements UseCaseRequest, ItemIdRequest {

	private string $itemId;

	public function __construct( string $itemId ) {
		$this->itemId = $itemId;
	}

	public function getItemId(): string {
		return $this->itemId;
	}

}
