<?php

namespace Wikibase\Client\Scribunto;

use InvalidArgumentException;
use Language;
use OutOfBoundsException;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\Serializer;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\LabelLookup;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\SettingsArray;

/**
 * Actual implementations of the functions to access Wikibase through the Scribunto extension
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 * @author Marius Hoch < hoo@online.de >
 */
class WikibaseLuaBindings {

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkTable;

	/**
	 * @var LanguageFallbackChainFactory
	 */
	private $fallbackChainFactory;

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @var SettingsArray
	 */
	private $settings;

	/**
	 * @var string[]
	 */
	private $languageCodes;

	/**
	 * @var string
	 */
	private $siteId;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $dataTypeLookup;

	/**
	 * @var LabelLookup
	 */
	private $labelLookup;

	/**
	 * @var UsageAccumulator
	 */
	private $usageAccumulator;

	/**
	 * @param EntityIdParser $entityIdParser
	 * @param EntityLookup $entityLookup
	 * @param SiteLinkLookup $siteLinkTable
	 * @param LanguageFallbackChainFactory $fallbackChainFactory
	 * @param Language $language
	 * @param SettingsArray $settings
	 * @param PropertyDataTypeLookup $dataTypeLookup
	 * @param LabelLookup $labelLookup
	 * @param UsageAccumulator $usageAccumulator
	 * @param string[] $languageCodes
	 * @param string $siteId
	 */
	public function __construct(
		EntityIdParser $entityIdParser,
		EntityLookup $entityLookup,
		SiteLinkLookup $siteLinkTable,
		LanguageFallbackChainFactory $fallbackChainFactory,
		Language $language,
		SettingsArray $settings,
		PropertyDataTypeLookup $dataTypeLookup,
		LabelLookup $labelLookup,
		UsageAccumulator $usageAccumulator,
		$languageCodes,
		$siteId
	) {
		$this->entityIdParser = $entityIdParser;
		$this->entityLookup = $entityLookup;
		$this->siteLinkTable = $siteLinkTable;
		$this->fallbackChainFactory = $fallbackChainFactory;
		$this->language = $language;
		$this->settings = $settings;
		$this->dataTypeLookup = $dataTypeLookup;
		$this->labelLookup = $labelLookup;
		$this->languageCodes = $languageCodes;
		$this->siteId = $siteId;
		$this->usageAccumulator = $usageAccumulator;
	}

