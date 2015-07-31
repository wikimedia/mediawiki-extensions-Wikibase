<?php

namespace Wikibase\Repo\Api;

use ApiResult;
use Revision;
use SiteStore;
use Status;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Term\TermList;
use Wikibase\EntityRevision;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikimedia\Assert\Assert;

/**
 * Builder for Api Results
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 * @author Daniel Kinzler
 */
class ResultBuilder {

	/**
	 * @var ApiResult
	 */
	private $result;

	/**
	 * @var int
	 */
	private $missingEntityCounter;

	/**
	 * @var SerializerFactory
	 */
	private $serializerFactory;

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var SiteStore
	 */
	private $siteStore;

	/**
	 * @var SerializationOptions
	 */
	private $options;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $dataTypeLookup;

	/**
	 * @var SerializationModifier
	 */
	private $modifier;

	/**
	 * @var bool when special elements such as '_element' are needed by the formatter.
	 */
	private $isRawMode;

	/**
	 * @param ApiResult $result
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param SerializerFactory $serializerFactory
	 * @param SiteStore $siteStore
	 * @param PropertyDataTypeLookup $dataTypeLookup
	 * @param bool $isRawMode when special elements such as '_element' are needed by the formatter.
	 */
	public function __construct(
		ApiResult $result,
		EntityTitleLookup $entityTitleLookup,
		SerializerFactory $serializerFactory,
		SiteStore $siteStore,
		PropertyDataTypeLookup $dataTypeLookup,
		$isRawMode
	) {
		$this->result = $result;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->serializerFactory = $serializerFactory;
		$this->missingEntityCounter = -1;
		$this->isRawMode = $isRawMode;
		$this->siteStore = $siteStore;
		$this->dataTypeLookup = $dataTypeLookup;
		$this->modifier = new SerializationModifier();
	}

