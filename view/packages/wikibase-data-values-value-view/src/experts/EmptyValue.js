/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( $, vv ) {
	'use strict';

	var PARENT = vv.Expert;

	/**
	 * Valueview expert for empty valueviews without any hint about what kind of value the user
	 * should be allowed to enter.
	 *
	 * @since 0.1
	 *
	 * @constructor
	 * @extends jQuery.valueview.Expert
	 */
	vv.experts.EmptyValue = vv.expert( 'EmptyValue', PARENT, {
		/**
		 * Options.
		 * @type {Object}
		 */
		_options: {
			messages: {
				'valueview-expert-emptyvalue-empty': 'empty'
			}
		},

		/**
		 * @see jQuery.valueview.Expert._init
		 */
		_init: function() {
			this.$viewPort.text(
				this._messageProvider.getMessage( 'valueview-expert-emptyvalue-empty' )
			);
		},

		/**
		 * @see jQuery.valueview.Expert.rawValue
		 */
		rawValue: function() {
			return null;
		},

		/**
		 * @see jQuery.valueview.Expert.draw
		 */
		draw: function() {
			return $.Deferred().resolve().promise();
		}
	} );

}( jQuery, jQuery.valueview ) );
