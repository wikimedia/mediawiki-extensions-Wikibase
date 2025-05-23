<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetItem;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\ItemFieldsRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\ItemIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\UseCaseRequest;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\ItemParts;

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
