<?php

namespace Wikibase;
use Title, Content, ParserOptions, ParserOutput, DataUpdate, WikiPage, User, Status;

/**
 * Content object for articles representing Wikibase queries.
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
class QueryContent extends EntityContent {

	/**
	 * @since 0.1
	 * @var Query
	 */
	protected $query;

	/**
	 * Constructor.
	 * Do not use to construct new stuff from outside of this class, use the static newFoobar methods.
	 * In other words: treat as protected (which it was, but now cannot be since we derive from Content).
	 * @protected
	 *
	 * @since 0.1
	 *
	 * @param Query $query
	 */
	public function __construct( Query $query ) {
		parent::__construct( CONTENT_MODEL_WIKIBASE_QUERY );

		$this->query = $query;
	}

	/**
	 * Create a new queryContent object for the provided query.
	 *
	 * @since 0.1
	 *
	 * @param Query $query
	 *
	 * @return QueryContent
	 */
	public static function newFromQuery( Query $query ) {
		return new static( $query );
	}

	/**
	 * Create a new QueryContent object from the provided Query data.
	 *
	 * @since 0.1
	 *
	 * @param array $data
	 *
	 * @return QueryContent
	 */
	public static function newFromArray( array $data ) {
		return new static( new QueryObject( $data ) );
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
	 * Gets the query that makes up this query content.
	 *
	 * @since 0.1
	 *
	 * @return Query
	 */
	public function getQuery() {
		return $this->query;
	}

	/**
	 * Sets the query that makes up this query content.
	 *
	 * @since 0.1
	 *
	 * @param Query $query
	 */
	public function setQuery( Query $query ) {
		$this->query = $query;
	}

	/**
	 * Returns a new empty QueryContent.
	 *
	 * @since 0.1
	 *
	 * @return QueryContent
	 */
	public static function newEmpty() {
		return new static( QueryObject::newEmpty() );
	}

	/**
	 * @see EntityContent::getEntity
	 *
	 * @since 0.1
	 *
	 * @return Query
	 */
	public function getEntity() {
		return $this->query;
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
			array( new EntityModificationUpdate( $this ) )
		);
	}
}
