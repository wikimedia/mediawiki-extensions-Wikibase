<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema;

use DataValues\StringValue;
use GraphQL\Type\Definition\UnionType;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Value;
use Wikibase\Repo\WikibaseRepo;

/**
 * @license GPL-2.0-or-later
 */
class ValueType extends UnionType {

	public function __construct() {
		$valueTypes = array_map(
			fn( $c ) => $c(),
			WikibaseRepo::getDataTypeDefinitions()->getGraphqlValueTypes()
		);
		$config = [
			'types' => array_values( $valueTypes ),
			'resolveType' => fn( Value $v ) => $v->content instanceof StringValue
					? $valueTypes[ 'VT:string' ]
					: $valueTypes[ 'PT:wikibase-item' ],
		];
		parent::__construct( $config );
	}

}
