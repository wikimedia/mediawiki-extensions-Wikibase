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
MODULE.StatementGroupSerializer = util.inherit( 'WbStatementGroupSerializer', PARENT, {
	/**
	 * @see wikibase.serialization.Serializer.serialize
	 *
	 * @param {wikibase.datamodel.StatementGroup} statementGroup
	 * @return {Object}
	 */
	serialize: function( statementGroup ) {
		if( !( statementGroup instanceof wb.datamodel.StatementGroup ) ) {
			throw new Error( 'Not an instance of wikibase.datamodel.StatementGroup' );
		}

		var statementListSerializer = new MODULE.StatementListSerializer();

		return statementListSerializer.serialize( statementGroup.getItemContainer() );
	}
} );

}( wikibase, util ) );
