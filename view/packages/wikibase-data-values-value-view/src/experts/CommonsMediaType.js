module.exports = ( function( $, vv ) {
	'use strict';

	var PARENT = vv.experts.StringValue;

	/**
	 * `Valueview` expert for adding specialized handling for `CommonsMedia` data type.
	 * Without this more specialized expert, the `StringValue` expert would be used since the
	 * `CommonsMedia` data type is using the `String` data value type.
	 * This expert is based on the ``StringValue` expert but will add a drop-down for choosing
	 * Commons media sources. It will also display the value as a link to Commons.
	 *
	 * @class jQuery.valueview.experts.CommonsMediaType
	 * @extends jQuery.valueview.experts.StringValue
	 * @since 0.1
	 * @license GNU GPL v2+
	 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
	 */
	vv.experts.CommonsMediaType = vv.expert( 'CommonsMediaType', PARENT, {
		/**
		 * @inheritdoc
		 * @protected
		 */
		_init: function() {
			PARENT.prototype._init.call( this );

			var notifier = this._viewNotifier,
				$input = this.$input;

			$input.commonssuggester( {
				apiUrl: 'https://commons.wikimedia.org/w/api.php',
				namespace: 'File'
			} );

			// Using the inputautoexpand plugin, the position of the dropdown needs to be updated
			// whenever the input box expands vertically:
			$input
			.on( 'eachchange', function( event, oldValue ) {
				// TODO/OPTIMIZE: Only reposition when necessary, i.e. when expanding vertically
				$input.data( 'commonssuggester' ).repositionMenu();
			} )
			.on( 'commonssuggesterchange', function( event, response ) {
				notifier.notify( 'change' );
				$input.data( 'inputautoexpand' ).expand();
			} );
		}
	} );

	return vv.experts.CommonsMediaType;

}( jQuery, jQuery.valueview ) );
