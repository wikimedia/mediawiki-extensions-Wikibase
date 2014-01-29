/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
jQuery.valueview.tests = jQuery.valueview.tests || {};

( function( vv ) {
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
		 * @type {*}
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
		 * @see jQuery.valueview.Expert._getRawValue
		 */
		_getRawValue: function() {
			return this._value;
		},

		/**
		 * @see jQuery.valueview.Expert._setRawValue
		 */
		_setRawValue: function( rawValue ) {
			this._value = rawValue;
		},

		/**
		 * @see jQuery.valueview.Expert.draw
		 */
		draw: function() {
			this.$viewPort.empty();
		}
	} );

}( jQuery.valueview ) );
