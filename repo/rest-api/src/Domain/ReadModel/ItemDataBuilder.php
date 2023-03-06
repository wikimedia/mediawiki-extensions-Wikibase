<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\ReadModel;

use LogicException;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @license GPL-2.0-or-later
 */
class ItemDataBuilder {

	private ItemId $id;
	private ?string $type = null;
	private ?Labels $labels = null;
	private ?Descriptions $descriptions = null;
	private ?Aliases $aliases = null;
	private ?StatementList $statements = null;
	private ?SiteLinks $siteLinks = null;
	private array $requestedFields;

	public function __construct( ItemId $id, array $requestedFields ) {
		$this->id = $id;
		$this->requestedFields = $requestedFields;
	}

	public function setType( string $type ): self {
		$this->checkRequested( ItemData::FIELD_TYPE );
		$this->type = $type;

		return $this;
	}

	public function setLabels( Labels $labels ): self {
		$this->checkRequested( ItemData::FIELD_LABELS );
		$this->labels = $labels;

		return $this;
	}

	public function setDescriptions( Descriptions $descriptions ): self {
		$this->checkRequested( ItemData::FIELD_DESCRIPTIONS );
		$this->descriptions = $descriptions;

		return $this;
	}

	public function setAliases( Aliases $aliases ): self {
		$this->checkRequested( ItemData::FIELD_ALIASES );
		$this->aliases = $aliases;

		return $this;
	}

	public function setStatements( StatementList $statements ): self {
		$this->checkRequested( ItemData::FIELD_STATEMENTS );
		$this->statements = $statements;

		return $this;
	}

	public function setSiteLinks( SiteLinks $siteLinks ): self {
		$this->checkRequested( ItemData::FIELD_SITELINKS );
		$this->siteLinks = $siteLinks;

		return $this;
	}

	public function build(): ItemData {
		return new ItemData(
			$this->id,
			$this->requestedFields,
			$this->type,
			$this->labels,
			$this->descriptions,
			$this->aliases,
			$this->statements,
			$this->siteLinks
		);
	}

	private function checkRequested( string $field ): void {
		if ( !in_array( $field, $this->requestedFields ) ) {
			throw new LogicException( "cannot set unrequested ItemData field '$field'" );
		}
	}

}
