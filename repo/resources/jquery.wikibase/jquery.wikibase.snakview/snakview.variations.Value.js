/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 *
 * @event animationstep: Triggered on the html element while the variation's viewport is being
 *        animated.
 *        (1) {jQuery.Event}
 *        (2) {Number} now
 *        (3) {jQuery.Tween} tween
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
	MODULE.variation( wb.PropertyValueSnak, PARENT, {
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
			this.$viewPort.css( 'height', 'auto' );
			if( this._valueView ) {
				this._valueView.element.off( '.' + this.variationBaseClass );
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

			if( this._newDataValue !== false ) {
				// draw() leaves the variable alone if it is an invalid value! In that case there
				// won't be a valueview where we can take the value from, but this is still the
				// actual value set as the Snak's value.
				// Also if draw has not been called yet, this will hold the current value.
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
			if( newValue !== false ) { // newValue could also be null for empty value
				var propertyId = this._viewState.propertyId(),
					fetchedProperty = wb.fetchedEntities[ propertyId ];

				// If the set property is not there, we have to display a warning. This can happen
				// if a property got deleted but the Snaks using it didn't change the property.
				var dataType = fetchedProperty
					? fetchedProperty.getContent().getDataType()
					: false;

				// if the new value's type is not the data value type used by the Snak's property
				// data type, something is very wrong. Display warning!
				if( newValue && dataType && newValue.getType() !== dataType.getDataValueType() ) {
					// NOTE: this can happen whenever something changes internally but there were
					//  no update scripts executed to change the data store. E.g. if a data type
					//  changes its data value type but the existing data is not migrated.

					if( this._viewState.isInEditMode() ) {
						// Doesn't make sense to edit the value of the wrong data value type directly,
						// this will set the value to empty and create a valueview for entering an
						// entirely new value instead.
						this._newDataValue = null;
						this.draw();
					} else {
						// Not in edit mode, we just display a note:
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
				if( this._valueView
					// can't check whether current valueview is most suitable for empty value if no
					// indication for what kind of value (specified by the data type) is available
					&& ( dataType || newValue !== null )
				) {
					// display current Snak's data value in existing valueview:
					this._valueView.option( 'on', dataType );
					this._valueView.value( newValue );
				} else {
					// remove old view, create a new one or display message if unsupported data type
					// or other issue which would prevent from creating a valueview
					this._createNewValueView( newValue, dataType );
				}

				this.$viewPort.removeClass( this.variationBaseClass + '-datavaluetypemismatch' );
			}

			if( this._valueView ) {
				// we have a valueview now, so it can take over rendering until next _setValue()
				this._newDataValue = false;

				// switch to edit/non-edit view depending on snakview:
				this._valueView[ ( this._viewState.isInEditMode() ? 'start' : 'stop' ) + 'Editing' ]();

				if( this._viewState.isInEditMode() ) {
					this._attachEventHandlers();
				} else {
					this.$viewPort.css( 'height', 'auto' );
					this._removeEventHandlers();
				}

				// set state
				if ( this._viewState.isDisabled() ) {
					this._valueView.disable();
				} else {
					this._valueView.enable();
				}
			}
		},

		/**
		 * Attaches event handlers to the value view widget's element.
		 */
		_attachEventHandlers: function() {
			var self = this;

			this._removeEventHandlers();

			this._valueView.element
			.on( 'valueviewparse.' + this.variationBaseClass, function( event ) {
				self._viewState.notify( 'invalid' );
			} )
			.on( 'valueviewafterparse.' + this.variationBaseClass, function( event ) {
				self._viewState.notify( ( self._valueView.value() ) ? 'valid' : 'invalid' );
			} )
			.on( 'inputextenderanimationstep.' + this.variationBaseClass, function( event, now, tween ) {
				if( tween !== undefined && tween.prop === 'opacity' ) {
					// Do not do anything when fading. Animation will be performed when fading is
					// completed via the animation's "complete" callback triggering an
					// "animationstep" event without parameters.
					return;
				}

				var $input = $( event.target ),
					$extension = $input.data( 'inputextender' ).$extension,
					newHeight = 0;

				self.$viewPort.stop( true );

				if( $extension.is( ':visible' ) ) {
					newHeight = $input.outerHeight() + $extension.outerHeight();
				} else {
					var currentHeight = self.$viewPort.height();
					self.$viewPort.css( 'height', 'auto' );
					newHeight = self.$viewPort.height();
					self.$viewPort.height( currentHeight );
				}

				if( tween === undefined ) {
					self.$viewPort.animate(
						{ height: newHeight },
						{
							duration: 250,
							step: function( now, tween ) {
								$( 'html' ).trigger( 'animationstep', [ now, tween ] );
							}
						}
					);
				} else {
					self.$viewPort.height( newHeight );
				}

				// Using .height() and .animate() automatically set overflow to "hidden" which we do
				// not want since it clips the input element.
				self.$viewPort.css( 'overflow', 'visible' );
			} );
		},

		/**
		 * Removes event handlers from the value view widget's element.
		 */
		_removeEventHandlers: function() {
			this._valueView.element.off( '.' + this.variationBaseClass );
		},

		/**
		 * Will create and insert a new valueview, also updates the internal _valueView field.
		 * The previously used valueview will be destroyed.
		 *
		 * @since 0.4
		 *
		 * @param {dv.DataValue} dataValue
		 * @param {dt.DataType} [dataType] The data type for which the given data value has been
		 *        created. Can be omitted but might result in a less specialized valueview.
		 * @return {boolean} Whether a valueview has actually been created.
		 */
		_createNewValueView: function( dataValue, dataType ) {
			var $valueViewDom = $( '<div/>' ),
				valueviewCriteria = dataType || dataValue;

			// Remove previous view. Put root node into DOM, so events can bubble from the start.
			this.$viewPort.empty().append( $valueViewDom );

			if( this._valueView ) {
				this._valueView.destroy();
				this._valueView = null;
			}

			// Can't choose a view for displaying empty value without indication by data type
			// definition which kind of value should be creatable by the new valueview.
			// NOTE: We run into this situation if we have a Snak which is using a deleted property,
			//  so the DataType can not be determined while we still want to display the valueview.
			if( !dataType && dataValue === null ) {
				// This message will be shown if the initial value uses a different Snak type but
				// the user tries to change the snak type to value Snak. This simply doesn't make
				// any sense since we have no indicator for what kind of value should be entered
				// if the Property doesn't provide us with that info.
				$valueViewDom
				.text( mw.msg( 'wikibase-snakview-variation-nonewvaluefordeletedproperty' ) )
				.addClass( this.variationBaseClass + '-nonewvaluefordeletedproperty' );

				return false; // no valueview created!
			}

			// TODO: Use something like an 'editview' and just change its data type rather than
			//  initializing this over and over again and doing the checks.
			$valueViewDom.valueview( {
				on: valueviewCriteria,
				value: dataValue,
				mediaWiki: mw
			} );
			this._valueView = $valueViewDom.data( 'valueview' );

			return true;
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
