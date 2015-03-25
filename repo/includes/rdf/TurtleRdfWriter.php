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

	public function writePrefixes( ) {
		$this->state( 'document' );

		foreach( $this->prefixes as $prefix => $uri ) {
			$uri = $this->quoter->escapeIRI( $uri );
			$this->write( "@prefix $prefix: <$uri> .\n" );
		}
	}

	protected function writeSubject( $base, $local = null ) {
		if( $local !== null ) {
			$this->write( "$base:$local" );
		} else {
			$this->writeIRI( $base );
		}
	}

	protected function writePredicate( $base, $local = null ) {
		if( $base === 'a' ) {
			$this->write( 'a' );
			return;
		}
		if( $local !== null ) {
			$this->write( "$base:$local" );
		} else {
			$this->writeIRI( $base );
		}
	}

	protected function writeResource( $base, $local = null ) {
		if( $local !== null) {
			$this->write( "$base:$local" );
		} else {
			$this->writeIRI( $base );
		}
	}

// 	protected function writeValue( $value, $typeBase = null, $typeLocal = null  ) {
// 		//TODO: shorthand form for xsd:integer|decimal|double|boolean
// 		parent::writeValue( $value, $typeBase, $typeLocal );
// 	}

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

	protected function transitionObjectDocument() {
		$this->write( " .\n" );
	}
	protected function transitionObjectSubject() {
		$this->write( " .\n\n" );
	}
	protected function transitionObjectPredicate() {
		$this->write( " ;\n\t" );
	}
	protected function transitionObjectObject() {
		$this->write( ",\n\t\t" );
	}

	protected function transitionObjectDrain() {
		$this->write( " .\n" );
	}

	protected function transitionDocumentSubject() {
		$this->write( "\n" );
	}


	protected function transitionSubjectPredicate() {
		$this->write( ' ' );
	}


	protected function transitionPredicateObject() {
		$this->write( ' ' );
	}

}
