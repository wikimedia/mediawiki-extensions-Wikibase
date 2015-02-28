<?php

namespace Wikibase;

use EasyRdf_Resource;
use EasyRdf_Namespace;
use EasyRdf_Exception;

/**
 * RDR serialization for wikibase data model.
 *
 * @author Stas Malyshev
 */

/**
 * Class to serialise an EasyRdf_Graph to RDR/Turtle
 */
class RdrSerializer extends \EasyRdf_Serialiser_Turtle {
	// TODO: make externally configurable
	protected $rdrProperties = array (
			'statement' => RdfBuilder::WIKIBASE_STATEMENT_QNAME,
			'valueProp' => 'v:P(\d+)'
	);
	/**
	 * List of nodes which were aggregated into RDR
	 *
	 * @var array
	 */
	private $subnodes;

	/**
	 * Serialise a statement in RDR form
	 *
	 * @param string $parent
	 * @param EasyRdf_Resource $statement
	 * @return string
	 */
	protected function serialiseRDR( $parent, EasyRdf_Resource $statement ) {
		foreach ( $statement->properties() as $prop ) {
			if ( preg_match( "|{$this->rdrProperties['valueProp']}|", $prop ) ) {
				$value = $statement->get( $prop );
				$oStr = $this->serialiseObject( $value );
				break;
			}
		}
		$rdrHead = "<< $parent " . $this->serialiseResource( EasyRdf_Namespace::expand( $prop ) )
			. " " . $oStr . " >>";
		$skip = array ( "a", $prop );
		$rdr = "";
		$val = $statement->get( $prop . "-value" );
		if ( $val ) {
			$rdr .= $this->serialiseProperties( $val, 1, array( "a" ) );
			$rdr = substr( $rdr, 0, -2 ) . ";";
			$this->subnodes[$val->getUri()] = true;
			$skip[] = $prop . "-value";
		}

		$rdr .= $this->serialiseProperties( $statement, 1, $skip, $rdrHead );
		$this->subnodes[$statement->getUri()] = true;

		return "\n" . $rdrHead . $rdr;
	}

	/**
	 * Serialize property set in RDR with values
	 *
	 * @param EasyRdf_Resource $res
	 * @param string $property
	 * @param string $parent Parent predicate
	 * @return string
	 */
	protected function serializeRDRProperty( EasyRdf_Resource $res, $property, $parent ) {
		$values = $res->all( "<$property>" );
		$expanded = $res->all( "<$property-value>" );
		$predicate = $this->serialiseResource( $property );
		$rdr = '';
		// This assumes both value sets are in the same order. I think
		// we don't have any way to match them otherwise so that's the best we can do
		$cnt = 0;
		foreach ( $values as $value ) {
			$type = $value->type();
			if (!$type) {
				// no type means it's novalue or somevalue
				continue;
			}
			$expvalue = $expanded[$cnt++];
			$rdr .= "<< $parent $predicate " . $this->serialiseObject( $value ) . " >>";

			$rdr .= $this->serialiseProperties( $expvalue, 1, array( "a" ), $parent );
			$this->subnodes[$expvalue->getUri()] = true;
		}
		return $rdr;
	}

