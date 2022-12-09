<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Serialization;

use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;

/**
 * @license GPL-2.0-or-later
 */
class StatementDeserializer {

	private PropertyValuePairDeserializer $propertyValuePairDeserializer;
	private ReferenceDeserializer $referenceDeserializer;

	public function __construct(
		PropertyValuePairDeserializer $propertyValuePairDeserializer,
		ReferenceDeserializer $referenceDeserializer
	) {
		$this->propertyValuePairDeserializer = $propertyValuePairDeserializer;
		$this->referenceDeserializer = $referenceDeserializer;
	}

	public function deserialize( array $serialization ): Statement {
		$serialization['id'] ??= null;
		$serialization['qualifiers'] ??= [];
		$serialization['references'] ??= [];
		$serialization['rank'] ??= StatementSerializer::RANK_LABELS[Statement::RANK_NORMAL];

		$fieldValidation = [
			'id' => is_string( $serialization['id'] ) || $serialization['id'] === null,
			'rank' => in_array( $serialization['rank'], StatementSerializer::RANK_LABELS, true ),
			'qualifiers' => is_array( $serialization['qualifiers'] ) && $this->isArrayOfArrays( $serialization['qualifiers'] ),
			'references' => is_array( $serialization['references'] ) && $this->isArrayOfArrays( $serialization['references'] ),
		];
		foreach ( $fieldValidation as $field => $isValid ) {
			if ( !$isValid ) {
				throw new InvalidFieldException( $field, $serialization[$field] );
			}
		}

		$statement = new Statement(
			$this->propertyValuePairDeserializer->deserialize( $serialization ),
			new SnakList( array_map(
				fn( array $q ) => $this->propertyValuePairDeserializer->deserialize( $q ),
				$serialization['qualifiers']
			) ),
			new ReferenceList( array_map(
				fn( array $r ) => $this->referenceDeserializer->deserialize( $r ),
				$serialization['references']
			) ),
			$serialization['id']
		);
		$statement->setRank( array_flip( StatementSerializer::RANK_LABELS )[$serialization['rank']] );

		return $statement;
	}

	private function isArrayOfArrays( array $list ): bool {
		return array_reduce( $list, fn( bool $isValid, $item ) => $isValid && is_array( $item ), true );
	}

}
