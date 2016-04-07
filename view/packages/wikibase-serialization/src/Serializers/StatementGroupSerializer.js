( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * @class wikibase.serialization.StatementGroupSerializer
 * @extends wikibase.serialization.Serializer
 * @since 2.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
MODULE.StatementGroupSerializer = util.inherit( 'WbStatementGroupSerializer', PARENT, {
	/**
	 * @inheritdoc
	 *
	 * @param {wikibase.datamodel.StatementGroup} statementGroup
	 * @return {Object}
	 *
	 * @throws {Error} if statementGroup is not a StatementGroup instance.
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
