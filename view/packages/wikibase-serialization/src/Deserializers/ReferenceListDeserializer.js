( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Deserializer;

/**
 * @class wikibase.serialization.ReferenceListDeserializer
 * @extends wikibase.serialization.Deserializer
 * @since 2.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
MODULE.ReferenceListDeserializer = util.inherit( 'WbReferenceListDeserializer', PARENT, {
	/**
	 * @inheritdoc
	 *
	 * @return {wikibase.datamodel.ReferenceList}
	 */
	deserialize: function( serialization ) {
		var references = [],
			referenceDeserializer = new MODULE.ReferenceDeserializer();

		for( var i = 0; i < serialization.length; i++ ) {
			references.push( referenceDeserializer.deserialize( serialization[i] ) );
		}

		return new wikibase.datamodel.ReferenceList( references );
	}
} );

}( wikibase, util ) );
