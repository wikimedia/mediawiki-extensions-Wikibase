<?php

namespace Wikibase\Repo\Api;

use ApiResult;
use Serializers\Serializer;
use SiteLookup;
use Status;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Serialization\CallbackFactory;
use Wikibase\Lib\Serialization\SerializationModifier;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikibase\Repo\AddPageInfo;
use Wikibase\Repo\Dumpers\JsonDataTypeInjector;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Assert\Assert;

/**
 * Builder of MediaWiki ApiResult objects with various convenience functions for adding Wikibase concepts
 * and result parts to results in a uniform way.
 *
 * This class was introduced when Wikibase was reduced from 2 sets of serializers (lib & data-model) to one.
 * This class makes various modifications to the 1 standard serialization of Wikibase concepts for public exposure.
 * The resulting format can be seen as the public serialization of Wikibase concepts.
 *
 * Many concepts such as "tag name" relate to concepts explained within ApiResult.
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 * @author Daniel Kinzler
 */
class ResultBuilder {

	/**
	 * @var ApiResult
	 */
	private $result;

	/**
	 * @var EntityTitleStoreLookup
	 */
	private $entityTitleStoreLookup;

	/**
	 * @var SerializerFactory
	 */
	private $serializerFactory;

	/**
	 * @var Serializer
	 */
	private $entitySerializer;

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $dataTypeLookup;

	/**
	 * @var bool|null when special elements such as '_element' are needed by the formatter.
	 */
	private $addMetaData;

	/**
	 * @var SerializationModifier
	 */
	private $modifier;

	/**
	 * @var CallbackFactory
	 */
	private $callbackFactory;

	/**
	 * @var int
	 */
	private $missingEntityCounter = -1;

	/**
	 * @var JsonDataTypeInjector
	 */
	private $dataTypeInjector;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var AddPageInfo
	 */
	private $addPageInfo;

	/**
	 * @param ApiResult $result
	 * @param EntityTitleStoreLookup $entityTitleStoreLookup
	 * @param SerializerFactory $serializerFactory
	 * @param Serializer $entitySerializer
	 * @param SiteLookup $siteLookup
	 * @param PropertyDataTypeLookup $dataTypeLookup
	 * @param EntityIdParser $entityIdParser
	 * @param bool|null $addMetaData when special elements such as '_element' are needed
	 */
	public function __construct(
		ApiResult $result,
		EntityTitleStoreLookup $entityTitleStoreLookup,
		SerializerFactory $serializerFactory,
		Serializer $entitySerializer,
		SiteLookup $siteLookup,
		PropertyDataTypeLookup $dataTypeLookup,
		EntityIdParser $entityIdParser,
		$addMetaData = null
	) {
		$this->result = $result;
		$this->entityTitleStoreLookup = $entityTitleStoreLookup;
		$this->serializerFactory = $serializerFactory;
		$this->entitySerializer = $entitySerializer;
		$this->siteLookup = $siteLookup;
		$this->dataTypeLookup = $dataTypeLookup;
		$this->entityIdParser = $entityIdParser;
		$this->addMetaData = $addMetaData;

		$this->modifier = new SerializationModifier();
		$this->callbackFactory = new CallbackFactory();

		$this->dataTypeInjector = new JsonDataTypeInjector(
			$this->modifier,
			$this->callbackFactory,
			$dataTypeLookup,
			$entityIdParser
		);

		$this->addPageInfo = new AddPageInfo( $this->entityTitleStoreLookup );
	}

	/**
	 * Mark the ApiResult as successful.
	 *
	 * { "success": 1 }
	 *
	 * @param bool|int|null $success
	 */
	public function markSuccess( $success = true ) {
		$value = (int)$success;

		Assert::parameter(
			$value == 1 || $value == 0,
			'$success',
			'$success must evaluate to either 1 or 0 when casted to integer'
		);

		$this->result->addValue( null, 'success', $value );
	}

	/**
	 * Adds a list of values for the given path and name.
	 * This automatically sets the indexed tag name, if appropriate.
	 *
	 * To set atomic values or records, use setValue() or appendValue().
	 *
	 * @see ApiResult::addValue
	 * @see ApiResult::setIndexedTagName
	 * @see ResultBuilder::setValue()
	 * @see ResultBuilder::appendValue()
	 *
	 * @param array|string|null $path
	 * @param string $name
	 * @param array $values
	 * @param string $tag tag name to use for elements of $values if not already present
	 */
	public function setList( $path, $name, array $values, $tag ) {
		$this->checkPathType( $path );
		Assert::parameterType( 'string', $name, '$name' );
		Assert::parameterType( 'string', $tag, '$tag' );

		if ( $this->addMetaData ) {
			if ( !array_key_exists( ApiResult::META_TYPE, $values ) ) {
				ApiResult::setArrayType( $values, 'array' );
			}
			if ( !array_key_exists( ApiResult::META_INDEXED_TAG_NAME, $values ) ) {
				ApiResult::setIndexedTagName( $values, $tag );
			}
		}

		$this->result->addValue( $path, $name, $values );
	}

