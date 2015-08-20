( function( vv, UnitSelector ) {
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
				var value = self.viewState().value();
				return value && value.getUnit();
			},
			function() {
				self._viewNotifier.notify( 'change' );
			},
			{
				language: self._options.language
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
			return {
				unit: this._unitSelector && this._unitSelector.getConceptUri() || null
			};
		},

		/**
		 * @inheritdoc
		 */
		destroy: function() {
			PARENT.prototype.destroy.call( this );

			this._unitSelector = null;
		}
	} );

}( jQuery.valueview, jQuery.valueview.ExpertExtender.UnitSelector ) );
