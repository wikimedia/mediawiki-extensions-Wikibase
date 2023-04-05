<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\ReadModel;

/**
 * @license GPL-2.0-or-later
 */
class Item {

	private Labels $labels;
	private StatementList $statements;

	public function __construct( Labels $labels, StatementList $statements ) {
		$this->labels = $labels;
		$this->statements = $statements;
	}

	public function getLabels(): Labels {
		return $this->labels;
	}

	public function getStatements(): StatementList {
		return $this->statements;
	}

}
