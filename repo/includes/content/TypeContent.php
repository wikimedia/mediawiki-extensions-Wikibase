<?php

namespace Wikibase;
use Title, Content, ParserOptions, ParserOutput, DataUpdate;

/**
 * Content object for articles representing Wikibase datatypes.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 * @ingroup Content
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TypeContent extends EntityContent {

	/**
	 * @since 0.1
	 * @var Type
	 */
	protected $type;

	/**
	 * Constructor.
	 * Do not use to construct new stuff from outside of this class, use the static newFoobar methods.
	 * In other words: treat as protected (which it was, but now cannot be since we derive from Content).
	 * @protected
	 *
	 * @since 0.1
	 *
	 * @param Type $type
	 */
	public function __construct( Type $type ) {
		parent::__construct( CONTENT_MODEL_WIKIBASE_TYPE );

		$this->type = $type;
	}

	/**
	 * Create a new TypeContent object for the provided query.
	 *
	 * @since 0.1
	 *
	 * @param Type $type
	 *
	 * @return TypeContent
	 */
	public static function newFromType( Type $type ) {
		return new static( $type );
	}

	/**
	 * Create a new TypeContent object from the provid`ed Type data.
	 *
	 * @since 0.1
	 *
	 * @param array $data
	 *
	 * @return TypeContent
	 */
	public static function newFromArray( array $data ) {
		return new static( new TypeObject( $data ) );
	}

	/**
	 * Gets the datatype that makes up this datatype content.
	 *
	 * @since 0.1
	 *
	 * @return Type
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Sets the datatype that makes up this datatype content.
	 *
	 * @since 0.1
	 *
	 * @param Type $type
	 */
	public function setType( Type $type ) {
		$this->type = $type;
	}

	/**
	 * Returns a new empty TypeContent.
	 *
	 * @since 0.1
	 *
	 * @return TypeContent
	 */
	public static function newEmpty() {
		return new static( TypeObject::newEmpty() );
	}

	/**
	 * @see EntityContent::getEntity
	 *
	 * @since 0.1
	 *
	 * @return Type
	 */
	public function getEntity() {
		return $this->type;
	}

	/**
	 * @see Content::getDeletionUpdates
	 *
	 * @param \WikiPage $page
	 * @param null|\ParserOutput $parserOutput
	 *
	 * @since 0.1
	 *
	 * @return array of \DataUpdate
	 */
	public function getDeletionUpdates( \WikiPage $page, \ParserOutput $parserOutput = null ) {
		return array_merge(
			parent::getDeletionUpdates( $page, $parserOutput ),
			array( /* new TypeDeletionUpdate( $this ) */ )
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
		$parserOutput = new ParserOutput();

		$parserOutput->setText( 'TODO' ); // TODO

		return $parserOutput;
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
	 * @return array of \DataUpdate
	 */
	public function getSecondaryDataUpdates( Title $title, Content $old = null,
											 $recursive = false, ParserOutput $parserOutput = null ) {

		return array_merge(
			parent::getSecondaryDataUpdates( $title, $old, $recursive, $parserOutput ),
			array( /* new TypeStructuredSave( $content ) */ )
		);
	}
}
