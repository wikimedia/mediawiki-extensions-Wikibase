<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\ReadModel;

use LogicException;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * @license GPL-2.0-or-later
 */
class PropertyPartsBuilder {

	private PropertyId $id;
	private array $requestedFields;
	private ?string $dataType = null;
	private ?Labels $labels = null;
	private ?Descriptions $descriptions = null;
	private ?Aliases $aliases = null;
	private ?StatementList $statements = null;

	public function __construct( PropertyId $id, array $requestedFields ) {
		$this->id = $id;
		$this->requestedFields = $requestedFields;
	}

	public function setDataType( string $dataType ): self {
		$this->checkRequested( PropertyParts::FIELD_DATA_TYPE );
		$this->dataType = $dataType;

		return $this;
	}

	public function setLabels( Labels $labels ): self {
		$this->checkRequested( PropertyParts::FIELD_LABELS );
		$this->labels = $labels;

		return $this;
	}

	public function setDescriptions( Descriptions $descriptions ): self {
		$this->checkRequested( PropertyParts::FIELD_DESCRIPTIONS );
		$this->descriptions = $descriptions;

		return $this;
	}

	public function setAliases( Aliases $aliases ): self {
		$this->checkRequested( PropertyParts::FIELD_ALIASES );
		$this->aliases = $aliases;

		return $this;
	}

	public function setStatements( StatementList $statements ): self {
		$this->checkRequested( PropertyParts::FIELD_STATEMENTS );
		$this->statements = $statements;

		return $this;
	}

	public function build(): PropertyParts {
		return new PropertyParts(
			$this->id,
			$this->requestedFields,
			$this->dataType,
			$this->labels,
			$this->descriptions,
			$this->aliases,
			$this->statements
		);
	}

	private function checkRequested( string $field ): void {
		if ( !in_array( $field, $this->requestedFields ) ) {
			throw new LogicException( 'cannot set unrequested ' . PropertyParts::class . " field '$field'" );
		}
	}

}
