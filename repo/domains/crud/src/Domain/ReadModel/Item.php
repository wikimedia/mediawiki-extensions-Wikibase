<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\ReadModel;

use Wikibase\DataModel\Entity\ItemId;

/**
 * @license GPL-2.0-or-later
 */
class Item {

	private ItemId $id;
	private Labels $labels;
	private Descriptions $descriptions;
	private Aliases $aliases;
	private Sitelinks $sitelinks;
	private StatementList $statements;

	public function __construct(
		ItemId $id,
		Labels $labels,
		Descriptions $descriptions,
		Aliases $aliases,
		Sitelinks $sitelinks,
		StatementList $statements
	) {
		$this->id = $id;
		$this->labels = $labels;
		$this->descriptions = $descriptions;
		$this->aliases = $aliases;
		$this->sitelinks = $sitelinks;
		$this->statements = $statements;
	}

	public function getId(): ItemId {
		return $this->id;
	}

	public function getLabels(): Labels {
		return $this->labels;
	}

	public function getDescriptions(): Descriptions {
		return $this->descriptions;
	}

	public function getAliases(): Aliases {
		return $this->aliases;
	}

	public function getSitelinks(): Sitelinks {
		return $this->sitelinks;
	}

	public function getStatements(): StatementList {
		return $this->statements;
	}

}
