<?php

namespace Wikibase\Repo\GraphQLPrototype;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;

/**
 * @license GPL-2.0-or-later
 */
class StatementsResolver {

	public function __construct( private EntityLookup $entityLookup ) {
	}

	public function fetchStatements( array $rootValue, array $args ): array {
		/** @var Item $item */
		$item = $this->entityLookup->getEntity( new ItemId( $rootValue['id'] ) );
		'@phan-var Item $item';

		return array_map(
			fn( Statement $statement ) => [
				'property' => [ 'id' => $statement->getPropertyId()->getSerialization() ],
				'value' => $this->serializeStatementValue( $statement ),
			],
			array_filter(
				iterator_to_array( $item->getStatements() ),
				fn( Statement $statement ) => $this->includeStatement( $statement, $args['properties'] ?? null )
			)
		);
	}

	private function includeStatement( Statement $statement, ?array $requestedProperties ): bool {
		if ( $requestedProperties && !in_array( $statement->getPropertyId(), $requestedProperties ) ) {
			return false; // Property was not requested by the user
		}

		if ( $statement->getMainSnak() instanceof PropertySomeValueSnak
			|| $statement->getMainSnak() instanceof PropertyNoValueSnak ) {
			return true;
		}

		/** @var PropertyValueSnak $snak */
		$snak = $statement->getMainSnak();
		'@phan-var PropertyValueSnak $snak';

		$dataValue = $snak->getDataValue();
		if ( $dataValue instanceof EntityIdValue ) {
			return $dataValue->getEntityId()->getEntityType() === Item::ENTITY_TYPE;
		}

		return $dataValue instanceof StringValue;
	}

	private function serializeStatementValue( Statement $statement ): array {
		if ( $statement->getMainSnak() instanceof PropertySomeValueSnak
			|| $statement->getMainSnak() instanceof PropertyNoValueSnak ) {
			return [ 'type' => $statement->getMainSnak()->getType() ];
		}

		/** @var PropertyValueSnak $snak */
		$snak = $statement->getMainSnak();
		'@phan-var PropertyValueSnak $snak';

		$dataValue = $snak->getDataValue();
		if ( $dataValue instanceof StringValue ) {
			return [
				'type' => 'value',
				'content' => $dataValue->getValue(),
			];
		} elseif ( $dataValue instanceof EntityIdValue ) {
			return [
				'type' => 'value',
				'content' => [ 'id' => $dataValue->getEntityId()->getSerialization() ],
			];
		}

		throw new \LogicException( 'Unexpected value type.' );
	}
}
