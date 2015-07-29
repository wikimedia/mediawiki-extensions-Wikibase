<?php

namespace Wikibase\Client\DataAccess\Scribunto;

use DataValues\Serializers\DataValueSerializer;
use Language;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Services\EntityId\EntityIdParser;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\Serialization\CallbackFactory;
use Wikibase\Lib\Serialization\SerializationModifier;
use Wikibase\Lib\Store\EntityLookup;

/**
 * Functionality needed to expose Entities to Lua.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 * @author Adam Shorland
 */
class EntityAccessor {

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var UsageAccumulator
	 */
	private $usageAccumulator;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $dataTypeLookup;

	/**
	 * @var LanguageFallbackChain
	 */
	private $fallbackChain;

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @var ContentLanguages
	 */
	private $termsLanguages;

	/**
	 * @var SerializationModifier
	 */
	private $modifier;

	/**
	 * @var CallbackFactory
	 */
	private $callbackFactory;

	/**
	 * @param EntityIdParser $entityIdParser
	 * @param EntityLookup $entityLookup
	 * @param UsageAccumulator $usageAccumulator
	 * @param PropertyDataTypeLookup $dataTypeLookup
	 * @param LanguageFallbackChain $fallbackChain
	 * @param Language $language
	 * @param ContentLanguages $termsLanguages
	 */
	public function __construct(
		EntityIdParser $entityIdParser,
		EntityLookup $entityLookup,
		UsageAccumulator $usageAccumulator,
		PropertyDataTypeLookup $dataTypeLookup,
		LanguageFallbackChain $fallbackChain,
		Language $language,
		ContentLanguages $termsLanguages
	) {
		$this->entityIdParser = $entityIdParser;
		$this->entityLookup = $entityLookup;
		$this->usageAccumulator = $usageAccumulator;
		$this->dataTypeLookup = $dataTypeLookup;
		$this->fallbackChain = $fallbackChain;
		$this->language = $language;
		$this->termsLanguages = $termsLanguages;
		$this->modifier = new SerializationModifier();
		$this->callbackFactory = new CallbackFactory();
	}

	/**
	 * Recursively renumber a serialized array in place, so it is indexed at 1, not 0.
	 * Just like Lua wants it.
	 *
	 * @param array &$entityArr
	 */
	private function renumber( array &$entityArr ) {
		foreach ( $entityArr as &$value ) {
			if ( !is_array( $value ) ) {
				continue;
			}
			if ( array_key_exists( 0, $value ) ) {
				$value = array_combine( range( 1, count( $value ) ), array_values( $value ) );
			}
			$this->renumber( $value );
		}
	}

	/**
	 * Get entity from prefixed ID (e.g. "Q23") and return it as serialized array.
	 *
	 * @since 0.5
	 *
	 * @param string $prefixedEntityId
	 *
	 * @return array
	 */
	public function getEntity( $prefixedEntityId ) {
		$prefixedEntityId = trim( $prefixedEntityId );

		$entityId = $this->entityIdParser->parse( $prefixedEntityId );

		$entityObject = $this->entityLookup->getEntity( $entityId );

		if ( $entityObject === null ) {
			return null;
		}

		$entityArr = $this->getEntityArray(
			$entityObject,
			//TODO is this the right set of langs to return? or just 1? CONFUSING!
			array_unique( array_merge(
				$this->termsLanguages->getLanguages(),
				$this->fallbackChain->getFetchLanguageCodes(),
				array( $this->language->getCode() )
			) ),
			array( $this->language->getCode() => $this->fallbackChain )
		);

		// Renumber the entity as Lua uses 1-based array indexing
		$this->renumber( $entityArr );
		$entityArr['schemaVersion'] = 2;

		$this->usageAccumulator->addAllUsage( $entityId );
		return $entityArr;
	}

	/**
	 * @see ResultBuilder::addEntityRevision
	 *
	 * @param Entity $entity
	 * @param array $filterLangCodes
	 * @param array $fallbackChains
	 *
	 * @return array
	 */
	private function getEntityArray( Entity $entity, array $filterLangCodes, array $fallbackChains ) {
		$serializerOptions = SerializerFactory::OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH +
			SerializerFactory::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH;
		$serializerFactory = new SerializerFactory( new DataValueSerializer(), $serializerOptions );
		$entitySerializer = $serializerFactory->newEntitySerializer();
		$serialization = $entitySerializer->serialize( $entity );

		if ( !empty( $fallbackChains ) ) {
			$serialization = $this->addEntitySerializationFallbackInfo( $serialization, $fallbackChains );
		}

		$serialization = $this->injectEntitySerializationWithDataTypes( $serialization );

		$serialization = $this->filterEntitySerializationUsingLangCodes(
			$serialization,
			$filterLangCodes
		);

		// Omit empty arrays from the result
		$serialization = array_filter(
			$serialization,
			function( $value ) {
				return $value !== array();
			}
		);

		return $serialization;
	}

	/**
	 * @param array $serialization
	 * @param LanguageFallbackChain[] $fallbackChains
	 *
	 * @TODO FIXME duplicated code in Repo ResultBuilder
	 *
	 * @return array
	 */
	private function addEntitySerializationFallbackInfo(
		array $serialization,
		array $fallbackChains
	) {
		$serialization['labels'] = $this->getTermsSerializationWithFallbackInfo(
			$serialization['labels'],
			$fallbackChains
		);
		$serialization['descriptions'] = $this->getTermsSerializationWithFallbackInfo(
			$serialization['descriptions'],
			$fallbackChains
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
	 * @param LanguageFallbackChain[] $fallbackChains
	 *
	 * @TODO FIXME duplicated / similar code in Repo ResultBuilder
	 *
	 * @return array
	 */
	private function getTermsSerializationWithFallbackInfo(
		array $serialization,
		array $fallbackChains
	) {
		$newSerialization = $serialization;
		foreach ( $fallbackChains as $requestedLanguageCode => $fallbackChain ) {
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
	 * @param array|null $langCodes
	 *
	 * @TODO FIXME duplicated / similar code in Repo ResultBuilder
	 *
	 * @return array
	 */
	private function filterEntitySerializationUsingLangCodes( array $serialization, $langCodes ) {
		if ( !empty( $langCodes ) ) {
			if ( array_key_exists( 'labels', $serialization ) ) {
				foreach ( $serialization['labels'] as $langCode => $languageArray ) {
					if ( !in_array( $langCode, $langCodes ) ) {
						unset( $serialization['labels'][$langCode] );
					}
				}
			}
			if ( array_key_exists( 'descriptions', $serialization ) ) {
				foreach ( $serialization['descriptions'] as $langCode => $languageArray ) {
					if ( !in_array( $langCode, $langCodes ) ) {
						unset( $serialization['descriptions'][$langCode] );
					}
				}
			}
			if ( array_key_exists( 'aliases', $serialization ) ) {
				foreach ( $serialization['aliases'] as $langCode => $languageArray ) {
					if ( !in_array( $langCode, $langCodes ) ) {
						unset( $serialization['aliases'][$langCode] );
					}
				}
			}
		}
		return $serialization;
	}

}