	/**
	 * Set an atomic value (or record) for the given path and name.
	 * If the value is an array, it should be a record (associative), not a list.
	 * For adding lists, use setList().
	 *
	 * @see ResultBuilder::setList()
	 * @see ResultBuilder::appendValue()
	 * @see ApiResult::addValue
	 *
	 * @param array|string|null $path
	 * @param string $name
	 * @param mixed $value
	 */
	public function setValue( $path, $name, $value ) {
		$this->checkPathType( $path );
		Assert::parameterType( 'string', $name, '$name' );
		$this->checkValueIsNotList( $value );

		$this->result->addValue( $path, $name, $value );
	}

	/**
	 * Appends a value to the list at the given path.
	 * This automatically sets the indexed tag name, if appropriate.
	 *
	 * If the value is an array, it should be associative, not a list.
	 * For adding lists, use setList().
	 *
	 * @see ResultBuilder::setList()
	 * @see ResultBuilder::setValue()
	 * @see ApiResult::addValue
	 * @see ApiResult::setIndexedTagName_internal
	 *
	 * @param array|string|null $path
	 * @param int|string|null $key the key to use when appending, or null for automatic.
	 * May be ignored even if given, based on $this->addMetaData.
	 * @param mixed $value
	 * @param string $tag tag name to use for $value in indexed mode
	 */
	public function appendValue( $path, $key, $value, $tag ) {
		$this->checkPathType( $path );
		$this->checkKeyType( $key );
		Assert::parameterType( 'string', $tag, '$tag' );
		$this->checkValueIsNotList( $value );

		$this->result->addValue( $path, $key, $value );
		if ( $this->addMetaData ) {
			$this->result->addIndexedTagName( $path, $tag );
		}
	}

	/**
	 * @param array|string|null $path
	 */
	private function checkPathType( $path ) {
		Assert::parameter(
			is_string( $path ) || is_array( $path ) || $path === null,
			'$path',
			'$path must be an array (or null)'
		);
	}

	/**
	 * @param int|string|null $key the key to use when appending, or null for automatic.
	 */
	private function checkKeyType( $key ) {
		Assert::parameter(
			is_string( $key ) || is_int( $key ) || $key === null,
			'$key',
			'$key must be an array (or null)'
		);
	}

	/**
	 * @param mixed $value
	 */
	private function checkValueIsNotList( $value ) {
		Assert::parameter(
			!( is_array( $value ) && isset( $value[0] ) ),
			'$value',
			'$value must not be a list'
		);
	}

	/**
	 * Get serialized entity for the EntityRevision and add it to the result alongside other needed properties.
	 *
	 *
	 * @param string|null $sourceEntityIdSerialization EntityId used to retrieve $entityRevision
	 *        Used as the key for the entity in the 'entities' structure and for adding redirect
	 *     info Will default to the entity's serialized ID if null. If given this must be the
	 *     entity id before any redirects were resolved.
	 * @param EntityRevision $entityRevision
	 * @param string[]|string $props a list of fields to include, or "all"
	 * @param string[]|null $filterSiteIds A list of site IDs to filter by
	 * @param string[] $filterLangCodes A list of language codes to filter by
	 * @param TermLanguageFallbackChain[] $termFallbackChains with keys of the origional language
	 */
	public function addEntityRevision(
		$sourceEntityIdSerialization,
		EntityRevision $entityRevision,
		$props = 'all',
		array $filterSiteIds = null,
		array $filterLangCodes = [],
		array $termFallbackChains = []
	) {
		$entity = $entityRevision->getEntity();
		$entityId = $entity->getId();

		if ( $sourceEntityIdSerialization === null ) {
			$sourceEntityIdSerialization = $entityId->getSerialization();
		}

		$record = [];

		// If there are no props defined only return type and id..
		// @phan-suppress-next-line PhanTypeComparisonToArray
		if ( $props === [] ) {
			$record = $this->addEntityInfoToRecord( $record, $entityId );
		} else {
			// @phan-suppress-next-line PhanTypeMismatchArgumentInternal False positive
			if ( $props == 'all' || in_array( 'info', $props ) ) {
				$record = $this->addPageInfo->add( $record, $entityRevision );
			}
			if ( $sourceEntityIdSerialization !== $entityId->getSerialization() ) {
				$record = $this->addEntityRedirectInfoToRecord( $record, $sourceEntityIdSerialization, $entityId );
			}

			$entitySerialization = $this->getModifiedEntityArray(
				$entity,
				$props,
				$filterSiteIds,
				$filterLangCodes,
				$termFallbackChains
			);

			$record = array_merge( $record, $entitySerialization );
		}

		$this->appendValue( [ 'entities' ], $sourceEntityIdSerialization, $record, 'entity' );
		if ( $this->addMetaData ) {
			$this->result->addArrayType( [ 'entities' ], 'kvp', 'id' );
			$this->result->addValue(
				[ 'entities' ],
				ApiResult::META_KVP_MERGE,
				true,
				ApiResult::OVERRIDE
			);
		}
	}

