<?php

namespace Wikibase;

/**
 * Interface for site configuration objects.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface SiteConfig {

	/**
	 * Returns the local identifier (ie "en") of the site.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getLocalId();

	/**
	 * Returns if inline links to this site should be allowed.
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function getLinkInline();

	/**
	 * returns if the sit should show up in intersite navigation interfaces.
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function getLinkNavigation();

	/**
	 * Returns if site.tld/path/key:pageTitle should forward users to  the page on
	 * the actual site, where "key" os either the local or global identifier.
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function getForward();

}