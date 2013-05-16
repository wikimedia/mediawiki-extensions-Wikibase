<?php

namespace Wikibase;

/**
 * RDF serialization for wikibase data model.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseRepo
 * @ingroup Content
 * @ingroup RDF
 *
 * @licence GNU GPL v2+
 * @author Anja Jentzsch < anja.jentzsch@wikimedia.de >
 * @author Thomas Pellissier Tanon
 * @author Daniel Kinzler
 */

use DataTypes\DataTypeFactory;
use EasyRdf_Exception;
use EasyRdf_Format;
use EasyRdf_Graph;
use EasyRdf_Namespace;
use Wikibase\Lib\EntityIdFormatter;

class RdfSerializer {

	/**
	 * @var DataTypeFactory
	 * @note: currently unused. keep?
	 */
	protected $dataTypeFactory;

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
	 * @param string                $uriBase
	 * @param EntityLookup|null     $entityLookup
	 * @param DataTypeFactory|null  $dataTypeFactory
	 * @param Lib\EntityIdFormatter $idFormatter
	 */
	public function __construct(
		EasyRdf_Format $format,
		$uriBase,
		$entityLookup,
		$dataTypeFactory,
		EntityIdFormatter $idFormatter
	) {
		$this->uriBase = $uriBase;
		$this->format = $format;
		$this->entityLookup = $entityLookup;
		$this->dataTypeFactory = $dataTypeFactory;
		$this->idFormatter = $idFormatter;
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
			$this->uriBase,
			$this->idFormatter
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
	public function buildGraphForEntity( Entity $entity, $revision = null ) {
		$builder = $this->newRdfBuilder();

		$builder->addEntity( $entity, $revision );
		if ( $this->entityLookup !== null ) {
			$builder->resolvedMentionedEntities( $this->entityLookup );
		}

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
	public function serializeEntity( Entity $entity, $revision = null ) {
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
