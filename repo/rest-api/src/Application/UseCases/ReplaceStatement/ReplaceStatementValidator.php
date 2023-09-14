<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement;

use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\DeserializedRequestAdapter;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\ValidatingRequestDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class ReplaceStatementValidator {

	private ValidatingRequestDeserializer $requestDeserializer;

	public function __construct( ValidatingRequestDeserializer $requestDeserializer ) {
		$this->requestDeserializer = $requestDeserializer;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( ReplaceStatementRequest $request ): DeserializedReplaceStatementRequest {
		return new class( $this->requestDeserializer->validateAndDeserialize( $request ) )
			extends DeserializedRequestAdapter implements DeserializedReplaceStatementRequest {
		};
	}
}
