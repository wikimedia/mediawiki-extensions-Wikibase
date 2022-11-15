<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Serialization;

use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;

/**
 * @license GPL-2.0-or-later
 */
class StatementDeserializer {

	private PropertyValuePairDeserializer $propertyValuePairDeserializer;

	public function __construct( PropertyValuePairDeserializer $propertyValuePairDeserializer ) {
		$this->propertyValuePairDeserializer = $propertyValuePairDeserializer;
	}

	public function deserialize( array $serialization ): Statement {
		$id = $serialization['id'] ?? null;
		$qualifiers = $serialization['qualifiers'] ?? [];
		$references = $serialization['references'] ?? [];
		$rank = $serialization['rank'] ?? StatementSerializer::RANK_LABELS[Statement::RANK_NORMAL];

		if ( !is_string( $id ) && $id !== null
			|| !in_array( $rank, StatementSerializer::RANK_LABELS, true )
			|| !is_array( $qualifiers ) || !$this->isArrayOfArrays( $qualifiers )
			|| !is_array( $references ) || !$this->isArrayOfArrays( $references ) ) {
			throw new InvalidFieldException();
		}

		$statement = new Statement(
			$this->propertyValuePairDeserializer->deserialize( $serialization ),
			new SnakList( array_map(
				fn( array $q ) => $this->propertyValuePairDeserializer->deserialize( $q ),
				$qualifiers
			) ),
			null, // TODO
			$id
		);
		$statement->setRank( array_flip( StatementSerializer::RANK_LABELS )[$rank] );

		return $statement;
	}

	private function isArrayOfArrays( array $list ): bool {
		return array_reduce( $list, fn( bool $isValid, $item ) => $isValid && is_array( $item ), true );
	}

}
