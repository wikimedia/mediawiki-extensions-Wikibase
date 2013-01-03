/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( mw, wb, dv, dt, $ ) {
	'use strict';

	var MODULE = $.wikibase.snakview.variations,
		PARENT = MODULE.Variation;

	/**
	 * Required snakview variation for displaying and creating PropertyValueSnak Snaks. Serves a
	 * valueview input widget when in edit mode and uses the same to display the Snak's value in
	 * non-edit mode.
	 *
	 * @constructor
	 * @extends jQuery.wikibase.snakview.variations.Variation
	 * @since 0.4
	 */
	var SELF = MODULE.Value = wb.utilities.inherit( PARENT, function( $viewPort ) {
		PARENT.call( this, $viewPort );
	}, {
		UI_CLASS: 'wb-snakview-variation',

		/**
		 * The value view widget object or null if property's data type isn't supported.
		 * @type jQuery.valueview.Widget
		 */
		_valueView: null,

		/**
		 * @see jQuery.wikibase.snakview.variations.Variation.destroy
		 */
		destroy: function() {
			if( this._valueView ) {
				this._valueView.destroy();
			}
		},

		/**
		 * @see jQuery.wikibase.snakview.variations.Variation.newSnak
		 */
		newSnak: function( propertyId ) {
			if( !this._valueView || !this._valueView.value() ) {
				// no PropertyValueSnak without value!
				return null;
			}
			return new wb.PropertyValueSnak(
				propertyId,
				this._valueView.value()
			);
		},

		/**
		 * @see jQuery.wikibase.snakview.variations.Variation.draw
		 */
		draw: function( inEditMode, propertyId, snak ) {
			var dataValue = snak ? snak.getValue() : null,
				dataType = dt.getDataType( wb.entities[ propertyId ].datatype );

			if( !this._valueView
				// if new data type has different data value type, we have to refresh view
				// TODO: this is a little flawed since there could be special widgets per DataType,
				//       see next TODO as well!
				|| this._valueView.dataValueType !== dataType.getDataValueType()
			) {
				var $valueViewDom = $( '<div/>' );
				this.$viewPort.empty().append( $valueViewDom );

				if( !$.valueview.canChooseView( dataType ) ) {
					// display message instead if there is no input method for data values of that type
					$valueViewDom
					.text( mw.msg( 'wikibase-snakview-variation-unsupporteddatatype', dataType.getLabel() ) )
					.addClass( this.UI_CLASS + '-unsupporteddatatype' );

					this._valueView = null;
					return; // nothing else we can do
				}

				// TODO: use something like an 'editview' and just change its data type rather than
				//       initializing this over and over again and doing the checks.
				$valueViewDom.valueview( { on: dataType } );
				this._valueView = $valueViewDom.data( 'valueview' );
				// NOTE: if valueview values can be set initially some day, do it here for performance
			}

			// display current Snak's data value in our valueview
			this._valueView.value( dataValue );

			// valueview will take care of setting value in edit or non edit mode
			this._valueView[ ( inEditMode ? 'start' : 'stop' ) + 'Editing' ]();
		},

		/**
		 * @see jQuery.wikibase.snakview.variations.Variation.focus
		 */
		focus: function() {
			if( this._valueView ) {
				this._valueView.focus();
			}
		},

		/**
		 * @see jQuery.wikibase.snakview.variations.Variation.blur
		 */
		blur: function() {
			if( this._valueView ) {
				this._valueView.blur();
			}
		}
	} );

	// make jQuery.snakview aware of this variation:
	MODULE.registerVariation( wb.PropertyValueSnak.TYPE, SELF );

}( mediaWiki, wikibase, dataValues, dataTypes, jQuery ) );
