<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases;

use Wikibase\Repo\RestApi\Domain\Services\Exceptions\AbuseFilterException;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\RateLimitReached;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\ResourceTooLargeException;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\SpamBlacklistException;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\TempAccountCreationLimitReached;

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
		} catch ( AbuseFilterException $e ) {
			throw UseCaseError::newPermissionDenied( UseCaseError::PERMISSION_DENIED_REASON_ABUSE_FILTER, [
				'filter_id' => $e->getFilterId(),
				'filter_description' => $e->getFilterDescription(),
			] );
		} catch ( RateLimitReached $e ) {
			throw UseCaseError::newRateLimitReached( UseCaseError::REQUEST_LIMIT_REASON_RATE_LIMIT );
		} catch ( TempAccountCreationLimitReached $e ) {
			throw UseCaseError::newRateLimitReached( UseCaseError::REQUEST_LIMIT_REASON_TEMP_ACCOUNT_CREATION_LIMIT );
		} catch ( SpamBlacklistException $e ) {
			throw UseCaseError::newPermissionDenied(
				UseCaseError::PERMISSION_DENIED_REASON_SPAM_BLACKLIST,
				[ 'blocked_text' => $e->getBlockedText() ]
			);
		}
	}

}
