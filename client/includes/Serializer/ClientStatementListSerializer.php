<?php

namespace Wikibase\Client\Serializer;

use Serializers\Exceptions\SerializationException;
use Serializers\Serializer;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\Serialization\SerializationModifier;

/**
 * @license GPL-2.0+
 * @author Addshore
 */
class ClientStatementListSerializer extends ClientSerializer {

	/**
	 * @var Serializer
	 */
	private $statementSerializer;

	/**
	 * @param Serializer $entitySerializer
	 * @param PropertyDataTypeLookup $dataTypeLookup
	 * @param string[] $filterLangCodes
	 */
	public function __construct(
		Serializer $statementSerializer,
		PropertyDataTypeLookup $dataTypeLookup,
		array $filterLangCodes
	) {
		parent::__construct( $dataTypeLookup, $filterLangCodes );
		$this->statementSerializer = $statementSerializer;
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
		$serialization = $this->statementSerializer->serialize( $statementList );

		$serialization = $this->injectSerializationWithDataTypes( $serialization, '' );
		$serialization = $this->filterEntitySerializationUsingLangCodes( $serialization );

		return $this->omitEmptyArrays( $serialization );
	}

}
