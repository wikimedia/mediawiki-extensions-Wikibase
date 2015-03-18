<?php

namespace Wikibase\RDF;

/**
 * Factory for RdfEmitters
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class RdfEmitterFactory {

	/**
	 * Returns a list of canonical format names.
	 * These names for internal use with getMimeTypes() and getFileExtension(),
	 * they are not themselves MIME types or file extensions.
	 *
	 * @return string[]
	 */
	public function getSupportedFormats() {
		return array( 'n3', 'turtle', 'n-triples', 'rdfxml' );
	}

	/**
	 * Returns a list of mime types that correspond to the format.
	 * If the format is not known, the empty array is returned.
	 *
	 * @param string $format a format name, as returned by getSupportedFormats().
	 *
	 * @return string[]
	 */
	public function getMimeTypes( $format ) {
		//NOTE: Maintaining mime types and file extensions in the RdfEmitter implementations
		//      is tempting, but means we have to load all these classes to find the right
		//      one for a requested name. Better avoid that overhead when serving lots of
		//      HTTP requests.

		switch ( strtolower( $format ) ) {
			case 'n3':
				return array( 'text/n3', 'text/rdf+n3' );

			case 'turtle':
				return array( 'text/turtle', 'application/x-turtle' );

			case 'n-triples':
				return array( 'application/n-triples', 'text/n-triples', 'text/plain' );

			case 'rdfxml':
				return array( 'application/rdf+xml', 'application/xml', 'text/xml' );

			default:
				return array();
		}
	}

	/**
	 * Returns a file extension that correspond to the format.
	 * If the format is not known, the empty array is returned.
	 *
	 * @param string $format a format name, as returned by getSupportedFormats().
	 *
	 * @return string
	 */
	public function getFileExtension( $format ) {
		switch ( strtolower( $format ) ) {
			case 'n3':
				return 'n3';

			case 'turtle':
				return 'ttl';

			case 'n-triples':
				return 'nt';

			case 'rdfxml':
				return 'rdf';

			default:
				return array();
		}
	}

	/**
	 * Returns an RdfEmitter for the given format name.
	 * The name may be a MIME type, a file extension,
	 * or a canonical name.
	 *
	 * If no format is found for $format, this method returns null.
	 *
	 * @param string $format the name (file extension, mime type) of the desired format.
	 *
	 * @return RdfEmitter|null the format object, or null if not found.
	 */
	public function getEmitter( $format ) {
		switch ( strtolower( $format ) ) {
			case 'n3':
			case 'text/n3':
			case 'text/rdf+n3':
				// n3 falls through to turtle

			case 'ttl':
			case 'turtle':
			case 'text/turtle':
			case 'application/x-turtle':
				return new TurtleRdfEmitter();

			case 'nt':
			case 'ntriples':
			case 'n-triples':
			case 'text/plain':
			case 'text/n-triples':
			case 'application/ntriples':
			case 'application/n-triples':
				return new NTriplesRdfEmitter();

			case 'xml':
			case 'rdf':
			case 'rdfxml':
			case 'application/rdf+xml':
			case 'application/xml':
			case 'text/xml':
				return new XmlRdfEmitter();

			default:
				return null;
		}
	}

}
