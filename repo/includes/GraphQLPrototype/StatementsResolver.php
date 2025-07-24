<?php

namespace Wikibase\Repo\GraphQLPrototype;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Statement\Statement;

/**
 * @license GPL-2.0-or-later
 */
class StatementsResolver {

	public function __construct( private EntityLookup $entityLookup ) {
	}

	public function fetchStatements( array $rootValue ): array {
		/** @var Item $item */
		$item = $this->entityLookup->getEntity( new ItemId( $rootValue['id'] ) );
		'@phan-var Item $item';

		return array_map(
			fn( Statement $statement ) => [
				'property' => [ 'id' => $statement->getPropertyId()->getSerialization() ],
			],
			iterator_to_array( $item->getStatements() )
		);
	}
}
