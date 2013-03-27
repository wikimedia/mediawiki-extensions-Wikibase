<?php
namespace Wikibase;

/**
 * Checks if a namespace in Wikibase Client shall have wikibase links, etc., based on settings
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
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class NamespaceChecker {

	protected $excludedNamespaces;

	protected $enabledNamespaces;

	/**
	 * @since 0.4
	 *
	 * @param $excluded[]
	 * @param $enabled[] - if empty, setting not in use and all namespaces enabled
	 *
	 * @throws \MWException
	 */
	public function __construct( array $excludedNamespaces, array $enabledNamespaces ) {
		$this->excludedNamespaces = $excludedNamespaces;

		$this->enabledNamespaces = $enabledNamespaces;
	}

	/**
	 * Per the settings, does the namespace have wikibase enabled?
	 * note: excludeNamespaces, if set, overrides namespace (inclusion) settings
	 *
	 * @since 0.4
	 *
	 * @param int $namespace
	 *
	 * @return bool
	 */
	public function isWikibaseEnabled( $namespace ) {
		if( !is_int( $namespace ) ) {
			wfDebugLog( __CLASS__, __METHOD__ . " expected a namespace ID." );
			return false;
		}

		if ( $this->isExcluded( $namespace ) ) {
			return false;
		}

		return $this->isEnabled( $namespace );
	}

	/**
	 * Check if the namespace is excluded by settings for having wikibase links, etc.
	 * based on Settings::get( 'excludeNamespaces' )
	 *
	 * @since 0.4
	 *
	 * @param int $namespace
	 *
	 * @return bool
	 */
	protected function isExcluded( $namespace ) {
		return in_array( $namespace, $this->excludedNamespaces );
	}

	/**
	 * Check if namespace is enabled for Wikibase, based on Settings::get( 'namespaces' ).
	 *
	 * Note: If no list of enabled namespaces is configured, all namespaces are considered
	 * to be enabled for Wikibase.
	 *
	 * @since 0.4
	 *
	 * @param int $namespace
	 *
	 * @return bool
	 */
	protected function isEnabled( $namespace ) {
		return empty( $this->enabledNamespaces )
			|| in_array( $namespace, $this->enabledNamespaces );
	}

	/**
	 * Get enabled namespaces
	 *
	 * @since 0.4
	 *
	 * @return array|bool
	 */
	public function getEnabledNamespaces() {
		return $this->enabledNamespaces;
	}

	/**
	 * Get excluded namespaces
	 *
	 * @since 0.4
	 *
	 * @return array
	 */
	public function getExcludedNamespaces() {
		return $this->excludedNamespaces;
	}

	/**
	 * Get the namespaces Wikibase is effectively enabled in.
	 *
	 * @since 0.4
	 *
	 * @return array
	 */
	public function getWikibaseNamespaces() {
		$enabled = $this->enabledNamespaces;

		if ( empty( $enabled ) ) {
			$enabled = \MWNamespace::getValidNamespaces();
		}

		return array_diff( $enabled, $this->excludedNamespaces );
	}

}
