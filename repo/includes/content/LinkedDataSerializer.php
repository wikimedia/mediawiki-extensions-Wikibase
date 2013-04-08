<?php

namespace Wikibase;

/**
 * Linked Data mapping for Wikidata data model.
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
 */

use EasyRdf_Graph;
use EasyRdf_Namespace;
require_once "easyRdf/EasyRdf.php";

class LinkedDataSerializer {

	/*
	 * @var EasyRdf_Graph
	 */
	protected $graph;

	protected $rdf_serializer = array( "rdf" => "rdfxml", "nt" => "ntriples" );

	protected $wikidataItemNamespace = "http://wikidata.org/id/Q";
	protected $wikidataOntologyNamespace = "http://wikidata.org/ontology/";
	protected $wikidataPropertyNamespace = "http://wikidata.org/property/";
	// FIXME: vocabulary and property namespace should be the same?
	protected $wikidataValueNamespace = "http://wikidata.org/value/";
	protected $wikidataStatementNamespace = "http://wikidata.org/statement/";

	public function __construct() {
		self::createRdfGraph();
	}


	/**
	 * Inits a new Graph
	 *
	 * @return EasyRdf_Graph
	 */
	protected function createRdfGraph() {
		$this->graph = new EasyRdf_Graph();
		EasyRdf_Namespace::set( 'wikidata_ontology', $this->wikidataOntologyNamespace );
		EasyRdf_Namespace::set( 'wikidata_statement', $this->wikidataStatementNamespace );
	}

	/**
	 * Returns the rdf graph for an item
	 *
	 * @param EntityContent $entity the entity to output.
	 * @param String $format the RDF format to output.
	 *
	 * @return string
	 */
	public function getRdfForItem( EntityContent $entity, $format ) {
		$entityId = $entity->getEntity()->getId()->getNumericId();
		$entityUri = $this->wikidataItemNamespace . strval( $entityId );
		$entityResource = $this->graph->resource( $entityUri );

		foreach ( $entity->getEntity()->getLabels() as $languageCode => $labelText ) {
			$entityResource->addLiteral( 'rdfs:label', $labelText, $languageCode );
		}

		foreach ( $entity->getEntity()->getDescriptions() as $languageCode => $description ) {
			$entityResource->addLiteral( 'rdfs:comment', $description, $languageCode );
		}

		foreach ( $entity->getEntity()->getAllAliases() as $languageCode => $aliases ) {
			foreach ( $aliases as $alias ) {
				$entityResource->addLiteral( $entityUri, 'wikidata_ontology:knownAs', $alias, $languageCode );
			}
		}

		$libRegistry = new \Wikibase\LibRegistry( \Wikibase\Settings::singleton() );
		$claims = $entity->getEntity()->getClaims();
		$claimsByProperty = array();
		foreach( $claims as $claim ) {
			$propertyId = $claim->getMainSnak()->getPropertyId();
			$claimsByProperty[$propertyId->getNumericId()][] = $claim;
		}

		foreach( $claimsByProperty as $claims ) {
			$counter = 1;
			$propertyId = $claims[0]->getMainSnak()->getPropertyId();
			$propertyUri = $this->wikidataPropertyNamespace . strval( $propertyId );
			$property = EntityContentFactory::singleton()->getFromId( $propertyId )->getProperty();
			//$property->getEntity()->getLabel( $languageCode );
			$valueUri = $this->wikidataValueNamespace . strval( $propertyId );

			foreach( $claims as $claim ) {
				if ( $claim->getMainSnak()->getType() === 'value' ) {
					$value = $claim->getMainSnak()->getDataValue()->getValue();
					$statementUri = $this->wikidataStatementNamespace . ucfirst( $claim->getGuid() );
					$statementResource = $this->graph->resource( $statementUri, $this->wikidataStatementNamespace . "Statement" );
					$entityResource->addResource( $propertyUri, $statementResource );
					if ( $property->getDataType() == $libRegistry->getDataTypeFactory()->getType( 'wikibase-item' ) ) {
						$value = $this->wikidataItemNamespace . str_replace( "q", "", $value );
						$statementResource->addResource( $valueUri, $value );
					}
					if ( $property->getDataType() == $libRegistry->getDataTypeFactory()->getType( 'commonsMedia' ) ) {
						$statementResource->addResource( $valueUri, $value );
					}
					$counter += 1;
				}
			}
		}

		// TODO: add http://xmlns.com/foaf/0.1/isPrimaryTopicOf
		// EasyRdf_Namespace::set( 'foaf', 'http://xmlns.com/foaf/0.1/' );

		// TODO: what if serializer format is unknown?
		return self::outputRdf( $this->rdf_serializer[$format] );
	}

	/**
	 * Returns the serialized graph
	 *
	 * @param string $format the format id
	 *
	 * @return string
	 */
	protected function outputRdf( $format ) {
		$data = $this->graph->serialise( $format );
		if (!is_scalar( $data )) {
			$data = var_export( $data, true );
		}
		return $data;
	}
}
