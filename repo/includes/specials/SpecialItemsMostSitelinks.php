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
		return array(
			'tables' => array( 'page_props', 'page' ),
			'fields' => array(
				'namespace' => $itemNs,
				'title' => 'page_title',
				'value' => 'pp_sortkey'
			),
			'conds' => array(
				'pp_propname' => 'wb-sitelinks',
			),
			'join_conds' => array(
				'page' => array( 'INNER JOIN', 'page_id=pp_page' )
			),
		);
	}

	/**
	 * @param Skin $skin
	 * @param object $result Result row
	 * @return string
	 */
	public function formatResult( $skin, $result ) {
		$title = Title::makeTitleSafe( $result->namespace, $result->title );
		$titleSection = Title::makeTitleSafe( $result->namespace, $result->title, $fragment = 'sitelinks-wikipedia' );
		$label = $this->msg( 'wikibase-nsitelinks' )->numParams( $result->value )->escaped();
		return $this->getLanguage()->specialList(
			Linker::link( $title ),
			Linker::link( $titleSection, $label )
		);
	}

}
