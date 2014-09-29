/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util, $ ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * @constructor
 * @extends {wikibase.serialization.Serializer}
 * @since 2.0
 */
MODULE.PropertySerializer = util.inherit( 'WbPropertySerializer', PARENT, {
	/**
	 * @see wb.serialization.Serializer.serialize
	 *
	 * @param {wikibase.datamodel.Property} property
	 * @return {Object}
	 */
	serialize: function( property ) {
		if( !( property instanceof wb.datamodel.Property ) ) {
			throw new Error( 'Not an instance of wikibase.datamodel.Property' );
		}

		var fingerprintSerializer = new MODULE.FingerprintSerializer(),
			statementGroupSetSerializer = new MODULE.StatementGroupSetSerializer();

		return $.extend( true,
			{
				type: property.getType(),
				id: property.getId(),
				claims: statementGroupSetSerializer.serialize( property.getStatements() ),
				datatype: property.getDataTypeId()
			},
			fingerprintSerializer.serialize( property.getFingerprint() )
		);
	}
} );

}( wikibase, util, jQuery ) );
