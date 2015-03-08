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
		$this->quoter->getAllowQNames( 'false' );

		$this->quoter->registerShorthand( 'a', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' );
	}

	private function quoteResource( $s ) {
		if ( $s instanceof RdfEmitter ) {
			return $s;
		}

		return $this->quoter->quoteResource( $s );
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
		$this->emit( $object, ' ' );
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
		$literal = $this->quoter->getLiteral( $text, '^^', $type );
		$this->emitTriple( $literal );
	}

	protected function finishObject( $last = false ) {
		$this->emit( ' .', "\n" );
	}

}
