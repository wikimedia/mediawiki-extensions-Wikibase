<?php

namespace DataValues;

use InvalidArgumentException;

/**
 * Class representing a IRI value.
 *
 * This code is based on the SMWDIUri class
 * from Semantic MediaWiki, written by Markus Krötzsch.
 *
 * For info on IRI syntax, see https://en.wikipedia.org/wiki/URI_scheme#Generic_syntax
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
 * @ingroup DataValue
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class IriValue extends DataValueObject {

	/**
	 * The URI scheme of the IRI.
	 * For instance "http", "ssh" or "mailto".
	 *
	 * @since 0.1
	 *
	 * @var string
	 */
	protected $scheme;

	/**
	 * The hierarchical part of the IRI.
	 * Consists out of an authority and a path.
	 * For instance
	 * - //username:password@example.com:8042/over/there/index.dtb
	 * - //www.wikidata.org
	 *
	 * @since 0.1
	 *
	 * @var string
	 */
	protected $hierarchicalPart;

	/**
	 * The query of the IRI.
	 * For instance type=animal&name=narwhal
	 *
	 * @since 0.1
	 *
	 * @var string
	 */
	protected $query;

	/**
	 * The fragment of the IRI.
	 * For instance headerSection
	 *
	 * @since 0.1
	 *
	 * @var string
	 */
	protected $fragment;

	/**
	 * @since 0.1
	 *
	 * @param string $scheme
	 * @param string $hierarchicalPart
	 * @param string $query
	 * @param string $fragment
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $scheme, $hierarchicalPart, $query = '', $fragment = '' ) {
		foreach ( func_get_args() as $value ) {
			if ( !is_string( $value ) ) {
				throw new InvalidArgumentException( 'Can only construct IriValue from strings' );
			}
		}

		if ( $scheme === '' || preg_match( '/[^a-zA-Z]/u', $scheme ) ) {
			throw new InvalidArgumentException( "Illegal URI scheme '$scheme'." );
		}

		if ( $hierarchicalPart === '' ) {
			throw new InvalidArgumentException( "Illegal URI hierarchical part '$hierarchicalPart'." );
		}

		$this->scheme = $scheme;
		$this->hierarchicalPart = $hierarchicalPart;
		$this->query = $query;
		$this->fragment = $fragment;
	}

	/**
	 * @see Serializable::serialize
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function serialize() {
		return $this->getValue();
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @since 0.1
	 *
	 * @param string $value
	 *
	 * @return StringValue
	 */
	public function unserialize( $value ) {
		list( $scheme, $hierarchicalPart, $query, $fragment ) = self::getIriParts( $value );
		$this->__construct( $scheme, $hierarchicalPart, $query, $fragment );
	}

	/**
	 * @see DataValue::getType
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getType() {
		return 'iri';
	}

	/**
	 * @see DataValue::getSortKey
	 *
	 * @since 0.1
	 *
	 * @return string|float|int
	 */
	public function getSortKey() {
		return $this->getValue();
	}

	/**
	 * Returns the string.
	 * @see DataValue::getValue
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getValue() {
		$uri = $this->scheme . ':'
			. $this->hierarchicalPart
			. ( $this->query ? '?' . $this->query : '' )
			. ( $this->fragment ? '#' . $this->fragment : '' );

		return $uri;
	}

	/**
	 * Returns the scheme or the IRI.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getScheme() {
		return $this->scheme;
	}

	/**
	 * Returns the hierarchical part of the IRI.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getHierarchicalPart() {
		return $this->hierarchicalPart;
	}

	/**
	 * Returns the query part of the IRI.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getQuery() {
		return $this->query;
	}

	/**
	 * Returns the fragment part of the IRI.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getFragment() {
		return $this->fragment;
	}

	/**
	 * @since 0.1
	 *
	 * @param string $serialization
	 *
	 * @return array
	 * @throws InvalidArgumentException
	 */
	public static function getIriParts( $serialization ) {
		if ( !is_string( $serialization ) ) {
			throw new InvalidArgumentException( 'IriValue::getIriParts expects a string value' );
		}

		$parts = explode( ':', $serialization, 2 ); // try to split "schema:rest"

		if ( count( $parts ) === 1 ) {
			throw new InvalidArgumentException( "Unserialization failed: the string \"$serialization\" is no valid URI." );
		}

		$scheme = $parts[0];
		$parts = explode( '?', $parts[1], 2 ); // try to split "hier-part?queryfrag"

		if ( count( $parts ) == 2 ) {
			$hierpart = $parts[0];
			$parts = explode( '#', $parts[1], 2 ); // try to split "query#frag"
			$query = $parts[0];
			$fragment = count( $parts ) == 2 ? $parts[1] : '';
		} else {
			$query = '';
			$parts = explode( '#', $parts[0], 2 ); // try to split "hier-part#frag"
			$hierpart = $parts[0];
			$fragment = count( $parts ) == 2 ? $parts[1] : '';
		}

		return array( $scheme, $hierpart, $query, $fragment );
	}

	/**
	 * @see DataValue::getArrayValue
	 *
	 * @since 0.1
	 *
	 * @return mixed
	 */
	public function getArrayValue() {
		return array(
			'scheme' => $this->scheme,
			'fragment' => $this->fragment,
			'query' => $this->query,
			'hierarchicalpart' => $this->hierarchicalPart,
		);
	}

	/**
	 * Constructs a new instance of the DataValue from the provided data.
	 * This can round-trip with @see getArrayValue
	 *
	 * @since 0.1
	 *
	 * @param array $data
	 *
	 * @return IriValue
	 */
	public static function newFromArray( array $data ) {
		return new static( $data['scheme'], $data['hierarchicalpart'], $data['query'], $data['fragment'] );
	}

}