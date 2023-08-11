<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\AddPropertyStatement;

use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\RestApi\Application\Validation\StatementValidator;

/**
 * @license GPL-2.0-or-later
 */
class AddPropertyStatementValidator {

	private StatementValidator $statementValidator;

	public function __construct( StatementValidator $statementDeserializer ) {
		$this->statementValidator = $statementDeserializer;
	}

	public function assertValidRequest( AddPropertyStatementRequest $request ): void {
		// will be implemented and tested in T343804
		$this->statementValidator->validate( $request->getStatement() );
	}

	public function getValidatedStatement(): Statement {
		return $this->statementValidator->getValidatedStatement();
	}

}
