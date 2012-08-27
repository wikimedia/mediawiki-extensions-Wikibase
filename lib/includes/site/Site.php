<?php

/**
 * Interface for site objects.
 *
 * @since 1.20
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface Site {

	/**
	 * Returns the global site identifier (ie enwiktionary).
	 *
	 * @since 1.20
	 *
	 * @return string
	 */
	public function getGlobalId();

	/**
	 * Returns the type of the site (ie mediawiki).
	 *
	 * @since 1.20
	 *
	 * @return string
	 */
	public function getType();

	/**
	 * Returns the type of the site (ie wikipedia).
	 *
	 * @since 1.20
	 *
	 * @return string
	 */
	public function getGroup();

	/**
	 * Returns the source of the site data (ie 'local', 'wikidata', 'my-magical-repo').
	 *
	 * @since 1.20
	 *
	 * @return string
	 */
	public function getSource();

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
	 * Returns the configuration associated with this site.
	 *
	 * @since 1.20
	 *
	 * @return SiteConfig
	 */
	public function getConfig();

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
	public function getPagePath( $pageName = false );

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
	 * Returns an array with additional data part of the
	 * site definition. This is meant for usage by fields
	 * we never need to search against and for those that
	 * are site type specific, ie "allows file uploads"
	 * for MediaWiki sites.
	 *
	 * @since 1.20
	 *
	 * @return array
	 */
	public function getExtraData();

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

}