	/**
	 * Recursively renumber a serialized array in place, so it is indexed at 1, not 0.
	 * Just like Lua wants it.
	 *
	 * @since 0.5
	 *
	 * @param array &$entityArr
	 */
	public function renumber( array &$entityArr ) {
		foreach( $entityArr as &$value ) {
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
	 * @param bool $legacyStyle Whether to return a legacy style entity
	 *
	 * @return array
	 */
	public function getEntity( $prefixedEntityId, $legacyStyle = false ) {
		$prefixedEntityId = trim( $prefixedEntityId );

		$entityId = $this->entityIdParser->parse( $prefixedEntityId );

		$entityObject = $this->entityLookup->getEntity( $entityId );

		if ( $entityObject === null ) {
			return null;
		}

		$serializer = $this->getEntitySerializer( $entityObject, $legacyStyle );

		$entityArr = $serializer->getSerialized( $entityObject );

		if ( $legacyStyle ) {
			// Mark the output as Legacy so that we can easily distinguish the styles in Lua
			$entityArr['schemaVersion'] = 1;
		} else {
			// Renumber the entity as Lua uses 1-based array indexing
			$this->renumber( $entityArr );
			$entityArr['schemaVersion'] = 2;
		}

		$this->usageAccumulator->addAllUsage( $entityId );
		return $entityArr;
	}

	/**
	 * @since 0.5
	 *
	 * @param EntityDocument $entityObject
	 * @param bool $lowerCaseIds Whether to also use lower case ids
	 *
	 * @return Serializer
	 */
	private function getEntitySerializer( EntityDocument $entityObject, $lowerCaseIds ) {
		$opt = new SerializationOptions();
		$serializerFactory = new SerializerFactory( $opt, $this->dataTypeLookup );

		// Using "ID_KEYS_BOTH" here means that all lists of Snaks or Claims will be listed
		// twice, once with a lower case key and once with an upper case key.
		// This is a B/C hack to allow existing lua code to use hardcoded IDs
		// in both lower (legacy) and upper case.
		if ( $lowerCaseIds ) {
			$opt->setIdKeyMode( SerializationOptions::ID_KEYS_BOTH );
		}

		// See mw.wikibase.lua. This is the only way to inject values into mw.wikibase.label( ),
		// so any customized Lua modules can access labels of another entity written in another variant,
		// unless we give them the ability to getEntity() any entity by specifying its ID, not just self.
		$chain = $this->fallbackChainFactory->newFromLanguage(
			$this->language,
			LanguageFallbackChainFactory::FALLBACK_SELF | LanguageFallbackChainFactory::FALLBACK_VARIANTS
		);

		// SerializationOptions accepts mixed types of keys happily.
		$opt->setLanguages( $this->languageCodes + array( $this->language->getCode() => $chain ) );

		return $serializerFactory->newSerializerForObject( $entityObject, $opt );
	}

	/**
	 * Get entity id from page title.
	 *
	 * @since 0.5
	 *
	 * @param string $pageTitle
	 *
	 * @return string|null $id
	 */
	public function getEntityId( $pageTitle ) {
		$id = $this->siteLinkTable->getItemIdForLink( $this->siteId, $pageTitle );

		if ( !$id ) {
			return null;
		}

		$this->usageAccumulator->addPageUsage( $id );
		return $id->getSerialization();
	}

	/**
	 * Get global site ID (e.g. "enwiki")
	 * This is basically a helper function.
	 * I can see this becoming part of mw.site in the Scribunto extension.
	 *
	 * @since 0.5
	 *
	 * @return string
	 */
	public function getGlobalSiteId() {
		return $this->siteId;
	}

	/**
	 * @param string $setting
	 *
	 * @return mixed
	 */
	public function getSetting( $setting ) {
		return $this->settings->getSetting( $setting );
	}

	/**
	 * @param string $prefixedEntityId
	 *
	 * @since 0.5
	 * @return string Empty string if entity couldn't be found/ no label present
	 */
	public function getLabel( $prefixedEntityId ) {
		try {
			$entityId = $this->entityIdParser->parse( $prefixedEntityId );
		} catch( EntityIdParsingException $e ) {
			return null;
		}

		try {
			$label = $this->labelLookup->getLabel( $entityId );
		} catch ( StorageException $ex ) {
			return null;
		} catch ( OutOfBoundsException $ex ) {
			return null;
		}

		// NOTE: This tracks a label usage in the wiki's content language.
		//       If the actual label is derived via language fallback,
		//       updates to the source language will not be seen to apply
		//       to this usage. We would need to trigger on changes to
		//       *all* languages to fix that.
		$this->usageAccumulator->addLabelUsage( $entityId );
		return $label;
	}

	/**
	 * @param string $prefixedEntityId
	 *
	 * @since 0.5
	 * @return string Empty string if entity couldn't be found/ no sitelink present
	 */
	public function getSiteLinkPageName( $prefixedEntityId ) {
		try {
			$itemId = new ItemId( $prefixedEntityId );
		} catch( InvalidArgumentException $e ) {
			return null;
		}

		$item = $this->entityLookup->getEntity( $itemId );
		if ( !$item || !$item->getSiteLinkList()->hasLinkWithSiteId( $this->siteId ) ) {
			return null;
		}

		$this->usageAccumulator->addPageUsage( $itemId );
		return $item->getSiteLinkList()->getBySiteId( $this->siteId )->getPageName();
	}

}
