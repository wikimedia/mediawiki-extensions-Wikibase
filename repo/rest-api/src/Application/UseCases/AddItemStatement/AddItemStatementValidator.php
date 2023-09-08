<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\AddItemStatement;

use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\DeserializedRequestAdapter;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\ValidatingRequestDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class AddItemStatementValidator {

	private ValidatingRequestDeserializer $requestDeserializer;

	public function __construct( ValidatingRequestDeserializer $requestDeserializer ) {
		$this->requestDeserializer = $requestDeserializer;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( AddItemStatementRequest $request ): DeserializedAddItemStatementRequest {
		return new class( $this->requestDeserializer->validateAndDeserialize( $request ) )
			extends DeserializedRequestAdapter implements DeserializedAddItemStatementRequest {
		};
	}

}
