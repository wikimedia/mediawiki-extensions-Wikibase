<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Filters;

/**
 * @license GPL-2.0-or-later
 */
class FieldFilter {

	/**
	 * @var string[]
	 */
	private $fields;

	public function __construct( array $fields ) {
		$this->fields = $fields;
	}

	public function filter( array $entitySerialization ): array {
		$filteredEntity = [];

		foreach ( $entitySerialization as $key => $value ) {
			if ( in_array( $key, $this->fields ) || $key === 'id' ) {
				$filteredEntity[ $key ] = $value;
			}
		}

		return $filteredEntity;
	}
}
