<?php

namespace Wikibase\Client\DataAccess\Scribunto;

use InvalidArgumentException;
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
	 * @var float
	 */
	private $sampleRate;

	/**
	 * @param StatsdDataFactoryInterface $statsdDataFactory
	 * @param string $siteId
	 * @param string $siteGroup
	 * @param bool $trackLuaFunctionCallsPerSiteGroup
	 * @param bool $trackLuaFunctionCallsPerWiki
	 * @param float $sampleRate A number in the range of [0, 1], representing
	 *   the fraction of counter increments that will be reported from Lua.
	 */
	public function __construct(
		StatsdDataFactoryInterface $statsdDataFactory,
		$siteId,
		$siteGroup,
		$trackLuaFunctionCallsPerSiteGroup,
		$trackLuaFunctionCallsPerWiki,
		$sampleRate
	) {
		$this->statsdDataFactory = $statsdDataFactory;
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
	 */
	public function incrementKey( $key ) {
		if ( $this->sampleRate === 0 ) {
			return;
		}
		$prefixedKeys = $this->getPrefixedKeys( $key );
		$count = intval( 1 / $this->sampleRate );

		foreach ( $prefixedKeys as $prefixedKey ) {
			$this->statsdDataFactory->updateCount( $prefixedKey, $count );
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
