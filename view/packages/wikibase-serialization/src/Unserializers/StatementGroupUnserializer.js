/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util, $ ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Unserializer;

/**
 * @constructor
 * @extends wikibase.serialization.Unserializer
 * @since 2.0
 */
MODULE.StatementGroupUnserializer = util.inherit( 'WbStatementGroupUnserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Unserializer.unserialize
	 *
	 * @return {wikibase.datamodel.StatementGroup}
	 */
	unserialize: function( serialization ) {
		if( $.isEmptyObject( serialization ) ) {
			throw new Error( 'Cannot unserialize empty serialization' );
		}

		var statementListUnserializer = new MODULE.StatementListUnserializer(),
			statementList = statementListUnserializer.unserialize( serialization );

		return new wb.datamodel.StatementGroup( statementList.getPropertyIds()[0], statementList );
	}
} );

}( wikibase, util, jQuery ) );
