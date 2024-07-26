<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidFieldTypeException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidStatementsException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\MissingFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\PropertyIdMismatchException;

/**
 * @license GPL-2.0-or-later
 */
class StatementsDeserializer {

	private StatementDeserializer $statementDeserializer;

	public function __construct( StatementDeserializer $statementDeserializer ) {
		$this->statementDeserializer = $statementDeserializer;
	}

	/**
	 * @throws InvalidFieldTypeException
	 * @throws InvalidFieldException
	 * @throws MissingFieldException
	 * @throws PropertyIdMismatchException
	 */
	public function deserialize( array $serialization, string $basePath = '' ): StatementList {
		if ( count( $serialization ) && array_is_list( $serialization ) ) {
			throw new InvalidStatementsException( '', $serialization, $basePath );
		}

		$statementList = [];
		foreach ( $serialization as $propertyId => $statementGroups ) {
			// @phan-suppress-next-line PhanRedundantConditionInLoop - $statementGroups is not guaranteed to be an array
			if ( !( is_array( $statementGroups ) && array_is_list( $statementGroups ) ) ) {
				throw new InvalidFieldTypeException( "$propertyId", "$basePath/$propertyId" );
			}
			foreach ( $statementGroups as $index => $statement ) {
				if ( !is_array( $statement ) ) {
					throw new InvalidFieldTypeException( "$propertyId/$index", "$basePath/$propertyId/$index" );
				}

				$statementPropertyId = $statement[ 'property' ][ 'id' ] ?? null;
				if ( $statementPropertyId && $statementPropertyId !== (string)$propertyId ) {
					throw new PropertyIdMismatchException(
						(string)$propertyId,
						$statementPropertyId,
						"$propertyId/$index/property/id"
					);
				}

				$statementList[] = $this->statementDeserializer->deserialize( $statement, "$basePath/$propertyId/$index" );
			}
		}

		return new StatementList( ...$statementList );
	}

}
