<?php

namespace Wikibase\Repo\GraphQLPrototype;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Snak\PropertyValueSnak;
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
				// @phan-suppress-next-line PhanUndeclaredMethod guaranteed to be a string value per array_filter
				'value' => [ 'content' => $statement->getMainSnak()->getDataValue()->getValue() ],
			],
			array_filter(
				iterator_to_array( $item->getStatements() ),
				fn( Statement $statement ) => $statement->getMainSnak() instanceof PropertyValueSnak
					// @phan-suppress-next-line PhanUndeclaredMethod guaranteed to be a value snak per line above
					&& $statement->getMainSnak()->getDataValue() instanceof StringValue
			)
		);
	}
}
