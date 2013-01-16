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
	var SELF = MODULE.variation( wb.PropertyValueSnak, PARENT, {
		/**
		 * The value view widget object or null if property's data type isn't supported.
		 * @type jQuery.valueview.Widget
		 */
		_valueView: null,

		/**
		 * The data value last set in _setValue(). This field will not be updated, it only serves
		 * to remember the value until draw() is called. Afterwards, it is set to false until the
		 * next call to _setValue().
		 * @type dv.DataValue|null|false
		 */
		_newDataValue: null,

		/**
		 * @see jQuery.wikibase.snakview.variations.Variation.destroy
		 */
		destroy: function() {
			if( this._valueView ) {
				this._valueView.destroy();
			}
			PARENT.prototype.destroy.call( this );
		},

		/**
		 * @see jQuery.wikibase.snakview.variations.Variation._setValue
		 */
		_setValue: function( value ) {
			this._newDataValue = value.datavalue || null;
		},

		/**
		 * @see jQuery.wikibase.snakview.variations.Variation._getValue
		 */
		_getValue: function() {
			var dataValue = null;
			this.draw(); // makes sure that _valueView is set

			if( this._newDataValue !== false ) {
				// draw() leaves the variable alone if it is an invalid value! In that case there
				// won't be a valueview where we can take the value from, but this is still the
				// actual value set as the Snak's value.
				dataValue = this._newDataValue;
			}
			else if( this._valueView ) {
				dataValue = this._valueView.value();
			}

			return {
				// null if no value yet or if value with no suitable valueview for
				datavalue: dataValue
			};
		},

		/**
		 * @see jQuery.wikibase.snakview.variations.Variation.draw
		 */
		draw: function() {
			var newValue = this._newDataValue;

			// if _setValue() wasn't called and this isn't initial draw(), changes done to valueview
			// directly (also by user interaction) are always rendered immediately
			if( newValue !== false ) {
				var propertyId = this._viewState.propertyId(),
					dataType = dt.getDataType( wb.entities[ propertyId ].datatype );

				// if the new value's type is not the data value type used by the Snak's property
				// data type, something is very wrong. Display warning!
				if( newValue && newValue.getType() !== dataType.getDataValueType() ) {
					// NOTE: this can happen whenever something changes internally but there were
					//  no update scripts executed to change the data store. E.g. if a data type
					//  changes its data value type but the existing data is not migrated.

					if( this._viewState.isInEditMode() ) {
						// doesn't make sense to edit the value of the wrong data value type directly,
						// this will set the value to empty and create a valueview for entering an
						// entirely new value instead.
						this._newDataValue = null;
						this.draw();
					} else {
						// in non edit mode, we just display note
						if( this._valueView ) {
							this._valueView.destroy();
							this._valueView = null;
						}
						this.$viewPort.empty().append(
							$( '<div/>', {
								'class': this.variationBaseClass + '-datavaluetypemismatch-message',
								text: mw.msg( 'wikibase-snakview-variation-datavaluetypemismatch',
									newValue.getType(), dataType.getDataValueType() )
							} )
							.append( $( '<div/>', {
								text: mw.msg( 'wikibase-snakview-variation-datavaluetypemismatch-details',
									newValue.getType(), dataType.getDataValueType() )
							} ) )
						);
						this.$viewPort.addClass( this.variationBaseClass + '-datavaluetypemismatch' );
						// TODO: display value nonetheless (if any valueview can handle it) and move
						//  this code into _createNewValueView() then.
					}
					return; // do not change this._newDataValue as long as value is invalid
				}
				// Check whether valueview exists and if so, whether it is suitable for creating a
				// new data value valid against the given data type.
				else if( this._valueView && this._valueView.isMostSuitableFor( dataType ) ) {
					// display current Snak's data value in existing valueview:
					this._valueView.value( newValue );
				} else {
					// remove old view, create a new one:
					this._createNewValueView( dataType, newValue );
				}

				// from now on, valueview itself takes over rendering until next _setValue()
				this._newDataValue = false;
				this.$viewPort.removeClass( this.variationBaseClass + '-datavaluetypemismatch' );
			}

			// switch to edit/non-edit view depending on snakview:
			this._valueView[ ( this._viewState.isInEditMode() ? 'start' : 'stop' ) + 'Editing' ]();
		},

		/**
		 * Will create and insert a new valueview, also updates the internal _valueView field.
		 * The previously set view will be destroyed
		 *
		 * @since 0.4
		 *
		 * @param {dt.DataType} dataType
		 * @param {dv.DataValue} dataValue
		 */
		_createNewValueView: function( dataType, dataValue ) {
			var $valueViewDom = $( '<div/>' );
			this.$viewPort.empty().append( $valueViewDom );

			if( this._valueView ) {
				this._valueView.destroy();
				this._valueView = null;
			}

			// check if there is a suitable valueview:
			if( !$.valueview.canChooseView( dataType ) ) {
				// display message instead if there is no input method for data values of that type
				$valueViewDom
					.text( mw.msg( 'wikibase-snakview-variation-unsupporteddatatype', dataType.getLabel() ) )
					.addClass( this.variationBaseClass + '-unsupporteddatatype' );

				return; // nothing else we can do
			}

			// TODO: use something like an 'editview' and just change its data type rather than
			//       initializing this over and over again and doing the checks.
			$valueViewDom.valueview( { on: dataType } );
			this._valueView = $valueViewDom.data( 'valueview' );

			// NOTE: if valueview values can be set initially some day, do it for performance
			this._valueView.value( dataValue );
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

}( mediaWiki, wikibase, dataValues, dataTypes, jQuery ) );
