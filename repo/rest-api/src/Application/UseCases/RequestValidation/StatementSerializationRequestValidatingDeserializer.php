<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RequestValidation;

use LogicException;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\RestApi\Application\UseCases\StatementSerializationRequest;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\StatementValidator;

/**
 * @license GPL-2.0-or-later
 */
class StatementSerializationRequestValidatingDeserializer {

	private StatementValidator $validator;

	public function __construct( StatementValidator $validator ) {
		$this->validator = $validator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( StatementSerializationRequest $request ): Statement {
		$validationError = $this->validator->validate( $request->getStatement() );
		if ( $validationError ) {
			$context = $validationError->getContext();
			switch ( $validationError->getCode() ) {
				case StatementValidator::CODE_INVALID_FIELD:
					throw new UseCaseError(
						UseCaseError::STATEMENT_DATA_INVALID_FIELD,
						"Invalid input for '{$context[StatementValidator::CONTEXT_FIELD_NAME]}'",
						[
							UseCaseError::CONTEXT_PATH => $context[StatementValidator::CONTEXT_FIELD_NAME],
							UseCaseError::CONTEXT_VALUE => $context[StatementValidator::CONTEXT_FIELD_VALUE],
						]
					);
				case StatementValidator::CODE_MISSING_FIELD:
					throw new UseCaseError(
						UseCaseError::STATEMENT_DATA_MISSING_FIELD,
						"Mandatory field missing in the statement data: {$context[StatementValidator::CONTEXT_FIELD_NAME]}",
						[ UseCaseError::CONTEXT_PATH => $context[StatementValidator::CONTEXT_FIELD_NAME] ]
					);
				default:
					throw new LogicException( "Unknown validation error code: {$validationError->getCode()}" );
			}
		}

		return $this->validator->getValidatedStatement();
	}

}
