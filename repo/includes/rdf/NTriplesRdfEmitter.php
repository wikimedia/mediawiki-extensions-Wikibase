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

	/**
	 * @return string a MIME type
	 */
	public function getMimeType() {
		//NOTE: Add charset=UTF-8 if and when the constructor configures $this->quoter
		//      to emit utf-8.
		return 'application/n-triples';
	}

}
