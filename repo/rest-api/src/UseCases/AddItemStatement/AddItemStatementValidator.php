<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\AddItemStatement;

use Wikibase\DataModel\Deserializers\StatementDeserializer;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class AddItemStatementValidator {

	private $statementDeserializer;
	private $validatedStatement = null;

	public function __construct( StatementDeserializer $statementDeserializer ) {
		$this->statementDeserializer = $statementDeserializer;
	}

	public function validate( AddItemStatementRequest $request ): ?ValidationError {
		// The StatementDeserializer and setting the validated statement should move into the dedicated StatementValidator
		// as part of T309843. It's only here now to make the use case test less awkward.
		$this->validatedStatement = $this->statementDeserializer->deserialize( $request->getStatement() );
		return null;
	}

	public function getValidatedStatement(): ?Statement {
		return $this->validatedStatement;
	}

}
