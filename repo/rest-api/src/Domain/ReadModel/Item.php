<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\ReadModel;

/**
 * @license GPL-2.0-or-later
 */
class Item {

	private StatementList $statements;

	public function __construct( StatementList $statements ) {
		$this->statements = $statements;
	}

	public function getStatements(): StatementList {
		return $this->statements;
	}

}
