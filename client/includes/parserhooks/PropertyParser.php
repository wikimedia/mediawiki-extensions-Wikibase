<?php

namespace Wikibase;

/**
 * {{#property}} parser function
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
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class PropertyParser {

	protected $site;

	protected $entityId;

	protected $entityLookup;

	protected $siteLinkLookup;

	/**
	 * @since 0.4
	 *
	 * @param \Title $title
	 * @param string $siteId
	 */
	public function __construct( \Title $title, $siteId ) {
		$this->entityLookup = ClientStoreFactory::getStore()->newEntityLookup();
		$this->siteLinkLookup = ClientStoreFactory::getStore()->newSiteLinkTable();

		$this->site = \Sites::singleton()->getSite( $siteId );
		$this->setEntityId( $title, $siteId );
	}

	/**
	 * Stores EntityId for item associated with a client title
	 *
	 * @since 0.4
	 *
	 * @param \Title $title
	 * @param string $siteId
	 *
	 * @return bool
	 */
	protected function setEntityId( \Title $title, $siteId ) {
		$itemId = $this->siteLinkLookup->getItemIdForLink(
			$siteId,
			$title->getFullText()
		);

		$this->entityId = new EntityId( 'item', $itemId );

		if ( $this->entityId === null ) {
			// @todo: maybe the item got deleted or sitelink removed
			// how to handle that for properties?
			throw new \MWException( 'Invalid item id associated with client wiki page.' );
		}

		return true;
	}

	/**
	 * Get id of entity associated with a client title
	 *
	 * @since 0.4
	 *
	 * @return EntityId
	 */
	protected function getEntityId() {
		return $this->entityId;
	}

	/**
	 * Formats an error message
	 * @todo is there really nothing like this function in core?
	 *
	 * @since 0.4
	 *
	 * @param string $messageKey
	 * @param $params[] message params
	 *
	 * @return string
	 */
	protected function error( $messageKey, $params = null ) {
		$msg = is_array( $params ) ? wfMessage( $messageKey, $params )->text() : wfMessage( $messageKey );
		return \Xml::element(
			'span',
			array( 'class' => 'error' ),
			$msg
		);
	}

	/**
	 * Get data value for a property of item associated with client wiki page
	 *
	 * @since 0.4
	 *
	 * @param Entity $entity
	 * @param string $propertyLabel
	 *
	 * @return Snak
	 */
	public function getMainSnak( Entity $entity, $propertyLabel ) {
		$claimsByProperty = array();

		foreach( $entity->getClaims() as $claim ) {
			$propertyId = $claim->getMainSnak()->getPropertyId();
			$claimsByProperty[$propertyId->getNumericId()][] = $claim;
		}

		if ( $claimsByProperty !== array() ) {
			foreach( $claimsByProperty as $id => $claims ) {
				foreach( $claims as $claim ) {
					$mainSnak = $claim->getMainSnak();
					$property = $this->entityLookup->getEntity( $mainSnak->getPropertyId() );
					$propertyLabels = $property->getLabels( array( $this->site->getLanguageCode() ) );
					foreach( $propertyLabels as $lang => $value ) {
						if ( $value === $propertyLabel ) {
							return $mainSnak;
						}
					}
				}
			}
		}

		return false;
	}

	/**
	 * Get data value for snak
	 * @todo handle all property types!
	 *
	 * @since 0.4
	 *
	 * @param Snak $snak
	 * @param string $propertyLabel
	 *
	 * @return string
	 */
	protected function getSnakValue( Snak $snak, $propertyLabel ) {
		$propertyValue = $snak->getDataValue();

		if ( $propertyValue instanceof \Wikibase\EntityId ) {
			$langCode = $this->site->getLanguageCode();
			$entity = $this->entityLookup->getEntity( $propertyValue );
			$labels = $entity->getLabels( array( $langCode ) );

			return $labels[$langCode];
		}

		return $this->error( 'wikibase-property-notsupportedyet', array( $propertyLabel ) );
	}

	/**
	 * @since 0.4
	 *
	 * @param string $propertyLabel
	 *
	 * @return string
	 */
	public function parse( $propertyLabel ) {
		$snak = $this->getMainSnak(
			$this->entityLookup->getEntity( $this->getEntityId() ),
			$propertyLabel
		);

		if ( $snak instanceof \Wikibase\Snak ) {
			return $this->getSnakValue( $snak, $propertyLabel );
		}

		return $this->error( 'wikibase-property-notfound', array( $propertyLabel ) );
	}

	/**
	 * @since 0.4
	 *
	 * @param \Parser &$parser
	 *
	 * @return bool
	 */
	public static function render( \Parser $parser, $propertyLabel ) {
		$instance = new self( $parser->getTitle(), Settings::get( 'siteGlobalID' ) );
		return $instance->parse( $propertyLabel );
	}

}
