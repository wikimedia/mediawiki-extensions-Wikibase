<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Infrastructure;

use Exception;
use InvalidArgumentException;
use Swaggest\JsonDiff\JsonPatch;
use Swaggest\JsonDiff\PatchTestOperationFailedException;
use Swaggest\JsonDiff\PathException;
use Wikibase\Repo\Domains\Crud\Domain\Services\Exceptions\PatchPathException;
use Wikibase\Repo\Domains\Crud\Domain\Services\Exceptions\PatchTestConditionFailedException;
use Wikibase\Repo\Domains\Crud\Domain\Services\JsonPatcher;

/**
 * @license GPL-2.0-or-later
 */
class JsonDiffJsonPatcher implements JsonPatcher {

	/**
	 * @inheritDoc
	 */
	public function patch( array $target, array $patch ) {
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
			throw new PatchPathException( $e->getMessage(), $e->getField(), $e->getOpIndex() );
		}

		// TODO investigate. JsonPatch (sometimes) adds/replaces new values as object, not associative array
		return self::convertObjectsToArray( $target );
	}

	/**
	 * @param mixed $serialization
	 *
	 * @return mixed
	 */
	private static function convertObjectsToArray( $serialization ) {
		if ( !( is_object( $serialization ) || is_array( $serialization ) ) ) {
			return $serialization;
		}

		$output = [];
		foreach ( $serialization as $key => $value ) {
			if ( is_array( $value ) ) {
				$output[ $key ] = self::convertObjectsToArray( $value );
			} elseif ( is_object( $value ) ) {
				$output[ $key ] = self::convertObjectsToArray( (array)$value );
			} else {
				$output[ $key ] = $value;
			}
		}
		return $output;
	}

}
