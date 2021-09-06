<?php

declare( strict_types=1 );
namespace Wikibase\Repo\Dumpers;

use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\Serialization\CallbackFactory;
use Wikibase\Lib\Serialization\SerializationModifier;

/**
 * Class for injecting property datatypes in entity json serialization
 *
 * @license GPL-2.0-or-later
 */
class JsonDataTypeInjector {

	/** @var PropertyDataTypeLookup */
	private $dataTypeLookup;

	/** @var CallbackFactory */
	private $callbackFactory;

	/** @var SerializationModifier */
	private $modifier;

	/** @var EntityIdParser */
	private $entityIdParser;

	public function __construct(
		SerializationModifier $modifier,
		CallbackFactory $callbackFactory,
		PropertyDataTypeLookup $dataTypeLookup,
		EntityIdParser $entityIdParser
	) {
		$this->callbackFactory = $callbackFactory;
		$this->modifier = $modifier;
		$this->dataTypeLookup = $dataTypeLookup;
		$this->entityIdParser = $entityIdParser;
	}

	public function injectEntitySerializationWithDataTypes( array $serialization ) {
		$callback = $this->callbackFactory->getCallbackToAddDataTypeToSnak( $this->dataTypeLookup, $this->entityIdParser );
		$groupedCallback = $this->callbackFactory->getCallbackToAddDataTypeToSnaksGroupedByProperty(
			$this->dataTypeLookup,
			$this->entityIdParser
		);

		return $this->modifier->modifyUsingCallbacks(
			$serialization,
			[
				'claims/*/*/mainsnak' => $callback,
				'*/*/claims/*/*/mainsnak' => $callback, // statements on subentities
				'claims/*/*/qualifiers' => $groupedCallback,
				'claims/*/*/references/*/snaks' => $groupedCallback,
				'*/*/claims/*/*/qualifiers' => $groupedCallback,
				'*/*/claims/*/*/references/*/snaks' => $groupedCallback,
			]
		);
	}
}
