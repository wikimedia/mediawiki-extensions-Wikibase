<?php

namespace Wikibase;

/**
 * Represents a single Wikibase item.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#Items
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
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class ItemObject extends EntityObject implements Item {

	/**
	 * @since 0.2
	 *
	 * @var Statements|null
	 */
	protected $statements = null;

	/**
	 * @see EntityObject::getIdPrefix
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public static function getIdPrefix() {
		return Settings::get( 'itemPrefix' );
	}

	/**
	 * @see Item::addSiteLink
	 *
	 * @since 0.1
	 *
	 * @param SiteLink $link the link to the target page
	 * @param string $updateType
	 *
	 * @return array|false Returns array on success, or false on failure
	 */
	public function addSiteLink( SiteLink $link, $updateType = 'add' ) {
		$siteId = $link->getSite()->getGlobalId();

		$success =
			( $updateType === 'add' && !array_key_exists( $siteId, $this->data['links'] ) )
				|| ( $updateType === 'update' && array_key_exists( $siteId, $this->data['links'] ) )
				|| ( $updateType === 'set' );

		if ( $success ) {
			$this->data['links'][$siteId] = $link->getPage();
		}

		return $success ? $link : false;
	}

	/**
	 * @see   Item::removeSiteLink
	 *
	 * @since 0.1
	 *
	 * @param string      $siteId the target site's id
	 * @param bool|string $pageName he target page's name (in normalized form)
	 *
	 * @return bool Success indicator
	 */
	public function removeSiteLink( $siteId, $pageName = false ) {
		if ( $pageName !== false) {
			$success = array_key_exists( $siteId, $this->data['links'] ) && $this->data['links'][$siteId] === $pageName;
		}
		else {
			$success = array_key_exists( $siteId, $this->data['links'] );
		}

		if ( $success ) {
			unset( $this->data['links'][$siteId] );
		}

		return $success;
	}

	/**
	 * @see Item::getSiteLinks
	 *
	 * @since 0.1
	 *
	 * @return array a list of SiteLink objects
	 */
	public function getSiteLinks() {
		$links = array();

		foreach ( $this->data['links'] as $globalSiteId => $title ) {
			$links[] = SiteLink::newFromText( $globalSiteId, $title );
		}

		return $links;
	}

	/**
	 * @see Item::getSiteLink
	 *
	 * @since 0.1
	 *
	 * @param String $siteId the id of the site to which to get the lin
	 *
	 * @return SiteLink|null the corresponding SiteLink object, or null
	 */
	public function getSiteLink( $siteId ) {
		if ( array_key_exists( $siteId, $this->data['links'] ) ) {
			return SiteLink::newFromText( $siteId, $this->data['links'][$siteId] );
		} else {
			return null;
		}
	}

	/**
	 * @see Item::isEmpty
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function isEmpty() {
		return parent::isEmpty()
			&& $this->data['links'] === array()
			&& !$this->hasStatements();
	}

	/**
	 * @see Item::hasStatements
	 *
	 * On top of being a convenience function, this implementation allows for doing
	 * the check without forcing an unstub in contrast to count( $item->getStatements() ).
	 *
	 * @since 0.2
	 *
	 * @return boolean
	 */
	public function hasStatements() {
		if ( $this->statements === null ) {
			return $this->data['statements'] !== array();
		}
		else {
			return count( $this->statements ) > 0;
		}
	}

	/**
	 * @see EntityObject::cleanStructure
	 *
	 * @since 0.1
	 *
	 * @param boolean $wipeExisting
	 */
	protected function cleanStructure( $wipeExisting = false ) {
		parent::cleanStructure( $wipeExisting );

		foreach ( array( 'links', 'statements' ) as $field ) {
			if (  $wipeExisting || !array_key_exists( $field, $this->data ) ) {
				$this->data[$field] = array();
			}
		}
	}

	/**
	 * @see Entity::newFromArray
	 *
	 * @since 0.1
	 *
	 * @param array $data
	 *
	 * @return Item
	 */
	public static function newFromArray( array $data ) {
		return new static( $data );
	}

	/**
	 * @since 0.1
	 *
	 * @return Item
	 */
	public static function newEmpty() {
		return self::newFromArray( array() );
	}

	/**
	 * @see Entity::getType
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getType() {
		return Item::ENTITY_TYPE;
	}

	/**
	 * @see Entity::getLocalType
	 *
	 * @since 0.2
	 *
	 * @return string
	 */
	public function getLocalizedType() {
		return wfMessage( 'wikibaselib-entity-item' )->parse();
	}

	/**
	 * @see Entity::getDiff
	 *
	 * @since 0.1
	 *
	 * @param Entity $target
	 *
	 * @return ItemDiff
	 */
	public function getDiff( Entity $target ) {
		return ItemDiff::newFromItems( $this, $target );
	}

	/**
	 * @see Statements::addStatement
	 *
	 * @since 0.2
	 *
	 * @param Statement $statement
	 */
	public function addStatement( Statement $statement ) {
		$this->unstubStatements();
		$this->statements->addStatement( $statement );
	}

	/**
	 * @see Statements::hasStatement
	 *
	 * @since 0.2
	 *
	 * @param Statement $statement
	 *
	 * @return boolean
	 */
	public function hasStatement( Statement $statement ) {
		$this->unstubStatements();
		return $this->statements->hasStatement( $statement );
	}

	/**
	 * @see Statements::removeStatement
	 *
	 * @since 0.2
	 *
	 * @param Statement $statement
	 */
	public function removeStatement( Statement $statement ) {
		$this->unstubStatements();
		$this->statements->removeStatement( $statement );
	}

	/**
	 * @see StatementAggregate::getStatements
	 *
	 * @since 0.2
	 *
	 * @return Statements
	 */
	public function getStatements() {
		$this->unstubStatements();
		return $this->statements;
	}

	/**
	 * Unsturbs the statements from the JSOn into the $statements field
	 * if this field is not already set.
	 *
	 * @since 0.2
	 *
	 * @return Statements
	 */
	protected function unstubStatements() {
		if ( $this->statements === null ) {
			$this->statements = new StatementList();

			foreach ( $this->data['statements'] as $statementSerialization ) {
				// TODO: right now using PHP serialization as the final JSON structure has not been decided upon yet
				$this->statements->addStatement( unserialize( $statementSerialization ) );
			}
		}
	}

	/**
	 * Takes the statements element of the $data array of an item and writes the statements to it as stubs.
	 *
	 * @since 0.2
	 *
	 * @param array $statements
	 *
	 * @return array
	 */
	protected function getStubbedStatements( array $statements ) {
		if ( $this->statements !== null ) {
			$statements = array();

			foreach ( $this->statements as $statement ) {
				// TODO: right now using PHP serialization as the final JSON structure has not been decided upon yet
				$statements[] = serialize( $statement );
			}
		}

		return $statements;
	}

	/**
	 * @see Entity::stub
	 *
	 * @since 0.2
	 */
	public function stub() {
		parent::stub();
		$this->data['statements'] = $this->getStubbedStatements( $this->data['statements'] );
	}

	/**
	 * @see Item::setStatements
	 *
	 * @since 0.2
	 *
	 * @param Statements $statements
	 */
	public function setStatements( Statements $statements ) {
		$this->statements = $statements;
	}

	/**
	 * @see Entity::toArray
	 *
	 * @since 0.2
	 *
	 * @return array
	 */
	public function toArray() {
		$data = parent::toArray();

		$data['statements'] = $this->getStubbedStatements( $data['statements'] );

		return $data;
	}

}
