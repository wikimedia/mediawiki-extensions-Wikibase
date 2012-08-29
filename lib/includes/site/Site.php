<?php

/**
 * Interface for site objects.
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
 * @since 1.20
 *
 * @file
 * @ingroup Site
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface Site {

	const TYPE_UNKNOWN = 'unknown';
	const TYPE_MEDIAWIKI = 'mediawiki';

	const GROUP_NONE = 'none';

	const ID_INTERWIKI = 'interwiki';
	const ID_EQUIVALENT = 'equivalent';

	const SOURCE_LOCAL = 'local';

	/**
	 * Returns the global site identifier (ie enwiktionary).
	 *
	 * @since 1.20
	 *
	 * @return string
	 */
	public function getGlobalId();

	/**
	 * Sets the global site identifier (ie enwiktionary).
	 *
	 * @since 1.20
	 *
	 * @param string $globalId
	 */
	public function setGlobalId( $globalId );

	/**
	 * Returns the type of the site (ie mediawiki).
	 *
	 * @since 1.20
	 *
	 * @return string
	 */
	public function getType();

	/**
	 * Sets the type of the site (ie mediawiki).
	 *
	 * @since 1.20
	 *
	 * @param string $type
	 */
	public function setType( $type );

	/**
	 * Gets the type of the site (ie wikipedia).
	 *
	 * @since 1.20
	 *
	 * @return string
	 */
	public function getGroup();

	/**
	 * Sets the type of the site (ie wikipedia).
	 *
	 * @since 1.20
	 *
	 * @param string $group
	 */
	public function setGroup( $group );

	/**
	 * Returns the source of the site data (ie 'local', 'wikidata', 'my-magical-repo').
	 *
	 * @since 1.20
	 *
	 * @return string
	 */
	public function getSource();

	/**
	 * Sets the source of the site data (ie 'local', 'wikidata', 'my-magical-repo').
	 *
	 * @since 1.20
	 *
	 * @param string $source
	 */
	public function setSource( $source );

	/**
	 * Returns the protocol of the site, ie 'http://', 'irc://', '//'
	 *
	 * @since 1.20
	 *
	 * @return string
	 */
	public function getProtocol();

	/**
	 * Returns the domain of the site, ie en.wikipedia.org
	 *
	 * @since 1.20
	 *
	 * @return string
	 */
	public function getDomain();

	/**
	 * Returns language code of the sites primary language.
	 *
	 * @since 1.20
	 *
	 * @return string
	 */
	public function getLanguageCode();

	/**
	 * Sets language code of the sites primary language.
	 *
	 * @since 1.20
	 *
	 * @param string $languageCode
	 */
	public function setLanguageCode( $languageCode );

	/**
	 * Returns the full page path (protocol + domain + page name).
	 * The page title should go at the $1 marker. If the $pageName
	 * argument is provided, the marker will be replaced by it's value.
	 *
	 * @since 1.20
	 *
	 * @param string|false $pageName
	 *
	 * @return string
	 */
	//public function getPagePath( $pageName = false ); TODO

	/**
	 * Returns the normalized, canonical form of the given page name.
	 * How normalization is performed or what the properties of a normalized name are depends on the site.
	 * The general contract of this method is that the normalized form shall refer to the same content
	 * as the original form, and any other page name referring to the same content will have the same normalized form.
	 *
	 * @since 1.20
	 *
	 * @param string $pageName
	 *
	 * @return string the normalized page name
	 */
	public function normalizePageName( $pageName );

	/**
	 * Returns the interwiki link identifiers that can be used for this site.
	 *
	 * @since 1.20
	 *
	 * @return array of string
	 */
	public function getInterwikiIds();

	/**
	 * Returns the equivalent link identifiers that can be used to make
	 * the site show up in interfaces such as the "language links" section.
	 *
	 * @since 1.20
	 *
	 * @return array of string
	 */
	public function getNavigationIds();

	/**
	 * Adds an local identifier to the site.
	 *
	 * @since 1.20
	 *
	 * @param string $type The type of the identifier, element of the Site::ID_ enum
	 * @param string $identifier
	 */
	public function addLocalId( $type, $identifier );

	/**
	 * Adds an interwiki id to the site.
	 *
	 * @since 1.20
	 *
	 * @param string $identifier
	 */
	public function addInterwikiId( $identifier );

	/**
	 * Adds a navigation id to the site.
	 *
	 * @since 1.20
	 *
	 * @param string $identifier
	 */
	public function addNavigationId( $identifier );

	/**
	 * Saves the site.
	 *
	 * @since 1.20
	 *
	 * @param string|null $functionName
	 */
	public function save( $functionName = null );

	/**
	 * Returns the internal ID of the site.
	 *
	 * @since 1.20
	 *
	 * @return integer
	 */
	public function getInternalId();

	/**
	 * Sets the internal ID of the site.
	 *
	 * @since 1.20
	 *
	 * @param integer $id
	 */
	public function setInternalId( $id );

}