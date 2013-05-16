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
 * @ingroup RDF
 *
 * @licence GNU GPL v2+
 * @author Anja Jentzsch < anja.jentzsch@wikimedia.de >
 * @author Thomas Pellissier Tanon
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

class RdfBuilder {

	const ONTOLOGY_BASE_URI = 'http://www.wikidata.org/ontology#'; //XXX: Denny made me put the "www" there...

	const NS_ONTOLOGY =  'wikibase'; // wikibase ontology (shared)
	const NS_ENTITY =    'entity';   // concept uris
	const NS_DATA =      'data';     // document uris
	const NS_PROPERTY =  'p'; // entity -> value
	const NS_VALUE =     'v'; // statement -> value
	const NS_QUALIFIER = 'q'; // statement -> qualifier
	const NS_STATEMENT = 's'; // entity -> statement

	const NS_SKOS = 'skos'; // SKOS vocabulary
	const NS_SCHEMA_ORG = 'schema'; // schema.org vocabulary

	const SKOS_URI = 'http://www.w3.org/2004/02/skos/core#';
	const SCHEMA_ORG_URI = 'http://schema.org/';

	const WIKIBASE_STATEMENT_QNAME = 'wikibase:Statement';

	/**
	 * Map of qnames to namespace URIs
	 *
	 * @var array
	 */
	protected $namespaces = array();

	/**
	 * A list of entities mentioned/touched to or by this builder.
	 * The prefixed entity IDs are used as keys in the array, the values 'true'
	 * is used to indicate that the entity has been resolved, 'false' indicates
	 * that the entity was mentioned but not resolved (defined).
	 *
	 * @var array
	 */
	protected $entitiesResolved = array();

	/**
	 * @param string                $baseUri
	 * @param Lib\EntityIdFormatter $idFormatter
	 * @param EasyRdf_Graph|null    $graph
	 */
	public function __construct(
		$baseUri,
		EntityIdFormatter $idFormatter,
		EasyRdf_Graph $graph = null
	) {
		if ( !$graph ) {
			$graph = new EasyRdf_Graph();
		}

		$this->graph = $graph;
		$this->baseUri = $baseUri;
		$this->idFormatter = $idFormatter;

		$this->namespaces = array(
			self::NS_ONTOLOGY => self::ONTOLOGY_BASE_URI,
			self::NS_DATA => $this->baseUri . '/data/',
			self::NS_ENTITY => $this->baseUri . '/entity/',
			self::NS_PROPERTY => $this->baseUri . '/property/',
			self::NS_VALUE => $this->baseUri . '/value/',
			self::NS_QUALIFIER => $this->baseUri . '/qualifier/',
			self::NS_STATEMENT => $this->baseUri . '/statement/',
			self::NS_SKOS => self::SKOS_URI,
			self::NS_SCHEMA_ORG => self::SCHEMA_ORG_URI,
		);

		//XXX: Ugh, static. Should go into $this->graph.
		foreach ( $this->getNamespaces() as $gname => $uri ) {
			EasyRdf_Namespace::set( $gname, $uri );
		}
	}

	/**
	 * Checks whether the necessary libraries for RDF serialization are installed.
	 */
	public static function isSupported() {
		// check that the submodule is present
		return class_exists( 'EasyRdf_Graph' );
	}

	/**
	 * Returns the builder's graph
	 *
	 * @return EasyRdf_Graph
	 */
	public function getGraph() {
		return $this->graph;
	}

	/**
	 * Returns a map of namespace names to URIs
	 *
	 * @return array
	 */
	public function getNamespaces() {
		return $this->namespaces;
	}

	/**
	 * Returns a qname for the given entity using the given prefix.
	 * To refer to the entity as such, use self::NS_ENTITY.
	 * To refer to the description of the entity, use NS_DATA.
	 * For the different prefixes used for properties in statements,
	 * refer to the Wikibase RDF mapping spec.
	 *
	 * @param string   $prefix use a self::NS_XXX constant
	 * @param EntityId $id
	 *
	 * @return string
	 */
	public function getEntityQName( $prefix, EntityId $id ) {
		return $prefix . ':' . $this->idFormatter->format( $id );
	}

