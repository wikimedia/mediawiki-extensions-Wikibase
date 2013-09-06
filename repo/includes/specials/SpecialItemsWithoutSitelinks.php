<?php

use Wikibase\StoreFactory;

/**
 * Page for listing entities without label.
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
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class SpecialItemsWithoutSitelinks extends SpecialWikibaseQueryPage {

	public function __construct() {
		parent::__construct( 'ItemsWithoutSitelinks' );
	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @since 0.4
	 *
	 * @param string $subPage
	 * @return boolean
	 */
	public function execute( $subPage ) {
		if ( !parent::execute( $subPage ) ) {
			return false;
		}

		$this->showQuery();
	}

	/**
	 * @see SpecialWikibaseQueryPage::getResult
	 *
	 * @since 0.4
	 */
	protected function getResult( $offset = 0, $limit = 0 ) {
		$entityPerPage = StoreFactory::getStore( 'sqlstore' )->newEntityPerPage();
		return $entityPerPage->getItemsWithoutSitelinks( null, $limit, $offset );
	}

}
