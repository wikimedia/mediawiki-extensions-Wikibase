<?php

namespace Wikibase\RDF;

/**
 * XML/RDF implementation of RdfEmitter
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class XmlRdfEmitter extends RdfEmitterBase {

	private $currentPredicate  = null;

	private $namespaces = array();

	public function __construct( $role = parent::DOCUMENT_ROLE, BNodeLabeler $labeler = null ) {
		parent::__construct( $role, $labeler );

		$this->namespaces['rdf'] = "http://www.w3.org/1999/02/22-rdf-syntax-ns#";
	}

	public function reset() {
		parent::reset();

		$this->namespaces = array();
		$this->currentPredicate = null;
	}

	private function escape( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES );
	}

	private function checkQName( $name ) {
		if ( !preg_match( '!^\w+(:\w+)?$!', $name ) ) {
			throw new \InvalidArgumentException( 'Not a qname: ' . $name );
		}
	}

	private function tag( $name, $attributes = array(), $close = '' ) {
		$this->checkQName( $name );

		$this->emit( '<', $name );

		foreach ( $attributes as $attr => $value ) {
			$this->checkQName( $attr );
			$this->emit( ' ', $attr, '=', '"', $this->escape( $value ), '"' );
		}

		$this->emit( $close, '>' );
	}

	private function close( $name ) {
		$this->emit( '</', $name, '>' );
	}

	private function getNamespaceAttributes( array $namespaces ) {
		$attributes = array();

		foreach ( $namespaces as $ns => $uri ) {
			$aname = 'xmlns:' . $ns;
			$attributes[ $aname ] = $uri;
		}

		return $attributes;
	}

	/**
	 * Generates an attribute list, containing the attribute given by $name, or rdf:nodeID
	 * if $target is a blank node id (starting with "_:"). If $target is a qname, an attempt
	 * is made to resolve it into a full IRI based on the namespaces registered by calling
	 * prefix().
	 *
	 * @param string $name the attribute name (without namespace)
	 * @param string|null $target the target (IRI or QName)
	 *
	 * @return string[]
	 */
	private function getTargetAttributes( $name, $target ) {
		if ( $target === null | $target === '' ) {
			return array();
		}

		// handle blank
		if ( strlen( $target ) > 0 && $target[0] === '_' && $target[1] === ':' ) {
			$name = 'nodeID';
			$target = substr( $target, 2 );
		}

		// resolve qname
		if ( preg_match( '!^(\w+):(\w+)$!', $target, $m ) ) {
			$ns = $m[1];
			if ( isset( $this->namespaces[$ns] ) ) {
				$target = $this->namespaces[$ns] . $m[2];
			}
		}

		return array(
			"rdf:$name" => $target
		);
	}

	/**
	 * Emit a document header.
	 */
	protected function beginDocument() {
		$this->emit( '<?xml version="1.0"?>', "\n" );
	}

	protected function emitPrefix( $prefix, $uri ) {
		$this->namespaces[$prefix] = $uri;
	}

	/**
	 * Emit the root element
	 *
	 * @param bool $first
	 */
	protected function beginSubject( $first = false ) {
		if ( $first ) {
			// begin document element
			$attr = $this->getNamespaceAttributes( $this->namespaces );
			$this->tag( 'rdf:RDF', $attr );
			$this->emit( "\n" );
		}
	}

	protected function emitSubject( $subject ) {
		$attr = $this->getTargetAttributes( 'about', $subject );

		$this->emit( "\t" );
		$this->tag( 'rdf:Description', $attr );
		$this->emit( "\n" );
	}

	/**
	 * Emit the root element
	 *
	 * @param bool $last
	 */
	protected function finishSubject( $last = false ) {
		$this->emit( "\t" );
		$this->close( 'rdf:Description' );
		$this->emit( "\n" );

		if ( $last ) {
			// close document element
			$this->close( 'rdf:RDF' );
			$this->emit( "\n" );
		}
	}

	protected function emitPredicate( $verb ) {
		if ( $verb === 'a' ) {
			$verb = 'rdf:type';
		}

		$this->checkQName( $verb );
		$this->currentPredicate = $verb;
	}

	protected function emitResource( $object ) {
		$attr = $this->getTargetAttributes( 'resource', $object );

		$this->emit( "\t\t" );
		$this->tag( $this->currentPredicate, $attr, '/' );
		$this->emit( "\n" );
	}

	protected function emitText( $text, $language = null ) {
		$attr = empty( $language ) ? array() : array( 'xml:lang' => $language );

		$this->emit( "\t\t" );
		$this->tag( $this->currentPredicate, $attr );
		$this->emit( $this->escape( $text ) );
		$this->close( $this->currentPredicate );
		$this->emit( "\n" );
	}

	public function emitValue( $literal, $type = null ) {
		$attr = $attr = $this->getTargetAttributes( 'datatype', $type );

		$this->emit( "\t\t" );
		$this->tag( $this->currentPredicate, $attr );
		$this->emit( $this->escape( $literal ) );
		$this->close( $this->currentPredicate );
		$this->emit( "\n" );
	}

	/**
	 * @param string $role
	 * @param BNodeLabeler $labeler
	 *
	 * @return RdfEmitterBase
	 */
	protected function newSubEmitter( $role, BNodeLabeler $labeler ) {
		//FIXME: "first subject" logic is messed up!

		$emitter = new self( $role, $labeler );
		$emitter->namespaces =& $this->namespaces;

		return $emitter;
	}

	/**
	 * @return string a MIME type
	 */
	public function getMimeType() {
		return 'application/rdf+xml; charset=UTF-8';
	}

}
