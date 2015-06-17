<?php

namespace Wikibase\Lib;

use DataTypes\DataType;

/**
 * Defines the data types supported by Wikibase.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Thiemo Mättig
 */
class WikibaseDataTypeBuilders {

	/**
	 * @see DataTypeFactory::buildType
	 *
	 * @return callable[] DataType builder specs
	 */
	public function getDataTypeBuilders() {
		//XXX: Using callbacks here is somewhat pointless, we could just as well have a
		//     registerTypes( DataTypeFactory ) method and register the DataType objects
		//     directly. But that would make it awkward to filter the types according to
		//     the dataTypes setting. On the other hand, perhaps that setting should only
		//     be used for the UI, and the factory should simply know all data types always.

		/**
		 * Data types to data value types mapping:
		 * commonsMedia      => string (camel case, FIXME maybe?)
		 * globe-coordinate  => globecoordinate (FIXME!)
		 * monolingualtext   => monolingualtext
		 * multilingualtext  => multilingualtext
		 * quantity          => quantity
		 * string            => string
		 * time              => time
		 * url               => string
		 * wikibase-item     => wikibase-entityid
		 * wikibase-property => wikibase-entityid
		 */

		// Update ValidatorBuilders in repo if you change this
		$types = array(
			'commonsMedia'      => array( $this, 'buildStringType' ),
			'globe-coordinate'  => array( $this, 'buildCoordinateType' ),
			'monolingualtext'   => array( $this, 'buildMonolingualTextType' ),
			'quantity'          => array( $this, 'buildQuantityType' ),
			'string'            => array( $this, 'buildStringType' ),
			'time'              => array( $this, 'buildTimeType' ),
			'url'               => array( $this, 'buildStringType' ),
			'wikibase-item'     => array( $this, 'buildWikibaseEntityIdType' ),
			'wikibase-property' => array( $this, 'buildWikibaseEntityIdType' ),
		);

		$experimental = array(
			// 'multilingualtext' => array( $this, 'buildMultilingualTextType' ),
		);

		if ( defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES ) {
			$types = array_merge( $types, $experimental );
		}

		return $types;
	}

	/**
	 * @param string $id Data type ID, typically 'wikibase-item', or 'wikibase-property'
	 *
	 * @return DataType
	 */
	public function buildWikibaseEntityIdType( $id ) {
		return new DataType( $id, 'wikibase-entityid', array() );
	}

	/**
	 * @param string $id Data type ID, typically 'string', 'commonsMedia' or 'url'
	 *
	 * @return DataType
	 */
	public function buildStringType( $id ) {
		return new DataType( $id, 'string', array() );
	}

	/**
	 * @param string $id Data type ID, typically 'monolingualtext'
	 *
	 * @return DataType
	 */
	public function buildMonolingualTextType( $id ) {
		return new DataType( $id, 'monolingualtext', array() );
	}

	/**
	 * @param string $id Data type ID, typically 'time'
	 *
	 * @return DataType
	 */
	public function buildTimeType( $id ) {
		return new DataType( $id, 'time', array() );
	}

	/**
	 * @param string $id Data type ID, typically 'globe-coordinate'
	 *
	 * @return DataType
	 */
	public function buildCoordinateType( $id ) {
		return new DataType( $id, 'globecoordinate', array() );
	}

	/**
	 * @param string $id Data type ID, typically 'quantity'
	 *
	 * @return DataType
	 */
	public function buildQuantityType( $id ) {
		return new DataType( $id, 'quantity', array() );
	}

}
