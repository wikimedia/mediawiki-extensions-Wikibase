<?php

namespace Wikibase;

use Language;
use LogicException;
use ObjectCache;

/**
 * Implementation of the client store interface using an SQL backend via MediaWiki's
 * storage abstraction layer.
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
 * @todo: rename to MirrorSqlStore
 */
class CachingSqlStore implements ClientStore {

	/**
	 * @var EntityLookup
	 */
	private $entityLookup = null;

	/**
	 * @var PropertyLabelResolver
	 */
	private $propertyLabelResolver = null;

	/**
	 * @var Language
	 */
	protected $language;

	/**
	 * @var string
	 */
	private $cachePrefix;

	/**
	 * @var string
	 */
	private $cacheType;

	/**
	 * @var int
	 */
	private $cacheDuration;

	/**
	 * @param Language $wikiLanguage
	 * @param string   $cachePrefix
	 * @param          $cacheDuration
	 * @param          $cacheType
	 */
	public function __construct( Language $wikiLanguage, $cachePrefix, $cacheDuration, $cacheType ) {
		$this->language = $wikiLanguage;
		$this->cachePrefix = $cachePrefix;
		$this->cacheDuration = $cacheDuration;
		$this->cacheType = $cacheType;
	}

	/**
	 * This pseudo-constructor uses the following settings from $settings:
	 * - sharedCacheKeyPrefix
	 * - sharedCacheDuration
	 * - sharedCacheType
	 *
	 * @param SettingsArray $settings
	 * @param Language      $wikiLanguage
	 *
	 * @return CachingSqlStore
	 */
	public static function newFromSettings( SettingsArray $settings, Language $wikiLanguage ) {
		$cachePrefix = $settings->getSetting( 'sharedCacheKeyPrefix' );
		$cacheDuration = $settings->getSetting( 'sharedCacheDuration' );
		$cacheType = $settings->getSetting( 'sharedCacheType' );

		return new self( $wikiLanguage, $cachePrefix, $cacheDuration, $cacheType );
	}

	/**
	 * This pseudo-constructor uses the following settings from $settings:
	 * - sharedCacheKeyPrefix
	 * - sharedCacheDuration
	 * - sharedCacheType
	 *
	 * @param SettingsArray $settings
	 * @param Language      $wikiLanguage
	 *
	 * @return CachingSqlStore
	 */
	public static function newFromSettings( SettingsArray $settings, Language $wikiLanguage ) {
		return new self( $wikiLanguage );
	}

	/**
	 * @var SiteLinkTable
	 */
	private $siteLinkTable = null;

	/**
	 * @var EntityUsageIndex
	 */
	private $entityUsageIndex = null;

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

		return $this->siteLinkTable;
	}

	/**
	 * @since 0.4
	 *
	 * @return SiteLinkLookup
	 */
	protected function newEntityUsageIndex() {
		return new EntityUsageIndex( $this->getSite(), $this->getSiteLinkTable() );
	}

	/**
	 * @todo ClientStoreFactory should be factored into WikibaseClient, so WikibaseClient
	 *       can inject info like the wiki's Site object into the ClientStore instance.
	 *
	 * @return null|\Site
	 */
	private function getSite() {
		$site = \Sites::singleton()->getSite( Settings::get( 'siteGlobalID' ) );
		return $site;
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
		return new SiteLinkTable( 'wbc_items_per_site', true );
	}

	/**
	 * Returns a new EntityCache instance
	 *
	 * @since 0.3
	 *
	 * @return EntityCache
	 *
	 * @todo: rename to newEntityMirror
	 */
	public function newEntityCache() {
		return new EntityCacheTable();
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
		$mirror = $this->newEntityCache();
		return new CachingEntityLoader( $mirror );
	}

	/**
	 * Get a PropertyLabelResolver object
	 *
	 * @since 0.4
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
	 * Get a TermIndex object
	 *
	 * @return TermIndex
	 */
	public function getTermIndex() {
		throw new LogicException( "Not Implemented, " . __CLASS__ . " is incomplete." );
	}

	/**
	 * @see Store::getPropertyInfoStore
	 *
	 * @since 0.4
	 *
	 * @return PropertyInfoStore
	 */
	public function getPropertyInfoStore() {
		throw new LogicException( "Not Implemented, " . __CLASS__ . " is incomplete." );
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
	 * Throws an MWException, because no changes table is available.
	 *
	 * @since 0.4
	 *
	 * @throws \MWException because no changes table can be supplied.
	 */
	public function newChangesTable() {
		throw new \MWException( "no changes table available" );
	}

	/**
	 * Delete client store data
	 *
	 * @since 0.2
	 */
	public function clear() {
		$this->newEntityCache()->clear();

		$tables = array(
			'wbc_item_usage',
			'wbc_query_usage',
		);

		$dbw = wfGetDB( DB_MASTER );

		foreach ( $tables as $table ) {
			$dbw->delete( $dbw->tableName( $table ), '*', __METHOD__ );
		}
	}

	/**
	 * Rebuild client store data
	 *
	 * @since 0.2
	 */
	public function rebuild() {
		$this->clear();
	}

}
