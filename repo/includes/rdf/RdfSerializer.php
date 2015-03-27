<?php

namespace Wikibase;

use BagOStuff;
use SiteList;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;
use Wikibase\Lib\Store\EntityLookup;
use Wikimedia\Purtle\RdfWriter;
use Wikimedia\Purtle\RdfWriterFactory;

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
	 * @var PropertyDataTypeLookup
	 */
	private $propertyLookup;

	/**
	 * Hash to store seen references/values for deduplication
	 * @var BagOStuff
	 */
	private $dedupBag;

	/**
	 * @param EasyRdf_Format $format
	 * @param string $baseUri
	 * @param string $dataUri
	 * @param SiteList $sites;
	 * @param EntityLookup $entityLookup
	 * @param PropertyDataTypeLookup $propertyLookup
	 * @param int $flavor
	 * @param BagOStuff|null $dedupBag
	 */
	public function __construct(
		RdfWriter $emitter,
		$baseUri,
		$dataUri,
		SiteList $sites,
		PropertyDataTypeLookup $propertyLookup,
		EntityLookup $entityLookup,
		$flavor,
		BagOStuff $dedupBag = null
	) {
		$this->baseUri = $baseUri;
		$this->dataUri = $dataUri;
		$this->emitter = $emitter;
		$this->sites = $sites;
		$this->entityLookup = $entityLookup;
		$this->propertyLookup = $propertyLookup;
		$this->flavor = $flavor;
		$this->dedupBag = $dedupBag;
	}

	/**
	 * Returns an RdfWriter for the given format name.
	 * The name may be a MIME type, a file extension,
	 * or a canonical name.
	 *
	 * If no format is found for $name, this method returns null.
	 *
	 * @deprecated use a RdfWriterFactory instead
	 *
	 * @param string $name the name (file extension, mime type) of the desired format.
	 *
	 * @return RdfWriter|null the format object, or null if not found.
	 */
	public static function getRdfWriter( $name ) {
		$factory = new RdfWriterFactory();
		$format = $factory->getFormatName( $name );

		if ( !$format ) {
			return null;
		}

		return $factory->getWriter( $format );
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

		$builder = new RdfBuilder(
			$this->sites,
			$this->baseUri,
			$this->dataUri,
			$this->propertyLookup,
			$this->flavor,
			$this->emitter,
			$this->dedupBag
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
		// reset the emitter's output buffer
		$this->emitter->reset();

		$builder = $this->newRdfBuilder();

		$builder->addEntityRevisionInfo(
			$entityRevision->getEntity()->getId(),
			$entityRevision->getRevisionId(),
			$entityRevision->getTimestamp()
		);

		$builder->addEntity( $entityRevision->getEntity() );

		$builder->resolveMentionedEntities( $this->entityLookup ); //TODO: optional

		$rdf = $builder->getRDF();
		return $rdf;
	}

	/**
	 * Start RDF document
	 * @param int $ts Timestamp (for testing)
	 * @return string RDF
	 */
	public function startDocument( ) {
		$builder = $this->newRdfBuilder();
		$builder->startDocument();
		return $builder->getRDF();
	}

	/**
	 * Start RDF dump
	 * @param int $ts Timestamp (for testing)
	 * @return string RDF
	 */
	public function startDump( $ts = 0 ) {
		$builder = $this->newRdfBuilder();
		$builder->startDocument();
		$builder->addDumpHeader( $ts );
		return $builder->getRDF();
	}

	/**
	 * Finish RDF document
	 * @param int $ts Timestamp (for testing)
	 * @return string RDF
	 */
	public function finishDocument( ) {
		$builder = $this->newRdfBuilder();
		$builder->finishDocument();
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
