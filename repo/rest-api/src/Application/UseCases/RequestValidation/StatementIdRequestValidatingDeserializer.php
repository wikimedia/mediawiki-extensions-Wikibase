<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RequestValidation;

use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\Repo\RestApi\Application\UseCases\StatementIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\StatementIdValidator;

/**
 * @license GPL-2.0-or-later
 */
class StatementIdRequestValidatingDeserializer {
	public const DESERIALIZED_VALUE = 'statement-id';

	private StatementIdValidator $validator;
	private StatementGuidParser $parser;

	public function __construct( StatementIdValidator $validator, StatementGuidParser $parser ) {
		$this->validator = $validator;
		$this->parser = $parser;
	}

	public function validateAndDeserialize( StatementIdRequest $request ): array {
		$validationError = $this->validator->validate( $request->getStatementId() );
		if ( $validationError ) {
			throw new UseCaseError(
				UseCaseError::INVALID_STATEMENT_ID,
				"Not a valid statement ID: {$validationError->getContext()[StatementIdValidator::CONTEXT_VALUE]}"
			);
		}

		return [ self::DESERIALIZED_VALUE => $this->parser->parse( $request->getStatementId() ) ];
	}

}
