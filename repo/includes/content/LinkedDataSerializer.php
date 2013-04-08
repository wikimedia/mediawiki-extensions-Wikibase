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
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseRepo
 * @ingroup Content
 *
 * @licence GNU GPL v2+
 * @author Anja Jentzsch < anja.jentzsch@wikimedia.de >
 */

use EasyRdf_Graph;
use EasyRdf_Namespace;
require_once "easyRdf/EasyRdf.php";

class LinkedDataSerializer {

	/*
	 * @var EasyRdf_Graph
	 */
	private $graph;

	private $rdf_serializer = array( "rdf" => "rdfxml", "nt" => "ntriples" );

	private $wikidataItemNamespace = "http://wikidata.org/id/Q";
	private $wikidataOntologyNamespace = "http://wikidata.org/ontology/";
	private $wikidataPropertyNamespace = "http://wikidata.org/property/";
	// FIXME: vocabulary and property namespace should be the same?
	private $wikidataValueNamespace = "http://wikidata.org/value/";
	private $wikidataStatementNamespace = "http://wikidata.org/statement/";

	public function __construct() {
		self::createRdfGraph();
	}

	private function createRdfGraph() {
		$this->graph = new EasyRdf_Graph();
		EasyRdf_Namespace::set( 'wikidata_ontology', $this->wikidataOntologyNamespace );
		EasyRdf_Namespace::set( 'wikidata_statement', $this->wikidataStatementNamespace );
	}

	/**
	 * @param EntityContent $entity the entity to output.
	 * @param String $format the RDF format to output.
	 */
	public function getRdfForItem( EntityContent $entity, $format ) {
		$entityId = $entity->getEntity()->getId()->getNumericId();
		$entityUri = $this->wikidataItemNamespace . strval( $entityId );

		foreach ( $entity->getEntity()->getLabels() as $languageCode => $labelText ) {
			$this->graph->addLiteral( $entityUri, "rdfs:label", $labelText, $languageCode );
		}

		foreach ( $entity->getEntity()->getDescriptions() as $languageCode => $description ) {
			$this->graph->addLiteral( $entityUri, "rdfs:comment", $description, $languageCode );
		}

		foreach ( $entity->getEntity()->getAllAliases() as $languageCode => $aliases ) {
			foreach ( $aliases as $alias ) {
				$this->graph->addLiteral( $entityUri, "wikidata_ontology:knownAs", $alias, $languageCode );
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
					$this->graph->addResource( $entityUri, $propertyUri, $statementUri );
					$this->graph->setType( $statementUri, $this->wikidataStatementNamespace . "Statement" );
					if ( $property->getDataType() == $libRegistry->getDataTypeFactory()->getType( 'wikibase-item' ) ) {
						$value = $this->wikidataItemNamespace . str_replace( "q", "", $value );
						$this->graph->addResource( $statementUri, $valueUri, $value );
					}
					if ( $property->getDataType() == $libRegistry->getDataTypeFactory()->getType( 'commonsMedia' ) ) {
						$this->graph->addResource( $statementUri, $valueUri, $value );
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

	private function outputRdf( $format ) {
		$data = $this->graph->serialise( $format );
		if (!is_scalar( $data )) {
			$data = var_export( $data, true );
		}
		return $data;
	}
}