	private function addEntityInfoToRecord( array $record, EntityId $entityId ): array {
		$record['id'] = $entityId->getSerialization();
		$record['type'] = $entityId->getEntityType();
		return $record;
	}

	private function addEntityRedirectInfoToRecord( array $record, $sourceEntityIdSerialization, EntityId $entityId ): array {
		$record['redirects'] = [
			'from' => $sourceEntityIdSerialization,
			'to' => $entityId->getSerialization(),
		];
		return $record;
	}

	/**
	 * Gets the standard serialization of an EntityDocument and modifies it in a standard way.
	 *
	 * This code was created for Items and Properties and since new entity types have been introduced
	 * it may not work in the desired way.
	 * @see https://phabricator.wikimedia.org/T249206
	 *
	 * @see ResultBuilder::addEntityRevision
	 *
	 * @param EntityDocument $entity
	 * @param array|string $props
	 * @param string[]|null $filterSiteIds
	 * @param string[] $filterLangCodes
	 * @param TermLanguageFallbackChain[] $termFallbackChains
	 *
	 * @return array
	 */
	public function getModifiedEntityArray(
		EntityDocument $entity,
		$props,
		?array $filterSiteIds,
		array $filterLangCodes,
		array $termFallbackChains
	) {
		$serialization = $this->entitySerializer->serialize( $entity );

		$serialization = $this->filterEntitySerializationUsingProps( $serialization, $props );

		if ( $props == 'all' || in_array( 'sitelinks/urls', $props ) ) {
			$serialization = $this->injectEntitySerializationWithSiteLinkUrls( $serialization );
		}
		$serialization = $this->sortEntitySerializationSiteLinks( $serialization );
		$serialization = $this->dataTypeInjector->injectEntitySerializationWithDataTypes( $serialization );
		$serialization = $this->filterEntitySerializationUsingSiteIds( $serialization, $filterSiteIds );
		if ( !empty( $termFallbackChains ) ) {
			$serialization = $this->addEntitySerializationFallbackInfo( $serialization, $termFallbackChains );
		}
		$serialization = $this->filterEntitySerializationUsingLangCodes(
			$serialization,
			$filterLangCodes
		);

		if ( $this->addMetaData ) {
			$serialization = $this->getEntitySerializationWithMetaData( $serialization );
		}

		return $serialization;
	}

	/**
	 * @param array $serialization
	 * @param string|array $props
	 *
	 * @return array
	 */
	private function filterEntitySerializationUsingProps( array $serialization, $props ) {
		if ( $props !== 'all' ) {
			if ( !in_array( 'labels', $props ) ) {
				unset( $serialization['labels'] );
			}
			if ( !in_array( 'descriptions', $props ) ) {
				unset( $serialization['descriptions'] );
			}
			if ( !in_array( 'aliases', $props ) ) {
				unset( $serialization['aliases'] );
			}
			if ( !in_array( 'claims', $props ) ) {
				unset( $serialization['claims'] );
			}
			if ( !in_array( 'sitelinks', $props ) ) {
				unset( $serialization['sitelinks'] );
			}
		}
		return $serialization;
	}

	private function injectEntitySerializationWithSiteLinkUrls( array $serialization ) {
		if ( isset( $serialization['sitelinks'] ) ) {
			$serialization['sitelinks'] = $this->getSiteLinkListArrayWithUrls( $serialization['sitelinks'] );
		}
		return $serialization;
	}

	private function sortEntitySerializationSiteLinks( array $serialization ) {
		if ( isset( $serialization['sitelinks'] ) ) {
			ksort( $serialization['sitelinks'] );
		}
		return $serialization;
	}

