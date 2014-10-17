/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( mw, wb, util ) {
	'use strict';

	var PARENT = wb.serialization.Deserializer;

	/**
	 * Unserializer for Property entities.
	 *
	 * @option contentUnserializer {wb.serialization.Unserializer} The unserializer which should be
	 *         used to unserialize the actual content of the final FetchedContent object. If this
	 *         is not set, the content will be a string taken from the serialized data.
	 *
	 * @constructor
	 * @extends wb.Unserializer
	 * @since 0.4
	 */
	var SELF =
		wb.store.FetchedContentUnserializer =
			util.inherit( 'WbFetchedContentUnserializer', PARENT, {
		/**
		 * @see wb.serialization.Unserializer.unserialize
		 *
		 * @return {wikibase.store.FetchedContent}
		 */
		unserialize: function( serialization ) {
			var title = new mw.Title( serialization.title ),
				contentUnserializer = this._options.contentUnserializer;

			// If content unserializer option is not given, take plain content value.
			var content = contentUnserializer
				? contentUnserializer.unserialize( serialization.content )
				: serialization.content;

			return new wb.store.FetchedContent( {
				title: title,
				content: content
			} );
		}
	} );

}( mediaWiki, wikibase, util ) );
