<?php

namespace Wikibase\RDF;

/**
 * XML/RDF implementation of RdfWriter
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class XmlRdfWriter extends RdfWriterBase {

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

		$this->write( '<', $name );

		foreach ( $attributes as $attr => $value ) {
			$this->checkQName( $attr );
			$this->write( ' ', $attr, '=', '"', $this->escape( $value ), '"' );
		}

		$this->write( $close, '>' );
	}

	private function close( $name ) {
		$this->write( '</', $name, '>' );
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
		$this->write( '<?xml version="1.0"?>', "\n" );
	}

	protected function writePrefix( $prefix, $uri ) {
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
			//FIXME: do this in beginDocument, use a callback for the namespace declarations
			$attr = $this->getNamespaceAttributes( $this->namespaces );
			$this->tag( 'rdf:RDF', $attr );
			$this->write( "\n" );
		}
	}

	protected function writeSubject( $subject ) {
		$attr = $this->getTargetAttributes( 'about', $subject );

		$this->write( "\t" );
		$this->tag( 'rdf:Description', $attr );
		$this->write( "\n" );
	}

	/**
	 * Emit the root element
	 *
	 * @param bool $last
	 */
	protected function finishSubject( $last = false ) {
		$this->write( "\t" );
		$this->close( 'rdf:Description' );
		$this->write( "\n" );

		if ( $last ) {
			// close document element
			$this->close( 'rdf:RDF' );
			$this->write( "\n" );
		}
	}

	protected function writePredicate( $verb ) {
		if ( $verb === 'a' ) {
			$verb = 'rdf:type';
		}

		$this->checkQName( $verb );
		$this->currentPredicate = $verb;
	}

	protected function writeResource( $object ) {
		$attr = $this->getTargetAttributes( 'resource', $object );

		$this->write( "\t\t" );
		$this->tag( $this->currentPredicate, $attr, '/' );
		$this->write( "\n" );
	}

	protected function writeText( $text, $language = null ) {
		$attr = empty( $language ) ? array() : array( 'xml:lang' => $language );

		$this->write( "\t\t" );
		$this->tag( $this->currentPredicate, $attr );
		$this->write( $this->escape( $text ) );
		$this->close( $this->currentPredicate );
		$this->write( "\n" );
	}

	public function writeValue( $literal, $type = null ) {
		$attr = $attr = $this->getTargetAttributes( 'datatype', $type );

		$this->write( "\t\t" );
		$this->tag( $this->currentPredicate, $attr );
		$this->write( $this->escape( $literal ) );
		$this->close( $this->currentPredicate );
		$this->write( "\n" );
	}

	/**
	 * @param string $role
	 * @param BNodeLabeler $labeler
	 *
	 * @return RdfWriterBase
	 */
	protected function newSubWriter( $role, BNodeLabeler $labeler ) {
		//FIXME: "first subject" logic is messed up!

		$writer = new self( $role, $labeler );
		$writer->namespaces =& $this->namespaces;

		return $writer;
	}

	/**
	 * @return string a MIME type
	 */
	public function getMimeType() {
		return 'application/rdf+xml; charset=UTF-8';
	}

}
