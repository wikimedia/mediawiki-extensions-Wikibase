<?php

namespace Wikibase\Client\Serializer;

use Serializers\Exceptions\SerializationException;
use Serializers\Serializer;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\TermLanguageFallbackChain;

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
	 * @var TermLanguageFallbackChain[]
	 */
	private $termFallbackChains;

	/**
	 * @var string[]
	 */
	private $filterLangCodes;

	/**
	 * @param Serializer $entitySerializer
	 * @param PropertyDataTypeLookup $dataTypeLookup
	 * @param string[] $filterLangCodes
	 * @param TermLanguageFallbackChain[] $termFallbackChains
	 */
	public function __construct(
		Serializer $entitySerializer,
		PropertyDataTypeLookup $dataTypeLookup,
		EntityIdParser $entityIdParser,
		array $filterLangCodes,
		array $termFallbackChains
	) {
		parent::__construct( $dataTypeLookup, $entityIdParser );

		$this->filterLangCodes = $filterLangCodes;
		$this->entitySerializer = $entitySerializer;
		$this->termFallbackChains = $termFallbackChains;
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

		if ( !empty( $this->termFallbackChains ) ) {
			$serialization = $this->addEntitySerializationFallbackInfo( $serialization );
		}

		$serialization = $this->injectSerializationWithDataTypes( $serialization, 'claims/' );
		$serialization = $this->filterEntitySerializationUsingLangCodes( $serialization );

		return $this->omitEmptyArrays( $serialization );
	}

	/**
	 * @param array $serialization
	 *
	 * @todo FIXME duplicated code in Repo ResultBuilder
	 *
	 * @return array
	 */
	private function addEntitySerializationFallbackInfo( array $serialization ) {
		if ( array_key_exists( 'labels', $serialization ) ) {
			$serialization['labels'] = $this->getTermsSerializationWithFallbackInfo(
				$serialization['labels']
			);
		}
		if ( array_key_exists( 'descriptions', $serialization ) ) {
			$serialization['descriptions'] = $this->getTermsSerializationWithFallbackInfo(
				$serialization['descriptions']
			);
		}

		return $serialization;
	}

	/**
	 * @param array $serialization
	 *
	 * @todo FIXME duplicated / similar code in Repo ResultBuilder
	 *
	 * @return array
	 */
	private function getTermsSerializationWithFallbackInfo( array $serialization ) {
		$newSerialization = $serialization;
		foreach ( $this->termFallbackChains as $requestedLanguageCode => $fallbackChain ) {
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
	 * @todo FIXME duplicated / similar code in Repo ResultBuilder
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
