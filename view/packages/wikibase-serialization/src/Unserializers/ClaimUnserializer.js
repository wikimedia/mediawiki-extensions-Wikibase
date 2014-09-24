/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Unserializer;

/**
 * Unserializer for single Claims.
 *
 * @constructor
 * @extends wikibase.serialization.Unserializer
 * @since 2.0
 */
MODULE.ClaimUnserializer = util.inherit( 'WbClaimUnserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Unserializer.unserialize
	 *
	 * @return {wikibase.datamodel.Claim}
	 */
	unserialize: function( serialization ) {
		var mainSnak = ( new MODULE.SnakUnserializer ).unserialize( serialization.mainsnak ),
			qualifiers = null,
			references = [],
			rank,
			guid,
			isStatement = serialization.type === 'statement';

		if( serialization.qualifiers !== undefined ) {
			qualifiers = ( new MODULE.SnakListUnserializer() ).unserialize(
				serialization.qualifiers,
				serialization['qualifiers-order']
			);
		}

		if( isStatement && serialization.references !== undefined ) {
			var referenceUnserializer = new MODULE.ReferenceUnserializer();
			for( var i = 0; i < serialization.references.length; i++ ) {
				references.push( referenceUnserializer.unserialize( serialization.references[i] ) );
			}
		}

		guid = serialization.id || null;

		if( isStatement ) {
			rank = wb.datamodel.Statement.RANK[serialization.rank.toUpperCase()];
			return new wb.datamodel.Statement( mainSnak, qualifiers, references, rank, guid );
		}

		return new wb.datamodel.Claim( mainSnak, qualifiers, guid );
	}
} );

}( wikibase, util ) );
