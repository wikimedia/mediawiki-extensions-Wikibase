<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\ReadModel;

use Wikibase\DataModel\Entity\Item as ItemWriteModel;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @license GPL-2.0-or-later
 */
class ItemParts {
	public const TYPE = ItemWriteModel::ENTITY_TYPE;

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

	private ItemId $id;
	private array $requestedFields;
	private ?Labels $labels;
	private ?Descriptions $descriptions;
	private ?Aliases $aliases;
	private ?StatementList $statements;
	private ?Sitelinks $sitelinks;

	public function __construct(
		ItemId $id,
		array $requestedFields,
		?Labels $labels,
		?Descriptions $descriptions,
		?Aliases $aliases,
		?StatementList $statements,
		?Sitelinks $sitelinks
	) {
		$this->id = $id;
		$this->requestedFields = $requestedFields;
		$this->labels = $labels;
		$this->descriptions = $descriptions;
		$this->aliases = $aliases;
		$this->statements = $statements;
		$this->sitelinks = $sitelinks;
	}

	public function getId(): ItemId {
		return $this->id;
	}

	public function getLabels(): ?Labels {
		return $this->labels;
	}

	public function getDescriptions(): ?Descriptions {
		return $this->descriptions;
	}

	public function getAliases(): ?Aliases {
		return $this->aliases;
	}

	public function getStatements(): ?StatementList {
		return $this->statements;
	}

	public function getSitelinks(): ?Sitelinks {
		return $this->sitelinks;
	}

	public function isRequested( string $field ): bool {
		return in_array( $field, $this->requestedFields );
	}
}
