/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( mw, wb, $, dataTypeStore ) {
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
	MODULE.variation( wb.datamodel.PropertyValueSnak, PARENT, {
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
			var self = this,
				newValue = this._newDataValue;

			function _render() {
				if( self._valueView ) {
					// We have a valueview now, so it can take over rendering until next
					// _setValue().
					self._newDataValue = false;

					if( !self._viewState.isInEditMode() ) {
						self.$viewPort.css( 'height', 'auto' );
					}

					// Set state
					if( self._viewState.isDisabled() ) {
						self._valueView.disable();
					} else {
						self._valueView.enable();
					}
				}

				$( self ).trigger( 'afterdraw' );
			}

			/**
			 * Handles a data value type mismatch by rendering appropriate messages.
			 * Such a mismatch can happen whenever something changes internally but there were no
			 * update scripts executed to change the data store. E.g. if a data type changes its
			 * data value type but the existing data is not migrated.
			 *
			 * @param {string} actualDataValueType
			 * @param {string} intendedDataValueType
			 */
			function handleDataValueTypeMismatch( actualDataValueType, intendedDataValueType ) {
				if( self._viewState.isInEditMode() ) {
					// Doesn't make sense to edit the value of the wrong data value type directly,
					// this will set the value to empty and create a valueview for entering an
					// entirely new value instead.
					self._newDataValue = null;
					self.draw();
				} else {
					// Not in edit mode, we just display a note:
					if( self._valueView ) {
						self._valueView.destroy();
						self._valueView = null;
					}
					self.$viewPort.empty().append(
						$( '<div/>', {
							'class': self.variationBaseClass + '-datavaluetypemismatch-message',
							text: mw.msg(
								'wikibase-snakview-variation-datavaluetypemismatch',
								actualDataValueType, intendedDataValueType
							)
						} )
						.append( $( '<div/>', {
							text: mw.msg(
								'wikibase-snakview-variation-datavaluetypemismatch-details',
								actualDataValueType, intendedDataValueType )
						} ) )
					);
					self.$viewPort.addClass( self.variationBaseClass + '-datavaluetypemismatch' );
					// TODO: display value nonetheless (if any valueview can handle it) and move
					//  this code into _createNewValueView() then.
				}
			}

			// if _setValue() wasn't called and this isn't initial draw(), changes done to valueview
			// directly (also by user interaction) are always rendered immediately
			if( newValue !== false ) { // newValue could also be null for empty value
				this.__currentNewValue = newValue;
				this._entityStore
				.get( this._viewState.propertyId() )
				.done( function( fetchedProperty ) {
					if( newValue !== self.__currentNewValue ) {
						// If the API response is not for the most recent newValue, discard it
						return;
					}

					// If the set property is not there, we have to display a warning. This can
					// happen if a property got deleted but the Snaks using it didn't change the
					// property.
					var dataTypeId = fetchedProperty
						? fetchedProperty.getContent().getDataType()
						: false;
					var dataType = false;

					if( dataTypeId ) {
						dataType = dataTypeStore.getDataType( dataTypeId );
					}

					// If the new value's type is not the data value type used by the Snak's
					// property data type, something is very wrong. Display warning!
					var isComparable = newValue && dataType;
					if( isComparable && newValue.getType() !== dataType.getDataValueType() ) {
						handleDataValueTypeMismatch(
							newValue.getType(),
							dataType.getDataValueType()
						);
						return; // do not change this._newDataValue as long as value is invalid
					}

					// Check whether valueview exists and if so, whether it is suitable for creating
					// a new data value valid against the given data type.
					if( self._valueView
						// can't check whether current valueview is most suitable for empty value if
						// no indication for what kind of value (specified by the data type) is
						// available
						&& ( dataType || newValue !== null )
					) {
						// display current Snak's data value in existing valueview:
						self._valueView.option( 'on', dataType );
						self._valueView.value( newValue );
					} else {
						// remove old view, create a new one or display message if unsupported data
						// type or other issue which would prevent from creating a valueview
						self._createNewValueView( newValue, dataType );
					}

					self.$viewPort.removeClass(
						self.variationBaseClass + '-datavaluetypemismatch'
					);

					_render();
				} );
			} else {
				_render();
			}
		},

		/**
		 * @see jQuery.wikibase.snakview.variations.Variation.startEditing
		 */
		startEditing: function() {
			if( !this._valueView || this._valueView.isInEditMode() ) {
				return;
			}
			this._valueView.startEditing();
			this._attachEventHandlers();
			this.draw();
		},

		/**
		 * @see jQuery.wikibase.snakview.variations.Variation.stopEditing
		 */
		stopEditing: function( dropValue ) {
			if( !this._valueView || !this._valueView.isInEditMode() ) {
				return;
			}
			this._valueView.stopEditing( dropValue );
			this._removeEventHandlers();
			this.draw();
		},

		/**
		 * Attaches event handlers to the value view widget's element.
		 */
		_attachEventHandlers: function() {
			var self = this;
			var $viewPort = this.$viewPort;
			var heightAnimationQueue = self.variationBaseClass + 'height';

			this._removeEventHandlers();

			this._valueView.element
			.on( 'valueviewparse.' + this.variationBaseClass, function( event ) {
				self._viewState.notify( 'invalid' );
			} )
			.on( 'valueviewafterparse.' + this.variationBaseClass, function( event ) {
				self._viewState.notify( ( self._valueView.value() ) ? 'valid' : 'invalid' );
			} )
			.on( 'inputextenderanimation.' + this.variationBaseClass, function( animationEvent ) {
				animationEvent.animationCallbacks.add( 'done', function() {
					var $input = $( animationEvent.target );
					var $extension = $input.data( 'inputextender' ).extension();
					var newHeight = 0;

					$viewPort.stop( heightAnimationQueue, true );

					if( $extension ) {
						newHeight = $input.outerHeight() + $extension.outerHeight();
					} else {
						var currentHeight = $viewPort.height();
						$viewPort.css( 'height', 'auto' );
						newHeight = $viewPort.height();
						$viewPort.height( currentHeight );
					}

					$viewPort.animate(
						{ height: newHeight },
						{
							queue: heightAnimationQueue,
							duration: 'fast', //defaults to 200
							progress: function( animation, progress, remainingMs ) {
								$.ui.inputextender.redrawVisibleExtensions();
							}
						}
					).dequeue( heightAnimationQueue );
				} );
			} )
			.on( 'inputextendercontentanimation.' + this.variationBaseClass, function( animationEvent ) {
				var $input = $( animationEvent.target );
				var inputHeight = $input.outerHeight();
				var $extension = $input.data( 'inputextender' ).extension();

				animationEvent.animationCallbacks
				.add( 'progress', function() {
					var newHeight = inputHeight + $extension.outerHeight();
					$viewPort.height( newHeight );
				} );
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
		 * @param {dataTypes.DataType} [dataType] The data type for which the given data value has
		 *        been created. Can be omitted but might result in a less specialized valueview.
		 * @return {boolean} Whether a valueview has actually been created.
		 */
		_createNewValueView: function( dataValue, dataType ) {
			var $valueViewDom;

			if( this._valueView ) {
				this._valueView.destroy();
				this._valueView = null;
				this.$viewPort.empty();
			}
			$valueViewDom = this.$viewPort.wrapInner( '<div/>' ).children();

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

			this._valueView = this._valueViewBuilder.initValueView(
				$valueViewDom,
				dataType,
				dataValue
			);

			return true;
		},

		/**
		 * @see jQuery.wikibase.snakview.variations.Variation.focus
		 */
		focus: function() {
			if( this._valueView && this._viewState.isDisabled() === false ) {
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

}( mediaWiki, wikibase, jQuery, wikibase.dataTypes ) );
