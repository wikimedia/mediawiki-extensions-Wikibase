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
 * @author John Erling Blad < jeblad@gmail.com >
 */
class NamespaceChecker {

	/**
	 * @var array
	 */
	protected $excludedNamespaces;

	/**
	 * @var array
	 */
	protected $enabledNamespaces;

	/**
	 * @since 0.4
	 *
	 * @param $excluded[]
	 * @param $enabled[]|false - if false, then setting not in use and all namespaces enabled
	 *
	 * @throws \MWException
	 */
	public function __construct( array $excludedNamespaces, array $enabledNamespaces = null, array $defaultNamespaces = null ) {
		$this->excludedNamespaces = $excludedNamespaces;
		$this->enabledNamespaces = array_filter(
			isset( $defaultNamespaces ) ? $defaultNamespaces : array(),
			function ( $ns ) {
				return $ns % 2 === 0;
			}
		);
		$this->enabledNamespaces = array_unique( array_merge( $this->enabledNamespaces, $enabledNamespaces ) );
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
			throw new \MWException( __METHOD__ . " expected a namespace ID." );
		}

		if ( $this->excludedNamespaces !== array() ) {
			return !$this->isExcluded( $namespace );
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
		if ( is_int( $namespace ) && ! in_array( $namespace, $this->excludedNamespaces ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if namespace is enabled for Wikibase, based on Settings::get( 'namespaces' )
	 *
	 * @since 0.4
	 *
	 * @param int $namespace
	 *
	 * @return bool
	 */
	protected function isEnabled( $namespace ) {
		if ( is_array( $this->enabledNamespaces ) && $this->enabledNamespaces !== array()
			&& !in_array( $namespace, $this->enabledNamespaces ) ) {
			return false;
		}

		return true;
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
	 * Get valid namespaces
	 *
	 * @since 0.4
	 *
	 * @return array|bool
	 */
	public function getValidNamespaces() {
		return array_diff( $this->enabledNamespaces, $this->excludedNamespaces );
	}

}
