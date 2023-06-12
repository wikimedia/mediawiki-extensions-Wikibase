<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\ReadModel;

use Wikibase\DataModel\Entity\Property as DataModelProperty;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * @license GPL-2.0-or-later
 */
class PropertyParts {
	public const TYPE = DataModelProperty::ENTITY_TYPE;
	public const FIELD_ID = 'id';
	public const FIELD_TYPE = 'type';
	public const FIELD_DATA_TYPE = 'data-type';
	public const FIELD_LABELS = 'labels';
	public const FIELD_DESCRIPTIONS = 'descriptions';
	public const FIELD_ALIASES = 'aliases';
	public const FIELD_STATEMENTS = 'statements';

	public const VALID_FIELDS = [
		self::FIELD_TYPE,
		self::FIELD_DATA_TYPE,
		self::FIELD_LABELS,
		self::FIELD_DESCRIPTIONS,
		self::FIELD_ALIASES,
		self::FIELD_STATEMENTS,
	];

	private PropertyId $id;
	private array $requestedFields;
	private ?string $dataType;
	private ?Labels $labels;
	private ?Descriptions $descriptions;
	private ?Aliases $aliases;
	private ?StatementList $statements;

	public function __construct(
		PropertyId $id,
		array $requestedFields,
		?string $dataType,
		?Labels $labels,
		?Descriptions $descriptions,
		?Aliases $aliases,
		?StatementList $statements
	) {
		$this->id = $id;
		$this->requestedFields = $requestedFields;
		$this->dataType = $dataType;
		$this->labels = $labels;
		$this->descriptions = $descriptions;
		$this->aliases = $aliases;
		$this->statements = $statements;
	}

	public function getId(): PropertyId {
		return $this->id;
	}

	public function getDataType(): ?string {
		return $this->dataType;
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

	public function isRequested( string $field ): bool {
		return in_array( $field, $this->requestedFields );
	}
}
