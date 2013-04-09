<?php

namespace Wikibase;

/**
 * Linked Data mapping for wikibase data model.
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

class LinkedDataSerializer {

	/*
	 * @var EasyRdf_Graph
	 */
	protected $graph;

	public function __construct() {
		self::createRdfGraph();
	}


	/**
	 * Inits a new Graph
	 *
	 * @return EasyRdf_Graph
	 */
	protected function createRdfGraph() {
		global $wgServer;

		//Get base URI
		$base = $wgServer;
		if ( preg_match( '/^\/\/.*/', $base ) ) {
			$base = 'http:' . $base; //Add http: if it's a protocol relative URI
		}

		//register namespaces
		EasyRdf_Namespace::set( 'wikibase_id', $base . '/id/' );
		EasyRdf_Namespace::set( 'wikibase_ontology', $base . '/ontology/' );
		//FIXME: Can we share the wikibase_ontology between Wikibase based repositories?
		EasyRdf_Namespace::set( 'wikibase_property', $base . '/property/' );
		EasyRdf_Namespace::set( 'wikibase_value', $base . '/value/' );
		// FIXME: vocabulary and property namespace should be the same?
		EasyRdf_Namespace::set( 'wikibase_statement', $base . '/statement/' );

		$this->graph = new EasyRdf_Graph();
	}

	/**
	 * Add an entity to the RDF graph
	 *
	 * @param EntityContent $entity the entity to output.
	 *
	 * @return string
	 */
	public function addEntity( EntityContent $entity ) {
		$entityUri = 'wikibase_id:' . ucfirst( $entity->getEntity()->getId() );
		$entityResource = $this->graph->resource( $entityUri );

		foreach ( $entity->getEntity()->getLabels() as $languageCode => $labelText ) {
			$entityResource->addLiteral( 'rdfs:label', $labelText, $languageCode );
		}

		foreach ( $entity->getEntity()->getDescriptions() as $languageCode => $description ) {
			$entityResource->addLiteral( 'rdfs:comment', $description, $languageCode );
		}

		foreach ( $entity->getEntity()->getAllAliases() as $languageCode => $aliases ) {
			foreach ( $aliases as $alias ) {
				$entityResource->addLiteral( 'wikibase_ontology:knownAs', $alias, $languageCode );
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
			$propertyUri = 'wikibase_property:' . strval( $propertyId );
			$property = EntityContentFactory::singleton()->getFromId( $propertyId )->getProperty();
			//$property->getEntity()->getLabel( $languageCode );
			$valueUri = 'wikibase_value:' . strval( $propertyId );

			foreach( $claims as $claim ) {
				if ( $claim->getMainSnak()->getType() === 'value' ) {
					$value = $claim->getMainSnak()->getDataValue()->getValue();
					$statementUri = 'wikibase_statement:' . ucfirst( $claim->getGuid() );
					$statementResource = $this->graph->resource( $statementUri, 'wikibase_statement:Statement' );
					//FIXME: Why wikibase_statement:Statement and not wikibase_ontology:Statement?
					$entityResource->addResource( $propertyUri, $statementResource );
					if ( $property->getDataType() == $libRegistry->getDataTypeFactory()->getType( 'wikibase-item' ) ) {
						$value = 'wikibase_id:' . ucfirst( $value );
						$statementResource->addResource( $valueUri, $value );
					}
					if ( $property->getDataType() == $libRegistry->getDataTypeFactory()->getType( 'commonsMedia' ) ) {
						$statementResource->addResource( $valueUri, $value );
					}
					$counter += 1;
				}
			}
		}

		// TODO: items: add http://xmlns.com/foaf/0.1/isPrimaryTopicOf
		// EasyRdf_Namespace::set( 'foaf', 'http://xmlns.com/foaf/0.1/' );
	}

	/**
	 * Returns the serialized graph
	 *
	 * @param EasyRdf_Format $format the format id
	 *
	 * @return string
	 */
	public function outputRdf( \EasyRdf_Format $format ) {
		$serialiser = $format->newSerialiser();
		$data = $serialiser->serialise( $this->graph, $format->getName() );
		if (!is_scalar( $data )) {
			$data = var_export( $data, true );
		}
		return $data;
	}
}
