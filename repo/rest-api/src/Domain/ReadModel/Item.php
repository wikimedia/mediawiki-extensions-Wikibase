<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\ReadModel;

/**
 * @license GPL-2.0-or-later
 */
class Item {

	private Labels $labels;
	private StatementList $statements;
	private Descriptions $descriptions;

	public function __construct( Labels $labels, Descriptions $descriptions, StatementList $statements ) {
		$this->labels = $labels;
		$this->statements = $statements;
		$this->descriptions = $descriptions;
	}

	public function getLabels(): Labels {
		return $this->labels;
	}

	public function getStatements(): StatementList {
		return $this->statements;
	}

	public function getDescriptions(): Descriptions {
		return $this->descriptions;
	}

}
