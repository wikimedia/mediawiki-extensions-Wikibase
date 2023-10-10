<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\ReadModel;

/**
 * @license GPL-2.0-or-later
 */
class Property {

	private StatementList $statements;
	private Aliases $aliases;

	public function __construct( Aliases $aliases, StatementList $statements ) {
		$this->aliases = $aliases;
		$this->statements = $statements;
	}

	public function getAliases(): Aliases {
		return $this->aliases;
	}

	public function getStatements(): StatementList {
		return $this->statements;
	}

}
