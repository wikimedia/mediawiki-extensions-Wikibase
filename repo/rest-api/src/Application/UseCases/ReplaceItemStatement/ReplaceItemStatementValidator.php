<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\ReplaceItemStatement;

use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\DeserializedRequestAdapter;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\ValidatingRequestDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class ReplaceItemStatementValidator {

	private ValidatingRequestDeserializer $requestDeserializer;

	public function __construct( ValidatingRequestDeserializer $requestDeserializer ) {
		$this->requestDeserializer = $requestDeserializer;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( ReplaceItemStatementRequest $request ): DeserializedReplaceItemStatementRequest {
		return new class( $this->requestDeserializer->validateAndDeserialize( $request ) )
			extends DeserializedRequestAdapter implements DeserializedReplaceItemStatementRequest {
		};
	}
}
