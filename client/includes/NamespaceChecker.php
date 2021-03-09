<?php

namespace Wikibase\Client;

use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use NamespaceInfo;

/**
 * Checks if a namespace in Wikibase Client shall have wikibase links, etc., based on settings
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class NamespaceChecker {

	/**
	 * @var int[]
	 */
	private $excludedNamespaces;

	/**
	 * @var int[]
	 */
	private $enabledNamespaces;

	/** @var NamespaceInfo */
	private $namespaceInfo;

	/**
	 * @param int[] $excludedNamespaces
	 * @param int[] $enabledNamespaces if empty, setting not in use and all namespaces enabled
	 * @param NamespaceInfo|null $namespaceInfo falls back to default instance
	 */
	public function __construct(
		array $excludedNamespaces,
		array $enabledNamespaces = [],
		NamespaceInfo $namespaceInfo = null
	) {
		$this->excludedNamespaces = $excludedNamespaces;
		$this->enabledNamespaces = $enabledNamespaces;
		$this->namespaceInfo = $namespaceInfo ?: MediaWikiServices::getInstance()->getNamespaceInfo();
	}

	/**
	 * Per the settings, does the namespace have wikibase enabled?
	 * note: excludeNamespaces, if set, overrides namespace (inclusion) settings
	 *
	 * @param int $namespace
	 *
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	public function isWikibaseEnabled( $namespace ) {
		if ( !is_int( $namespace ) ) {
			throw new InvalidArgumentException( '$namespace is must be an integer' );
		}

		if ( $this->isExcluded( $namespace ) ) {
			return false;
		}

		return $this->isEnabled( $namespace );
	}

	/**
	 * Check if the namespace is excluded by settings for having wikibase links, etc.
	 * based on the 'excludeNamespaces' setting.
	 *
	 * @param int $namespace
	 *
	 * @return bool
	 */
	private function isExcluded( $namespace ) {
		return in_array( $namespace, $this->excludedNamespaces );
	}

	/**
	 * Check if namespace is enabled for Wikibase, based on the 'namespaces' setting.
	 *
	 * Note: If no list of enabled namespaces is configured, all namespaces are considered
	 * to be enabled for Wikibase.
	 *
	 * @param int $namespace
	 *
	 * @return bool
	 */
	private function isEnabled( $namespace ) {
		return empty( $this->enabledNamespaces )
			|| in_array( $namespace, $this->enabledNamespaces );
	}

	/**
	 * @return int[]
	 */
	public function getEnabledNamespaces() {
		return $this->enabledNamespaces;
	}

	/**
	 * @return int[]
	 */
	public function getExcludedNamespaces() {
		return $this->excludedNamespaces;
	}

	/**
	 * Get the namespaces Wikibase is effectively enabled in.
	 *
	 * @return int[]
	 */
	public function getWikibaseNamespaces() {
		$enabled = $this->enabledNamespaces;

		if ( empty( $enabled ) ) {
			$enabled = $this->namespaceInfo->getValidNamespaces();
		}

		return array_diff( $enabled, $this->excludedNamespaces );
	}

}
