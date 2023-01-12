<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItem;

use Wikibase\Repo\RestApi\Domain\ReadModel\ItemData;

/**
 * @license GPL-2.0-or-later
 */
class GetItemRequest {

	private string $itemId;
	private array $fields;

	public function __construct( string $itemId, array $fields = ItemData::VALID_FIELDS ) {
		$this->itemId = $itemId;
		$this->fields = $fields;
	}

	public function getItemId(): string {
		return $this->itemId;
	}

	public function getFields(): array {
		return $this->fields;
	}

}
