<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure;

use Exception;
use InvalidArgumentException;
use Swaggest\JsonDiff\JsonPatch;
use Swaggest\JsonDiff\PatchTestOperationFailedException;
use Swaggest\JsonDiff\PathException;
use Wikibase\Repo\RestApi\Domain\Exceptions\PatchPathException;
use Wikibase\Repo\RestApi\Domain\Exceptions\PatchTestConditionFailedException;
use Wikibase\Repo\RestApi\Domain\Services\JsonPatcher;

/**
 * @license GPL-2.0-or-later
 */
class JsonDiffJsonPatcher implements JsonPatcher {

	/**
	 * @inheritDoc
	 */
	public function patch( array $target, array $patch ): array {
		try {
			$patchDocument = JsonPatch::import( $patch );
		} catch ( Exception $e ) {
			throw new InvalidArgumentException( 'Invalid patch' );
		}

		$patchDocument->setFlags( JsonPatch::TOLERATE_ASSOCIATIVE_ARRAYS );

		try {
			$patchDocument->apply( $target );
		} catch ( PatchTestOperationFailedException $e ) {
			throw new PatchTestConditionFailedException(
				$e->getMessage(),
				(array)$e->getOperation(),
				$e->getActualValue()
			);
		} catch ( PathException $e ) {
			throw new PatchPathException( $e->getMessage(), (array)$e->getOperation(), $e->getField() );
		}

		return $target;
	}

}
