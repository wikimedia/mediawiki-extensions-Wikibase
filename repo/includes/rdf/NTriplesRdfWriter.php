<?php

namespace Wikibase\RDF;
use InvalidArgumentException;

/**
 * NTriplesRdfWriter
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class NTriplesRdfWriter extends RdfWriterBase {

	/**
	 * @var N3Quoter
	 */
	private $quoter;

	private $currentSubject;

	private $currentPredicate;

	public function __construct( $role = parent::DOCUMENT_ROLE, BNodeLabeler $labeler = null, N3Quoter $quoter = null ) {
		parent::__construct( $role, $labeler );

		$this->quoter = $quoter ?: new N3Quoter();
		$this->quoter->setAllowQNames( false );

		//NOTE: The RDF 1.1 spec of N-Triples allows full UTF-8, so escaping would not be required.
		//      However, as of 2015, many consumers of N-Triples still expect non-ASCII characters
		//      to be escaped.
		//NOTE: if this is changed, getMimeType must be changed accordingly.
		$this->quoter->setEscapeUnicode( true );

		$this->quoter->registerShorthand( 'a', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' );
		$this->quoter->registerPrefix( 'rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#' );
	}

	public function reset() {
		parent::reset();

		$this->quoter = new N3Quoter(); // FIXME: if the quoter was shared, this may cause inconsistencies
		$this->currentPredicate = null;
		$this->currentSubject = null;
	}

	private function quoteResource( $s ) {
		if ( $s instanceof RdfWriter ) {
			return $s;
		}

		//FIXME: nasty hack for little benefit
		return call_user_func_array( array( $this->quoter, 'quoteResource' ), func_get_args() );
	}

	protected function writePrefix( $prefix, $uri ) {
		$this->quoter->registerPrefix( $prefix, $uri );
	}

	protected function writeSubject( $subject ) {
		$subject = $this->quoteResource( $subject );
		$this->currentSubject = $subject;
	}

	protected function writePredicate( $verb ) {
		$verb = $this->quoteResource( $verb, 'a' );
		$this->currentPredicate = $verb;
	}

	private function writeTriple( $object ) {
		$this->write( $this->currentSubject, ' ' );
		$this->write( $this->currentPredicate, ' ' );
		$this->write( $object );
	}

	protected function writeResource( $object ) {
		$object = $this->quoteResource( $object );
		$this->writeTriple( $object );
	}

	protected function writeText( $text, $language = null ) {
		$literal = $this->quoter->getLiteral( $text, '@', $language );
		$this->writeTriple( $literal );
	}

	protected function writeValue( $text, $type = null ) {
		$type = $type === null ? null : $this->quoteResource( $type );
		$literal = $this->quoter->getLiteral( $text, '^^', $type );
		$this->writeTriple( $literal );
	}

	protected function finishObject( $last = false ) {
		$this->write( ' .', "\n" );
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
		//NOTE: Add charset=UTF-8 if and when the constructor configures $this->quoter
		//      to write utf-8.
		return 'application/n-triples';
	}

}
