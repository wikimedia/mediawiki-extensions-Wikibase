<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatement;

use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\DeserializedRequestAdapter;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\ValidatingRequestDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyStatementValidator {

	private ValidatingRequestDeserializer $requestDeserializer;

	public function __construct( ValidatingRequestDeserializer $requestDeserializer ) {
		$this->requestDeserializer = $requestDeserializer;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( GetPropertyStatementRequest $request ): DeserializedGetPropertyStatementRequest {
		return new class( $this->requestDeserializer->validateAndDeserialize( $request ) )
			extends DeserializedRequestAdapter implements DeserializedGetPropertyStatementRequest {
		};
	}

}
