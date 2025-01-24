<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCaseRequestValidation;

use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\StatementIdValidator;

/**
 * @license GPL-2.0-or-later
 */
class StatementIdRequestValidatingDeserializer {
	private StatementIdValidator $validator;
	private StatementGuidParser $parser;

	public function __construct( StatementIdValidator $validator, StatementGuidParser $parser ) {
		$this->validator = $validator;
		$this->parser = $parser;
	}

	public function validateAndDeserialize( StatementIdRequest $request ): StatementGuid {
		$validationError = $this->validator->validate( $request->getStatementId() );
		if ( $validationError ) {
			throw new UseCaseError(
				UseCaseError::INVALID_PATH_PARAMETER,
				"Invalid path parameter: 'statement_id'",
				[ UseCaseError::CONTEXT_PARAMETER => 'statement_id' ]
			);
		}

		return $this->parser->parse( $request->getStatementId() );
	}

}
