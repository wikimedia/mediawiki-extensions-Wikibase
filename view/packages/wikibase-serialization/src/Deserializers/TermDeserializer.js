( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer;

/**
 * @class wikibase.serialization.TermDeserializer
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
MODULE.TermDeserializer = util.inherit( 'WbTermDeserializer', PARENT, {
	/**
	 * @inheritdoc
	 *
	 * @return {wikibase.datamodel.Term}
	 */
	deserialize: function( serialization ) {
		return new wb.datamodel.Term( serialization.language, serialization.value );
	}
} );

}( wikibase, util ) );
