module.exports = ( function( $, vv ) {
	'use strict';

	var PARENT = vv.experts.StringValue,
		UnitSelector = require( '../ExpertExtender/ExpertExtender.UnitSelector.js' );

	/**
	 * @class jQuery.valueview.experts.QuantityInput
	 * @extends jQuery.valueview.experts.StringValue
	 * @since 0.6
	 * @license GNU GPL v2+
	 */
	vv.experts.QuantityInput = vv.expert( 'QuantityInput', PARENT, function() {
		PARENT.apply( this, arguments );

		var self = this;

		this._unitSelector = new UnitSelector(
			this._messageProvider,
			function() {
				var value = self.viewState().value(),
					unit = value && value.getUnit(),
					formattedValue = self.viewState().getFormattedValue(),
					$unit = $( '<div>' ).html( formattedValue ).find( '.wb-unit' ).first();
				return {
					conceptUri: unit,
					label: $unit.text() || unit
				};
			},
			function() {
				self._viewNotifier.notify( 'change' );
			},
			{
				language: this._options.language || null,
				vocabularyLookupApiUrl: this._options.vocabularyLookupApiUrl || null
			}
		);

		var inputExtender = new vv.ExpertExtender(
			this.$input,
			[
				this._unitSelector
			]
		);

		this.addExtension( inputExtender );
	}, {
		/**
		 * @property {jQuery.valueview.ExpertExtender.UnitSelector}
		 * @private
		 */
		_unitSelector: null,

		/**
		 * @inheritdoc
		 */
		valueCharacteristics: function() {
			return { unit: this._unitSelector.getConceptUri() || null };
		},

		/**
		 * @inheritdoc
		 */
		destroy: function() {
			PARENT.prototype.destroy.call( this );

			this._unitSelector = null;
		}
	} );

	return vv.experts.QuantityInput;

}( jQuery, jQuery.valueview ) );
