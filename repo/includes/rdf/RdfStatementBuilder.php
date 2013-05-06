<?php

namespace Wikibase;

/**
 * RDF mapping for wikibase statements.
 * The mapping is specified in https://meta.wikimedia.org/wiki/Wikidata/Development/RDF
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
 * @author Daniel Kinzler
 */

use DataTypes\DataTypeFactory;
use DataValues\DataValue;
use EasyRdf_Format;
use EasyRdf_Graph;
use EasyRdf_Namespace;
use EasyRdf_Resource;
use Revision;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\PropertyDataTypeLookup;

class RdfStatementBuilder {

	/**
	 * @var RdfBuilder
	 */
	protected $graphBuilder;

	/**
	 * @var EasyRdf_Resource
	 */
	protected $statementResource;

	/**
	 * @var PropertyDataTypeLookup
	 */
	protected $dataTypeLookup;

	/**
	 * @param EasyRdf_Resource       $statementResource
	 * @param RdfBuilder             $graphBuilder
	 * @param PropertyDataTypeLookup $dataTypeLookup
	 */
	public function __construct(
		EasyRdf_Resource $statementResource,
		RdfBuilder       $graphBuilder,
		PropertyDataTypeLookup $dataTypeLookup
	) {
		$this->graphBuilder = $graphBuilder; //TODO: inject a reduced interface here
		$this->statementResource = $statementResource;
		$this->dataTypeLookup = $dataTypeLookup;
	}

	/**
	 * Returns the builder's resource
	 *
	 * @return EasyRdf_Resource
	 */
	public function getResource() {
		return $this->statementResource;
	}

	/**
	 * Adds a main Snak to this statement
	 *
	 * @param Snak $snak
	 */
	public function addMainSnak( Snak $snak ) {

		//TODO: replace with a proper registry? It's just therese three...
		if ( $snak instanceof PropertyValueSnak ) {
			$this->addPropertyValueSnak( $snak );
		} elseif ( $snak instanceof PropertyNoValueSnak ) {
			$this->addPropertyNoValueSnak( $snak );
		} elseif ( $snak instanceof PropertySomeValueSnak ) {
			$this->addPropertySomeValueSnak( $snak );
		} else {
			wfWarn( "Unexpected snak type: " . get_class( $snak ) );
		}
	}

	//TODO: addReference()
	//TODO: addQualifier()

	/**
	 * Adds the given PropertySomeValueSnak to the RDF graph.
	 *
	 * @param PropertySomeValueSnak $snak
	 */
	public function addPropertySomeValueSnak( PropertySomeValueSnak $snak ) {
		/* https://meta.wikimedia.org/wiki/Wikidata/Development/RDF#MainSnak_a_PropertySomeValueSnak
		 StatementID rdf:type _:1 .
		 _:1 rdf:type owl:Restriction .
		 _:1 owl:onProperty v:Property .
		 _:1 owl:someValuesFrom owl:Thing .
		 StatementID rdfs:label ValueLabel(Statement) .
		*/

		$propertyId = $snak->getPropertyId();
		$this->addPropertyRestriction( $propertyId, array( 'owl:someValues' => 'owl:Thing' ) );

		$this->statementResource->addLiteral( "rdfs:label", "(some)" );
	}

	/**
	 * Adds the given PropertyNoValueSnak to the RDF graph.
	 *
	 * @param PropertyNoValueSnak $snak
	 */
	public function addPropertyNoValueSnak( PropertyNoValueSnak $snak ) {
		/* https://meta.wikimedia.org/wiki/Wikidata/Development/RDF#MainSnak_a_PropertyNoValueSnak
		 StatementID rdf:type _:1 .
		 _:1 rdf:type owl:Restriction .
		 _:1 owl:onProperty v:Property .
		 _:1 owl:allValuesFrom owl:Nothing .
		 StatementID rdfs:label ValueLabel(Statement) .
		*/

		$propertyId = $snak->getPropertyId();
		$this->addPropertyRestriction( $propertyId, array( 'owl:allValuesFrom' => 'owl:Nothing' ) );

		$this->statementResource->addLiteral( "rdfs:label", "(none)" );
	}

