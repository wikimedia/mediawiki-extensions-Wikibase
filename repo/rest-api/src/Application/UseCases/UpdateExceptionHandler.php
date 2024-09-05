<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases;

use Wikibase\Repo\RestApi\Domain\Services\Exceptions\ResourceTooLargeException;

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
		}
	}

}