	private function filterEntitySerializationUsingSiteIds(
		array $serialization,
		array $siteIds = null
	) {
		if ( !empty( $siteIds ) && array_key_exists( 'sitelinks', $serialization ) ) {
			foreach ( $serialization['sitelinks'] as $siteId => $siteLink ) {
				if ( is_array( $siteLink ) && !in_array( $siteLink['site'], $siteIds ) ) {
					unset( $serialization['sitelinks'][$siteId] );
				}
			}
		}
		return $serialization;
	}

	/**
	 * @param array $serialization
	 * @param TermLanguageFallbackChain[] $termFallbackChains
	 *
	 * @return array
	 */
	private function addEntitySerializationFallbackInfo(
		array $serialization,
		array $termFallbackChains
	) {
		if ( isset( $serialization['labels'] ) ) {
			$serialization['labels'] = $this->getTermsSerializationWithFallbackInfo(
				$serialization['labels'],
				$termFallbackChains
			);
		}

		if ( isset( $serialization['descriptions'] ) ) {
			$serialization['descriptions'] = $this->getTermsSerializationWithFallbackInfo(
				$serialization['descriptions'],
				$termFallbackChains
			);
		}

		return $serialization;
	}

	/**
	 * @param array $serialization
	 * @param TermLanguageFallbackChain[] $termFallbackChains
	 *
	 * @return array
	 */
	private function getTermsSerializationWithFallbackInfo(
		array $serialization,
		array $termFallbackChains
	) {
		$newSerialization = $serialization;
		foreach ( $termFallbackChains as $requestedLanguageCode => $fallbackChain ) {
			if ( !array_key_exists( $requestedLanguageCode, $serialization ) ) {
				$fallbackSerialization = $fallbackChain->extractPreferredValue( $serialization );
				if ( $fallbackSerialization !== null ) {
					if ( $fallbackSerialization['source'] !== null ) {
						$fallbackSerialization['source-language'] = $fallbackSerialization['source'];
					}
					unset( $fallbackSerialization['source'] );
					if ( $requestedLanguageCode !== $fallbackSerialization['language'] ) {
						$fallbackSerialization['for-language'] = $requestedLanguageCode;
					}
					$newSerialization[$requestedLanguageCode] = $fallbackSerialization;
				}
			}
		}
		return $newSerialization;
	}

