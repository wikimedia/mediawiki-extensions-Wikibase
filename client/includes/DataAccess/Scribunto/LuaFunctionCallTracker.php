<?php

namespace Wikibase\Client\DataAccess\Scribunto;

use InvalidArgumentException;
use Wikimedia\Stats\StatsFactory;

/**
 * Helper for tracking accesses of Lua functions.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class LuaFunctionCallTracker {
	/**
	 * @var StatsFactory
	 */
	private $statsFactory;

	/**
	 * @var string
	 */
	private $siteId;

	/**
	 * @var string
	 */
	private $siteGroup;

	/**
	 * @var bool
	 */
	private $trackLuaFunctionCallsPerSiteGroup;

	/**
	 * @var bool
	 */
	private $trackLuaFunctionCallsPerWiki;

	/**
	 * @var float
	 */
	private $sampleRate;

	/**
	 * @param StatsFactory $statsFactory
	 * @param string $siteId
	 * @param string $siteGroup
	 * @param bool $trackLuaFunctionCallsPerSiteGroup
	 * @param bool $trackLuaFunctionCallsPerWiki
	 * @param float $sampleRate A number in the range of [0, 1], representing
	 *   the fraction of counter increments that will be reported from Lua.
	 */
	public function __construct(
		$statsFactory,
		$siteId,
		$siteGroup,
		$trackLuaFunctionCallsPerSiteGroup,
		$trackLuaFunctionCallsPerWiki,
		$sampleRate
	) {
		$this->statsFactory = $statsFactory;
		$this->siteId = $siteId;
		$this->siteGroup = $siteGroup;
		$this->trackLuaFunctionCallsPerSiteGroup = $trackLuaFunctionCallsPerSiteGroup;
		$this->trackLuaFunctionCallsPerWiki = $trackLuaFunctionCallsPerWiki;
		if ( $sampleRate < 0 || $sampleRate > 1 ) {
			throw new InvalidArgumentException( '$sampleRate must be between 0 and 1.' );
		}
		$this->sampleRate = $sampleRate;
	}

	/**
	 * Prefix and increment the given statsd key.
	 *
	 * @param string $key
	 * @param string $module
	 */
	public function incrementKey( $key, $module ) {
		if ( $this->sampleRate === 0 ) {
			return;
		}

		$prefixedKeys = $this->getPrefixedKeys( $key, $module );
		$count = intval( 1 / $this->sampleRate );

		$counter = $this->statsFactory->withComponent( 'WikibaseClient' )
		->getCounter( "Scribunto_Lua_function_calls_total" )
		->setLabel( 'module', $module )
		->setLabel( 'function_name', $key );

		$counter->setLabel( 'site', $this->trackLuaFunctionCallsPerWiki ? $this->siteId : 'not_tracked' );
		$counter->setLabel( 'site_group', $this->trackLuaFunctionCallsPerSiteGroup ? $this->siteGroup : 'not_tracked' );

		$counter->copyToStatsdAt( $prefixedKeys )
		->incrementBy( $count );
	}

	/**
	 * @param string $key
	 * @param string $module
	 * @return string[]
	 */
	private function getPrefixedKeys( $key, $module ) {
		$prefixedKeys = [];
		$statsdKey = "wikibase.client.scribunto.$module.$key.call";

		if ( $this->trackLuaFunctionCallsPerWiki ) {
			$prefixedKeys[] = "$this->siteId.$statsdKey";
		}
		if ( $this->trackLuaFunctionCallsPerSiteGroup ) {
			$prefixedKeys[] = "$this->siteGroup.$statsdKey";
		}

		return $prefixedKeys;
	}

}
