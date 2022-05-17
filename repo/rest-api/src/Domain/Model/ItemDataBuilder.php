<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Model;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0-or-later
 */
class ItemDataBuilder {

	private $id;
	private $type = null;
	private $labels = null;
	private $descriptions = null;
	private $aliases = null;
	private $statements = null;
	private $siteLinks = null;

	public function setId( ItemId $id ): self {
		$this->id = $id;
		return $this;
	}

	public function setType( string $type ): self {
		$this->type = $type;
		return $this;
	}

	public function setLabels( TermList $labels ): self {
		$this->labels = $labels;
		return $this;
	}

	public function setDescriptions( TermList $descriptions ): self {
		$this->descriptions = $descriptions;
		return $this;
	}

	public function setAliases( AliasGroupList $aliases ): self {
		$this->aliases = $aliases;
		return $this;
	}

	public function setStatements( StatementList $statements ): self {
		$this->statements = $statements;
		return $this;
	}

	public function setSiteLinks( SiteLinkList $siteLinks ): self {
		$this->siteLinks = $siteLinks;
		return $this;
	}

	public function build(): ItemData {
		return new ItemData(
			$this->id,
			$this->type,
			$this->labels,
			$this->descriptions,
			$this->aliases,
			$this->statements,
			$this->siteLinks
		);
	}

}
