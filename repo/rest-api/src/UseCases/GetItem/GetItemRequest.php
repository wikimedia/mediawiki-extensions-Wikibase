<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItem;

/**
 * @license GPL-2.0-or-later
 */
class GetItemRequest {

	public const VALID_FIELDS = [ 'type', 'labels', 'descriptions', 'aliases', 'statements', 'sitelinks' ];
	private $itemId;

	private $fields;

	public function __construct( string $itemId, array $fields = self::VALID_FIELDS ) {
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
