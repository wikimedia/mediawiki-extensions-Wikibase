<?php

namespace Wikibase\RDF;

/**
 * RdfWriter implementation for generating Turtle output.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class TurtleRdfWriter extends RdfWriterBase {

	/**
	 * @var N3Quoter
	 */
	private $quoter;

	public function __construct( $role = parent::DOCUMENT_ROLE, BNodeLabeler $labeler = null, N3Quoter $quoter = null ) {
		parent::__construct( $role, $labeler );

		$this->quoter = $quoter ?: new N3Quoter();
	}

	private function quoteResource( $s ) {
		if ( $s instanceof RdfWriter ) {
			return $s;
		}

		//FIXME: nasty hack for little benefit, move shorthand resolution to RdfWriterBase
		return call_user_func_array( array( $this->quoter, 'quoteResource' ), func_get_args() );
	}

	protected function writePrefix( $prefix, $uri ) {
		if ( $prefix !== '' ) {
			$prefix .= ' ';
		}

		$this->write( '@prefix ' . $prefix . ': ' . $this->quoter->quoteURI( $uri ), " .\n" );
	}

	protected function writeSubject( $subject ) {
		$subject = $this->quoteResource( $subject );
		$this->write( $subject );
	}

	protected function writePredicate( $verb ) {
		$verb = $this->quoteResource( $verb, 'a' );
		$this->write( $verb );
	}

	protected function writeResource( $object ) {
		$object = $this->quoteResource( $object );
		$this->write( $object );
	}

	protected function writeText( $text, $language = null ) {
		$literal = $this->quoter->getLiteral( $text, '@', $language );
		$this->write( $literal );
	}

	protected function writeValue( $text, $type = null ) {
		//TODO: shorthand form for xsd:integer|decimal|double|boolean
		$literal = $this->quoter->getLiteral( $text, '^^', $type );
		$this->write( $literal );
	}

	protected function beginSubject() {
		$this->write( "\n" );
	}

	protected function finishSubject() {
		$this->write( ' .', "\n" );
	}

	protected function beginPredicate( $first = false ) {
		if ( $first ) {
			$this->write( ' ' );
		}
	}

	protected function finishPredicate( $last = false ) {
		if ( !$last ) {
			$this->write( ' ;', "\n\t" );
		}
	}

	protected function beginObject( $first = false ) {
		if ( $first ) {
			$this->write( ' ' );
		}
	}

	protected function finishObject( $last = false ) {
		if ( !$last ) {
			$this->write( ',', "\n\t\t" );
		}
	}

	/**
	 * @param string $role
	 * @param BNodeLabeler $labeler
	 *
	 * @return RdfWriterBase
	 */
	protected function newSubWriter( $role, BNodeLabeler $labeler ) {
		$writer = new self( $role, $labeler, $this->quoter );

		return $writer;
	}

	/**
	 * @return string a MIME type
	 */
	public function getMimeType() {
		return 'text/turtle; charset=UTF-8';
	}
}
