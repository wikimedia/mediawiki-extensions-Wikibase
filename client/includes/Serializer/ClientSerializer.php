<?php

namespace Wikibase\Client\Serializer;

use Serializers\Serializer;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\Serialization\CallbackFactory;
use Wikibase\Lib\Serialization\SerializationModifier;

/**
 * @license GPL-2.0-or-later
 * @author Addshore
 */
abstract class ClientSerializer implements Serializer {

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $dataTypeLookup;

	/**
	 * @var SerializationModifier
	 */
	private $modifier;

	/**
	 * @var CallbackFactory
	 */
	private $callbackFactory;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	public function __construct( PropertyDataTypeLookup $dataTypeLookup, EntityIdParser $entityIdParser ) {
		$this->dataTypeLookup = $dataTypeLookup;
		$this->entityIdParser = $entityIdParser;

		$this->modifier = new SerializationModifier();
		$this->callbackFactory = new CallbackFactory();
	}

	/**
	 * @param array $serialization
	 *
	 * @return array
	 */
	protected function omitEmptyArrays( array $serialization ) {
		return array_filter(
			$serialization,
			function( $value ) {
				return $value !== [];
			}
		);
	}

	/**
	 * @param array $serialization
	 * @param string $pathPrefix
	 *
	 * @todo FIXME duplicated / similar code in Repo ResultBuilder
	 *
	 * @return array
	 */
	protected function injectSerializationWithDataTypes( array $serialization, $pathPrefix ) {
		$callback = $this->callbackFactory->getCallbackToAddDataTypeToSnak( $this->dataTypeLookup, $this->entityIdParser );
		$groupedCallback = $this->callbackFactory->getCallbackToAddDataTypeToSnaksGroupedByProperty(
			$this->dataTypeLookup,
			$this->entityIdParser
		);

		return $this->modifier->modifyUsingCallbacks(
			$serialization,
			[
				"$pathPrefix*/*/mainsnak" => $callback,
				"$pathPrefix*/*/qualifiers" => $groupedCallback,
				"$pathPrefix*/*/references/*/snaks" => $groupedCallback,
			]
		);
	}

}
