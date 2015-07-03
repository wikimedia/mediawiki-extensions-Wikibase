( function( vv ) {
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

		var inputExtender = new vv.ExpertExtender(
			this.$input,
			[]
		);

		this.addExtension( inputExtender );
	}, {
		/**
		 * @inheritdoc
		 */
		valueCharacteristics: function() {
			return {
				unit: null
			};
		},

		/**
		 * @inheritdoc
		 */
		destroy: function() {
			PARENT.prototype.destroy.call( this );
			// TODO: Unset private properties.
		}
	} );

}( jQuery.valueview ) );
