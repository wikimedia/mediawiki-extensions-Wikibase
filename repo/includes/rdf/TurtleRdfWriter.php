<?php

namespace Wikibase\RDF;

/**
 * RdfWriter implementation for generating Turtle output.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class TurtleRdfWriter extends N3RdfWriterBase {

	public function __construct( $role = parent::DOCUMENT_ROLE, BNodeLabeler $labeler = null, N3Quoter $quoter = null ) {
		parent::__construct( $role, $labeler, $quoter );
	}

	protected function writePrefix( $prefix, $uri ) {
		$this->write( "@prefix $prefix: <{$this->quoter->escapeIRI( $uri )}> .\n" );
	}

	protected function writeSubject( $base, $local = null ) {
		$this->writeRef( $base, $local );
	}

	protected function writePredicate( $base, $local = null ) {
		$this->writeRef( $base, $local );
	}

	protected function writeResource( $base, $local = null ) {
		$this->writeRef( $base, $local );
	}

	protected function writeValue( $value, $typeBase = null, $typeLocal = null  ) {
		//TODO: shorthand form for xsd:integer|decimal|double|boolean
		parent::writeValue( $value, $typeBase, $typeLocal );
	}

	protected function beginSubject( $first = false ) {
		$this->write( "\n" );
	}

	protected function finishSubject() {
		$this->write( " .\n" );
	}

	protected function beginPredicate( $first = false ) {
		if ( $first ) {
			$this->write( ' ' );
		}
	}

	protected function finishPredicate( $last = false ) {
		if ( !$last ) {
			$this->write( " ;\n\t" );
		}
	}

	protected function beginObject( $first = false ) {
		if ( $first ) {
			$this->write( ' ' );
		}
	}

	protected function finishObject( $last = false ) {
		if ( !$last ) {
			$this->write( ",\n\t\t" );
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
