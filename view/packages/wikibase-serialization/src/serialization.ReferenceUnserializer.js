/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Unserializer;

/**
 * Unserializer for single Reference objects.
 *
 * @constructor
 * @extends wikibase.serialization.Unserializer
 * @since 2.0
 */
MODULE.ReferenceUnserializer = util.inherit( 'WbReferenceUnserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Unserializer.unserialize
	 *
	 * @return {wikibase.datamodel.Reference}
	 */
	unserialize: function( serialization ) {
		return new wikibase.datamodel.Reference(
			( new MODULE.SnakListUnserializer() ).unserialize(
				serialization.snaks,
				serialization['snaks-order']
			),
			serialization.hash
		);
	}
} );

}( wikibase, util ) );
