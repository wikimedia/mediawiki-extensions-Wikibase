<?php

declare( strict_types=1 );
namespace Wikibase\Repo\Dumpers;

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

	public function __construct(
		SerializationModifier $modifier,
		CallbackFactory $callbackFactory,
		PropertyDataTypeLookup $dataTypeLookup
	) {
		$this->callbackFactory = $callbackFactory;
		$this->modifier = $modifier;
		$this->dataTypeLookup = $dataTypeLookup;
	}

	public function injectEntitySerializationWithDataTypes( array $serialization ) {
		$modifyPaths = [
			'claims/*/*/mainsnak',
			'*/*/claims/*/*/mainsnak', // statements on subentities
		];
		$groupedSnakModifyPaths = [
			'claims/*/*/qualifiers',
			'claims/*/*/references/*/snaks',
			'*/*/claims/*/*/qualifiers',
			'*/*/claims/*/*/references/*/snaks',
		];
		foreach ( $modifyPaths as $modifyPath ) {
			$serialization = $this->modifier->modifyUsingCallback(
				$serialization,
				$modifyPath,
				$this->callbackFactory->getCallbackToAddDataTypeToSnak( $this->dataTypeLookup )
			);
		}

		foreach ( $groupedSnakModifyPaths as $groupedSnakModifyPath ) {
			$serialization = $this->getArrayWithDataTypesInGroupedSnakListAtPath(
				$serialization,
				$groupedSnakModifyPath
			);
		}
		return $serialization;
	}

	/**
	 * @param array $array
	 * @param string $path
	 *
	 * @return array
	 */
	public function getArrayWithDataTypesInGroupedSnakListAtPath( array $array, $path ) {
		return $this->modifier->modifyUsingCallback(
			$array,
			$path,
			$this->callbackFactory->getCallbackToAddDataTypeToSnaksGroupedByProperty( $this->dataTypeLookup )
		);
	}

	/**
	 * @param array $array
	 * @param string $path
	 *
	 * @return array
	 */
	public function getArrayWithDataTypesInSnakAtPath( array $array, $path ) {
		return $this->modifier->modifyUsingCallback(
			$array,
			$path,
			$this->callbackFactory->getCallbackToAddDataTypeToSnak( $this->dataTypeLookup )
		);
	}
}