	/**
	 * Returns a qname for the given statement using the given prefix.
	 *
	 * @param string $prefix use a self::NS_XXX constant, usually self::NS_STATEMENT
	 * @param Claim $claim
	 *
	 * @return string
	 */
	public function getStatementQName( $prefix, Claim $claim ) {
		return $prefix . ':' . $claim->getGuid();
	}

	/**
	 * Returns a qname for the given entity type.
	 * For well known types, these qnames refer to classes from the Wikibase ontology.
	 *
	 * @param $type
	 *
	 * @return string
	 */
	public function getEntityTypeQName( $type ) {
		//TODO: the list of types is configurable, need to register URIs for extra types!

		return self::NS_ONTOLOGY . ':' . ucfirst( $type );
	}

	/**
	 * Gets a resource object representing the given entity
	 *
	 * @param EntityId $id
	 *
	 * @return EasyRDF_Resource
	 */
	public function getEntityResource( EntityId $id ) {
		$entityQName = $this->getEntityQName( self::NS_ENTITY, $id );
		$entityResource = $this->graph->resource( $entityQName );
		return $entityResource;
	}

	/**
	 * Gets a URL of the rdf description of the given entity
	 *
	 * @param EntityId $id
	 *
	 * @return string
	 */
	public function getDataURL( EntityId $id ) {
		$base = $this->namespaces[ self::NS_DATA ];
		$url = $base . $this->idFormatter->format( $id );
		return $url;
	}

	/**
	 * Language filter
	 *
	 * @param $lang
	 *
	 * @return bool
	 */
	protected function isLanguageIncluded( $lang ) {
		return true; //todo: optional filter
	}

	/**
	 * Registers an entity as mentioned. Will be recorded as unresolved
	 * if it wasn't already marked as resolved.
	 *
	 * @param EntityId $id
	 */
	protected function entityMentioned( EntityId $id ) {
		$prefixedId = $this->idFormatter->format( $id );

		if ( !isset( $this->entitiesResolved[$prefixedId] ) ) {
			$this->entitiesResolved[$prefixedId] = false;
		}
	}

	/**
	 * Registers an entity as resolved.
	 *
	 * @param EntityId $id
	 */
	protected function entityResolved( EntityId $id ) {
		$prefixedId = $this->idFormatter->format( $id );
		$this->entitiesResolved[$prefixedId] = true;
	}

	/**
	 * Adds meta-information about an entity (such as the ID and type) to the RDF graph.
	 *
	 * @param Entity         $entity
	 * @param \Revision|null $rev
	 */
	public function addEntityMetaData( Entity $entity, $rev = null ) {
		$entityResource = $this->getEntityResource( $entity->getId() );
		$entityResource->addResource( 'rdf:type', $this->getEntityTypeQName( $entity->getType() ) );

		$dataResource = $this->graph->resource( '#' ); // "this document"
		$dataURL = $this->getDataURL( $entity->getId() );
		$dataResource->addResource( self::NS_SCHEMA_ORG . ':about', $entityResource );
		$dataResource->addResource( self::NS_SCHEMA_ORG . ':url', $dataURL );
		$dataResource->addResource( 'rdf:type', self::NS_SCHEMA_ORG . ":Dataset" );

		if ( $rev ) {
			$dataResource->addLiteral( self::NS_SCHEMA_ORG . ':version', $rev->getId() );
			$dataResource->addLiteral( self::NS_SCHEMA_ORG . ':dateModified', wfTimestamp( TS_ISO_8601, $rev->getTimestamp() ) );
			//TODO: add support for date types to EasyRDF
		}

		//TODO: revision timestamp, revision id, versioned data URI, current-version-of

		$this->entityResolved( $entity->getId() );
	}

