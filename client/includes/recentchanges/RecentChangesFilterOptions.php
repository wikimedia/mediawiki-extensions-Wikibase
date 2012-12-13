<?php

namespace Wikibase;

/**
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
 */
class RecentChangesFilterOptions {

	protected $opts;

	public function __construct( \FormOptions $opts ) {
		$this->opts = $opts;
	}

	/**
	 * Is Wikibase recent changes feature disabled?
	 *
	 * @since 0.4
	 *
	 * @return bool
	 */
	protected function isDisabled() {
		$rcSetting = Settings::get( 'showExternalRecentChanges' );

		// sanity check for the setting
		if ( !is_bool( $rcSetting ) ) {
			$rcSetting = false;
		}

		return $rcSetting;
	}

	/**
	 * Is hidewikidata filter selected?
	 *
	 * @since 0.4
	 *
	 * @return bool
	 */
	protected function hideWikibase() {
		if ( isset( $this->opts['hidewikidata'] ) && $this->opts['hidewikidata'] === true ) {
			return true;
		}
		return false;
	}

	/**
	 * Do we show wikibase edits in recent changes?
	 *
	 * @since 0.4
	 *
	 * @return bool
	 */
	public function showWikibaseEdits() {
        if ( $this->isDisabled() || $this->opts['hideanons'] || $this->hideWikibase() ) {
			return false;
		}
		return true;
	}

}
