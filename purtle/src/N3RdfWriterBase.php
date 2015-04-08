<?php

namespace Wikimedia\Purtle;

use InvalidArgumentException;

/**
 * Base class for RdfWriter implementations that output an N3 dialect.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
abstract class N3RdfWriterBase extends RdfWriterBase {

	/**
	 * @var N3Quoter
	 */
	protected $quoter;

	public function __construct( $role = parent::DOCUMENT_ROLE, BNodeLabeler $labeler = null, N3Quoter $quoter = null ) {
		parent::__construct( $role, $labeler );

		$this->quoter = $quoter ?: new N3Quoter();
	}

	protected function writeRef( $base, $local = null ) {
		if ( $local === null ) {
			if( $base === 'a' ) {
				$this->write( 'a' );
			} else {
				$this->writeIRI( $base );
			}
		} else {
			$this->write( "$base:$local" );
		}
	}

	protected function writeShorthand( $shorthand ) {
// 		if ( $shorthand === null || $shorthand === '' ) {
// 			throw new InvalidArgumentException( '$shorthand must not be empty' );
// 		}

		$this->write( $shorthand );
	}

	protected function writeIRI( $iri, $trustIRI = false ) {
// 		if ( $iri === null || $iri === '' ) {
// 			throw new InvalidArgumentException( '$iri must not be empty' );
// 		}

// 		if ( $iri[0] === '_' || $iri[0] === ':' || $iri[0] === '/' || $iri[0] === '#' ) {
// 			throw new InvalidArgumentException( '$iri must be an absolute iri: ' . $iri );
// 		}
		if ( !$trustIRI ) {
			$iri = $this->quoter->escapeIRI( $iri );
		}
		$this->write( "<$iri>" );
	}

	protected function writeQName( $base, $local ) {
// 		if ( $base === null ) {
// 			throw new InvalidArgumentException( '$base must not be null' );
// 		}

// 		if ( $local === null || $local === '' ) {
// 			throw new InvalidArgumentException( '$local must not be empty' );
// 		}

		$this->write( "$base:$local" );
	}


	protected function writeText( $text, $language = null ) {
		$value = $this->quoter->escapeLiteral( $text );
		$this->write( '"' . $value . '"' );

		if ( $language !== null ) {
			$this->write( '@' . $language );
		}
	}

	protected function writeValue( $value, $typeBase = null, $typeLocal = null ) {
		$value = $this->quoter->escapeLiteral( $value );
		$this->write( '"' . $value. '"' );

		if ( $typeBase !== null ) {
			$this->write( '^^' );
			$this->writeRef( $typeBase, $typeLocal );
		}
	}

}
