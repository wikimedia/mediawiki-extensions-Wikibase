( function( $, ExpertExtender ) {
	'use strict';

	/**
	 * An `ExpertExtender` module for selecting a quantity's unit.
	 * @class jQuery.valueview.ExpertExtender.UnitSelector
	 * @since 0.15.0
	 * @licence GNU GPL v2+
	 * @author Thiemo Mättig
	 *
	 * @constructor
	 *
	 * @param {Function} getUpstreamValue
	 * @param {Function} onValueChange
	 */
	ExpertExtender.UnitSelector = function(
		getUpstreamValue,
		onValueChange
	) {
		this._getUpstreamValue = getUpstreamValue;
		this._onValueChange = onValueChange;

		this.$selector = $( '<input>' );
	};

	$.extend( ExpertExtender.UnitSelector.prototype, {
		/**
		 * @property {Function}
		 * @private
		 */
		_getUpstreamValue: null,

		/**
		 * @property {Function}
		 * @private
		 */
		_onValueChange: null,

		/**
		 * @property {jQuery}
		 * @private
		 * @readonly
		 */
		$selector: null,

		/**
		 * Callback for the `init` `ExpertExtender` event.
		 *
		 * @param {jQuery} $extender
		 */
		init: function( $extender ) {
			this.$selector.on( 'eachchange', this._onValueChange );
			$extender
				// FIXME: Use a MessageProvider!
				.append( $( '<span>' ).text( 'Unit (optional)' ) )
				.append( this.$selector );
		},

		/**
		 * Callback for the `onInitialShow` `ExpertExtender` event.
		 */
		onInitialShow: function() {
			var value = this._getUpstreamValue();
			this.$selector.val( value );
		},

		/**
		 * Callback for the `destroy` `ExpertExtender` event.
		 */
		destroy: function() {
			this._getUpstreamValue = null;
			this.$selector = null;
			this._onValueChange = null;
		},

		/**
		 * Gets the value currently set in the rotator.
		 *
		 * @return {string|null} The current value
		 */
		getValue: function() {
			return this.$selector.val();
		}
	} );

}( jQuery, jQuery.valueview.ExpertExtender ) );
