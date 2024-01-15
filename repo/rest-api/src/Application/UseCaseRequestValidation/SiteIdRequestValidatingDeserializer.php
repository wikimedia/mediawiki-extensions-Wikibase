<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCaseRequestValidation;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\SiteIdValidator;

/**
 * @license GPL-2.0-or-later
 */
class SiteIdRequestValidatingDeserializer {

	private SiteIdValidator $siteIdValidator;

	public function __construct( SiteIdValidator $siteIdValidator ) {
		$this->siteIdValidator = $siteIdValidator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( SiteIdRequest $request ): string {
		$validationError = $this->siteIdValidator->validate( $request->getSiteId() );
		if ( $validationError ) {
			throw new UseCaseError(
				UseCaseError::INVALID_SITE_ID,
				"Not a valid site id: {$validationError->getContext()[SiteIdValidator::CONTEXT_SITE_ID_VALUE]}",
			);
		}
		return $request->getSiteId();
	}

}
