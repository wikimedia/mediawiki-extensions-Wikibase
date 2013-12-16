<?php

namespace Wikibase;

use EasyRdf_Exception;
use EasyRdf_Format;
use EasyRdf_Graph;

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
	 * @var EntityLookup
	 */
	protected $entityLookup;

	/**
	 * @var EasyRdf_Format
	 */
	protected $format;

	/**
	 * @param EasyRdf_Format        $format
	 * @param string                $baseUri
	 * @param string                $dataUri
	 * @param EntityLookup          $entityLookup
	 */
	public function __construct(
		EasyRdf_Format $format,
		$baseUri,
		$dataUri,
		EntityLookup $entityLookup
	) {
		$this->baseUri = $baseUri;
		$this->dataUri = $dataUri;
		$this->format = $format;
		$this->entityLookup = $entityLookup;
	}

	/**
	 * Checks whether the necessary libraries for RDF serialization are installed.
	 */
	public static function isSupported() {
		return RdfBuilder::isSupported();
	}

	/**
	 * Returns an EasyRdf_Format object for the given format name.
	 * The name may be a MIME type or a file extension (or a format URI
	 * or canonical name).
	 *
	 * If no format is found for $name, or EasyRdf is not installed,
	 * this method returns null.
	 *
	 * @param string $name the name (file extension, mime type) of the desired format.
	 *
	 * @return EasyRdf_Format|null the format object, or null if not found.
	 */
	public static function getFormat( $name ) {
		if ( !self::isSupported() ) {
			wfDebug( __METHOD__ . ": EasyRdf not found\n" );
			return null;
		}

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
			$this->baseUri,
			$this->dataUri
		);

		return $builder;
	}

	/**
	 * Generates an RDF graph representing the given entity
	 *
	 * @param Entity $entity the entity to output.
	 * @param \Revision $revision for meta data (optional)
	 *
	 * @return EasyRdf_Graph
	 */
	public function buildGraphForEntity( Entity $entity, \Revision $revision = null ) {
		$builder = $this->newRdfBuilder();

		$builder->addEntity( $entity, $revision );
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
	 * @param Entity   $entity   the entity to serialize
	 * @param \Revision $revision for meta data (optional)
	 *
	 * @return string
	 */
	public function serializeEntity( Entity $entity, \Revision $revision = null ) {
		$graph = $this->buildGraphForEntity( $entity, $revision );
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
