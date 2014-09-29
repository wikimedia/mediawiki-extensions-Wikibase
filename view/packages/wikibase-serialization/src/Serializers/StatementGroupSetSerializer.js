/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * @constructor
 * @extends wikibase.serialization.Serializer
 * @since 2.0
 */
MODULE.StatementGroupSetSerializer = util.inherit( 'WbStatementGroupSetSerializer', PARENT, {
	/**
	 * @see wikibase.serialization.Serializer.serialize
	 *
	 * @param {wikibase.datamodel.StatementGroupSet} statementGroupSet
	 * @return {Object}
	 */
	serialize: function( statementGroupSet ) {
		if( !( statementGroupSet instanceof wb.datamodel.StatementGroupSet ) ) {
			throw new Error( 'Not an instance of wikibase.datamodel.StatementGroupSet' );
		}

		var serialization = {},
			propertyIds = statementGroupSet.getKeys(),
			statementGroupSerializer = new MODULE.StatementGroupSerializer();

		for( var i = 0; i < propertyIds.length; i++ ) {
			serialization[propertyIds[i]] = statementGroupSerializer.serialize(
				statementGroupSet.getByKey( propertyIds[i] )
			);
		}

		return serialization;
	}
} );

}( wikibase, util ) );