	/**
	 * @since 0.5
	 *
	 * @param $success bool|int|null
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
	 * @since 0.5
	 *
	 * @param $path array|string|null
	 * @param $name string
	 * @param $values array
	 * @param string $tag tag name to use for elements of $values
	 */
	public function setList( $path, $name, array $values, $tag ) {
		$this->checkPathType( $path );
		Assert::parameterType( 'string', $name, '$name' );
		Assert::parameterType( 'string', $tag, '$tag' );

		if ( $this->isRawMode ) {
			// Unset first, so we don't make the tag name an actual value.
			// We'll be setting this to $tag by calling setIndexedTagName().
			unset( $values['_element'] );

			$values = array_values( $values );
			ApiResult::setIndexedTagName( $values, $tag );
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
	 * @since 0.5
	 *
	 * @param $path array|string|null
	 * @param $name string
	 * @param $value mixed
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
	 * @since 0.5
	 *
	 * @param $path array|string|null
	 * @param $key int|string|null the key to use when appending, or null for automatic.
	 * May be ignored even if given, based on $this->result->getIsRawMode().
	 * @param $value mixed
	 * @param string $tag tag name to use for $value in indexed mode
	 */
	public function appendValue( $path, $key, $value, $tag ) {
		$this->checkPathType( $path );
		$this->checkKeyType( $key );
		Assert::parameterType( 'string', $tag, '$tag' );
		$this->checkValueIsNotList( $value );

		if ( $this->isRawMode ) {
			$key = null;
		}

		$this->result->addValue( $path, $key, $value );
		$this->result->addIndexedTagName( $path, $tag );
	}

	/**
	 * @param array|string|null $path
	 */
	private function checkPathType( $path ) {
		Assert::parameter(
			is_string( $path ) || is_array( $path ) || is_null( $path ),
			'$path',
			'$path must be an array (or null)'
		);
	}

	/**
	 * @param $key int|string|null the key to use when appending, or null for automatic.
	 */
	private function checkKeyType( $key ) {
		Assert::parameter(
			is_string( $key ) || is_int( $key ) || is_null( $key ),
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
	 * Get serialized entity for the EntityRevision and add it to the result
	 *
	 * @param string|null $sourceEntityIdSerialization EntityId used to retreive $entityRevision
	 *        Used as the key for the entity in the 'entities' structure and for adding redirect
	 *     info Will default to the entity's serialized ID if null. If given this must be the
	 *     entity id before any redirects were resolved.
	 * @param EntityRevision $entityRevision
	 * @param string[]|string $props a list of fields to include, or "all"
	 * @param string[] $filterSiteIds A list of site IDs to filter by
	 * @param string[] $filterLangCodes A list of language codes to filter by
	 * @param LanguageFallbackChain[] $fallbackChains with keys of the origional language
	 *
	 * @since 0.5
	 */
	public function addEntityRevision(
		$sourceEntityIdSerialization,
		EntityRevision $entityRevision,
		$props = 'all',
		$filterSiteIds = array(),
		$filterLangCodes = array(),
		$fallbackChains = array()
	) {
		$entity = $entityRevision->getEntity();
		$entityId = $entity->getId();

		if ( $sourceEntityIdSerialization === null ) {
			$sourceEntityIdSerialization = $entityId->getSerialization();
		}

		$record = array();

		//if there are no props defined only return type and id..
		if ( $props === array() ) {
			$record['id'] = $entityId->getSerialization();
			$record['type'] = $entityId->getEntityType();
		} else {
			if ( $props == 'all' || in_array( 'info', $props ) ) {
				$title = $this->entityTitleLookup->getTitleForId( $entityId );
				$record['pageid'] = $title->getArticleID();
				$record['ns'] = $title->getNamespace();
				$record['title'] = $title->getPrefixedText();
				$record['lastrevid'] = $entityRevision->getRevisionId();
				$record['modified'] = wfTimestamp( TS_ISO_8601, $entityRevision->getTimestamp() );
			}
			if ( $sourceEntityIdSerialization !== $entityId->getSerialization() ) {
				$record['redirects'] = array(
					'from' => $sourceEntityIdSerialization,
					'to' => $entityId->getSerialization()
				);
			}

			$entitySerializer = $this->serializerFactory->newEntitySerializer();
			$serialization = $entitySerializer->serialize( $entity );

			$serialization = $this->filterEntitySerializationUsingProps( $serialization, $props );
			if ( $props == 'all' || in_array( 'sitelinks/urls', $props ) ) {
				$serialization = $this->injectEntitySerializationWithSiteLinkUrls( $serialization );
			}
			$serialization = $this->sortEntitySerializationSiteLinks( $serialization );
			$serialization = $this->injectEntitySerializationWithDataTypes( $serialization );
			$serialization = $this->filterEntitySerializationUsingSiteIds( $serialization, $filterSiteIds );
			if ( !empty( $fallbackChains ) ) {
				$serialization = $this->addEntitySerializationFallbackInfo( $serialization, $fallbackChains );
			}
			$serialization = $this->filterEntitySerializationUsingLangCodes( $serialization, $filterLangCodes );

			if ( !$this->isRawMode ) {
				// Non raw mode formats dont want empty parts....
				$serialization = $this->filterEmptyEntitySerializationParts( $serialization );
			}

			if ( $this->isRawMode ) {
				$serialization = $this->getRawModeEntitySerialization( $serialization );
			}

			$record = array_merge( $record, $serialization );
		}

		$this->appendValue( array( 'entities' ), $sourceEntityIdSerialization, $record, 'entity' );
	}

	private function filterEmptyEntitySerializationParts( array $serialization ) {
		if ( empty( $serialization['labels'] ) ) {
			unset( $serialization['labels'] );
		}
		if ( empty( $serialization['descriptions'] ) ) {
			unset( $serialization['descriptions'] );
		}
		if ( empty( $serialization['aliases'] ) ) {
			unset( $serialization['aliases'] );
		}
		if ( empty( $serialization['claims'] ) ) {
			unset( $serialization['claims'] );
		}
		if ( empty( $serialization['sitelinks'] ) ) {
			unset( $serialization['sitelinks'] );
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

	private function injectEntitySerializationWithDataTypes( array $serialization ) {
		$serialization = $this->modifier->modifyUsingCallback(
			$serialization,
			'claims/*/*/mainsnak',
			$this->getModCallbackToAddDataTypeToSnak()
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

	private function filterEntitySerializationUsingSiteIds( array $serialization, $siteIds ) {
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
	 * @param LanguageFallbackChain[] $fallbackChains
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
	 * @param LanguageFallbackChain[] $fallbackChains
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
					if ( $this->isRawMode && $requestedLanguageCode !== $fallbackSerialization['language'] ) {
						$fallbackSerialization['for-language'] = $requestedLanguageCode;
					}
					$newSerialization[$requestedLanguageCode] = $fallbackSerialization;
				}
			}
		}
		return $newSerialization;
	}

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

	private function getRawModeEntitySerialization( $serialization ) {
		// In raw mode aliases are not currently grouped by language
		$serialization = $this->modifier->modifyUsingCallback(
			$serialization,
			'aliases',
			function( $array ) {
				$newArray = array();
				foreach ( $array as $aliasGroup ) {
					foreach ( $aliasGroup as $alias ) {
						$newArray[] = $alias;
					}
				}
				return $newArray;
			}
		);
		// In the old Lib serializers
		$serialization = $this->modifier->modifyUsingCallback(
			$serialization,
			'claims/*/*',
			function( $array ) {
				if ( !array_key_exists( 'qualifiers', $array ) ) {
					$array['qualifiers'] = array();
				}
				if ( !array_key_exists( 'qualifiers-order', $array ) ) {
					$array['qualifiers-order'] = array();
				}
				return $array;
			}
		);
		$keysToValues = array(
			'aliases' => null,
			'descriptions' => null,
			'labels' => null,
			'claims/*/*/references/*/snaks' => 'id',
			'claims/*/*/qualifiers' => 'id',
			'claims' => 'id',
			'sitelinks' => null,
		);
		foreach ( $keysToValues as $path => $newKey ) {
			$serialization = $this->modifier->modifyUsingCallback(
				$serialization,
				$path,
				$this->getModCallbackToRemoveKeys( $newKey )
			);
		}
		$tagsToAdd = array(
			'labels' => 'label',
			'descriptions' => 'description',
			'aliases' => 'alias',
			'sitelinks/*/badges' => 'badge',
			'sitelinks' => 'sitelink',
			'claims/*/*/qualifiers/*' => 'qualifiers',
			'claims/*/*/qualifiers' => 'property',
			'claims/*/*/qualifiers-order' => 'property',
			'claims/*/*/references/*/snaks/*' => 'snak',
			'claims/*/*/references/*/snaks' => 'property',
			'claims/*/*/references/*/snaks-order' => 'property',
			'claims/*/*/references' => 'reference',
			'claims/*' => 'claim',
			'claims' => 'property',
		);
		foreach ( $tagsToAdd as $path => $tag ) {
			$serialization = $this->modifier->modifyUsingCallback(
				$serialization,
				$path,
				$this->getModCallbackToIndexTags( $tag )
			);
		}
		return $serialization;
	}

	/**
	 * Get serialized information for the EntityId and add them to result
	 *
	 * @param EntityId $entityId
	 * @param string|array|null $path
	 *
	 * @since 0.5
	 */
	public function addBasicEntityInformation( EntityId $entityId, $path ) {
		$this->setValue( $path, 'id', $entityId->getSerialization() );
		$this->setValue( $path, 'type', $entityId->getEntityType() );
	}

	/**
	 * Get serialized labels and add them to result
	 *
	 * @since 0.5
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
	 * @since 0.5
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
	 * @since 0.5
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
	 * @since 0.5
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
		$this->setList( $path, $name, $value, $tag );
	}

	/**
	 * Adds fake serialization to show a term has been removed
	 *
	 * @param string $language
	 * @param array|string $path where the data is located
	 */
	private function addRemovedTerm( $language, $name, $tag, $path ) {
		$value = array(
			$language => array(
				'language' => $language,
				'removed' => '',
			)
		);
		$this->setList( $path, $name, $value, $tag );
	}

	/**
	 * Get serialized AliasGroupList and add it to result
	 *
	 * @since 0.5
	 *
	 * @param AliasGroupList $aliasGroupList the AliasGroupList to set in the result
	 * @param array|string $path where the data is located
	 */
	public function addAliasGroupList( AliasGroupList $aliasGroupList, $path ) {
		if ( $this->isRawMode ) {
			$serializer = $this->serializerFactory->newAliasGroupSerializer();
			$values = array();
			foreach ( $aliasGroupList->toArray() as $aliasGroup ) {
				$values = array_merge( $values, $serializer->serialize( $aliasGroup ) );
			}
		} else {
			$serializer = $this->serializerFactory->newAliasGroupListSerializer();
			$values = $serializer->serialize( $aliasGroupList );
		}
		$this->setList( $path, 'aliases', $values, 'alias' );
	}

	/**
	 * Get serialized sitelinks and add them to result
	 *
	 * @since 0.5
	 *
	 * @todo use a SiteLinkListSerializer when created in DataModelSerialization here
	 *
	 * @param SiteLinkList $siteLinkList the site links to insert in the result
	 * @param array|string $path where the data is located
	 * @param bool $addUrl
	 */
	public function addSiteLinkList( SiteLinkList $siteLinkList, $path, $addUrl = false ) {
		$serializer = $this->serializerFactory->newSiteLinkSerializer();

		$values = array();
		foreach ( $siteLinkList->toArray() as $siteLink ) {
			$values[$siteLink->getSiteId()] = $serializer->serialize( $siteLink );
		}

		if ( $addUrl ) {
			$values = $this->getSiteLinkListArrayWithUrls( $values );
		}

		if ( $this->isRawMode ) {
			$values = $this->getRawModeSiteLinkListArray( $values );
		}

		$this->setList( $path, 'sitelinks', $values, 'sitelink' );
	}

	private function getSiteLinkListArrayWithUrls( array $array ) {
		$siteStore = $this->siteStore;
		$addUrlCallback = function( $array ) use ( $siteStore ) {
			$site = $siteStore->getSite( $array['site'] );
			if ( $site !== null ) {
				$array['url'] = $site->getPageUrl( $array['title'] );
			}
			return $array;
		};
		return $this->modifier->modifyUsingCallback( $array, '*', $addUrlCallback );
	}

	private function getRawModeSiteLinkListArray( array $array ) {
		$addIndexedBadgesCallback = function ( $array ) {
			ApiResult::setIndexedTagName( $array, 'badge' );
			return $array;
		};
		$array = array_values( $array );
		return $this->modifier->modifyUsingCallback( $array, '*/badges', $addIndexedBadgesCallback );
	}

	/**
	 * Adds fake serialization to show a sitelink has been removed
	 *
	 * @since 0.5
	 *
	 * @param SiteLinkList $siteLinkList
	 * @param array|string $path where the data is located
	 */
	public function addRemovedSiteLinks( SiteLinkList $siteLinkList, $path ) {
		$serializer = $this->serializerFactory->newSiteLinkSerializer();
		$values = array();
		foreach ( $siteLinkList->toArray() as $siteLink ) {
			$value = $serializer->serialize( $siteLink );
			$value['removed'] = '';
			$values[$siteLink->getSiteId()] = $value;
		}
		$this->setList( $path, 'sitelinks', $values, 'sitelink' );
	}

	/**
	 * Get serialized claims and add them to result
	 *
	 * @since 0.5
	 *
	 * @param Claim[] $claims the labels to set in the result
	 * @param array|string $path where the data is located
	 * @param array|string $props a list of fields to include, or "all"
	 */
	public function addClaims( array $claims, $path, $props = 'all' ) {
		$serializer = $this->serializerFactory->newStatementListSerializer();

		$values = $serializer->serialize( new StatementList( $claims ) );

		if ( is_array( $props ) && !in_array( 'references', $props ) ) {
			$values = $this->modifier->modifyUsingCallback(
				$values,
				'*/*',
				function ( $array ) {
					unset( $array['references'] );
					return $array;
				}
			);
		}

		if ( !$this->isRawMode ) {
			$values = $this->getArrayWithAlteredClaims( $values, false, '*/*/' );
		} else {
			$values = $this->getArrayWithAlteredClaims( $values, true, '*/*/' );
			$values = $this->getArrayWithRawModeClaims( $values, '*/*/' );
			$values = $this->modifier->modifyUsingCallback(
				$values,
				null,
				$this->getModCallbackToRemoveKeys( 'id' )
			);
			$values = $this->modifier->modifyUsingCallback(
				$values,
				'*',
				$this->getModCallbackToIndexTags( 'claim' )
			);
		}

		$this->setList( $path, 'claims', $values, 'property' );
	}

	/**
	 * Get serialized claim and add it to result
	 *
	 * @param Claim $claim
	 *
	 * @since 0.5
	 */
	public function addClaim( Claim $claim ) {
		$serializer = $this->serializerFactory->newStatementSerializer();

		//TODO: this is currently only used to add a Claim as the top level structure,
		//      with a null path and a fixed name. Would be nice to also allow claims
		//      to be added to a list, using a path and a id key or index.

		$value = $serializer->serialize( $claim );

		$value = $this->getArrayWithAlteredClaims( $value );

		if ( $this->isRawMode ) {
			$value = $this->getArrayWithRawModeClaims( $value );
		}

		$this->setValue( null, 'claim', $value );
	}

	/**
	 * @param array $array
	 * @param bool $allowEmptyQualifiers
	 * @param string $claimPath to the claim array/arrays with trailing /
	 *
	 * @return array
	 */
	private function getArrayWithAlteredClaims(
		array $array,
		$allowEmptyQualifiers = true,
		$claimPath = ''
	) {
		if ( $allowEmptyQualifiers ) {
			/**
			 * Below we force an empty qualifiers and qualifiers-order element in the output.
			 * This is to make sure we dont break anything that assumes this is always here.
			 * This hack was added when moving away from the Lib serializers
			 * TODO: remove this hack when we make other 'breaking changes' to the api output
			 */
			$array = $this->modifier->modifyUsingCallback(
				$array,
				trim( $claimPath, '/' ),
				function ( $array ) {
					if ( !isset( $array['qualifiers'] ) ) {
						$array['qualifiers'] = array();
					}
					if ( !isset( $array['qualifiers-order'] ) ) {
						$array['qualifiers-order'] = array();
					}

					return $array;
				}
			);
		}

		$array = $this->getArrayWithDataTypesInGroupedSnakListAtPath(
			$array,
			$claimPath . 'references/*/snaks'
		);
		$array = $this->getArrayWithDataTypesInGroupedSnakListAtPath(
			$array,
			$claimPath . 'qualifiers'
		);
		$array = $this->modifier->modifyUsingCallback(
			$array,
			$claimPath . 'mainsnak',
			$this->getModCallbackToAddDataTypeToSnak()
		);
		return $array;
	}

	/**
	 * @param array $array
	 * @param string $claimPath to the claim array/arrays with trailing /
	 *
	 * @return array
	 */
	private function getArrayWithRawModeClaims( array $array, $claimPath = '' ) {
		$rawModeModifications = array(
			'references/*/snaks/*' => array(
				$this->getModCallbackToIndexTags( 'snak' ),
			),
			'references/*/snaks' => array(
				$this->getModCallbackToRemoveKeys( 'id' ),
				$this->getModCallbackToIndexTags( 'property' ),
			),
			'references/*/snaks-order' => array(
				$this->getModCallbackToIndexTags( 'property' )
			),
			'references' => array(
				$this->getModCallbackToIndexTags( 'reference' ),
			),
			'qualifiers/*' => array(
				$this->getModCallbackToIndexTags( 'qualifiers' ),
			),
			'qualifiers' => array(
				$this->getModCallbackToRemoveKeys( 'id' ),
				$this->getModCallbackToIndexTags( 'property' ),
			),
			'qualifiers-order' => array(
				$this->getModCallbackToIndexTags( 'property' )
			),
			'mainsnak' => array(
				$this->getModCallbackToAddDataTypeToSnak(),
			),
		);

		foreach ( $rawModeModifications as $path => $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$array = $this->modifier->modifyUsingCallback( $array, $claimPath . $path, $callback );
			}
		}

		return $array;
	}

	/**
	 * Get serialized reference and add it to result
	 *
	 * @param Reference $reference
	 *
	 * @since 0.5
	 */
	public function addReference( Reference $reference ) {
		$serializer = $this->serializerFactory->newReferenceSerializer();

		//TODO: this is currently only used to add a Reference as the top level structure,
		//      with a null path and a fixed name. Would be nice to also allow references
		//      to be added to a list, using a path and a id key or index.

		$value = $serializer->serialize( $reference );

		$value = $this->getArrayWithDataTypesInGroupedSnakListAtPath( $value, 'snaks' );

		if ( $this->isRawMode ) {
			$value = $this->getRawModeReferenceArray( $value );
		}

		$this->setValue( null, 'reference', $value );
	}

	/**
	 * @param array $array
	 * @param string $path
	 *
	 * @return array
	 */
	private function getArrayWithDataTypesInGroupedSnakListAtPath( array $array, $path ) {
		return $this->modifier->modifyUsingCallback(
			$array,
			$path,
			$this->getModCallbackToAddDataTypeToSnaksGroupedByProperty()
		);
	}

	private function getRawModeReferenceArray( $array ) {
		$array = $this->modifier->modifyUsingCallback( $array, 'snaks-order', function ( $array ) {
			ApiResult::setIndexedTagName( $array, 'property' );
			return $array;
		} );
		$array = $this->modifier->modifyUsingCallback( $array, 'snaks', function ( $array ) {
			foreach ( $array as $propertyIdGroup => &$snakGroup ) {
				$snakGroup['id'] = $propertyIdGroup;
				ApiResult::setIndexedTagName( $snakGroup, 'snak' );
			}
			$array = array_values( $array );
			ApiResult::setIndexedTagName( $array, 'property' );
			return $array;
		} );
		return $array;
	}

	/**
	 * Add an entry for a missing entity...
	 *
	 * @param string|null $key The key under which to place the missing entity in the 'entities'
	 *        structure. If null, defaults to the 'id' field in $missingDetails if that is set;
	 *        otherwise, it defaults to using a unique negative number.
	 * @param array $missingDetails array containing key value pair missing details
	 *
	 * @since 0.5
	 */
	public function addMissingEntity( $key, $missingDetails ) {
		if ( $key === null && isset( $missingDetails['id'] ) ) {
			$key = $missingDetails['id'];
		}

		if ( $key === null ) {
			$key = $this->missingEntityCounter;
		}

		$this->appendValue(
			'entities',
			$key,
			array_merge( $missingDetails, array( 'missing' => "" ) ),
			'entity'
		);

		$this->missingEntityCounter--;
	}

	/**
	 * @param string $from
	 * @param string $to
	 * @param string $name
	 *
	 * @since 0.5
	 */
	public function addNormalizedTitle( $from, $to, $name = 'n' ) {
		$this->setValue(
			'normalized',
			$name,
			array( 'from' => $from, 'to' => $to )
		);
	}

	/**
	 * Adds the ID of the new revision from the Status object to the API result structure.
	 * The status value is expected to be structured in the way that EditEntity::attemptSave()
	 * resp WikiPage::doEditContent() do it: as an array, with an EntityRevision or Revision
	 *  object in the 'revision' field.
	 *
	 * If no revision is found the the Status object, this method does nothing.
	 *
	 * @see ApiResult::addValue()
	 *
	 * @since 0.5
	 *
	 * @param Status $status The status to get the revision ID from.
	 * @param string|null|array $path Where in the result to put the revision id
	 */
	public function addRevisionIdFromStatusToResult( Status $status, $path ) {
		$value = $status->getValue();

		if ( isset( $value['revision'] ) ) {
			$revision = $value['revision'];

			if ( $revision instanceof Revision ) {
				$revisionId = $revision->getId();
			} elseif ( $revision instanceof EntityRevision ) {
				$revisionId = $revision->getRevisionId();
			}

			$this->setValue( $path, 'lastrevid', empty( $revisionId ) ? 0 : $revisionId );
		}
	}

	/**
	 * Get callable to index array with the given tag name
	 *
	 * @param string $tagName
	 *
	 * @return callable
	 */
	private function getModCallbackToIndexTags( $tagName ) {
		return function( $array ) use ( $tagName ) {
			if ( is_array( $array ) ) {
				ApiResult::setIndexedTagName( $array, $tagName );
			}
			return $array;
		};
	}

	/**
	 * Get callable to remove array keys and optionally set the key as an array value
	 *
	 * @param string|null $addAsArrayElement
	 *
	 * @return callable
	 */
	private function getModCallbackToRemoveKeys( $addAsArrayElement = null ) {
		return function ( $array ) use ( $addAsArrayElement ) {
			if ( $addAsArrayElement !== null ) {
				foreach ( $array as $key => &$value ) {
					$value[$addAsArrayElement] = $key;
				}
			}
			$array = array_values( $array );
			return $array;
		};
	}

	private function getModCallbackToAddDataTypeToSnaksGroupedByProperty() {
		$dtLookup = $this->dataTypeLookup;
		return function ( $array ) use ( $dtLookup ) {
			foreach ( $array as $propertyIdGroupKey => &$snakGroup ) {
				$dataType = $dtLookup->getDataTypeIdForProperty( new PropertyId( $propertyIdGroupKey ) );
				foreach ( $snakGroup as &$snak ) {
					$snak['datatype'] = $dataType;
				}
			}
			return $array;
		};
	}

	private function getModCallbackToAddDataTypeToSnak() {
		$dtLookup = $this->dataTypeLookup;
		return function ( $array ) use ( $dtLookup ) {
			$dataType = $dtLookup->getDataTypeIdForProperty( new PropertyId( $array['property'] ) );
			$array['datatype'] = $dataType;
			return $array;
		};
	}

}
