<?php

namespace Wikibase;

/**
 * RDF mapping for wikibase data model.
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

class RdfSerializer {

	const ontologyBaseUri = 'http://www.wikidata.org'; //XXX: Denny made me put the "www" there...

	/**
	 * @var DataTypeFactory
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
	 * Map of gnames to namespace URIs
	 *
	 * @var array
	 */
	protected $namespaces = array();

	/**
	 * @param EasyRdf_Format  $format
	 * @param string          $uriBase
	 * @param EntityLookup    $entityLookup
	 * @param DataTypeFactory $dataTypeFactory
	 */
	public function __construct(
		EasyRdf_Format $format,
		$uriBase,
		EntityLookup $entityLookup,
		DataTypeFactory $dataTypeFactory
	) {
		$this->uriBase = $uriBase;
		$this->format = $format;
		$this->entityLookup = $entityLookup;
		$this->dataTypeFactory = $dataTypeFactory;

		$this->namespaces = array(
			'wikibase_ontology' => self::ontologyBaseUri . '/ontology/',
			'wikibase_entity' => $this->uriBase . '/entity/',
			'wikibase_data' => $this->uriBase . '/data/',
			'wikibase_property' => $this->uriBase . '/property/',
			'wikibase_value' => $this->uriBase . '/value/',
			'wikibase_qualifier' => $this->uriBase . '/qualifier/',
			'wikibase_statement' => $this->uriBase . '/statement/',
		);
	}

	/**
	 * Checks whether the necessary libraries for RDF serialization are installed.
	 */
	public static function isSupported() {
		// check that the submodule is present
		$file = __DIR__ . '/easyRdf/EasyRdf.php';
		return file_exists( $file ) && class_exists( 'EasyRdf_Graph' );
	}

	/**
	 * Returns an EasyRdf_Format object for the given format name.
	 * The name may be a MIME type or a file extension (or a format URI
	 * or canonical name).
	 *
	 * If no format is found for $name, or EasyRdf is not installed,
	 * this method returns null.
	 *
	 * @param $name the name (file extension, mime type) of the desired format.
	 *
	 * @return EasyRdf_Format|null the format object, or null if not found.
	 */
	public static function getFormat( $name ) {
		if ( !self::isSupported() ) {
			wfWarn( "EasyRdf not found" );
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
		return $this->namespaces;
	}

	/**
	 * Inits a new Graph
	 *
	 * @return EasyRdf_Graph
	 */
	protected function newRdfGraph() {
		//register namespaces (ugh, static crap)

		foreach ( $this->getNamespaces() as $gname => $uri ) {
			EasyRdf_Namespace::set( $gname, $uri );
		}

		return new EasyRdf_Graph();
	}

	/**
	 * Add an entity to the RDF graph
	 *
	 * @param Entity $entity the entity to output.
	 * @param \Revision $revision for meta data (optional)
	 *
	 * @return EasyRdf_Graph
	 */
	public function buildGraphForEntity( Entity $entity, \Revision $revision = null ) {
		$graph = $this->newRdfGraph();

		$entityUri = 'wikibase_id:' . ucfirst( $entity->getId()->getPrefixedId() );
		$entityResource = $graph->resource( $entityUri );

		//TODO: filter language

		foreach ( $entity->getLabels() as $languageCode => $labelText ) {
			//TODO: also skos:prefLabel
			$entityResource->addLiteral( 'rdfs:label', $labelText, $languageCode );
		}

		foreach ( $entity->getDescriptions() as $languageCode => $description ) {
			//TODO: use skos:note
			$entityResource->addLiteral( 'rdfs:comment', $description, $languageCode );
		}

		foreach ( $entity->getAllAliases() as $languageCode => $aliases ) {
			foreach ( $aliases as $alias ) {
				//TODO: use skos:altLabel
				$entityResource->addLiteral( 'wikibase_ontology:knownAs', $alias, $languageCode );
			}
		}

		$claims = $entity->getClaims();
		$claimsByProperty = array();
		foreach( $claims as $claim ) {
			$propertyId = $claim->getMainSnak()->getPropertyId();
			$claimsByProperty[$propertyId->getNumericId()][] = $claim;
		}

		//TODO: (optionally?) find referenced entities (items and properties)
		//      and include basic info about them

		/* @var Claim[] $claims */
		foreach( $claimsByProperty as $claims ) {
			$counter = 1;
			$propertyId = $claims[0]->getMainSnak()->getPropertyId();
			$propertyUri = 'wikibase_property:' . strval( $propertyId );

			/* @var Property $property */
			$property = $this->entityLookup->getEntity( $propertyId );

			//$property->getEntity()->getLabel( $languageCode );
			$valueUri = 'wikibase_value:' . strval( $propertyId );

			foreach( $claims as $claim ) {
				$snak = $claim->getMainSnak();
				if ( $snak instanceof PropertyValueSnak ) {
					$value = $claim->getMainSnak()->getDataValue()->getValue();

					$statementUri = 'wikibase_statement:' . ucfirst( $claim->getGuid() );
					$statementResource = $graph->resource( $statementUri, array( 'wikibase_ontology:Statement' ) );

					$entityResource->addResource( $propertyUri, $statementResource );
					if ( $property->getType() == $this->dataTypeFactory->getType( 'wikibase-item' ) ) {
						$value = 'wikibase_id:' . ucfirst( $value );
						$statementResource->addResource( $valueUri, $value );
					}
					if ( $property->getType() == $this->dataTypeFactory->getType( 'commonsMedia' ) ) {
						$statementResource->addResource( $valueUri, $value );
					}

					//TODO: sane mechanism for extending this? Some kind of expert logic?

					$counter += 1;
				}

				//TODO: handle NoValueSnak and SomeValueSnak!
			}
		}

		// TODO: sitelinks, use skos:isPrimarySubjectOf or foaf:primaryTopic?
		// + state language of resource
		// TODO: use rdf:about (or what?!) to link subject URI to document URI

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

	public function getDefaultMimeType() {
		return $this->format->getDefaultMimeType();
	}
}
