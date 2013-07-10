<?php

namespace Wikibase;

use Language;
use Site;
use ObjectCache;

/**
 * Implementation of the client store interface using direct access to the repository's
 * database via MediaWiki's foreign wiki mechanism as implemented by LBFactory_multi.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.3
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 *
 * @todo: share code with CachingSqlStore
 * */
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
	 * @var EntityUsageIndex
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
	 * @param Language $wikiLanguage
	 * @param Site     $site
	 * @param string   $repoWiki the symbolic database name of the repo wiki
	 * @param string   $cachePrefix
	 * @param int      $cacheDuration
	 * @param int      $cacheType
	 */
	public function __construct( Language $wikiLanguage, Site $site, $repoWiki, $cachePrefix, $cacheDuration, $cacheType ) {
		$this->repoWiki = $repoWiki;
		$this->cachePrefix = $cachePrefix;
		$this->cacheDuration = $cacheDuration;
		$this->cacheType = $cacheType;
		$this->language = $wikiLanguage;
		$this->site = $site;
	}

	/**
	 * This pseudo-constructor uses the following settings from $settings:
	 * - sharedCacheKeyPrefix
	 * - sharedCacheDuration
	 * - sharedCacheType
	 * - siteGlobalID
	 * - repoDatabase
	 *
	 * @param SettingsArray $settings
	 * @param Language      $wikiLanguage
	 *
	 * @return DirectSqlStore
	 */
	public static function newFromSettings( SettingsArray $settings, Language $wikiLanguage ) {
		$cachePrefix = $settings->getSetting( 'sharedCacheKeyPrefix' );
		$cacheDuration = $settings->getSetting( 'sharedCacheDuration' );
		$cacheType = $settings->getSetting( 'sharedCacheType' );
		$repoWiki = $settings->getSetting( 'repoDatabase' );
		$siteId = $settings->getSetting( 'siteGlobalID' );

		// HACK: Allow the Site object to be set directly, to avoid
		// depending on global state while testing.
		if ( $siteId instanceof Site ) {
			$site = $siteId;
			$siteId = $site->getGlobalId();
		} else {
			$sites = \Sites::singleton();
			$site = $sites->getSite( $siteId );
		}

		if ( $site === null ) {
			// HACK: If in testing mode, always pretend the Site exists.
			//       This covers the case where the test a) relies on the actual settings
			//       of the local installation and b) the site ID used there is not
			//       covered by TestSites.
			if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
				// XXX: Since the sites table isn't complete on some live sites, be lenient for now.
				//      But really, this should be a fatal error.
				wfLogWarning( __METHOD__ . ": "
					. "Constructing Site object for unknown site ID $siteId. "
					. "Make sure this ID is present in the sites table." );
			}

			$site = new Site( \MediaWikiSite::TYPE_MEDIAWIKI );
			$site->setGlobalId( $siteId );
		}

		return new self( $wikiLanguage, $site, $repoWiki, $cachePrefix, $cacheDuration, $cacheType );
	}


	/**
	 * @see Store::getEntityUsageIndex
	 *
	 * @since 0.4
	 *
	 * @return EntityUsageIndex
	 */
	public function getEntityUsageIndex() {
		if ( !$this->entityUsageIndex ) {
			$this->entityUsageIndex = $this->newEntityUsageIndex();
		}

		return $this->entityUsageIndex;
	}

	/**
	 * @since 0.4
	 *
	 * @return EntityUsageIndex
	 */
	protected function newEntityUsageIndex() {
		return new EntityUsageIndex( $this->getSite(), $this->getSiteLinkTable() );
	}

	/**
	 * Returns the site object representing the local wiki.
	 *
	 * @return Site
	 */
	private function getSite() {
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
	 * @return CachingEntityLoader
	 */
	protected function newEntityLookup() {
		//NOTE: two layers of caching: persistent external cache in WikiPageEntityLookup;
		//      transient local cache in CachingEntityLoader.
		//NOTE: Keep in sync with SqlStore::newEntityLookup on the repo
		$key = $this->cachePrefix . ':WikiPageEntityLookup';
		$lookup = new WikiPageEntityLookup( $this->repoWiki, $this->cacheType, $this->cacheDuration, $key );
		return new CachingEntityLoader( $lookup );
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
		$key = $this->cachePrefix . ':TermPropertyLabelResolver';
		return new TermPropertyLabelResolver(
			$this->language->getCode(),
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
		if ( Settings::get( 'usePropertyInfoTable' ) ) {
			$table = new PropertyInfoTable( true, $this->repoWiki );

			//TODO: get cache type etc from config
			//TODO: better version ID from config!
			$key = $this->repoWiki . '/Wikibase/CachingPropertyInfoStore/' . WBL_VERSION;
			return new CachingPropertyInfoStore( $table, wfGetMainCache(), 3600, $key );
		} else {
			// dummy info store
			return new DummyPropertyInfoStore();
		}
	}
}