	/**
	 * @param array $serialization
	 * @param string[] $langCodes
	 *
	 * @return array
	 */
	private function filterEntitySerializationUsingLangCodes(
		array $serialization,
		array $langCodes
	) {
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

	private function getEntitySerializationWithMetaData( array $serialization ) {
		$serializeEmptyListsAsObjects = WikibaseRepo::getSettings()->getSetting( 'tmpSerializeEmptyListsAsObjects' );
		$modifications = [];

		$makeIdKvpCallback = $this->callbackFactory->getCallbackToSetArrayType( 'kvp', 'id' );
		$makeLanguageKvpCallback = $this->callbackFactory->getCallbackToSetArrayType( 'kvp', 'language' );
		$makeSiteKvpCallback = $this->callbackFactory->getCallbackToSetArrayType( 'kvp', 'site' );

		$modifications['aliases'][] = $makeIdKvpCallback;
		$modifications['claims/*/*/references/*/snaks'][] = $makeIdKvpCallback;
		$modifications['claims/*/*/qualifiers'][] = $makeIdKvpCallback;
		$modifications['claims'][] = $makeIdKvpCallback;
		$modifications['descriptions'][] = $makeLanguageKvpCallback;
		$modifications['labels'][] = $makeLanguageKvpCallback;
		$modifications['sitelinks'][] = $makeSiteKvpCallback;

		if ( $serializeEmptyListsAsObjects ) {
			$modifications['*/*/claims/*/*/references/*/snaks'][] = $makeIdKvpCallback;
			$modifications['*/*/claims/*/*/qualifiers'][] = $makeIdKvpCallback;
			$modifications['*/*/claims'][] = $makeIdKvpCallback;
		}

		$kvpMergeCallback = function( $array ) {
			if ( is_array( $array ) ) {
				$array[ApiResult::META_KVP_MERGE] = true;
			}
			return $array;
		};

		$modifications['descriptions'][] = $kvpMergeCallback;
		$modifications['labels'][] = $kvpMergeCallback;
		$modifications['sitelinks'][] = $kvpMergeCallback;

		$indexLabelCallback = $this->callbackFactory->getCallbackToIndexTags( 'label' );
		$indexDescriptionCallback = $this->callbackFactory->getCallbackToIndexTags( 'description' );
		$indexAliasCallback = $this->callbackFactory->getCallbackToIndexTags( 'alias' );
		$indexLanguageCallback = $this->callbackFactory->getCallbackToIndexTags( 'language' );
		$indexBadgeCallback = $this->callbackFactory->getCallbackToIndexTags( 'badge' );
		$indexSitelinkCallback = $this->callbackFactory->getCallbackToIndexTags( 'sitelink' );
		$indexQualifiersCallback = $this->callbackFactory->getCallbackToIndexTags( 'qualifiers' );
		$indexPropertyCallback = $this->callbackFactory->getCallbackToIndexTags( 'property' );
		$indexSnakCallback = $this->callbackFactory->getCallbackToIndexTags( 'snak' );
		$indexReferenceCallback = $this->callbackFactory->getCallbackToIndexTags( 'reference' );
		$indexClaimCallback = $this->callbackFactory->getCallbackToIndexTags( 'claim' );

		$modifications['labels'][] = $indexLabelCallback;
		$modifications['descriptions'][] = $indexDescriptionCallback;
		$modifications['aliases/*'][] = $indexAliasCallback;
		$modifications['aliases'][] = $indexLanguageCallback;
		$modifications['sitelinks/*/badges'][] = $indexBadgeCallback;
		$modifications['sitelinks'][] = $indexSitelinkCallback;
		$modifications['claims/*/*/qualifiers/*'][] = $indexQualifiersCallback;
		$modifications['claims/*/*/qualifiers'][] = $indexPropertyCallback;
		$modifications['claims/*/*/qualifiers-order'][] = $indexPropertyCallback;
		$modifications['claims/*/*/references/*/snaks/*'][] = $indexSnakCallback;
		$modifications['claims/*/*/references/*/snaks'][] = $indexPropertyCallback;
		$modifications['claims/*/*/references/*/snaks-order'][] = $indexPropertyCallback;
		$modifications['claims/*/*/references'][] = $indexReferenceCallback;
		$modifications['claims/*'][] = $indexClaimCallback;
		$modifications['claims'][] = $indexPropertyCallback;

		if ( $serializeEmptyListsAsObjects ) {
			$modifications['*/*/claims/*/*/qualifiers/*'][] = $indexQualifiersCallback;
			$modifications['*/*/claims/*/*/qualifiers'][] = $indexPropertyCallback;
			$modifications['*/*/claims/*/*/qualifiers-order'][] = $indexPropertyCallback;
			$modifications['*/*/claims/*/*/references/*/snaks/*'][] = $indexSnakCallback;
			$modifications['*/*/claims/*/*/references/*/snaks'][] = $indexPropertyCallback;
			$modifications['*/*/claims/*/*/references/*/snaks-order'][] = $indexPropertyCallback;
			$modifications['*/*/claims/*/*/references'][] = $indexReferenceCallback;
			$modifications['*/*/claims/*'][] = $indexClaimCallback;
			$modifications['*/*/claims'][] = $indexPropertyCallback;
		}

		return $this->modifier->modifyUsingCallbacks( $serialization, $modifications );
	}

	/**
	 * Get serialized information for the EntityId and add them to result
	 *
	 * @param EntityId $entityId
	 * @param string|array|null $path
	 */
	public function addBasicEntityInformation( EntityId $entityId, $path ) {
		$this->setValue( $path, 'id', $entityId->getSerialization() );
		$this->setValue( $path, 'type', $entityId->getEntityType() );
	}

	/**
	 * Get serialized labels and add them to result
	 *
	 * @param TermList $labels the labels to insert in the result
	 * @param array|string $path where the data is located
	 */
	public function addLabels( TermList $labels, $path ) {
		$this->addTermList( $labels, 'labels', 'label', $path );
	}

	/**
	 * Adds fake serialization to show a label has been removed
	 *
	 * @param string $language
	 * @param array|string $path where the data is located
	 */
	public function addRemovedLabel( $language, $path ) {
		$this->addRemovedTerm( $language, 'labels', 'label', $path );
	}

	/**
	 * Get serialized descriptions and add them to result
	 *
	 * @param TermList $descriptions the descriptions to insert in the result
	 * @param array|string $path where the data is located
	 */
	public function addDescriptions( TermList $descriptions, $path ) {
		$this->addTermList( $descriptions, 'descriptions', 'description', $path );
	}

	/**
	 * Adds fake serialization to show a label has been removed
	 *
	 * @param string $language
	 * @param array|string $path where the data is located
	 */
	public function addRemovedDescription( $language, $path ) {
		$this->addRemovedTerm( $language, 'descriptions', 'description', $path );
	}

	/**
	 * Get serialized TermList and add it to the result
	 *
	 * @param TermList $termList
	 * @param string $name
	 * @param string $tag
	 * @param array|string $path where the data is located
	 */
	private function addTermList( TermList $termList, $name, $tag, $path ) {
		$serializer = $this->serializerFactory->newTermListSerializer();
		$value = $serializer->serialize( $termList );
		if ( $this->addMetaData ) {
			ApiResult::setArrayType( $value, 'kvp', 'language' );
			$value[ApiResult::META_KVP_MERGE] = true;
		}
		$this->setList( $path, $name, $value, $tag );
	}

	/**
	 * Adds fake serialization to show a term has been removed
	 *
	 * @param string $language
	 * @param string $name
	 * @param string $tag
	 * @param array|string $path where the data is located
	 */
	private function addRemovedTerm( $language, $name, $tag, $path ) {
		$value = [
			$language => [
				'language' => $language,
				'removed' => '',
			],
		];
		if ( $this->addMetaData ) {
			ApiResult::setArrayType( $value, 'kvp', 'language' );
			$value[ApiResult::META_KVP_MERGE] = true;
		}
		$this->setList( $path, $name, $value, $tag );
	}

	/**
	 * Get serialized AliasGroupList and add it to result
	 *
	 * @param AliasGroupList $aliasGroupList the AliasGroupList to set in the result
	 * @param array|string $path where the data is located
	 */
	public function addAliasGroupList( AliasGroupList $aliasGroupList, $path ) {
		$serializer = $this->serializerFactory->newAliasGroupListSerializer();
		$values = $serializer->serialize( $aliasGroupList );

		if ( $this->addMetaData ) {
			$values = $this->modifier->modifyUsingCallbacks(
				$values,
				[
					'' => $this->callbackFactory->getCallbackToSetArrayType( 'kvp', 'id' ),
					'*' => $this->callbackFactory->getCallbackToIndexTags( 'alias' ),
				]
			);
		}

		$this->setList( $path, 'aliases', $values, 'language' );
		ApiResult::setArrayType( $values, 'kvp', 'id' );
	}

	/**
	 * Get serialized sitelinks and add them to result
	 *
	 * @todo use a SiteLinkListSerializer when created in DataModelSerialization here
	 *
	 * @param SiteLinkList $siteLinkList the site links to insert in the result
	 * @param array|string $path where the data is located
	 * @param bool $addUrl
	 */
	public function addSiteLinkList( SiteLinkList $siteLinkList, $path, $addUrl = false ) {
		$serializer = $this->serializerFactory->newSiteLinkSerializer();

		$values = [];
		foreach ( $siteLinkList->toArray() as $siteLink ) {
			$values[$siteLink->getSiteId()] = $serializer->serialize( $siteLink );
		}

		if ( $addUrl ) {
			$values = $this->getSiteLinkListArrayWithUrls( $values );
		}

		if ( $this->addMetaData ) {
			$values = $this->getSiteLinkListArrayWithMetaData( $values );
		}

		$this->setList( $path, 'sitelinks', $values, 'sitelink' );
	}

	private function getSiteLinkListArrayWithUrls( array $array ) {
		$siteLookup = $this->siteLookup;
		$addUrlCallback = function( $array ) use ( $siteLookup ) {
			$site = $siteLookup->getSite( $array['site'] );
			if ( $site !== null ) {
				$array['url'] = $site->getPageUrl( $array['title'] );
			}
			return $array;
		};
		return $this->modifier->modifyUsingCallbacks( $array, [ '*' => $addUrlCallback ] );
	}

	private function getSiteLinkListArrayWithMetaData( array $array ) {
		$array[ApiResult::META_KVP_MERGE] = true;
		return $this->modifier->modifyUsingCallbacks(
			$array,
			[
				'' => $this->callbackFactory->getCallbackToSetArrayType( 'kvp', 'site' ),
				'*/badges' => $this->callbackFactory->getCallbackToIndexTags( 'badge' ),
			]
		);
	}

	/**
	 * Adds fake serialization to show a sitelink has been removed
	 *
	 * @param SiteLinkList $siteLinkList
	 * @param array|string $path where the data is located
	 */
	public function addRemovedSiteLinks( SiteLinkList $siteLinkList, $path ) {
		$serializer = $this->serializerFactory->newSiteLinkSerializer();
		$values = [];
		foreach ( $siteLinkList->toArray() as $siteLink ) {
			$value = $serializer->serialize( $siteLink );
			$value['removed'] = '';
			$values[$siteLink->getSiteId()] = $value;
		}
		if ( $this->addMetaData ) {
			$values = $this->modifier->modifyUsingCallbacks(
				$values,
				[ '' => $this->callbackFactory->getCallbackToSetArrayType( 'kvp', 'site' ) ]
			);
			$values[ApiResult::META_KVP_MERGE] = true;
		}
		$this->setList( $path, 'sitelinks', $values, 'sitelink' );
	}

	/**
	 * Get serialized claims and add them to result
	 *
	 * @param StatementList $statements the labels to set in the result
	 * @param array|string $path where the data is located
	 * @param array|string $props a list of fields to include, or "all"
	 */
	public function addStatements( StatementList $statements, $path, $props = 'all' ) {
		$serializer = $this->serializerFactory->newStatementListSerializer();

		$values = $serializer->serialize( $statements );

		if ( is_array( $props ) && !in_array( 'references', $props ) ) {
			$values = $this->modifier->modifyUsingCallbacks(
				$values,
				[ '*/*' => function ( $array ) {
					unset( $array['references'] );
					return $array;
				} ]
			);
		}

		$values = $this->getArrayWithAlteredClaims( $values, '*/*/' );

		if ( $this->addMetaData ) {
			$values = $this->getClaimsArrayWithMetaData( $values, '*/*/' );
			$values = $this->modifier->modifyUsingCallbacks( $values, [
				'' => $this->callbackFactory->getCallbackToSetArrayType( 'kvp', 'id' ),
				'*' => $this->callbackFactory->getCallbackToIndexTags( 'claim' ),
			] );
		}

		$values = $this->modifier->modifyUsingCallbacks( $values, [
			'*/*/mainsnak' => $this->callbackFactory->getCallbackToAddDataTypeToSnak( $this->dataTypeLookup, $this->entityIdParser ),
		] );

		if ( $this->addMetaData ) {
			ApiResult::setArrayType( $values, 'kvp', 'id' );
		}

		$this->setList( $path, 'claims', $values, 'property' );
	}

	/**
	 * Get serialized claim and add it to result
	 *
	 * @param Statement $statement
	 */
	public function addStatement( Statement $statement ) {
		$serializer = $this->serializerFactory->newStatementSerializer();

		//TODO: this is currently only used to add a Claim as the top level structure,
		//      with a null path and a fixed name. Would be nice to also allow claims
		//      to be added to a list, using a path and a id key or index.

		$value = $serializer->serialize( $statement );

		$value = $this->getArrayWithAlteredClaims( $value );

		if ( $this->addMetaData ) {
			$value = $this->getClaimsArrayWithMetaData( $value );
		}

		$value = $this->modifier->modifyUsingCallbacks( $value, [
			'mainsnak' => $this->callbackFactory->getCallbackToAddDataTypeToSnak( $this->dataTypeLookup, $this->entityIdParser ),
		] );

		$this->setValue( null, 'claim', $value );
	}

	/**
	 * @param array $array
	 * @param string $claimPath to the claim array/arrays with trailing /
	 *
	 * @return array
	 */
	private function getArrayWithAlteredClaims(
		array $array,
		$claimPath = ''
	) {
		$groupedCallback = $this->callbackFactory->getCallbackToAddDataTypeToSnaksGroupedByProperty(
			$this->dataTypeLookup,
			$this->entityIdParser
		);
		return $this->modifier->modifyUsingCallbacks( $array, [
			$claimPath . 'references/*/snaks' => $groupedCallback,
			$claimPath . 'qualifiers' => $groupedCallback,
			$claimPath . 'mainsnak' => $this->callbackFactory->getCallbackToAddDataTypeToSnak(
				$this->dataTypeLookup,
				$this->entityIdParser
			),
		] );
	}

	/**
	 * @param array $array
	 * @param string $claimPath to the claim array/arrays with trailing /
	 *
	 * @return array
	 */
	private function getClaimsArrayWithMetaData( array $array, $claimPath = '' ) {
		return $this->modifier->modifyUsingCallbacks( $array, [
			$claimPath . 'references/*/snaks/*' => [
				$this->callbackFactory->getCallbackToIndexTags( 'snak' ),
			],
			$claimPath . 'references/*/snaks' => [
				$this->callbackFactory->getCallbackToSetArrayType( 'kvp', 'id' ),
				$this->callbackFactory->getCallbackToIndexTags( 'property' ),
			],
			$claimPath . 'references/*/snaks-order' => [
				$this->callbackFactory->getCallbackToIndexTags( 'property' ),
			],
			$claimPath . 'references' => [
				$this->callbackFactory->getCallbackToIndexTags( 'reference' ),
			],
			$claimPath . 'qualifiers/*' => [
				$this->callbackFactory->getCallbackToIndexTags( 'qualifiers' ),
			],
			$claimPath . 'qualifiers' => [
				$this->callbackFactory->getCallbackToSetArrayType( 'kvp', 'id' ),
				$this->callbackFactory->getCallbackToIndexTags( 'property' ),
			],
			$claimPath . 'qualifiers-order' => [
				$this->callbackFactory->getCallbackToIndexTags( 'property' ),
			],
			$claimPath . 'mainsnak' => [
				$this->callbackFactory->getCallbackToAddDataTypeToSnak( $this->dataTypeLookup, $this->entityIdParser ),
			],
		] );
	}

	/**
	 * Get serialized reference and add it to result
	 *
	 * @param Reference $reference
	 */
	public function addReference( Reference $reference ) {
		$serializer = $this->serializerFactory->newReferenceSerializer();

		//TODO: this is currently only used to add a Reference as the top level structure,
		//      with a null path and a fixed name. Would be nice to also allow references
		//      to be added to a list, using a path and a id key or index.

		$value = $serializer->serialize( $reference );

		$value = $this->modifier->modifyUsingCallbacks( $value, [
			'snaks' => $this->callbackFactory->getCallbackToAddDataTypeToSnaksGroupedByProperty(
				$this->dataTypeLookup,
				$this->entityIdParser
			),
		] );

		if ( $this->addMetaData ) {
			$value = $this->getReferenceArrayWithMetaData( $value );
		}

		$this->setValue( null, 'reference', $value );
	}

	private function getReferenceArrayWithMetaData( array $array ) {
		return $this->modifier->modifyUsingCallbacks( $array, [
			'snaks-order' => function ( $array ) {
				ApiResult::setIndexedTagName( $array, 'property' );
				return $array;
			},
			'snaks' => function ( $array ) {
				foreach ( $array as &$snakGroup ) {
					if ( is_array( $snakGroup ) ) {
						ApiResult::setArrayType( $array, 'array' );
						ApiResult::setIndexedTagName( $snakGroup, 'snak' );
					}
				}
				ApiResult::setArrayType( $array, 'kvp', 'id' );
				ApiResult::setIndexedTagName( $array, 'property' );
				return $array;
			},
		] );
	}

	/**
	 * Add an entry for a missing entity...
	 *
	 * @param string|null $key The key under which to place the missing entity in the 'entities'
	 *        structure. If null, defaults to the 'id' field in $missingDetails if that is set;
	 *        otherwise, it defaults to using a unique negative number.
	 * @param array $missingDetails array containing key value pair missing details
	 */
	public function addMissingEntity( $key, array $missingDetails ) {
		if ( $key === null && isset( $missingDetails['id'] ) ) {
			$key = $missingDetails['id'];
		}

		if ( $key === null ) {
			$key = $this->missingEntityCounter;
		}

		$this->appendValue(
			'entities',
			$key,
			array_merge( $missingDetails, [ 'missing' => "" ] ),
			'entity'
		);

		if ( $this->addMetaData ) {
			$this->result->addIndexedTagName( 'entities', 'entity' );
			$this->result->addArrayType( [ 'entities' ], 'kvp', 'id' );
			$this->result->addValue(
				[ 'entities' ],
				ApiResult::META_KVP_MERGE,
				true,
				ApiResult::OVERRIDE
			);
		}

		$this->missingEntityCounter--;
	}

	/**
	 * @param string $from
	 * @param string $to
	 * @param string $name
	 */
	public function addNormalizedTitle( $from, $to, $name = 'n' ) {
		$this->setValue(
			'normalized',
			$name,
			[ 'from' => $from, 'to' => $to ]
		);
	}

	/**
	 * Adds the ID of the new revision from the Status object to the API result structure.
	 * The status value is expected to be structured in the way that EditEntity::attemptSave()
	 * resp WikiPage::doUserEditContent() do it: as an array, with an EntityRevision object in the
	 *  'revision' field. If $oldRevId is set and the latest edit was null, a 'nochange' flag
	 *  is also added.
	 *
	 * If no revision is found in the Status object, this method does nothing.
	 *
	 * @see ApiResult::addValue()
	 *
	 * @param Status $status The status to get the revision ID from.
	 * @param string|null|array $path Where in the result to put the revision id
	 * @param int|null $oldRevId The id of the latest revision of the entity before
	 *        the last (possibly null) edit
	 */
	public function addRevisionIdFromStatusToResult( Status $status, $path, $oldRevId = null ) {
		$value = $status->getValue();

		if ( isset( $value['revision'] ) ) {
			if ( $value['revision'] instanceof EntityRevision ) {
				// Should always be the case, but sanity check
				$revisionId = $value['revision']->getRevisionId();
			} else {
				$revisionId = 0;
			}

			$this->setValue( $path, 'lastrevid', $revisionId );

			if ( $oldRevId && $oldRevId === $revisionId ) {
				// like core's ApiEditPage
				$this->setValue( $path, 'nochange', true );
			}
		}
	}

}
