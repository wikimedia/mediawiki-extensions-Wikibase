<?php

namespace Wikibase\RDF;
use InvalidArgumentException;

/**
 * NTriplesRdfEmitter
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class NTriplesRdfEmitter extends RdfEmitterBase {

	/**
	 * @var N3Quoter
	 */
	private $quoter;

	private $currentSubject;

	private $currentPredicate;

	public function __construct() {
		parent::__construct( parent::DOCUMENT_ROLE );

		$this->quoter = new N3Quoter();
		$this->quoter->setAllowQNames( false );

		//NOTE: The RDF 1.1 spec of N-Triples allows full UTF-8, so escaping would not be required.
		//      However, as of 2015, many consumers of N-Triples still expect non-ASCII characters
		//      to be escaped.
		$this->quoter->setEscapeUnicode( true );

		$this->quoter->registerShorthand( 'a', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' );
		$this->quoter->registerPrefix( 'rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#' );
	}

	private function quoteResource( $s ) {
		if ( $s instanceof RdfEmitter ) {
			return $s;
		}

		//FIXME: nasty hack for little benefit
		return call_user_func_array( array( $this->quoter, 'quoteResource' ), func_get_args() );
	}

	protected function emitPrefix( $prefix, $uri ) {
		$this->quoter->registerPrefix( $prefix, $uri );
	}

	protected function emitSubject( $subject ) {
		$subject = $this->quoteResource( $subject );
		$this->currentSubject = $subject;
	}

	protected function emitPredicate( $verb ) {
		$verb = $this->quoteResource( $verb, 'a' );
		$this->currentPredicate = $verb;
	}

	private function emitTriple( $object ) {
		$this->emit( $this->currentSubject, ' ' );
		$this->emit( $this->currentPredicate, ' ' );
		$this->emit( $object );
	}

	protected function emitResource( $object ) {
		$object = $this->quoteResource( $object );
		$this->emitTriple( $object );
	}

	protected function emitText( $text, $language = null ) {
		$literal = $this->quoter->getLiteral( $text, '@', $language );
		$this->emitTriple( $literal );
	}

	protected function emitValue( $text, $type = null ) {
		$type = $type === null ? null : $this->quoteResource( $type );
		$literal = $this->quoter->getLiteral( $text, '^^', $type );
		$this->emitTriple( $literal );
	}

	protected function finishObject( $last = false ) {
		$this->emit( ' .', "\n" );
	}

}
