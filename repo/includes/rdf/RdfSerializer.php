<?php

namespace Wikibase;

use SiteList;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\RDF\NTriplesRdfWriter;
use Wikibase\RDF\RdfWriter;
use Wikibase\RDF\TurtleRdfWriter;
use Wikibase\RDF\XmlRdfWriter;

/**
 * RDF serialization for wikibase data model.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Anja Jentzsch < anja.jentzsch@wikimedia.de >
 * @author Thomas Pellissier Tanon
 * @author Daniel Kinzler
 */
class RdfSerializer implements RdfProducer {

	/**
	 * @var string
	 */
	private $baseUri;

	/**
	 * @var string
	 */
	private $dataUri;

	/**
	 * @var RdfWriter
	 */
	private $emitter;

	/**
	 * @var SiteList
	 */
	private $sites;

	/**
	 * @var String
	 */
	private $flavor;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @param RdfWriter $emitter
	 * @param string $baseUri
	 * @param string $dataUri
	 * @param SiteList $sites;
	 * @param EntityLookup $entityLookup
	 * @param integer $flavor
	 */
	public function __construct(
		RdfWriter $emitter,
		$baseUri,
		$dataUri,
		SiteList $sites,
		EntityLookup $entityLookup,
		$flavor
	) {
		$this->baseUri = $baseUri;
		$this->dataUri = $dataUri;
		$this->emitter = $emitter;
		$this->sites = $sites;
		$this->entityLookup = $entityLookup;
		$this->flavor = $flavor;
	}

	/**
	 * Returns an RdfWriter for the given format name.
	 * The name may be a MIME type, a file extension,
	 * or a canonical name.
	 *
	 * If no format is found for $name, this method returns null.
	 *
	 * @param string $name the name (file extension, mime type) of the desired format.
	 *
	 * @return RdfWriter|null the format object, or null if not found.
	 */
	public static function getRdfWriter( $name ) {
		switch ( strtolower( $name ) ) {
			case 'n3':
			case 'text/n3':
			case 'text/rdf+n3':
				// n3 falls through to turtle

			case 'ttl':
			case 'turtle':
			case 'text/turtle':
			case 'application/x-turtle':
				return new TurtleRdfWriter();

			case 'nt':
			case 'ntriples':
			case 'n-triples':
			case 'text/plain':
			case 'text/n-triples':
			case 'application/n-triples':
				return new NTriplesRdfWriter();

			case 'xml':
			case 'rdf':
			case 'application/rdf+xml':
				return new XmlRdfWriter();

			default:
				return null;
		}
	}

	public function getNamespaces() {
		return $this->newRdfBuilder()->getNamespaces(); //XXX: nasty hack!
	}

	/**
	 * Creates a new builder
	 *
	 * @return RdfBuilder
	 */
	public function newRdfBuilder() {
		//TODO: language filter

		// reset the emitter's output buffer
		$this->emitter->reset();

		$builder = new RdfBuilder(
			$this->sites,
			$this->baseUri,
			$this->dataUri,
			$this->entityLookup,
			$this->flavor,
			$this->emitter
		);

		return $builder;
	}

	/**
	 * Generates an RDF representing the given entity
	 *
	 * @param EntityRevision $entityRevision the entity to output.
	 *
	 * @return string rdf
	 */
	private function buildGraphForEntityRevision( EntityRevision $entityRevision ) {
		$builder = $this->newRdfBuilder();

		$builder->addEntityRevisionInfo(
			$entityRevision->getEntity()->getId(),
			$entityRevision->getRevisionId(),
			$entityRevision->getTimestamp()
		);

		$builder->addEntity( $entityRevision->getEntity() );

		$builder->resolvedMentionedEntities( $this->entityLookup ); //TODO: optional

		$rdf = $builder->getRDF();
		return $rdf;
	}

	/**
	 * Create dump header for RDF dump
	 * @param int $ts Timestamp (for testing)
	 * @return string RDF
	 */
	public function dumpHeader( $ts = 0) {
		$builder = $this->newRdfBuilder();

		$builder->addDumpHeader( $ts );

		return $builder->getRDF();
	}

	/**
	 * Returns the serialized entity.
	 * Shorthand for $this->serializeRdf( $this->buildGraphForEntity( $entity ) ).
	 *
	 * @param EntityRevision $entityRevision   the entity to serialize
	 *
	 * @return string
	 */
	public function serializeEntityRevision( EntityRevision $entityRevision ) {
		return $this->buildGraphForEntityRevision( $entityRevision );
	}

	/**
	 * @return string
	 */
	public function getDefaultMimeType() {
		return $this->emitter->getMimeType();
	}

}
