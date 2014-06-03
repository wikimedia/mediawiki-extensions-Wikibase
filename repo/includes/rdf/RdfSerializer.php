<?php

namespace Wikibase;

use EasyRdf_Exception;
use EasyRdf_Format;
use EasyRdf_Graph;
use SiteList;
use Wikibase\Lib\Store\EntityLookup;

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
class RdfSerializer {

	/**
	 * @var string
	 */
	private $baseUri;

	/**
	 * @var string
	 */
	private $dataUri;

	/**
	 * @var EasyRdf_Format
	 */
	private $format;

	/**
	 * @var SiteList
	 */
	private $sites;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @param EasyRdf_Format $format
	 * @param string $baseUri
	 * @param string $dataUri
	 * @param SiteList $sites;
	 * @param EntityLookup $entityLookup
	 */
	public function __construct(
		EasyRdf_Format $format,
		$baseUri,
		$dataUri,
		SiteList $sites,
		EntityLookup $entityLookup
	) {
		$this->baseUri = $baseUri;
		$this->dataUri = $dataUri;
		$this->format = $format;
		$this->sites = $sites;
		$this->entityLookup = $entityLookup;
	}

	/**
	 * Returns an EasyRdf_Format object for the given format name.
	 * The name may be a MIME type or a file extension (or a format URI
	 * or canonical name).
	 *
	 * If no format is found for $name, this method returns null.
	 *
	 * @param string $name the name (file extension, mime type) of the desired format.
	 *
	 * @return EasyRdf_Format|null the format object, or null if not found.
	 */
	public static function getFormat( $name ) {
		try {
			$format = EasyRdf_Format::getFormat( $name );
			return $format;
		} catch ( EasyRdf_Exception $ex ) {
			// noop
		}

		return null;
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
			$this->dataUri
		);

		return $builder;
	}

	/**
	 * Generates an RDF graph representing the given entity
	 *
	 * @param EntityRevision $entityRevision the entity to output.
	 *
	 * @return EasyRdf_Graph
	 */
	public function buildGraphForEntityRevision( EntityRevision $entityRevision ) {
		$builder = $this->newRdfBuilder();

		$builder->addEntityRevisionInfo(
			$entityRevision->getEntity()->getId(),
			$entityRevision->getRevision(),
			$entityRevision->getTimestamp()
		);

		$builder->addEntity( $entityRevision->getEntity() );

		$builder->resolvedMentionedEntities( $this->entityLookup ); //TODO: optional

		$graph = $builder->getGraph();
		return $graph;
	}

	/**
	 * Returns the serialized graph
	 *
	 * @param EasyRdf_Graph $graph the graph to serialize
	 *
	 * @return string
	 */
	public function serializeRdf( EasyRdf_Graph $graph ) {
		$serialiser = $this->format->newSerialiser();
		$data = $serialiser->serialise( $graph, $this->format->getName() );

		assert( is_string( $data ) );
		return $data;
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
		$graph = $this->buildGraphForEntityRevision( $entityRevision );
		$data = $this->serializeRdf( $graph );
		return $data;
	}

	/**
	 * @return string
	 */
	public function getDefaultMimeType() {
		return $this->format->getDefaultMimeType();
	}

}
