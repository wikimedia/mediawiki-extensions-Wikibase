/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( mw, wb, util ) {
	'use strict';

	var PARENT = wb.serialization.Deserializer;

	/**
	 * Deserializer for Property entities.
	 *
	 * @param {wb.serialization.Deserializer} [contentDeserializer] The deserializer which should be
	 *         used to deserialize the actual content of the final FetchedContent object. If this
	 *         is not set, the content will be a string taken from the serialized data.
	 *
	 * @constructor
	 * @extends wb.Deserializer
	 * @since 0.4
	 */
	var SELF = wb.store.FetchedContentUnserializer = util.inherit(
		'WbFetchedContentUnserializer',
		PARENT,
		function( contentDeserializer ) {
			this._contentDeserializer = contentDeserializer;
		}, {

		/**
		 * @type {wb.serialization.Deserializer|null}
		 */
		_contentDeserializer: null,

		/**
		 * @see wb.serialization.Deserializer.deserialize
		 *
		 * @return {wikibase.store.FetchedContent}
		 */
		deserialize: function( serialization ) {
			var title = new mw.Title( serialization.title );

			// If content deserializer is not given, take plain content value.
			var content = this._contentDeserializer
				? this._contentDeserializer.deserialize( serialization.content )
				: serialization.content;

			return new wb.store.FetchedContent( {
				title: title,
				content: content
			} );
		}
	} );

}( mediaWiki, wikibase, util ) );
