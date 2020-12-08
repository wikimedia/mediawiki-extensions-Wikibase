/**
 * @license GNU GPL v2+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 */
jQuery.valueview.tests = jQuery.valueview.tests || {};

( function( $, vv ) {
	'use strict';

	var PARENT = vv.Expert;

	/**
	 * Valueview expert for tests. Simply overwrites all abstract functions with some mock
	 * functions. A raw value can be set, all values are accepted.
	 *
	 * @since 0.1
	 *
	 * @constructor
	 * @extends jQuery.valueview.Expert
	 */
	vv.tests.MockExpert = vv.expert( 'Mock', PARENT, {
		/**
		 * Current value.
		 *
		 * @property {*}
		 */
		value: null,

		/**
		 * @see jQuery.valueview.Expert.destroy
		 */
		destroy: function() {
			this._value = null;
			PARENT.prototype.destroy.call( this );
		},

		/**
		 * @see jQuery.valueview.Expert.rawValue
		 * @return {null}
		 */
		rawValue: function() {
			return this._value;
		},

		/**
		 * @see jQuery.valueview.Expert.draw
		 */
		draw: function() {
			this.$viewPort.empty();
			return $.Deferred().resolve().promise();
		}
	} );

}( jQuery, jQuery.valueview ) );
