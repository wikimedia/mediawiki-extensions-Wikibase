<?php

namespace Wikibase\Lib\Serialization;

/**
 * Class which can be used to easily modify serializations and arrays.
 *
 * This could easily be factored out into a library.
 *
 * @license GPL-2.0-or-later
 */
class SerializationModifier {

	/**
	 * @param array $array The array to modify.
	 * @param (callable|callable[])[] $modifications Mapping from paths to modifications.
	 * Array keys are paths:
	 *     Element keys should be separated with / characters.
	 *     * characters can be used to match all keys at a given level.
	 *     Empty string can be used to modify $array directly.
	 *     Examples:
	 *         ''
	 *         'foo/*'
	 *         'root/entities/*\/statement/references/*\/snaks/*'
	 *     More specific paths always run first (e.g. foo/bar before foo),
	 *     so that array elements added by callbacks are never matched against other paths.
	 * Elements are callbacks or lists of callbacks:
	 *  Callback accepts 1 parameter which is the element to touch
	 *  Callback should return the altered element
	 * @return array The altered array.
	 */
	public function modifyUsingCallbacks( array $array, array $modifications ): array {
		$this->modifyUsingUnflattenedCallbacks( $array, $this->unflattenPaths( $modifications ) );
		return $array;
	}

	/**
	 * Iterates the value and modifications and runs all the needed sub-modifications,
	 * then runs the modifications of the current array level ('' key), if any.
	 *
	 * @param mixed $value The value to modify (usually an array except on leaf nodes).
	 * @param array $modifications Modifications as returned by {@link unflattenPaths}.
	 */
	private function modifyUsingUnflattenedCallbacks( &$value, array $modifications ): void {
		$rootModifications = $modifications[''] ?? [];
		unset( $modifications[''] );

		if ( is_array( $value ) ) {
			if ( array_key_exists( '*', $modifications ) ) {
				$starModifications = $modifications['*'];
				foreach ( $value as $key => &$subValue ) {
					if ( array_key_exists( $key, $modifications ) ) {
						$keyModifications = $modifications[$key];
						$subModifications = array_merge_recursive( $keyModifications, $starModifications );
					} else {
						$subModifications = $starModifications;
					}
					$this->modifyUsingUnflattenedCallbacks( $subValue, $subModifications );
				}
			} else {
				foreach ( $modifications as $key => $subModifications ) {
					if ( array_key_exists( $key, $value ) ) {
						$this->modifyUsingUnflattenedCallbacks( $value[$key], $subModifications );
					}
				}
			}
		}

		foreach ( (array)$rootModifications as $callback ) {
			$value = $callback( $value );
		}
	}

	/**
	 * Turn a flat array with paths as keys into a nested structure.
	 *
	 * Example input:
	 *
	 *     [
	 *         '' => 'cb0',
	 *         'claims' => 'cb1',
	 *         'claims/foo' => 'cb2',
	 *         'claims/*\/bar' => 'cb3',
	 *         'label' => 'cb4',
	 *     ]
	 *
	 * Example output:
	 *
	 *     [
	 *         '' => 'cb0',
	 *         'claims' => [
	 *             '' => 'cb1',
	 *             'foo' => [ '' => 'cb2' ],
	 *             '*' => [
	 *                 'bar' => [ '' => 'cb3' ],
	 *             ],
	 *         ],
	 *         'label' => [ '' => 'cb4' ],
	 *     ]
	 *
	 * @param array $array
	 * @return array
	 */
	private function unflattenPaths( array $array ): array {
		$unflattened = [];
		foreach ( $array as $key => $value ) {
			unset( $subArray );
			$subArray = &$unflattened;
			foreach ( $this->getPathParts( $key ) as $pathPart ) {
				// @phan-suppress-next-line PhanTypeInvalidDimOffset subArray is created implicitly
				$subArray = &$subArray[$pathPart];
			}
			$subArray[''] = $value;
		}
		return $unflattened;
	}

	/**
	 * @param null|string $path
	 *
	 * @return string[]
	 */
	private function getPathParts( ?string $path ): array {
		if ( $path === null || $path === '' ) {
			return [];
		}

		return explode( '/', $path );
	}

}
