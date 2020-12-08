( function( $, vv ) {
	'use strict';

	var PARENT = vv.Expert;

	/**
	 * `Valueview` expert for displaying (or rather not displaying) a data value not supported by
	 * the `valueview` UI because there is not specialised expert devoted to that data value type.
	 *
	 * @class jQuery.valueview.experts.UnsupportedValue
	 * @extends jQuery.valueview.Expert
	 * @since 0.1
	 * @license GNU GPL v2+
	 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
	 * @author Katie Filbert < aude.wiki@gmail.com >
	 */
	vv.experts.UnDeserializableValue = vv.expert( 'UnDeserializableValue', PARENT, {
		/**
		 * @inheritdoc
		 * @protected
		 */
		_options: {
			messages: {
				'valueview-expert-undeserializablevalue':
					'The value is invalid and cannot be displayed.'
			}
		},

		/**
		 * @inheritdoc
		 * @return {string}
		 */
		rawValue: function() {
			return this._viewState.getTextValue();
		},

		/**
		 * @inheritdoc
		 * @protected
		 */
		_init: function() {
			// reuse the existing formatted message
			this.$viewPort.html( this._viewState.getFormattedValue() );
		},

		/**
		 * @inheritdoc
		 */
		draw: function() {
			return $.Deferred().resolve().promise();
		}
	} );

}( jQuery, jQuery.valueview ) );
