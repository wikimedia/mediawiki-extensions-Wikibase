<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItem;

use Wikibase\Repo\RestApi\Application\UseCases\ItemFieldsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseRequest;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemParts;

/**
 * @license GPL-2.0-or-later
 */
class GetItemRequest implements UseCaseRequest, ItemIdRequest, ItemFieldsRequest {

	private string $itemId;
	private array $fields;

	public function __construct( string $itemId, array $fields = ItemParts::VALID_FIELDS ) {
		$this->itemId = $itemId;
		$this->fields = $fields;
	}

	public function getItemId(): string {
		return $this->itemId;
	}

	public function getItemFields(): array {
		return $this->fields;
	}

}