	/**
	 * Adds the labels of the given entity to the RDF graph
	 *
	 * @param Entity $entity
	 */
	public function addLabels( Entity $entity ) {
		$entityResource = $this->getEntityResource( $entity->getId() );

		foreach ( $entity->getLabels() as $languageCode => $labelText ) {
			if ( !$this->isLanguageIncluded( $languageCode ) ) {
				continue;
			}

			$entityResource->addLiteral( 'rdfs:label', $labelText, $languageCode );
			$entityResource->addLiteral( self::NS_SKOS . ':prefLabel', $labelText, $languageCode );
			$entityResource->addLiteral( self::NS_SCHEMA_ORG . ':name', $labelText, $languageCode );
		}
	}

	/**
	 * Adds the descriptions of the given entity to the RDF graph.
	 *
	 * @param Entity $entity
	 */
	public function addDescriptions( Entity $entity ) {
		$entityResource = $this->getEntityResource( $entity->getId() );

		foreach ( $entity->getDescriptions() as $languageCode => $description ) {
			if ( !$this->isLanguageIncluded( $languageCode ) ) {
				continue;
			}

			$entityResource->addLiteral( self::NS_SCHEMA_ORG . ':description', $description, $languageCode );
		}
	}

	/**
	 * Adds the aliases of the given entity to the RDF graph.
	 *
	 * @param Entity $entity
	 */
	public function addAliases( Entity $entity ) {
		$entityResource = $this->getEntityResource( $entity->getId() );

		foreach ( $entity->getAllAliases() as $languageCode => $aliases ) {
			if ( !$this->isLanguageIncluded( $languageCode ) ) {
				continue;
			}

			foreach ( $aliases as $alias ) {
				$entityResource->addLiteral( self::NS_SKOS . ':altLabel', $alias, $languageCode );
			}
		}
	}

	/**
	 * Adds the site links of the given item to the RDF graph.
	 *
	 * @param Item $item
	 */
	public function addSiteLinks( Item $item ) {
		$entityResource = $this->getEntityResource( $item->getId() );

		/* @var SiteLink $link */
		foreach ( $item->getSiteLinks() as $link ) {
			$languageCode = $link->getSite()->getLanguageCode();

			if ( !$this->isLanguageIncluded( $languageCode ) ) {
				continue;
			}

			//XXX: ideally, we'd use https if the target site supports it.
			$url = wfExpandUrl( $link->getUrl(), PROTO_HTTP );
			$pageRecourse = $this->graph->resource( $url );

			$pageRecourse->addResource( self::NS_SCHEMA_ORG . ':about', $entityResource );
			$pageRecourse->addResource( self::NS_SCHEMA_ORG . ':inLanguage', $languageCode );
			$pageRecourse->addResource( 'rdf:type', self::NS_SCHEMA_ORG . ':Article' );
		}
	}

	/**
	 * Adds all Claims/Statements from the given entity to the RDF graph.
	 *
	 * @param Entity $entity
	 */
	public function addClaims( Entity $entity ) {
		/* @var Claim $claim */
		foreach( $entity->getClaims() as $claim ) {
			$this->addClaim( $entity, $claim );
		}
	}

	/**
	 * Adds the given Claim from the given Entity to the RDF graph.
	 *
	 * @param Entity $entity
	 * @param Claim  $claim
	 */
	public function addClaim( Entity $entity, Claim $claim ) {
		$this->addMainSnak( $entity, $claim );

		//TODO: add qualifiers
		//TODO: add references
	}

	/**
	 * Adds the given Claim's main Snak to the RDF graph.
	 *
	 * @param Entity $entity
	 * @param Claim  $claim
	 */
	public function addMainSnak( Entity $entity, Claim $claim ) {
		$snak = $claim->getMainSnak();

		if ( $snak instanceof PropertyValueSnak ) {
			$this->addPropertyValueSnak( $entity, $claim, $snak );
		} else {
			//TODO: NoValueSnak, SomeValueSnak
			wfDebug( __METHOD__ . ": Unsupported snak type: " . get_class( $snak ) );
		}
	}


