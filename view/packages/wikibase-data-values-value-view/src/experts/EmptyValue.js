( function( $, vv ) {
	'use strict';

	var PARENT = vv.Expert;

	/**
	 * `Valueview` expert for empty `valueview` objects without any hint about what kind of
	 * value the user should be allowed to enter.
	 *
	 * @class jQuery.valueview.experts.EmptyValue
	 * @extends jQuery.valueview.Expert
	 * @since 0.1
	 * @license GNU GPL v2+
	 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
	 */
	vv.experts.EmptyValue = vv.expert( 'EmptyValue', PARENT, {
		/**
		 * @inheritdoc
		 * @protected
		 */
		_options: {
			messages: {
				'valueview-expert-emptyvalue-empty': 'empty'
			}
		},

		/**
		 * @inheritdoc
		 * @protected
		 */
		_init: function() {
			this.$viewPort.text(
				this._messageProvider.getMessage( 'valueview-expert-emptyvalue-empty' )
			);
		},

		/**
		 * @inheritdoc
		 * @return {null}
		 */
		rawValue: function() {
			return null;
		},

		/**
		 * @inheritdoc
		 */
		draw: function() {
			return $.Deferred().resolve().promise();
		}
	} );

}( jQuery, jQuery.valueview ) );
