( function( $, vv, UnitSelector ) {
	'use strict';

	var PARENT = vv.experts.StringValue;

	/**
	 * @class jQuery.valueview.experts.QuantityInput
	 * @extends jQuery.valueview.experts.StringValue
	 * @since 0.6
	 * @licence GNU GPL v2+
	 * @author Thiemo Mättig
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
		valueCharacteristics: function( format ) {
			var options = {
				unit: this._unitSelector && this._unitSelector.getConceptUri() || null
			};

			if ( format === 'text/plain' ) {
				options.applyRounding = false;
				options.applyUnit = false;
			}

			return options;
		},

		/**
		 * @inheritdoc
		 */
		destroy: function() {
			PARENT.prototype.destroy.call( this );

			this._unitSelector = null;
		}
	} );

}( jQuery, jQuery.valueview, jQuery.valueview.ExpertExtender.UnitSelector ) );
