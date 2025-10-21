<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema;

use GraphQL\Type\Definition\UnionType;
use Wikibase\Repo\Domains\Reuse\Domain\Model\PropertyValuePair;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Statement;

/**
 * @license GPL-2.0-or-later
 */
class ValueType extends UnionType {

	public function __construct( array $valueTypeCallbacks ) {
		$valueTypes = array_map( fn( $c ) => $c(), $valueTypeCallbacks );

		parent::__construct( [
			'types' => array_values( array_unique( $valueTypes ) ),
			'resolveType' => fn( Statement|PropertyValuePair $valueProvider ) => $valueTypes[$valueProvider->property->dataType],
		] );
	}

}
