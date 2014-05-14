/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( $, vv ) {
	'use strict';

	var PARENT = vv.experts.StringValue;

	/**
	 * Valueview expert for adding specialized handling for CommonsMedia data type. Without this
	 * more specialized expert, the StringValue expert would be used since the CommonsMedia data
	 * type is using the String data value type.
	 * This expert is based on the StringValue expert but will add a dropdown for choosing commons
	 * media sources. It will also display the value as a link to commons.
	 *
	 * @since 0.1
	 *
	 * @constructor
	 * @extends jQuery.valueview.experts.SuggestedStringValue
	 */
	vv.experts.CommonsMediaType = vv.expert( 'CommonsMediaType', PARENT, {
		/**
		 * @see jQuery.valueview.experts.StringValue._init
		 */
		_init: function() {
			PARENT.prototype._init.call( this );

			var notifier = this._viewNotifier,
				$input = this.$input;

			$input.suggestCommons();

			// Using the inputautoexpand plugin, the position of the dropdown needs to be updated
			// whenever the input box expands vertically:
			$input
			.on( 'eachchange', function( event, oldValue ) {
				// TODO/OPTIMIZE: Only reposition when necessary, i.e. when expanding vertically
				$input.data( 'suggestCommons' ).repositionMenu();
			} )
			.on( 'suggestcommonschange', function( event, response ) {
				notifier.notify( 'change' );
				$input.data( 'inputautoexpand' ).expand();
			} );
		}
	} );

}( jQuery, jQuery.valueview ) );