	/**
	 * Protected method to serialise the properties of a resource
	 *
	 * @ignore
	 */
	protected function serialiseProperties( $res, $depth = 1, $skip = array(), $parent = "" ) {
		$properties = $res->propertyUris();
		$indent = str_repeat( ' ', ($depth * 2) - 1 );

		$turtle = "";
		$rdr = "";
		if ( count( $properties ) > 1 ) {
			$turtle .= "\n$indent";
		}

		if ( empty( $parent ) ) {
			$parent = $this->serialiseResource( $res );
		}

		$pCount = 0;
		foreach ( $properties as $property ) {
			$pStr = "";
			if ( $pCount ) {
				$pStr .= " ;\n$indent";
			}

			if ( $property === 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' ) {
				$pName = 'a';
			} else {
				$pName = $this->serialiseResource( $property, true );
			}

			if ( in_array( $pName, $skip ) ) {
				continue;
			}

			if ( $res->hasProperty( $property . "-value" ) ) {
				$rdr .= $this->serializeRDRProperty( $res, $property, $parent );
				continue;
			}

			$pStr .= $pName;

			$oCount = 0;
			foreach ( $res->all( "<$property>" ) as $object ) {
				// check for subtype
				if ( $object instanceof EasyRdf_Resource && !empty( $this->subnodes[$object->getUri()] ) ) {
					// skip substatement
					continue;
				}

				if ( $object instanceof EasyRdf_Resource && $object->type() == $this->rdrProperties['statement'] ) {
					$rdr .= $this->serialiseRDR( $this->serialiseResource( $res ), $object );
					continue;
				}

				if ( $oCount ) {
					$pStr .= ',';
				}

				if ( $object instanceof EasyRdf_Resource and $object->isBNode() ) {
					$id = $object->getBNodeId();
					$rpcount = $this->reversePropertyCount( $object );
					if ( $rpcount <= 1 and !isset( $this->outputtedBnodes[$id] ) ) {
						// Nested unlabelled Blank Node
						$this->outputtedBnodes[$id] = true;
						$pStr .= ' [';
						$pStr .= $this->serialiseProperties( $object, $depth + 1 );
						$pStr .= ' ]';
					} else {
						// Multiple properties pointing to this blank node
						$pStr .= ' ' . $this->serialiseObject( $object );
					}
				} else {
					$pStr .= ' ' . $this->serialiseObject( $object );
				}
				$oCount ++;
			}
			if ( $oCount ) {
				$turtle .= $pStr;
				$pCount ++;
			}
		}

		if ( $depth == 1 ) {
			$turtle .= " .";
			if ( $pCount > 0 ) {
				$turtle .= "\n";
			}
		} elseif ( $pCount > 1 ) {
			$turtle .= "\n" . str_repeat( ' ', (($depth - 1) * 2) - 1 );
		}

		if ( $rdr ) {
			$turtle .= "\n" . $rdr;
		}

		return $turtle;
	}

	/**
	 * @ignore
	 */
	protected function serialiseSubjects( $graph, $filterType ) {
		$turtle = '';
		foreach ( $graph->resources() as $resource ) {
			/**
			 * @var $resource EasyRdf_Resource
			 */
			// If the resource has no properties - don't serialise it
			$properties = $resource->propertyUris();
			if ( count( $properties ) == 0 ) {
				continue;
			}

			$uri = $resource->getUri();
			if ( !empty( $this->subnodes[$uri] ) ) {
				continue;
			}

			// Is this node of the right type?
			$thisType = $resource->isBNode() ? 'bnode' : 'uri';
			if ( $thisType != $filterType ) {
				continue;
			}

			if ( $thisType == 'bnode' ) {
				$id = $resource->getBNodeId();
				if ( isset( $this->outputtedBnodes[$id] ) ) {
					// Already been serialised
					continue;
				} else {
					$this->outputtedBnodes[$id] = true;
					$rpcount = $this->reversePropertyCount( $resource );
					if ( $rpcount == 0 ) {
						$turtle .= '[]';
					} else {
						$turtle .= $this->serialiseResource( $resource );
					}
				}
			} else {
				$turtle .= $this->serialiseResource( $resource );
			}

			$turtle .= $this->serialiseProperties( $resource );
			$turtle .= "\n";
		}
		return $turtle;
	}

	/**
	 * Serialise an EasyRdf_Graph to RDR.
	 *
	 * @param object EasyRdf_Graph $graph An EasyRdf_Graph object.
	 * @param string $format The name of the format to convert to.
	 * @return string The RDF in the new desired format.
	 */
	public function serialise( $graph, $format ) {
		parent::checkSerialiseParams( $graph, $format );

		if ( $format != 'rdr' ) {
			throw new EasyRdf_Exception( "RdrSerializer does not support: $format" );
		}

		$this->prefixes = array ();
		$this->outputtedBnodes = array ();

		$turtle = '';
		$turtle .= $this->serialiseSubjects( $graph, 'uri' );
		$turtle .= $this->serialiseSubjects( $graph, 'bnode' );

		if ( count( $this->prefixes ) ) {
			return $this->serialisePrefixes() . "\n" . $turtle;
		} else {
			return $turtle;
		}
	}
}