	/**
	 * Returns a resource representing the given claim.
	 *
	 * @param Claim $claim
	 *
	 * @return EasyRDF_Resource
	 */
	public function getStatementResource( Claim $claim ) {
		$statementQName = $this->getStatementQName( self::NS_STATEMENT, $claim );
		$statementResource = $this->graph->resource( $statementQName, array( self::WIKIBASE_STATEMENT_QNAME ) );
		return $statementResource;
	}

	/**
	 * Adds the given PropertyValueSnak to the RDF graph.
	 *
	 * @param Entity            $entity
	 * @param PropertyValueSnak $snak
	 * @param Claim             $claim
	 */
	public function addPropertyValueSnak( Entity $entity, Claim $claim, PropertyValueSnak $snak ) {
		$entityResource = $this->getEntityResource( $entity->getId() );

		$propertyId = $claim->getMainSnak()->getPropertyId();
		$propertyQName = $this->getEntityQName( self::NS_PROPERTY, $propertyId );

		$statementResource = $this->getStatementResource( $claim );
		$entityResource->addResource( $propertyQName, $statementResource );

		$value = $snak->getDataValue();

		$this->entityMentioned( $propertyId );
		$this->addClaimValue( $claim, $propertyId, $value );
	}

	/**
	 * Adds the value of the given property to the RDF graph.
	 *
	 * @param Claim     $claim
	 * @param EntityId  $propertyId
	 * @param DataValue $value
	 */
	public function addClaimValue( Claim $claim, EntityId $propertyId, DataValue $value ) {
		$statementResource = $this->getStatementResource( $claim );
		$propertyValueQName = $this->getEntityQName( self::NS_VALUE, $propertyId );

		$typeId = $value->getType();

		switch ( $typeId ) {
			case 'wikibase-item':
				$rawValue = $value->getValue();

				assert( $rawValue instanceof EntityId );
				$valueQName = $this->getEntityQName( self::NS_ENTITY, $rawValue );
				$valueResource = $this->graph->resource( $valueQName );
				$statementResource->addResource( $propertyValueQName, $valueResource );
				$this->entityMentioned( $rawValue );
				break;
			case 'commonsMedia':
				$statementResource->addResource( $propertyValueQName, $value );
				break;
			default:
				//TODO: more media types
				wfDebug( __METHOD__ . ": Unsupported data type: $typeId\n" );
		}
	}

	/**
	 * Add stubs for any entities that were previously mentioned (e.g. as properties
	 * or data values).
	 *
	 * @param EntityLookup $entityLookup
	 */
	public function resolvedMentionedEntities( EntityLookup $entityLookup ) {
		foreach ( $this->entitiesResolved as $id => $resolved ) {
			if ( !$resolved ) {
				$id = EntityId::newFromPrefixedId( $id );
				$entity = $entityLookup->getEntity( $id );

				$this->addEntityStub( $entity );
			}
		}
	}

	/**
	 * Add an entity to the RDF graph, including all supported structural components
	 * of the entity.
	 *
	 * @param Entity $entity the entity to output.
	 * @param \Revision $revision for meta data (optional)
	 */
	public function addEntity( Entity $entity, $revision = null ) {
		$this->addEntityMetaData( $entity, $revision );

		$this->addLabels( $entity );
		$this->addDescriptions( $entity );
		$this->addAliases( $entity );

		if ( $entity instanceof Item ) {
			$this->addSiteLinks( $entity );
		}

		//$this->addClaims( $entity ); //TODO: finish this.
	}

	/**
	 * Adds stub information for the given Entity to the RDF graph. Stub information
	 * means meta information and labels.
	 *
	 * @param Entity $entity
	 */
	public function addEntityStub( Entity $entity ) {
		$this->addEntityMetaData( $entity );
		$this->addLabels( $entity );
		$this->addDescriptions( $entity );
	}
}
