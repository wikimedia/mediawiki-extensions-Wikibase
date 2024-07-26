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
			'qualifiers' => is_array( $serialization['qualifiers'] ) && array_is_list( $serialization['qualifiers'] ),
			'references' => is_array( $serialization['references'] ) && array_is_list( $serialization['references'] ),
		];
		foreach ( $fieldValidation as $field => $isValid ) {
			if ( !$isValid ) {
				throw new InvalidFieldException( $field, $serialization[ $field ], "$basePath/$field" );
			}
		}

		$statement = new Statement(
			$this->propertyValuePairDeserializer->deserialize( $serialization, $basePath ),
			$this->deserializeQualifiers( $serialization, $basePath ),
			$this->deserializeReferences( $serialization, $basePath ),
			// @phan-suppress-next-line PhanTypeMismatchArgument - 'id' has been checked that it is ?string above
			$serialization['id']
		);
		$statement->setRank( array_flip( StatementSerializer::RANK_LABELS )[$serialization['rank']] );

		return $statement;
	}

	private function deserializeQualifiers( array $serialization, string $basePath ): SnakList {
		$qualifiers = [];
		foreach ( $serialization['qualifiers'] as $index => $qualifier ) {
			if ( !is_array( $qualifier ) ) {
				throw new InvalidFieldException( "$index", $qualifier, "$basePath/qualifiers/$index" );
			}
			$qualifiers[] = $this->propertyValuePairDeserializer->deserialize( $qualifier, "$basePath/qualifiers/$index" );
		}

		return new SnakList( $qualifiers );
	}

	private function deserializeReferences( array $serialization, string $basePath ): ReferenceList {
		$references = [];
		foreach ( $serialization['references'] as $index => $reference ) {
			if ( !is_array( $reference ) ) {
				throw new InvalidFieldException( "$index", $reference, "$basePath/references/$index" );
			}
			$references[] = $this->referenceDeserializer->deserialize( $reference, "$basePath/references/$index" );
		}

		return new ReferenceList( $references );
	}

}
