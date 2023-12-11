<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases;

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

	public function execute( array $serialization, array $patch ): array {
		try {
			return $this->patcher->patch( $serialization, $patch );
		} catch ( PatchPathException $e ) {
			throw new UseCaseError(
				UseCaseError::PATCH_TARGET_NOT_FOUND,
				"Target '{$e->getOperation()[$e->getField()]}' not found on the resource",
				[ UseCaseError::CONTEXT_OPERATION => $e->getOperation(), UseCaseError::CONTEXT_FIELD => $e->getField() ]
			);
		} catch ( PatchTestConditionFailedException $e ) {
			$operation = $e->getOperation();
			throw new UseCaseError(
				UseCaseError::PATCH_TEST_FAILED,
				"Test operation in the provided patch failed. At path '{$operation[ 'path' ]}'" .
				" expected '" . json_encode( $operation[ 'value' ] ) .
				"', actual: '" . json_encode( $e->getActualValue() ) . "'",
				[
					UseCaseError::CONTEXT_OPERATION => $operation,
					UseCaseError::CONTEXT_ACTUAL_VALUE => $e->getActualValue(),
				]
			);
		}
	}

}
