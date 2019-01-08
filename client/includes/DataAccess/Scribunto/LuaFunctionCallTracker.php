<?php

namespace Wikibase\Client\DataAccess\Scribunto;

use Liuggio\StatsdClient\Factory\StatsdDataFactoryInterface;

/**
 * Helper for tracking accesses of Lua functions.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class LuaFunctionCallTracker {

	/**
	 * @var StatsdDataFactoryInterface
	 */
	private $statsdDataFactory;

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
	 * @param StatsdDataFactoryInterface $statsdDataFactory
	 * @param string $siteId
	 * @param string $siteGroup
	 * @param bool $trackLuaFunctionCallsPerSiteGroup
	 * @param bool $trackLuaFunctionCallsPerWiki
	 */
	public function __construct(
		StatsdDataFactoryInterface $statsdDataFactory,
		$siteId,
		$siteGroup,
		$trackLuaFunctionCallsPerSiteGroup,
		$trackLuaFunctionCallsPerWiki
	) {
		$this->statsdDataFactory = $statsdDataFactory;
		$this->siteId = $siteId;
		$this->siteGroup = $siteGroup;
		$this->trackLuaFunctionCallsPerSiteGroup = $trackLuaFunctionCallsPerSiteGroup;
		$this->trackLuaFunctionCallsPerWiki = $trackLuaFunctionCallsPerWiki;
	}

	/**
	 * Prefix and increment the given statsd key.
	 *
	 * @param string $key
	 */
	public function incrementKey( $key ) {
		$prefixedKeys = $this->getPrefixedKeys( $key );

		foreach ( $prefixedKeys as $prefixedKey ) {
			$this->statsdDataFactory->increment( $prefixedKey );
		}
	}

	/**
	 * @param string $key
	 * @return string[]
	 */
	private function getPrefixedKeys( $key ) {
		$prefixedKeys = [];

		if ( $this->trackLuaFunctionCallsPerWiki ) {
			$prefixedKeys[] = "$this->siteId.$key";
		}
		if ( $this->trackLuaFunctionCallsPerSiteGroup ) {
			$prefixedKeys[] = "$this->siteGroup.$key";
		}

		return $prefixedKeys;
	}

}
