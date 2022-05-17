<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItem;

/**
 * @license GPL-2.0-or-later
 */
class GetItemRequest {
	public const FIELD_TYPE = 'type';
	public const FIELD_LABELS = 'labels';
	public const FIELD_DESCRIPTIONS = 'descriptions';
	public const FIELD_ALIASES = 'aliases';
	public const FIELD_STATEMENTS = 'statements';
	public const FIELD_SITELINKS = 'sitelinks';
	public const VALID_FIELDS = [
		self::FIELD_TYPE,
		self::FIELD_LABELS,
		self::FIELD_DESCRIPTIONS,
		self::FIELD_ALIASES,
		self::FIELD_STATEMENTS,
		self::FIELD_SITELINKS,
	];

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
