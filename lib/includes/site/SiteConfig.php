<?php

/**
 * Interface for site configuration objects.
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
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface SiteConfig {

	/**
	 * Returns if site.tld/path/key:pageTitle should forward users to  the page on
	 * the actual site, where "key" os either the local or global identifier.
	 *
	 * @since 1.20
	 *
	 * @return boolean
	 */
	public function getForward();

	/**
	 * Returns an array with additional info part of the
	 * site condiguration. This is meant for usage by fields
	 * we never need to search against and for those that
	 * are site type specific, ie "allow template transclusion"
	 * for MediaWiki sites.
	 *
	 * @since 1.20
	 *
	 * @return array
	 */
	public function getExtraInfo();

}