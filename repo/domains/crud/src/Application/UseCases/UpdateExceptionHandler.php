<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases;

use Wikibase\Repo\Domains\Crud\Domain\Services\Exceptions\EditPrevented;
use Wikibase\Repo\Domains\Crud\Domain\Services\Exceptions\RateLimitReached;
use Wikibase\Repo\Domains\Crud\Domain\Services\Exceptions\ResourceTooLargeException;
use Wikibase\Repo\Domains\Crud\Domain\Services\Exceptions\TempAccountCreationLimitReached;

/**
 * @license GPL-2.0-or-later
 */
trait UpdateExceptionHandler {

	/**
	 * @throws UseCaseError
	 *
	 * @return mixed
	 */
	public function executeWithExceptionHandling( callable $callback ) {
		try {
			return $callback();
		} catch ( ResourceTooLargeException $e ) {
			$maxSizeInKb = $e->getResourceSizeLimit();
			throw new UseCaseError(
				UseCaseError::RESOURCE_TOO_LARGE,
				"Edit resulted in a resource that exceeds the size limit of $maxSizeInKb kB",
				[ UseCaseError::CONTEXT_LIMIT => $maxSizeInKb ]
			);
		} catch ( EditPrevented $e ) {
			throw UseCaseError::newPermissionDenied( $e->getReason(), $e->getContext() );
		} catch ( RateLimitReached $e ) {
			throw UseCaseError::newRateLimitReached( UseCaseError::REQUEST_LIMIT_REASON_RATE_LIMIT );
		} catch ( TempAccountCreationLimitReached $e ) {
			throw UseCaseError::newRateLimitReached( UseCaseError::REQUEST_LIMIT_REASON_TEMP_ACCOUNT_CREATION_LIMIT );
		}
	}

}
