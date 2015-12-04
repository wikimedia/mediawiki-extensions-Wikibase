<?php

namespace Wikimedia\Purtle;

use InvalidArgumentException;

/**
 * XML/RDF implementation of RdfWriter
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class XmlRdfWriter extends RdfWriterBase {

	public function __construct( $role = parent::DOCUMENT_ROLE, BNodeLabeler $labeler = null ) {
		parent::__construct( $role, $labeler );
		// Unfortunately, this is a bit ugly since PHP 5.3 can't call array($this, 'foo') directly
		// Also due to PHP 5.3 scope issues, used functions need to be public.
		// TODO: seek better solution (or move to PHP 5.4+)
		$self = $this;
		$this->transitionTable[self::STATE_START][self::STATE_DOCUMENT] = function() use ( $self ) {
			$self->beginDocument();
		};
		array( $this, 'beginDocument' );
		$this->transitionTable[self::STATE_DOCUMENT][self::STATE_FINISH] = function() use ( $self ) {
			$self->finishDocument();
		};
		$this->transitionTable[self::STATE_OBJECT][self::STATE_DOCUMENT] = function() use ( $self ) {
			$self->finishSubject();
		};
		$this->transitionTable[self::STATE_OBJECT][self::STATE_SUBJECT] = function() use ( $self ) {
			$self->finishSubject();
		};
	}

	private function escape( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES );
	}

	protected function expandSubject( &$base, &$local ) {
		$this->expandQName( $base, $local );
	}

	protected function expandPredicate( &$base, &$local ) {
		$this->expandShorthand( $base, $local );
	}

	protected function expandResource( &$base, &$local ) {
		$this->expandQName( $base, $local );
	}

	protected function expandType( &$base, &$local ) {
		$this->expandQName( $base, $local );
	}

	private function tag( $ns, $name, $attributes = array(), $content = null ) {
		$sep = $ns === '' ? '' : ':';
		$this->write( '<' . $ns . $sep . $name );

		foreach ( $attributes as $attr => $value ) {
			if ( is_int( $attr ) ) {
				// positional array entries are passed verbatim, may be callbacks.
				$this->write( $value );
				continue;
			}

			$this->write( " $attr=\"" . $this->escape( $value ) . '"' );
		}

		if ( $content === null ) {
			$this->write( '>' );
		} elseif ( $content === '' ) {
			$this->write( '/>' );
		} else {
			$this->write( '>' . $content );
			$this->close( $ns, $name );
		}
	}

	private function close( $ns, $name ) {
		$sep = $ns === '' ? '' : ':';
		$this->write( '</' . $ns . $sep . $name . '>' );
	}

	/**
	 * Generates an attribute list, containing the attribute given by $name, or rdf:nodeID
	 * if $target is a blank node id (starting with "_:"). If $target is a qname, an attempt
	 * is made to resolve it into a full IRI based on the namespaces registered by calling
	 * prefix().
	 *
	 * @param string $name the attribute name (without the 'rdf:' prefix)
	 * @param string|null $base
	 * @param string|null $local
	 *
	 * @throws InvalidArgumentException
	 * @return string[]
	 */
	private function getTargetAttributes( $name, $base, $local ) {
		if ( $base === null && $local === null ) {
			return array();
		}

		// handle blank
		if ( $base === '_' ) {
			$name = 'nodeID';
			$value = $local;
		} elseif ( $local !== null ) {
			throw new InvalidArgumentException( "Expected IRI, got QName: $base:$local" );
		} else {
			$value = $base;
		}

		return array(
			"rdf:$name" => $value
		);
	}

	/**
	 * Emit a document header.
	 */
	public function beginDocument() {
		$this->write( "<?xml version=\"1.0\"?>\n" );

		// define a callback for generating namespace attributes
		$self = $this;
		$namespaceAttrCallback = function() use ( $self ) {
			$attr = '';

			$namespaces = $self->getPrefixes();
			foreach ( $namespaces as $ns => $uri ) {
				$escapedUri = htmlspecialchars( $uri, ENT_QUOTES );
				$nss = $ns === '' ? '' : ":$ns";
				$attr .= " xmlns$nss=\"$escapedUri\"";
			}

			return $attr;
		};

		$this->tag( 'rdf', 'RDF', array( $namespaceAttrCallback ) );
		$this->write( "\n" );
	}

	protected function writeSubject( $base, $local = null ) {
		$attr = $this->getTargetAttributes( 'about', $base, $local );

		$this->write( "\t" );
		$this->tag( 'rdf', 'Description', $attr );
		$this->write( "\n" );
	}

	/**
	 * Emit the root element
	 */
	public function finishSubject() {
		$this->write( "\t" );
		$this->close( 'rdf', 'Description' );
		$this->write( "\n" );
	}

	/**
	 * Write document footer
	 */
	public function finishDocument() {
		// close document element
		$this->close( 'rdf', 'RDF' );
		$this->write( "\n" );
	}

	protected function writePredicate( $base, $local = null ) {
		// noop
	}

	protected function writeResource( $base, $local = null ) {
		$attr = $this->getTargetAttributes( 'resource', $base, $local );

		$this->write( "\t\t" );
		$this->tag( $this->currentPredicate[0], $this->currentPredicate[1], $attr, '' );
		$this->write( "\n" );
	}

	protected function writeText( $text, $language = null ) {
		$attr = empty( $language ) ? array() : array( 'xml:lang' => $language );

		$this->write( "\t\t" );
		$this->tag(
			$this->currentPredicate[0],
			$this->currentPredicate[1],
			$attr,
			$this->escape( $text )
		);
		$this->write( "\n" );
	}

	public function writeValue( $literal, $typeBase, $typeLocal = null ) {
		$attr = $this->getTargetAttributes( 'datatype', $typeBase, $typeLocal );

		$this->write( "\t\t" );
		$this->tag(
			$this->currentPredicate[0],
			$this->currentPredicate[1],
			$attr,
			$this->escape( $literal )
		);
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

		return $writer;
	}

	/**
	 * @return string a MIME type
	 */
	public function getMimeType() {
		return 'application/rdf+xml; charset=UTF-8';
	}

}
