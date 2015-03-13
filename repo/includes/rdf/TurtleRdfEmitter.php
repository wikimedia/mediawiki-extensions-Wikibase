<?php

namespace Wikibase\RDF;

/**
 * RdfEmitter implementation for generating Turtle output.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class TurtleRdfEmitter extends RdfEmitterBase {

	/**
	 * @var N3Quoter
	 */
	private $quoter;

	public function __construct( $role = parent::DOCUMENT_ROLE, BNodeLabeler $labeler = null, N3Quoter $quoter = null ) {
		parent::__construct( $role, $labeler );

		$this->quoter = $quoter ?: new N3Quoter();
	}

	private function quoteResource( $s ) {
		if ( $s instanceof RdfEmitter ) {
			return $s;
		}

		return $this->quoter->quoteResource( $s );
	}

	protected function emitPrefix( $prefix, $uri ) {
		$this->emit( '@prefix ' . $prefix . ' : ' . $this->quoter->quoteURI( $uri ), " .\n" );
	}

	protected function emitSubject( $subject ) {
		$subject = $this->quoteResource( $subject );
		$this->emit( $subject );
	}

	protected function emitPredicate( $verb ) {
		$verb = $this->quoteResource( $verb, 'a' );
		$this->emit( $verb );
	}

	protected function emitResource( $object ) {
		$object = $this->quoteResource( $object );
		$this->emit( $object );
	}

	protected function emitText( $text, $language = null ) {
		$literal = $this->quoter->getLiteral( $text, '@', $language );
		$this->emit( $literal );
	}

	protected function emitValue( $text, $type = null ) {
		$literal = $this->quoter->getLiteral( $text, '^^', $type );
		$this->emit( $literal );
	}

	protected function beginSubject() {
		$this->emit( "\n" );
	}

	protected function finishSubject() {
		$this->emit( ' .', "\n" );
	}

	protected function beginPredicate( $first = false ) {
		if ( $first ) {
			$this->emit( ' ' );
		}
	}

	protected function finishPredicate( $last = false ) {
		if ( !$last ) {
			$this->emit( ' ;', "\n\t" );
		}
	}

	protected function beginObject( $first = false ) {
		if ( $first ) {
			$this->emit( ' ' );
		}
	}

	protected function finishObject( $last = false ) {
		if ( !$last ) {
			$this->emit( ',', "\n\t\t" );
		}
	}

	/**
	 * @param string $role
	 * @param BNodeLabeler $labeler
	 *
	 * @return RdfEmitterBase
	 */
	protected function newSubEmitter( $role, BNodeLabeler $labeler ) {
		$emitter = new self( $role, $labeler, $this->quoter );

		return $emitter;
	}

}
