<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\AddPropertyStatement;

use Wikibase\Repo\RestApi\Domain\ReadModel\Statement;

/**
 * @license GPL-2.0-or-later
 */
class AddPropertyStatementResponse {

	private Statement $statement;

	public function __construct( Statement $statement ) {
		$this->statement = $statement;
	}

	public function getStatement(): Statement {
		return $this->statement;
	}

}
