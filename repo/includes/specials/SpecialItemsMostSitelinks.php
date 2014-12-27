<?php

namespace Wikibase\Repo\Specials;

use Linker;
use Title;
use QueryPage;

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

	function isSyndicated() {
		return false;
	}

	function sortDescending() {
		return true;
	}

	function isExpensive() {
                return true;
        }

	public function getGroupName() {
		return 'wikibaserepo';
        }

	function getQueryInfo() {
		return array (
			'tables' => array ( 'wb_items_per_site', 'wb_entity_per_page', 'page' ),
			'fields' => array ( 
				'namespace' => '"' . WB_NS_ITEM . '"',
				'title' => 'page_title',
				'value' => 'COUNT(page_title)' ),
				'join_conds' => array (
					'wb_entity_per_page' => array(
						'JOIN', array(
							'ips_item_id = epp_entity_id',
							'epp_entity_type = "item"'
						)
					),
					'page' => array(
						'JOIN', array(
							'epp_page_id = page_id',
							'page_namespace = "' . WB_NS_ITEM . '"',
							'page_is_redirect = 0',
							'page_content_model = "wikibase-item"'
						)
					)
				),
                                'options' => array ( 'GROUP BY' => array( 'page_title' ),
			),
		);
	}

	/**
	 * @param Skin $skin
	 * @param object $result Result row
	 * @return string
	 */
	function formatResult( $skin, $result ) {
		$title = Title::makeTitleSafe( $result->namespace, $result->title );
		$titleSection = Title::makeTitleSafe( $result->namespace, $result->title, $fragment = 'sitelinks-wikipedia' );
		$label = $this->msg( 'wikibase-nsitelinks' )->numParams( $result->value )->escaped();
		return $this->getLanguage()->specialList(
			Linker::link( $title ),
			Linker::link( $titleSection, $label )
		);
	}
}
