/**
 * @file
 * @ingroup ValueView
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( mw, dv, vp, $, vv ) {
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
				mw.msg( 'valueview-expert-emptyvalue-empty' ) );
		}
	} );

}( mediaWiki, dataValues, valueParsers, jQuery, jQuery.valueview ) );
