/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb, util, dv, $ ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Unserializer;

/**
 * Unserializer for Snak objects.
 *
 * @constructor
 * @extends wikibase.serialization.Unserializer
 * @since 2.0
 */
MODULE.SnakUnserializer = util.inherit( 'WbSnakUnserializer', PARENT, {
	/**
	 * @see wikibase.serialization.Unserializer.unserialize
	 *
	 * @return {wikibase.datamodel.Snak}
	 */
	unserialize: function( serialization ) {
		// Prevent altering original serialization:
		var map = $.extend( {}, serialization );

		if( serialization.snaktype === 'value' ) {
			var type = serialization.datavalue.type,
				value = serialization.datavalue.value;
			try {
				map.datavalue = dv.newDataValue( type, value );
			} catch( e ) {
				map.datavalue = new dv.UnUnserializableValue( value, type, e );
			}
		}
		return wb.datamodel.Snak.newFromMap( map );
	}
} );

}( wikibase, util, dataValues, jQuery ) );
