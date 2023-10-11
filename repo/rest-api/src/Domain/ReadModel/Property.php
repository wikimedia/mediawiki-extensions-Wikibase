<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\ReadModel;

/**
 * @license GPL-2.0-or-later
 */
class Property {

	private Descriptions $descriptions;
	private Aliases $aliases;
	private StatementList $statements;

	public function __construct( Descriptions $descriptions, Aliases $aliases, StatementList $statements ) {
		$this->descriptions = $descriptions;
		$this->aliases = $aliases;
		$this->statements = $statements;
	}

	public function getDescriptions(): Descriptions {
		return $this->descriptions;
	}

	public function getAliases(): Aliases {
		return $this->aliases;
	}

	public function getStatements(): StatementList {
		return $this->statements;
	}

}
