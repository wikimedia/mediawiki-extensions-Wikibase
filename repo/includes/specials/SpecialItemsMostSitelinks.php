<?php

namespace Wikibase\Repo\Specials;

use ContentHandler;
use Linker;
use Title;
use QueryPage;
use Wikibase\DataModel\Entity\ItemId;

/**
 * Page for listing items with most sitelinks.
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
 * @since 0.5
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Maarten Dammers
 */
class SpecialItemsMostSitelinks extends QueryPage {

	public function __construct() {
		parent::__construct( 'ItemsMostSitelinks' );
	}

	public function isSyndicated() {
		return false;
	}

	public function sortDescending() {
		return true;
	}

	public function isExpensive() {
		return true;
	}

	public function getGroupName() {
		return 'wikibaserepo';
	}

	public function getQueryInfo() {
		$itemNs = ContentHandler::getForModelID( CONTENT_MODEL_WIKIBASE_ITEM )->getEntityNamespace();
		return array (
			'tables' => array ( 'wb_items_per_site' ),
			'fields' => array (
				'namespace' => $itemNs,
				'title' => 'ips_item_id',
				'value' => 'COUNT(ips_item_id)' ),
                                'options' => array ( 'GROUP BY' => array( 'ips_item_id' ),
			),
		);
	}

	/**
	 * @param Skin $skin
	 * @param object $result Result row
	 * @return string
	 */
	public function formatResult( $skin, $result ) {
		$itemId = ItemId::newFromNumber( (int)$result->title );
		$title = Title::makeTitleSafe( $result->namespace, $itemId );
		$titleSection = Title::makeTitleSafe( $result->namespace, $itemId, $fragment = 'sitelinks-wikipedia' );
		$label = $this->msg( 'wikibase-nsitelinks' )->numParams( $result->value )->escaped();
		return $this->getLanguage()->specialList(
			Linker::link( $title ),
			Linker::link( $titleSection, $label )
		);
	}

}
