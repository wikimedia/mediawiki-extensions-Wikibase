<?php

namespace Wikibase;

use Language;
use ObjectCache;
use Site;
use Wikibase\Client\WikibaseClient;
use Wikibase\Store\EntityContentDataCodec;
use Wikibase\store\CachingEntityRevisionLookup;

/**
 * Implementation of the client store interface using direct access to the repository's
 * database via MediaWiki's foreign wiki mechanism as implemented by LBFactoryMulti.
 *
 * @since 0.3
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class DirectSqlStore implements ClientStore {

	/**
	 * @var EntityLookup
	 */
	private $entityLookup = null;

	/**
	 * @var PropertyLabelResolver
	 */
	private $propertyLabelResolver = null;

	/**
	 * @var TermIndex
	 */
	private $termIndex = null;

	/**
	 * @var PropertyInfoTable
	 */
	private $propertyInfoTable = null;

	/**
	 * @var String|bool $repoWiki
	 */
	protected $repoWiki;

	/**
	 * @var Language
	 */
	protected $language;

	/**
	 * @var SiteLinkTable
	 */
	private $siteLinkTable = null;

	/**
	 * @var ItemUsageIndex
	 */
	private $entityUsageIndex = null;

	/**
	 * @var Site|null
	 */
	private $site = null;

	/**
	 * @var string
	 */
	private $cachePrefix;

	/**
	 * @var int
	 */
	private $cacheType;

	/**
	 * @var int
	 */
	private $cacheDuration;

	/**
	 * @var \Wikibase\Store\EntityContentDataCodec
	 */
	private $contentCodec;

	/**
	 * @var EntityFactory
	 */
	private $entityFactory;

	/**
	 * @param EntityContentDataCodec $contentCodec
	 * @param EntityFactory $entityFactory
	 * @param Language $wikiLanguage
	 * @param string    $repoWiki the symbolic database name of the repo wiki
	 */
	public function __construct(
		EntityContentDataCodec $contentCodec,
		EntityFactory $entityFactory,
		Language $wikiLanguage,
		$repoWiki
	) {
		$this->repoWiki = $repoWiki;
		$this->language = $wikiLanguage;
		$this->contentCodec = $contentCodec;
		$this->entityFactory = $entityFactory;

		$settings = WikibaseClient::getDefaultInstance()->getSettings();
		$cachePrefix = $settings->getSetting( 'sharedCacheKeyPrefix' );
		$cacheDuration = $settings->getSetting( 'sharedCacheDuration' );
		$cacheType = $settings->getSetting( 'sharedCacheType' );

		$this->cachePrefix = $cachePrefix;
		$this->cacheDuration = $cacheDuration;
		$this->cacheType = $cacheType;
	}

	/**
	 * @see Store::getEntityUsageIndex
	 *
	 * @since 0.4
	 *
	 * @return ItemUsageIndex
	 */
	public function getItemUsageIndex() {
		if ( !$this->entityUsageIndex ) {
			$this->entityUsageIndex = $this->newEntityUsageIndex();
		}

		return $this->entityUsageIndex;
	}

	/**
	 * @since 0.4
	 *
	 * @return ItemUsageIndex
	 */
	protected function newEntityUsageIndex() {
		return new ItemUsageIndex( $this->getSite(), $this->getSiteLinkTable() );
	}

	/**
	 * Sets the site object representing the local wiki.
	 * For testing only!
	 *
	 * @todo: remove this once the Site can be injected via the constructor!
	 *
	 * @param Site $site
	 */
	public function setSite( Site $site ) {
		$this->site = $site;
	}

	/**
	 * Returns the site object representing the local wiki.
	 *
	 * @return Site
	 */
	private function getSite() {
		// @FIXME: inject the site
		if ( $this->site === null ) {
			$this->site = WikibaseClient::getDefaultInstance()->getSite();
		}

		return $this->site;
	}

	/**
	 * @see Store::getSiteLinkTable
	 *
	 * @since 0.4
	 *
	 * @return SiteLinkLookup
	 */
	public function getSiteLinkTable() {
		if ( !$this->siteLinkTable ) {
			$this->siteLinkTable = $this->newSiteLinkTable();
		}

		return $this->siteLinkTable;
	}

	/**
	 * @since 0.3
	 *
	 * @return SiteLinkLookup
	 */
	protected function newSiteLinkTable() {
		return new SiteLinkTable( 'wb_items_per_site', true, $this->repoWiki );
	}


	/**
	 * @see Store::getEntityLookup
	 *
	 * @since 0.4
	 *
	 * @return EntityLookup
	 */
	public function getEntityLookup() {
		if ( !$this->entityLookup ) {
			$this->entityLookup = $this->newEntityLookup();
		}

		return $this->entityLookup;
	}

	/**
	 * Create a new EntityLookup
	 *
	 * @return CachingEntityRevisionLookup
	 */
	protected function newEntityLookup() {
		//NOTE: Keep in sync with SqlStore::newEntityLookup on the repo
		$key = $this->cachePrefix . ':WikiPageEntityLookup';

		$lookup = new WikiPageEntityLookup( $this->contentCodec, $this->entityFactory, $this->repoWiki );

		// Lower caching layer using persistent cache (e.g. memcached).
		// We need to verify the revision ID against the database to avoid stale data.
		$lookup = new CachingEntityRevisionLookup( $lookup, wfGetCache( $this->cacheType ), $this->cacheDuration, $key );
		$lookup->setVerifyRevision( true );

		// Top caching layer using an in-process hash.
		// No need to verify the revision ID, we'll ignore updates that happen during the request.
		$lookup = new CachingEntityRevisionLookup( $lookup, new \HashBagOStuff() );
		$lookup->setVerifyRevision( false );

		return $lookup;
	}

	/**
	 * Get a TermIndex object
	 *
	 * @return TermIndex
	 */
	public function getTermIndex() {
		if ( !$this->termIndex ) {
			$this->termIndex = $this->newTermIndex();
		}

		return $this->termIndex;
	}

	/**
	 * Create a new TermIndex instance
	 *
	 * @return TermIndex
	 */
	protected function newTermIndex() {
		//TODO: Get $stringNormalizer from WikibaseClient?
		//      Can't really pass this via the constructor...
		$stringNormalizer = new StringNormalizer();
		return new TermSqlIndex( $stringNormalizer , $this->repoWiki );
	}

	/**
	 * Get a PropertyLabelResolver object
	 *
	 * @return PropertyLabelResolver
	 */
	public function getPropertyLabelResolver() {
		if ( !$this->propertyLabelResolver ) {
			$this->propertyLabelResolver = $this->newPropertyLabelResolver();
		}

		return $this->propertyLabelResolver;
	}


	/**
	 * Create a new PropertyLabelResolver instance
	 *
	 * @return PropertyLabelResolver
	 */
	protected function newPropertyLabelResolver() {
		$langCode = $this->language->getCode();

		// cache key needs to be language specific
		$key = $this->cachePrefix . ':TermPropertyLabelResolver' . '/' . $langCode;

		return new TermPropertyLabelResolver(
			$langCode,
			$this->getTermIndex(),
			ObjectCache::getInstance( $this->cacheType ),
			$this->cacheDuration,
			$key
		);
	}

	/**
	 * @see Store::newChangesTable
	 *
	 * @since 0.4
	 *
	 * @return ChangesTable
	 */
	public function newChangesTable() {
		return new ChangesTable( $this->repoWiki );
	}

	/**
	 * Does nothing.
	 *
	 * @since 0.3
	 */
	public function clear() {
		// noop
	}

	/**
	 * Does nothing.
	 *
	 * @since 0.3
	 */
	public function rebuild() {
		$this->clear();
	}


	/**
	 * @see Store::getPropertyInfoStore
	 *
	 * @since 0.4
	 *
	 * @return PropertyInfoStore
	 */
	public function getPropertyInfoStore() {
		if ( !$this->propertyInfoTable ) {
			$this->propertyInfoTable = $this->newPropertyInfoTable();
		}

		return $this->propertyInfoTable;
	}

	/**
	 * Creates a new PropertyInfoTable
	 *
	 * @return PropertyInfoTable
	 */
	protected function newPropertyInfoTable() {
		$usePropertyInfoTable = WikibaseClient::getDefaultInstance()
			->getSettings()->getSetting( 'usePropertyInfoTable' );

		if ( $usePropertyInfoTable ) {
			$table = new PropertyInfoTable( true, $this->repoWiki );
			$key = $this->cachePrefix . ':CachingPropertyInfoStore';
			return new CachingPropertyInfoStore( $table, ObjectCache::getInstance( $this->cacheType ),
				$this->cacheDuration, $key );
		} else {
			// dummy info store
			return new DummyPropertyInfoStore();
		}
	}
}
