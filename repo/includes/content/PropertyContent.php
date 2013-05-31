<?php

namespace Wikibase;
use Title, Content, ParserOptions, ParserOutput, WikiPage, User, Status, DataUpdate;
use \ValueFormatters\ValueFormatterFactory;

/**
 * Content object for articles representing Wikibase properties.
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
 * @ingroup WikibaseRepo
 * @ingroup Content
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyContent extends EntityContent {

	/**
	 * @since 0.1
	 * @var Property
	 */
	protected $property;

	/**
	 * Constructor.
	 * Do not use to construct new stuff from outside of this class, use the static newFoobar methods.
	 * In other words: treat as protected (which it was, but now cannot be since we derive from Content).
	 * @protected
	 *
	 * @since 0.1
	 *
	 * @param Property $property
	 */
	public function __construct( Property $property ) {
		parent::__construct( CONTENT_MODEL_WIKIBASE_PROPERTY );
		$this->property = $property;
	}

	/**
	 * Create a new propertyContent object for the provided property.
	 *
	 * @since 0.1
	 *
	 * @param Property $property
	 *
	 * @return PropertyContent
	 */
	public static function newFromProperty( Property $property ) {
		return new static( $property );
	}

	/**
	 * Create a new PropertyContent object from the provided Property data.
	 *
	 * @since 0.1
	 *
	 * @param array $data
	 *
	 * @return PropertyContent
	 */
	public static function newFromArray( array $data ) {
		return new static( new Property( $data ) );
	}

	/**
	 * @see Content::prepareSave
	 *
	 * @since 0.1
	 *
	 * @param WikiPage $page
	 * @param int      $flags
	 * @param int      $baseRevId
	 * @param User     $user
	 *
	 * @return Status
	 */
	public function prepareSave( WikiPage $page, $flags, $baseRevId, User $user ) {
		wfProfileIn( __METHOD__ );
		$status = parent::prepareSave( $page, $flags, $baseRevId, $user );

		if ( $status->isOK() ) {
			$this->addLabelUniquenessConflicts( $status );
		}

		wfProfileOut( __METHOD__ );
		return $status;
	}

	/**
	 * Gets the property that makes up this property content.
	 *
	 * @since 0.1
	 *
	 * @return Property
	 */
	public function getProperty() {
		return $this->property;
	}

	/**
	 * Sets the property that makes up this property content.
	 *
	 * @since 0.1
	 *
	 * @param Property $property
	 */
	public function setProperty( Property $property ) {
		$this->property = $property;
	}

	/**
	 * Returns a new empty PropertyContent.
	 *
	 * @since 0.1
	 *
	 * @return PropertyContent
	 */
	public static function newEmpty() {
		return new static( Property::newEmpty() );
	}

	/**
	 * @see EntityContent::getEntity
	 *
	 * @since 0.1
	 *
	 * @return Property
	 */
	public function getEntity() {
		return $this->property;
	}

	/**
	 * @see Content::getDeletionUpdates
	 *
	 * @param \WikiPage $page
	 * @param null|\ParserOutput $parserOutput
	 *
	 * @since 0.1
	 *
	 * @return DataUpdate[]
	 */
	public function getDeletionUpdates( \WikiPage $page, \ParserOutput $parserOutput = null ) {
		return array_merge(
			parent::getDeletionUpdates( $page, $parserOutput ),
			array( new EntityDeletionUpdate( $this ) )
		);
	}

	/**
	 * Returns a ParserOutput object containing the HTML.
	 *
	 * @since 0.1
	 *
	 * @param Title $title
	 * @param null $revId
	 * @param null|ParserOptions $options
	 * @param bool $generateHtml
	 *
	 * @return ParserOutput
	 */
	public function getParserOutput( Title $title, $revId = null, ParserOptions $options = null, $generateHtml = true )  {
		$valueFormatters = new ValueFormatterFactory( $GLOBALS['wgValueFormatters'] );

		$propertyView = new PropertyView( $valueFormatters );
		return $propertyView->getParserOutput( $this, $options, $generateHtml );
	}

	/**
	 * @see ContentHandler::getSecondaryDataUpdates
	 *
	 * @since 0.1
	 *
	 * @param Title $title
	 * @param Content|null $old
	 * @param boolean $recursive
	 *
	 * @param null|ParserOutput $parserOutput
	 *
	 * @return DataUpdate[]
	 */
	public function getSecondaryDataUpdates( Title $title, Content $old = null,
		$recursive = false, ParserOutput $parserOutput = null ) {

		return array_merge(
			parent::getSecondaryDataUpdates( $title, $old, $recursive, $parserOutput ),
			array( new EntityModificationUpdate( $this ) )
		);
	}

}
