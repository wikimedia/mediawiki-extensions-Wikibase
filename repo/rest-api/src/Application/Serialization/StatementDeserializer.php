<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidFieldTypeException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\MissingFieldException;

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

	/**
	 * @throws InvalidFieldTypeException
	 * @throws InvalidFieldException
	 * @throws MissingFieldException
	 */
	public function deserialize( array $serialization, string $basePath = '' ): Statement {
		if ( count( $serialization ) && array_is_list( $serialization ) ) {
			throw new InvalidFieldTypeException( $basePath );
		}

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
				throw new InvalidFieldException( $field, $serialization[ $field ], "$basePath/$field" );
			}
		}

		$statement = new Statement(
			$this->propertyValuePairDeserializer->deserialize( $serialization, $basePath ),
			new SnakList( array_map(
				fn( $i, array $q ) => $this->propertyValuePairDeserializer->deserialize( $q, "$basePath/qualifiers/$i" ),
				array_keys( $serialization['qualifiers'] ),
				$serialization['qualifiers']
			) ),
			new ReferenceList( array_map(
				fn( $i, array $r ) => $this->referenceDeserializer->deserialize( $r, "$basePath/references/$i" ),
				array_keys( $serialization['references'] ),
				$serialization['references']
			) ),
			// @phan-suppress-next-line PhanTypeMismatchArgument - 'id' has been checked that it is ?string above
			$serialization['id']
		);
		$statement->setRank( array_flip( StatementSerializer::RANK_LABELS )[$serialization['rank']] );

		return $statement;
	}

	private function isArrayOfArrays( array $list ): bool {
		return array_reduce( $list, fn( bool $isValid, $item ) => $isValid && is_array( $item ), true );
	}

}
