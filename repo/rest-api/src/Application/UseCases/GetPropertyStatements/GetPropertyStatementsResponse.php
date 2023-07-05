<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatements;

use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyStatementsResponse {

	private StatementList $statements;

	public function __construct( StatementList $serializedStatements ) {
		$this->statements = $serializedStatements;
	}

	public function getStatements(): StatementList {
		return $this->statements;
	}

}