	/**
	 * Adds an OWL:Restriction resource to the given property in this statement.
	 *
	 * @param EntityId $propertyId
	 * @param array    $assertions an associative array mapping predicates to resources. These
	 *                 will be used to define the restriction..
	 */
	public function addPropertyRestriction( EntityId $propertyId, array $assertions ) {
		/*
		 StatementID rdf:type _:1 .
		 _:1 rdf:type owl:Restriction .
		 _:1 owl:onProperty v:Property .
		 _:1 $predicate $assertion
		 StatementID rdfs:label ValueLabel(Statement) .
		*/

		$propertyValueQName = $this->graphBuilder->getEntityQName( RdfBuilder::NS_VALUE, $propertyId );

		$restriction = $this->graphBuilder->getGraph()->newBNode( 'owl:Restriction' );
		$restriction->addResource( 'owl:onProperty', $propertyValueQName );

		foreach ( $assertions as $predicate => $assertion ) {
			$restriction->addResource( $predicate, $assertion );
		}

		$this->statementResource->addResource( "rdf:type", $restriction );
	}

	/**
	 * Adds the given PropertyValueSnak to the RDF graph.
	 *
	 * @param PropertyValueSnak $snak
	 */
	public function addPropertyValueSnak( PropertyValueSnak $snak ) {
		/* https://meta.wikimedia.org/wiki/Wikidata/Development/RDF#MainSnak_a_PropertyValueSnak
		 StatementID v:Property Value .
		 StatementID rdfs:label ValueLabel(Statement) .
		*/

		$value = $snak->getDataValue();
		$type = $this->dataTypeLookup->getDataTypeIdForProperty( $snak->getPropertyId() );

		$propertyId = $snak->getPropertyId();
		$this->graphBuilder->entityMentioned( $propertyId );
		$this->addClaimValue( $propertyId, $type, $value );
	}

	/**
	 * Adds the value of the given property to the RDF graph.
	 *
	 * @param EntityId  $propertyId The ID of the property this value is associated with
	 * @param string    $type The property data type (not to be confused with the data value type)
	 * @param DataValue $value The value to be associated with the property
	 */
	public function addClaimValue( EntityId $propertyId, $type, DataValue $value ) {
		$propertyValueQName = $this->graphBuilder->getEntityQName( RdfBuilder::NS_VALUE, $propertyId );

		$rawValue = $value->getValue();

		//TODO: replace with a proper registry
		switch ( $type ) {
			case 'wikibase-item':
				assert( $rawValue instanceof EntityId );
				$valueQName = $this->graphBuilder->getEntityQName( RdfBuilder::NS_ENTITY, $rawValue );
				$valueResource = $this->graphBuilder->getGraph()->resource( $valueQName );
				$this->statementResource->addResource( $propertyValueQName, $valueResource );
				$this->graphBuilder->entityMentioned( $rawValue );

				$this->statementResource->addLiteral( "rdfs:label", $rawValue );
				break;
			case 'commonsMedia':
				assert( is_string( $rawValue ) );
				//TODO: build image (page?) URL/URI!
				//TODO: set a rdf:type for the image URI!
				$this->statementResource->addResource( $propertyValueQName, $rawValue );
				$this->statementResource->addLiteral( "rdfs:label", $rawValue );
				break;
			case 'string':
				assert( is_string( $rawValue ) );
				$this->statementResource->addLiteral( $propertyValueQName, $rawValue );
				$this->statementResource->addLiteral( "rdfs:label", $rawValue );
				break;
			default:
				//TODO: more media types
				wfWarn( "Unsupported data type: $type" );
		}
	}
}
