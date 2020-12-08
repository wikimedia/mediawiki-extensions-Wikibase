module.exports = ( function( $, vv ) {
	'use strict';

	var PARENT = vv.experts.StringValue;

	/**
	 * `Valueview` expert for adding specialized handling for `tabular-data` data type.
	 * Without this more specialized expert, the `StringValue` expert would be used since the
	 * `tabular-data` data type is using the `String` data value type.
	 * This expert is based on the `StringValue` expert but will add a drop-down for choosing
	 * Commons data sources. It will also display the value as a link to Commons.
	 *
	 * @class jQuery.valueview.experts.TabularData
	 * @extends jQuery.valueview.experts.StringValue
	 * @since 0.1
	 * @license GNU GPL v2+
	 * @author Amir Sarabadani <ladsgroup@gmail.com>
	 */
	vv.experts.TabularData = vv.expert( 'TabularData', PARENT, {
		/**
		 * @inheritdoc
		 * @protected
		 */
		_init: function() {
			PARENT.prototype._init.call( this );

			var notifier = this._viewNotifier,
				$input = this.$input;

			$input.commonssuggester( {
				apiUrl: this._options.commonsApiUrl,
				namespace: 'Data',
				contentModel: 'Tabular.JsonConfig'
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

	return vv.experts.TabularData;

}( jQuery, jQuery.valueview ) );
