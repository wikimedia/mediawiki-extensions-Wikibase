<?php

namespace Wikibase;

/**
 * Service interface for triggering different kinds of page updates
 * and generally notifying the local wiki of external changes.
 *
 * Used by ChangeHandler as an interface to the local wiki.
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
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 *
 */
interface PageUpdater {

	/**
	 * Invalidates local cached of the given pages.
	 *
	 * @since    0.4
	 *
	 * @param \Title[] $titles The Titles of the pages to update
	 */
	public function purgeParserCache( array $titles );

	/**
	 * Invalidates external web cached of the given pages.
	 *
	 * @since    0.4
	 *
	 * @param \Title[] $titles The Titles of the pages to update
	 */
	public function purgeWebCache( array $titles );

	/**
	 * Schedules RefreshLinks jobs for the given titles
	 *
	 * @since    0.4
	 *
	 * @param \Title[] $titles The Titles of the pages to update
	 */
	public function scheduleRefreshLinks( array $titles );

	/**
	 * Injects an RC entry into the recentchanges, using the the given title and attribs
	 *
	 * @param \Title $title
	 * @param array $attribs
	 *
	 * @return bool
	 */
	public function injectRCRecord( \Title $title, array $attribs );
}