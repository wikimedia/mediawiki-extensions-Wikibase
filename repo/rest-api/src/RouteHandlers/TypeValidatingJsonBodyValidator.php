<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\RouteHandlers;

use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\RequestInterface;
use MediaWiki\Rest\Validator\JsonBodyValidator;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * Can likely be removed once T305973 is done.
 *
 * @license GPL-2.0-or-later
 */
class TypeValidatingJsonBodyValidator extends JsonBodyValidator {

	public const TYPE_MISMATCH_CODE = 'invalid-request-body';
	public const TYPE_MISMATCH_MESSAGE = 'Invalid field type';

	private array $settings;

	public function __construct( array $settings ) {
		parent::__construct( $settings );
		$this->settings = $settings;
	}

	public function validateBody( RequestInterface $request ): array {
		$parsedJsonBody = parent::validateBody( $request );

		foreach ( $parsedJsonBody as $fieldName => $fieldValue ) {
			if ( !$this->hasExpectedType( $fieldName, $fieldValue ) && !$this->isOptionalAndNull( $fieldName, $fieldValue ) ) {
				throw new HttpException(
					self::TYPE_MISMATCH_MESSAGE,
					400,
					[
						'code' => self::TYPE_MISMATCH_CODE,
						'fieldName' => $fieldName,
						'expectedType' => $this->getFieldTypeFromSettings( $fieldName ),
					]
				);
			}
		}

		return $parsedJsonBody;
	}

	/**
	 * @param mixed $fieldValue
	 */
	private function hasExpectedType( string $fieldName, $fieldValue ): bool {
		$typeAsSpecifiedInSettings = $this->getFieldTypeFromSettings( $fieldName );
		// PHP can't tell "object" from "array" after deserializing JSON.
		// We could extend this method to check whether the array is associative for type "object" if we need to in the future.
		$expectedType = $typeAsSpecifiedInSettings === 'object' ? 'array' : $typeAsSpecifiedInSettings;

		return !$expectedType || gettype( $fieldValue ) === $expectedType;
	}

	/**
	 * @param mixed $fieldValue
	 */
	private function isOptionalAndNull( string $fieldName, $fieldValue ): bool {
		$isRequired = $this->settings[$fieldName][ParamValidator::PARAM_REQUIRED] ?? false;

		return !$isRequired && $fieldValue === null;
	}

	private function getFieldTypeFromSettings( string $fieldName ): ?string {
		return $this->settings[$fieldName][ParamValidator::PARAM_TYPE] ?? null;
	}

}
