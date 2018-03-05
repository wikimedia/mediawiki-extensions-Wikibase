<?php

namespace Wikibase\Client\Serializer;

use Serializers\Exceptions\SerializationException;
use Serializers\Serializer;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\LanguageFallbackChain;

/**
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class ClientEntitySerializer extends ClientSerializer {

	/**
	 * @var Serializer
	 */
	private $entitySerializer;

	/**
	 * @var LanguageFallbackChain[]
	 */
	private $fallbackChains;

	/**
	 * @var string[]
	 */
	private $filterLangCodes;

	/**
	 * @param Serializer $entitySerializer
	 * @param PropertyDataTypeLookup $dataTypeLookup
	 * @param string[] $filterLangCodes
	 * @param LanguageFallbackChain[] $fallbackChains
	 */
	public function __construct(
		Serializer $entitySerializer,
		PropertyDataTypeLookup $dataTypeLookup,
		array $filterLangCodes,
		array $fallbackChains
	) {
		parent::__construct( $dataTypeLookup );

		$this->filterLangCodes = $filterLangCodes;
		$this->entitySerializer = $entitySerializer;
		$this->fallbackChains = $fallbackChains;
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
		$serialization = $this->entitySerializer->serialize( $entity );

		if ( !empty( $this->fallbackChains ) ) {
			$serialization = $this->addEntitySerializationFallbackInfo( $serialization );
		}

		$serialization = $this->injectSerializationWithDataTypes( $serialization, 'claims/' );
		$serialization = $this->filterEntitySerializationUsingLangCodes( $serialization );

		return $this->omitEmptyArrays( $serialization );
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
