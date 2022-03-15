<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Presentation;

use stdClass;

/**
 * Converts empty associative arrays to objects to ensure empty objects are serialized as `{}` not `[]` in JSON
 *
 * @license GPL-2.0-or-later
 */
class EmptyArrayToObjectConverter {
	private const WILDCARD_FIELD = '*';

	/**
	 * @var string[][]
	 */
	private $paths;

	/**
	 * @param string[] $paths paths to convert delimited by '/', e.g. 'fluffy/kittens' to match [ 'fluffy' => [ 'kittens' => [] ]
	 */
	public function __construct( array $paths ) {
		$this->paths = array_map( function ( string $path ) {
			return explode( '/', $path );
		}, $paths );
	}

	public function convert( array $input ): array {
		$newArray = $input; // copy so that we don't modify the original

		$this->convertWithPath( [], $newArray );

		return $newArray;
	}

	private function convertWithPath( array $path, array &$subArray ): void {
		foreach ( $subArray as $key => $value ) {
			$currentPath = array_merge( $path, [ $key ] );

			if ( is_array( $value ) && !empty( $value ) ) {
				$this->convertWithPath( $currentPath, $subArray[$key] );
			} elseif ( is_array( $value ) && $this->hasPath( $currentPath ) ) {
				$subArray[$key] = new stdClass();
			}
		}
	}

	private function hasPath( array $currentPath ): bool {
		foreach ( $this->paths as $path ) {
			if ( count( $path ) !== count( $currentPath ) ) {
				continue;
			}

			foreach ( $path as $i => $field ) {
				if ( $currentPath[$i] !== $field && $field !== self::WILDCARD_FIELD ) {
					continue 2;
				}
			}

			return true; // whole path matched
		}

		return false;
	}
}
