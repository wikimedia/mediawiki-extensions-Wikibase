<?php

namespace Wikibase\Client\Serializer;

use Serializers\Exceptions\SerializationException;
use Serializers\Serializer;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Statement\StatementList;

/**
 * @license GPL-2.0-or-later
 * @author eranroz
 */
class ClientStatementListSerializer extends ClientSerializer {

	/**
	 * @var Serializer
	 */
	private $statementListSerializer;

	public function __construct(
		Serializer $statementListSerializer,
		PropertyDataTypeLookup $dataTypeLookup,
		EntityIdParser $entityIdParser
	) {
		parent::__construct( $dataTypeLookup, $entityIdParser );

		$this->statementListSerializer = $statementListSerializer;
	}

	/**
	 * Adds data types to serialization
	 *
	 * @param StatementList $statementList
	 *
	 * @throws SerializationException
	 * @return array
	 */
	public function serialize( $statementList ) {
		$serialization = $this->statementListSerializer->serialize( $statementList );

		$serialization = $this->injectSerializationWithDataTypes( $serialization, '' );

		return $this->omitEmptyArrays( $serialization );
	}

}
