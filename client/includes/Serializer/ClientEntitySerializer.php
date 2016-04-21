<?php

namespace Wikibase\Client\Serializer;

use DataValues\Serializers\DataValueSerializer;
use Serializers\Exceptions\SerializationException;
use Serializers\Serializer;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\Serialization\CallbackFactory;
use Wikibase\Lib\Serialization\SerializationModifier;

/**
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Addshore
 */
class ClientEntitySerializer implements Serializer {

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
	 * @var string[]
	 */
	private $filterLangCodes;

	/**
	 * @var LanguageFallbackChain[]
	 */
	private $fallbackChains;

	/**
	 * @param PropertyDataTypeLookup $dataTypeLookup
	 * @param string[] $filterLangCodes
	 * @param LanguageFallbackChain[] $fallbackChains
	 */
	public function __construct(
		PropertyDataTypeLookup $dataTypeLookup,
		array $filterLangCodes,
		array $fallbackChains
	) {
		$this->dataTypeLookup = $dataTypeLookup;
		$this->filterLangCodes = $filterLangCodes;
		$this->fallbackChains = $fallbackChains;

		$this->modifier = new SerializationModifier();
		$this->callbackFactory = new CallbackFactory();
	}

	/**
	 * @see ResultBuilder::addEntityRevision
	 *
	 * @param EntityDocument $entity
	 *
	 * @throws SerializationException
	 * @return array
	 */
	public function serialize( $entity ) {
		$serializerOptions = SerializerFactory::OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH +
			SerializerFactory::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH;
		$serializerFactory = new SerializerFactory( new DataValueSerializer(), $serializerOptions );
		$entitySerializer = $serializerFactory->newEntitySerializer();
		$serialization = $entitySerializer->serialize( $entity );

		if ( !empty( $this->fallbackChains ) ) {
			$serialization = $this->addEntitySerializationFallbackInfo( $serialization );
		}

		$serialization = $this->injectEntitySerializationWithDataTypes( $serialization );
		$serialization = $this->filterEntitySerializationUsingLangCodes( $serialization );

		return $this->omitEmptyArrays( $serialization );
	}

	private function omitEmptyArrays( array $serialization ) {
		return array_filter(
			$serialization,
			function( $value ) {
				return $value !== [];
			}
		);
	}

	/**
	 * @param array $serialization
	 *
	 * @TODO FIXME duplicated code in Repo ResultBuilder
	 *
	 * @return array
	 */
	private function addEntitySerializationFallbackInfo( array $serialization ) {
		$serialization['labels'] = $this->getTermsSerializationWithFallbackInfo(
			$serialization['labels']
		);
		$serialization['descriptions'] = $this->getTermsSerializationWithFallbackInfo(
			$serialization['descriptions']
		);
		return $serialization;
	}

	/**
	 * @param array $serialization
	 *
	 * @TODO FIXME duplicated / similar code in Repo ResultBuilder
	 *
	 * @return array
	 */
	private function injectEntitySerializationWithDataTypes( array $serialization ) {
		$serialization = $this->modifier->modifyUsingCallback(
			$serialization,
			'claims/*/*/mainsnak',
			$this->callbackFactory->getCallbackToAddDataTypeToSnak( $this->dataTypeLookup )
		);
		$serialization = $this->getArrayWithDataTypesInGroupedSnakListAtPath(
			$serialization,
			'claims/*/*/qualifiers'
		);
		$serialization = $this->getArrayWithDataTypesInGroupedSnakListAtPath(
			$serialization,
			'claims/*/*/references/*/snaks'
		);
		return $serialization;
	}

	/**
	 * @param array $array
	 * @param string $path
	 *
	 * @TODO FIXME duplicated / similar code in Repo ResultBuilder
	 *
	 * @return array
	 */
	private function getArrayWithDataTypesInGroupedSnakListAtPath( array $array, $path ) {
		return $this->modifier->modifyUsingCallback(
			$array,
			$path,
			$this->callbackFactory->getCallbackToAddDataTypeToSnaksGroupedByProperty( $this->dataTypeLookup )
		);
	}

	/**
	 * @param array $serialization
	 *
	 * @TODO FIXME duplicated / similar code in Repo ResultBuilder
	 *
	 * @return array
	 */
	private function getTermsSerializationWithFallbackInfo( array $serialization ) {
		$newSerialization = $serialization;
		foreach ( $this->fallbackChains as $requestedLanguageCode => $fallbackChain ) {
			if ( !array_key_exists( $requestedLanguageCode, $serialization ) ) {
				$fallbackSerialization = $fallbackChain->extractPreferredValue( $serialization );
				if ( $fallbackSerialization !== null ) {
					if ( $fallbackSerialization['source'] !== null ) {
						$fallbackSerialization['source-language'] = $fallbackSerialization['source'];
					}
					unset( $fallbackSerialization['source'] );
					$newSerialization[$requestedLanguageCode] = $fallbackSerialization;
				}
			}
		}
		return $newSerialization;
	}

	/**
	 * @param array $serialization
	 *
	 * @TODO FIXME duplicated / similar code in Repo ResultBuilder
	 *
	 * @return array
	 */
	private function filterEntitySerializationUsingLangCodes( array $serialization ) {
		if ( !empty( $this->filterLangCodes ) ) {
			if ( array_key_exists( 'labels', $serialization ) ) {
				foreach ( $serialization['labels'] as $langCode => $languageArray ) {
					if ( !in_array( $langCode, $this->filterLangCodes ) ) {
						unset( $serialization['labels'][$langCode] );
					}
				}
			}
			if ( array_key_exists( 'descriptions', $serialization ) ) {
				foreach ( $serialization['descriptions'] as $langCode => $languageArray ) {
					if ( !in_array( $langCode, $this->filterLangCodes ) ) {
						unset( $serialization['descriptions'][$langCode] );
					}
				}
			}
			if ( array_key_exists( 'aliases', $serialization ) ) {
				foreach ( $serialization['aliases'] as $langCode => $languageArray ) {
					if ( !in_array( $langCode, $this->filterLangCodes ) ) {
						unset( $serialization['aliases'][$langCode] );
					}
				}
			}
		}
		return $serialization;
	}

}
