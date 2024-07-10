<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases;

use LogicException;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\PatchPathException;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\PatchTestConditionFailedException;
use Wikibase\Repo\RestApi\Domain\Services\JsonPatcher;

/**
 * @license GPL-2.0-or-later
 */
class PatchJson {

	private JsonPatcher $patcher;

	public function __construct( JsonPatcher $patcher ) {
		$this->patcher = $patcher;
	}

	/**
	 * @return mixed
	 */
	public function execute( array $serialization, array $patch ) {
		try {
			return $this->patcher->patch( $serialization, $patch );
		} catch ( PatchPathException $e ) {
			$jsonPointer = "/patch/{$e->getOpIndex()}/{$e->getField()}";
			throw new UseCaseError(
				UseCaseError::PATCH_TARGET_NOT_FOUND,
				'Target not found on resource',
				[ UseCaseError::CONTEXT_PATH => $jsonPointer ]
			);
		} catch ( PatchTestConditionFailedException $e ) {
			$opIndex = array_search( $e->getOperation(), $patch );
			if ( !is_int( $opIndex ) ) {
				throw new LogicException( "The invalid test operation wasn't found in the original patch document" );
			}
			throw new UseCaseError(
				UseCaseError::PATCH_TEST_FAILED,
				'Test operation in the provided patch failed',
				[
					UseCaseError::CONTEXT_PATH => "/patch/{$opIndex}",
					UseCaseError::CONTEXT_ACTUAL_VALUE => $e->getActualValue(),
				]
			);
		}
	}

}
