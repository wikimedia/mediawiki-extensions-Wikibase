<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\ReplaceItemStatement;

use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\RestApi\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class ReplaceItemStatementValidator {

	public const SOURCE_STATEMENT = 'statement';

	private $statementValidator;

	public function __construct(
		StatementValidator $statementValidator
	) {
		$this->statementValidator = $statementValidator;
	}

	public function validate( ReplaceItemStatementRequest $request ): ?ValidationError {
		return $this->statementValidator->validate( $request->getStatement(), self::SOURCE_STATEMENT );
	}

	public function getValidatedStatement(): ?Statement {
		return $this->statementValidator->getValidatedStatement();
	}

}
