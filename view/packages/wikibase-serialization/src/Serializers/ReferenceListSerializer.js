/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, util ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * Serializer for ReferenceList objects.
 *
 * @constructor
 * @extends wikibase.serialization.Serializer
 * @since 2.0
 */
MODULE.ReferenceListSerializer = util.inherit( 'WbReferenceLisSerializer', PARENT, {
	/**
	 * @see wikibase.serialization.Serializer.serialize
	 *
	 * @param {wikibase.datamodel.ReferenceList} referenceList
	 * @return {Object[]}
	 */
	serialize: function( referenceList ) {
		if( !( referenceList instanceof wb.datamodel.ReferenceList ) ) {
			throw new Error( 'Not an instance of wikibase.datamodel.ReferenceList' );
		}

		var serialization = [],
			referenceSerializer = new MODULE.ReferenceSerializer(),
			references = referenceList.toArray();

		for( var i = 0; i < references.length; i++ ) {
			serialization.push( referenceSerializer.serialize( references[i] ) );
		}

		return serialization;
	}
} );

}( wikibase, util ) );
