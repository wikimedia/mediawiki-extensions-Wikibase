<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemStatement;

use Wikibase\Repo\RestApi\Validation\StatementIdValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class GetItemStatementValidator {

	public const SOURCE_STATEMENT_ID = 'statement ID';

	private $statementIdValidator;

	public function __construct( StatementIdValidator $statementIdValidator ) {
		$this->statementIdValidator = $statementIdValidator;
	}

	public function validate( GetItemStatementRequest $statementRequest ): ?ValidationError {
		return $this->statementIdValidator->validate(
			$statementRequest->getStatementId(),
			self::SOURCE_STATEMENT_ID
		);
	}
}
