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
		if ( !preg_match( '!^\w*(:\w+)?$!', $name ) ) {
			throw new \InvalidArgumentException( 'Not a qname: ' . $name );
		}
	}

	private function tag( $name, $attributes = array(), $close = '' ) {
		$this->checkQName( $name );

		$this->write( '<', $name );

		foreach ( $attributes as $attr => $value ) {
			if ( is_int( $attr ) ) {
				// positional array entries are passed verbatim, may be callbacks.
				$this->write( $value );
				continue;
			}

			$this->checkQName( $attr );
			$this->write( ' ', $attr, '=', '"', $this->escape( $value ), '"' );
		}

		$this->write( $close, '>' );
	}

	private function close( $name ) {
		$this->write( '</', $name, '>' );
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

		// define a callback for generating namespace attributes
		$namespaces =& $this->namespaces;
		$namespaceAttrCallback = function() use ( &$namespaces ) {
			$attr = '';

			foreach ( $namespaces as $ns => $uri ) {
				$escapedUri = htmlspecialchars( $uri, ENT_QUOTES );
				$nss = $ns === '' ? '' : ":$ns";
				$attr .= " xmlns$nss=\"$escapedUri\"";
			}

			return $attr;
		};

		$this->tag( 'rdf:RDF', array( $namespaceAttrCallback ) );
		$this->write( "\n" );
	}

	protected function writePrefix( $prefix, $uri ) {
		$this->namespaces[$prefix] = $uri;
	}

	protected function writeSubject( $subject ) {
		$attr = $this->getTargetAttributes( 'about', $subject );

		$this->write( "\t" );
		$this->tag( 'rdf:Description', $attr );
		$this->write( "\n" );
	}

	/**
	 * Emit the root element
	 */
	protected function finishSubject() {
		$this->write( "\t" );
		$this->close( 'rdf:Description' );
		$this->write( "\n" );
	}

	protected function finishDocument() {
		// close document element
		$this->close( 'rdf:RDF' );
		$this->write( "\n" );
	}

	protected function writePredicate( $verb ) {
		if ( $verb === 'a' ) {
			$verb = 'rdf:type';
		}

		$this->checkQName( $verb );

		if ( $verb[0] === ':' ) {
			// empty prefix means default namespace.
			$verb = substr( $verb, 1 );
		}

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
