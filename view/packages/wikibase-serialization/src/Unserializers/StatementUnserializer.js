/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Unserializer;

/**
 * Unserializer for Statement objects.
 *
 * @constructor
 * @extends wikibase.serialization.Unserializer
 * @since 2.0
 */
MODULE.StatementUnserializer = util.inherit( 'WbStatementUnserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Unserializer.unserialize
	 *
	 * @return {wikibase.datamodel.Statement}
	 */
	unserialize: function( serialization ) {
		var claim = ( new MODULE.ClaimUnserializer() ).unserialize( serialization ),
			references = null,
			rank = wb.datamodel.Statement.RANK[serialization.rank.toUpperCase()];

		if( serialization.references !== undefined ) {
			var referenceUnserializer = new MODULE.ReferenceListUnserializer();
			references = referenceUnserializer.unserialize( serialization.references );
		}

		return new wb.datamodel.Statement( claim, references, rank );
	}
} );

}( wikibase, util ) );
