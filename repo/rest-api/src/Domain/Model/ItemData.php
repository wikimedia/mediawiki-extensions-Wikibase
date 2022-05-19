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
class ItemData {
	private $id;
	private $type;
	private $labels;
	private $descriptions;
	private $aliases;
	private $statements;
	private $siteLinks;

	public function __construct(
		ItemId $id,
		?string $type,
		?TermList $labels,
		?TermList $descriptions,
		?AliasGroupList $aliases,
		?StatementList $statements,
		?SiteLinkList $siteLinks
	) {
		$this->id = $id;
		$this->type = $type;
		$this->labels = $labels;
		$this->descriptions = $descriptions;
		$this->aliases = $aliases;
		$this->statements = $statements;
		$this->siteLinks = $siteLinks;
	}

	public function getId(): ItemId {
		return $this->id;
	}

	public function getType(): ?string {
		return $this->type;
	}

	public function getLabels(): ?TermList {
		return $this->labels;
	}

	public function getDescriptions(): ?TermList {
		return $this->descriptions;
	}

	public function getAliases(): ?AliasGroupList {
		return $this->aliases;
	}

	public function getStatements(): ?StatementList {
		return $this->statements;
	}

	public function getSiteLinks(): ?SiteLinkList {
		return $this->siteLinks;
	}

}
