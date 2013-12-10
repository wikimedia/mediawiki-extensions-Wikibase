<?php

use Wikibase\Item;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\EntityLookup;
use Wikibase\SiteLinkLookup;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\EntityIdParser;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Utils;
use \Language;

/**
 * Actual implementations of the functions to access Wikibase through the Scribunto extension
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 */
class Scribunto_LuaWikibaseLibraryImplementation {

	/* @var EntityIdParser */
	protected $entityIdParser;

	/* @var EntityLookup */
	protected $entityLookup;

	/* @var EntityIdFormatter */
	protected $entityIdFormatter;

	/* @var SiteLinkLookup */
	protected $siteLinkTable;

	/* @var Language */
	protected $language;

	/* @var mixed */
	protected $siteId;


	/**
	 * @param EntityIdParser $entityIdParser
	 */
	public function __construct( EntityIdParser $entityIdParser,
								 EntityLookup $entityLookup,
								 EntityIdFormatter $entityIdFormatter,
								 SiteLinkLookup $siteLinkTable,
								 Language $language,
								 $siteId ) {
		$this->entityIdParser = $entityIdParser;
		$this->entityLookup = $entityLookup;
		$this->entityIdFormatter = $entityIdFormatter;
		$this->siteLinkTable = $siteLinkTable;
		$this->language = $language;
		$this->siteId = $siteId;
	}


	/**
	 * Get entity from prefixed ID (e.g. "Q23") and return it as serialized array.
	 *
	 * @since 0.5
	 *
	 * @param string $prefixedEntityId
	 *
	 * @return array $entityArr
	 */
	public function getEntity( $prefixedEntityId = null ) {
		$prefixedEntityId = trim( $prefixedEntityId );

		$entityId = $this->entityIdParser->parse( $prefixedEntityId );

		$entityObject = $this->entityLookup->getEntity( $entityId );

		if ( $entityObject === null ) {
			return array( null );
		}

		$opt = new SerializationOptions();
		$serializerFactory = new SerializerFactory( $opt );

		// Using "ID_KEYS_BOTH" here means that all lists of Snaks or Claims will be listed
		// twice, once with a lower case key and once with an upper case key.
		// This is a B/C hack to allow existing lua code to use hardcoded IDs
		// in both lower (legacy) and upper case.
		$opt->setIdKeyMode( SerializationOptions::ID_KEYS_BOTH );

		// See mw.wikibase.lua. This is the only way to inject values into mw.wikibase.label( ),
		// so any customized Lua modules can access labels of another entity written in another variant,
		// unless we give them the ability to getEntity() any entity by specifying its ID, not just self.
		$fallbackChainFactory = new LanguageFallbackChainFactory();
		$chain = $fallbackChainFactory->newFromLanguage(
			$this->language,
			LanguageFallbackChainFactory::FALLBACK_SELF | LanguageFallbackChainFactory::FALLBACK_VARIANTS
		);

		// SerializationOptions accepts mixed types of keys happily.
		$opt->setLanguages( Utils::getLanguageCodes() + array( $this->language->getCode() => $chain ) );

		$serializer = $serializerFactory->newSerializerForObject( $entityObject, $opt );

		$entityArr = $serializer->getSerialized( $entityObject );
		return array( $entityArr );
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
	public function getEntityId( $pageTitle = null ) {
		$globalSiteId = $this->getGlobalSiteId();
		$table = $this->siteLinkTable;
		if ( $table === null ) {
			return array( null );
		}

		$numericId = $table->getItemIdForLink( $globalSiteId, $pageTitle );
		if ( ! is_int( $numericId ) ) {
			return array( null );
		}

		$id = new EntityId( Item::ENTITY_TYPE, $numericId );
		if ( $id === null ) {
			return array( null );
		}

		return array( $this->entityIdFormatter->format( $id ) );
	}

	/**
	 * Get global site ID (e.g. "enwiki")
	 * This is basically a helper function.
	 * I can see this becoming part of mw.site in the Scribunto extension.
	 *
	 * @since 0.5
	 *
	 */
	public function getGlobalSiteId() {
		return array( $this->siteId );
	}
}
