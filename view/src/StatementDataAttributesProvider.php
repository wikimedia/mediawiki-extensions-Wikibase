<?php

namespace Wikibase\View;

use InvalidArgumentException;
use Serializers\Exceptions\SerializationException;
use Serializers\Serializer;
use stdClass;
use Wikibase\DataModel\Statement\Statement;

class StatementDataAttributesProvider extends DataAttributesProvider {

	/**
	 * @var Serializer
	 */
	private $statementSerializer;

	public function __construct( Serializer $statementSerializer ) {
		$this->statementSerializer = $statementSerializer;
	}

	/**
	 * @param Statement $statement
	 *
	 * @throws InvalidArgumentException if the provided element is not a statement
	 * @throws SerializationException if the provides serializer does not accept statements
	 * @return string[] Array of JSON serializations
	 */
	public function getDataAttributes( $statement ) {
		if ( !( $statement instanceof Statement ) ) {
			throw new InvalidArgumentException( 'Expected a Statement' );
		}

		// TODO: Utilize JSON_HEX_… options?
		$json = json_encode( $this->statementSerializer->serialize( $statement ) );

		return [
			'data-model-statement-serialization' => $json,
		];
	}

}
