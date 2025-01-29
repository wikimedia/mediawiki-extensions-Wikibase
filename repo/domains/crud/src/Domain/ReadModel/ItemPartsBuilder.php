<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\ReadModel;

use LogicException;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @license GPL-2.0-or-later
 */
class ItemPartsBuilder {

	private ItemId $id;
	private ?Labels $labels = null;
	private ?Descriptions $descriptions = null;
	private ?Aliases $aliases = null;
	private ?StatementList $statements = null;
	private ?Sitelinks $sitelinks = null;
	private array $requestedFields;

	public function __construct( ItemId $id, array $requestedFields ) {
		$this->id = $id;
		$this->requestedFields = $requestedFields;
	}

	public function setLabels( Labels $labels ): self {
		$this->checkRequested( ItemParts::FIELD_LABELS );
		$this->labels = $labels;

		return $this;
	}

	public function setDescriptions( Descriptions $descriptions ): self {
		$this->checkRequested( ItemParts::FIELD_DESCRIPTIONS );
		$this->descriptions = $descriptions;

		return $this;
	}

	public function setAliases( Aliases $aliases ): self {
		$this->checkRequested( ItemParts::FIELD_ALIASES );
		$this->aliases = $aliases;

		return $this;
	}

	public function setStatements( StatementList $statements ): self {
		$this->checkRequested( ItemParts::FIELD_STATEMENTS );
		$this->statements = $statements;

		return $this;
	}

	public function setSitelinks( Sitelinks $sitelinks ): self {
		$this->checkRequested( ItemParts::FIELD_SITELINKS );
		$this->sitelinks = $sitelinks;

		return $this;
	}

	public function build(): ItemParts {
		return new ItemParts(
			$this->id,
			$this->requestedFields,
			$this->labels,
			$this->descriptions,
			$this->aliases,
			$this->statements,
			$this->sitelinks
		);
	}

	private function checkRequested( string $field ): void {
		if ( !in_array( $field, $this->requestedFields ) ) {
			throw new LogicException( 'cannot set unrequested ' . ItemParts::class . " field '$field'" );
		}
	}

}
