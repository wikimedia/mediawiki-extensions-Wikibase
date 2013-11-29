/**
 * @file
 * @ingroup ValueView
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( dv, $, vv ) {
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
	vv.experts.EmptyValue = vv.expert( 'emptyvalue', {
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
		 * @see jQuery.valueview.Expert.destroy
		 */
		destroy: function() {
			this.$viewPort.empty();
			PARENT.prototype.destroy.call( this );
		},

		/**
		 * @see jQuery.valueview.Expert._getRawValue
		 */
		_getRawValue: function() {
			return null;
		},

		/**
		 * @see jQuery.valueview.Expert._setRawValue
		 */
		_setRawValue: function( rawValue ) {
			// Do nothing, we're always null!
		},

		/**
		 * @see jQuery.valueview.Expert.draw
		 */
		draw: function() {
			this.$viewPort.text(
				this._messageProvider.getMessage( 'valueview-expert-emptyvalue-empty' )
			);
		}
	} );

}( dataValues, jQuery, jQuery.valueview ) );
