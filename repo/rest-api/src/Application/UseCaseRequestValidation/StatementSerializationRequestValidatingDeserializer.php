<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCaseRequestValidation;

use LogicException;
use Wikibase\DataModel\Statement\Statement;
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
					throw UseCaseError::newInvalidValue( '/statement/' . $context[StatementValidator::CONTEXT_FIELD] );
				case StatementValidator::CODE_MISSING_FIELD:
					throw new UseCaseError(
						UseCaseError::STATEMENT_DATA_MISSING_FIELD,
						"Mandatory field missing in the statement data: {$context[StatementValidator::CONTEXT_FIELD]}",
						[ UseCaseError::CONTEXT_PATH => $context[StatementValidator::CONTEXT_FIELD] ]
					);
				case StatementValidator::CODE_INVALID_FIELD_TYPE:
					throw UseCaseError::newInvalidValue( '/statement' );
				default:
					throw new LogicException( "Unknown validation error code: {$validationError->getCode()}" );
			}
		}

		return $this->validator->getValidatedStatement();
	}

}
