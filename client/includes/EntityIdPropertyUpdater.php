<?php

namespace Wikibase;

use ParserOutput;
use Title;
use Wikibase\DataModel\SimpleSiteLink;

/**
 * Handles wikibase_item page and parser output property
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
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert
 */
class EntityIdPropertyUpdater {

	/* @var SiteLinkLookup */
	protected $siteLinkLookup;

	/* @var string */
	protected $siteId;

	/**
	 * @since 0.4
	 *
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param string $siteId
	 */
	public function __construct( SiteLinkLookup $siteLinkLookup, $siteId ) {
		$this->siteLinkLookup = $siteLinkLookup;
		$this->siteId = $siteId;
	}

	/**
	 * Set parser output property with item id
	 *
	 * @since 0.4
	 *
	 * @param ParserOutput $out
	 * @param Title $title
	 */
	public function updateItemIdProperty( ParserOutput $out, Title $title ) {
		$siteLink = new SimpleSiteLink(
			$this->siteId,
			$title->getFullText()
		);

		// todo: do we really want to fetch item id twice during parsing?
		$itemId = $this->siteLinkLookup->getEntityIdForSiteLink( $siteLink );

		if ( $itemId instanceof EntityId ) {
			// @todo get prefixed id in nicer way, or maybe we want it to be numeric id
			$out->setProperty( 'wikibase_item', $itemId->getPrefixedId() );
		} else {
			// unset property, if it was set
			$this->unsetProperty( $out, 'wikibase_item' );

			wfDebugLog( __CLASS__, __FUNCTION__ . 'Trying to set wikibase_item property for '
				. $siteLink->getSiteId() . ':' . $siteLink->getPageName()
				. ' but $itemId is not an EntityId object.' );
		}
	}

	/**
	 * Unsets the wikibase_item property
	 * @todo: should use functionality in core, and if not exists, add it there.
	 *
	 * @since 0.4
	 *
	 * @param ParserOutput $out
	 */
	protected function unsetProperty( \ParserOutput $out, $propertyName ) {
		// unset property, if it was set
		$properties = $out->getProperties();

		if ( array_key_exists( $propertyName, $properties ) ) {
			unset( $properties[$propertyName] );
			$out->mProperties = $properties;
		}
	}
}
