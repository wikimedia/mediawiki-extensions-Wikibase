<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

use LogicException;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Repo\RestApi\Application\Serialization\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\MissingFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\StatementsDeserializer;

/**
 * @license GPL-2.0-or-later
 */
class ItemStatementsValidator {
	public const CODE_INVALID_STATEMENTS = 'invalid-statements';
	public const CODE_INVALID_STATEMENT_DATA = 'statement-data-invalid-field';
	public const CODE_MISSING_STATEMENT_DATA = 'statement-data-missing-field';

	public const CONTEXT_STATEMENTS = 'statements';
	public const CONTEXT_PATH = 'path';
	public const CONTEXT_FIELD = 'field';
	public const CONTEXT_VALUE = 'value';

	private StatementsDeserializer $statementsDeserializer;

	private ?StatementList $deserializedStatements = null;

	public function __construct( StatementsDeserializer $statementsDeserializer ) {
		$this->statementsDeserializer = $statementsDeserializer;
	}

	public function validate( array $statements ): ?ValidationError {
		if ( count( $statements ) === 0 ) {
			$this->deserializedStatements = new StatementList();
			return null;
		}
		if ( array_is_list( $statements ) ) {
			return new ValidationError( self::CODE_INVALID_STATEMENTS, [ self::CONTEXT_STATEMENTS => $statements ] );
		}

		return $this->deserializeStatements( $statements );
	}

	private function deserializeStatements( array $statements ): ?ValidationError {
		try {
			$this->deserializedStatements = $this->statementsDeserializer->deserialize( $statements );
		} catch ( InvalidFieldException $e ) {
			return new ValidationError(
				self::CODE_INVALID_STATEMENT_DATA,
				[
					self::CONTEXT_PATH => $e->getPath(),
					self::CONTEXT_FIELD => $e->getField(),
					self::CONTEXT_VALUE => $e->getValue(),
				]
			);
		} catch ( MissingFieldException $e ) {
			return new ValidationError(
				self::CODE_MISSING_STATEMENT_DATA,
				[
					self::CONTEXT_PATH => $e->getPath(),
					self::CONTEXT_FIELD => $e->getField(),
				]
			);
		}

		return null;
	}

	public function getValidatedStatements(): StatementList {
		if ( $this->deserializedStatements === null ) {
			throw new LogicException( 'getValidatedStatements() called before validate()' );
		}

		return $this->deserializedStatements;
	}

}
