<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use InvalidArgumentException;
use MediaWiki\Rest\HttpException;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikimedia\Assert\Assert;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
trait AssertValidTopLevelFields {

	/**
	 * @throws HttpException
	 */
	public function assertValidTopLevelTypes( ?array $body, array $paramSettings ): void {
		foreach ( $paramSettings as $fieldName => $fieldSettings ) {
			if ( isset( $body[$fieldName] ) ) {
				$this->assertType( $fieldSettings[ParamValidator::PARAM_TYPE], $fieldName, $body[$fieldName] );
			} elseif ( $fieldSettings[ParamValidator::PARAM_REQUIRED] === true ) {
				throw $this->convertUseCaseErrorToHttpException( UseCaseError::newMissingField( '/', $fieldName ) );
			}
		}
	}

	/**
	 * @param string $type
	 * @param string $fieldName
	 * @param mixed $value
	 *
	 * @throws HttpException
	 */
	private function assertType( string $type, string $fieldName, $value ): void {
		try {
			Assert::parameterType( $type, $value, '$field' );
		} catch ( InvalidArgumentException $exception ) {
			throw $this->convertUseCaseErrorToHttpException( UseCaseError::newInvalidValue( "/$fieldName" ) );
		}
	}

	private function convertUseCaseErrorToHttpException( UseCaseError $error ): HttpException {
		return new HttpException(
			$error->getErrorMessage(),
			ErrorResponseToHttpStatus::lookup( $error->getErrorCode() ),
			[
				'code' => $error->getErrorCode(),
				'context' => $error->getErrorContext(),
			]
		);
	}

}
