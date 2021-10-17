( function ( dv ) {
	'use strict';

	var MODULE = require( './snakview.variations.js' ),
		Variation = require( './snakview.variations.Variation.js' ),
		datamodel = require( 'wikibase.datamodel' );

	/**
	 * `snakview` `Variation` for displaying and creating `datamodel.PropertyValueSnak`s.
	 * Serves a `jQuery.valueview` widget used to display and alter the `Snak`'s value.
	 *
	 * @see jQuery.valueview
	 * @see jQuery.wikibase.snakview
	 * @see datamodel.PropertyValueSnak
	 * @class jQuery.wikibase.snakview.variations.Value
	 * @extends Variation
	 * @license GPL-2.0-or-later
	 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
	 *
	 * @constructor
	 */
	MODULE.variation( datamodel.PropertyValueSnak, Variation, {
		/**
		 * The `valueview` widget instance or `null` if the `Property`'s `DataType` is not
		 * supported.
		 *
		 * @property {jQuery.valueview|null} [_valueView=null]
		 * @private
		 */
		_valueView: null,

		/**
		 * The `DataValue` last set in `_setValue()`. This field will not be updated, it only serves
		 * to remember the value until `draw()` is called. Afterwards, it is set to `false` until
		 * the next call to `_setValue()`.
		 *
		 * @property {dataValues.DataValue|null|false} [_newDataValue=null]
		 * @private
		 */
		_newDataValue: null,

		/**
		 * @inheritdoc
		 */
		destroy: function () {
			this.$viewPort.css( 'height', 'auto' );
			if ( this._valueView ) {
				this._valueView.element.off( '.' + this.variationBaseClass );
				this._valueView.destroy();
			}
			Variation.prototype.destroy.call( this );
		},

		/**
		 * @inheritdoc
		 * @protected
		 */
		_setValue: function ( value ) {
			this._newDataValue = null;
			if ( value.datavalue ) {
				this._newDataValue = dv.newDataValue( value.datavalue.type, value.datavalue.value );
			}
		},

		/**
		 * @inheritdoc
		 * @protected
		 */
		_getValue: function () {
			var dataValue = null;

			if ( this._newDataValue !== false ) {
				// draw() leaves the variable alone if it is an invalid value! In that case there
				// won't be a valueview where we can take the value from, but this is still the
				// actual value set as the Snak's value.
				// Also if draw has not been called yet, this will hold the current value.
				dataValue = this._newDataValue;
			} else if ( this._valueView ) {
				dataValue = this._valueView.value();
			}

			return !dataValue ? {} : { datavalue: dataValue };
		},

		/**
		 * @inheritdoc
		 */
		draw: function () {
			var self = this,
				newValue = this._newDataValue;

			function _render() {
				if ( self._valueView ) {
					// We have a valueview now, so it can take over rendering until next
					// _setValue().
					self._newDataValue = false;

					if ( !self._viewState.isInEditMode() ) {
						self.$viewPort.css( 'height', 'auto' );
					}

					// Set state
					if ( self._viewState.isDisabled() ) {
						self._valueView.disable();
					} else {
						self._valueView.enable();
					}
				}

				$( self ).trigger( 'afterdraw' );
			}

			/**
			 * @private
			 *
			 * @param {string|null} dataTypeId
			 * @return {wikibase.dataTypes.DataType|null}
			 */
			function _getDataType( dataTypeId ) {
				if ( dataTypeId ) {
					return self._dataTypeStore.getDataType( dataTypeId );
				}

				return null;
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
				if ( self._viewState.isInEditMode() ) {
					// Doesn't make sense to edit the value of the wrong data value type directly,
					// this will set the value to empty and create a valueview for entering an
					// entirely new value instead.
					self._newDataValue = null;
					self.draw();
				} else {
					// Not in edit mode, we just display a note:
					if ( self._valueView ) {
						self._valueView.destroy();
						self._valueView = null;
					}
					self.$viewPort.empty().append(
						$( '<div>' )
							.addClass( self.variationBaseClass + '-datavaluetypemismatch-message' )
							.text( mw.msg(
								'wikibase-snakview-variation-datavaluetypemismatch',
								actualDataValueType, intendedDataValueType
							) )
							.append(
								$( '<div>' )
									.text( mw.msg(
										'wikibase-snakview-variation-datavaluetypemismatch-details',
										actualDataValueType, intendedDataValueType
									) )
							)
					);
					self.$viewPort.addClass( self.variationBaseClass + '-datavaluetypemismatch' );
					// TODO: display value nonetheless (if any valueview can handle it) and move
					//  this code into _createNewValueView() then.
				}
			}

			// if _setValue() wasn't called and this isn't initial draw(), changes done to valueview
			// directly (also by user interaction) are always rendered immediately
			if ( newValue !== false ) { // newValue could also be null for empty value
				this.__currentNewValue = newValue;
				var propertyId = this._viewState.propertyId();
				this._propertyDataTypeStore
				.getDataTypeForProperty( propertyId )
				.done( function ( dataTypeId ) {
					if ( self.isDestroyed() ) {
						return;
					}

					if ( newValue !== self.__currentNewValue ) {
						// If the API response is not for the most recent newValue, discard it
						return;
					}

					var dataType = _getDataType( dataTypeId );

					if ( !dataType ) {
						// Note: Could not find a link between the caching problem and localStorage
						// and this issue (T216728), meaning the following clear doesn't seem to be
						// necessary in most cases.
						// However, clearing resourceloader storage prior to a hard refresh sounded
						// a good idea at the time if odds that this is causing an issue under
						// some edge circumstances.
						mw.loader.store.clear();

						mw.notify(
							mw.msg(
								'wikibase-refresh-for-missing-datatype',
								dataTypeId
							),
							{
								autoHide: false,
								title: mw.msg( 'wikibase-outdated-client-script' ),
								type: 'warn',
								tag: 'wikibase-outdated-datatypes'
							}
						);

						mw.log.warn(
							'Found property ' + propertyId + ' in entityStore but couldn\'t find ' +
							'the datatype "' + dataTypeId + '" in dataTypeStore. ' +
							'This is a bug or a configuration issue.'
						);
						return;
					}

					// If the new value's type is not the data value type used by the Snak's
					// property data type, something is very wrong. Display warning!
					if ( newValue && dataType && newValue.getType() !== dataType.getDataValueType()
						&& newValue.getType() !== dv.UnDeserializableValue.TYPE ) {
						handleDataValueTypeMismatch(
							newValue.getType(),
							dataType.getDataValueType()
						);
						return; // do not change this._newDataValue as long as value is invalid
					}

					// Check whether valueview exists and if so, whether it is suitable for creating
					// a new data value valid against the given data type.
					if ( self._valueView
						// can't check whether current valueview is most suitable for empty value if
						// no indication for what kind of value (specified by the data type) is
						// available
						&& ( dataType || newValue !== null )
					) {
						// display current Snak's data value in existing valueview:
						self._valueView.value( newValue );
					} else {
						// remove old view, create a new one or display message if unsupported data
						// type or other issue which would prevent from creating a valueview
						self._createNewValueView( newValue, dataType, propertyId );
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
		 * @inheritdoc
		 */
		startEditing: function () {
			if ( !this._valueView || this._valueView.isInEditMode() ) {
				return;
			}

			var self = this;

			this._valueView.element.one(
				this._valueView.widgetEventPrefix + 'afterstartediting',
				function () {
					$( self ).triggerHandler( 'afterstartediting' );
				}
			);

			this._valueView.startEditing();
			this._attachEventHandlers();
			this.draw();
		},

		/**
		 * @inheritdoc
		 */
		stopEditing: function ( dropValue ) {
			if ( !this._valueView || !this._valueView.isInEditMode() ) {
				return;
			}
			this._valueView.stopEditing( dropValue );
			this._removeEventHandlers();
			this.draw();
		},

		/**
		 * Attaches event handlers to the `valueview` widget's element.
		 *
		 * @private
		 */
		_attachEventHandlers: function () {
			var self = this;

			this._removeEventHandlers();

			this._valueView.element
			.on( 'valueviewparse.' + this.variationBaseClass, function ( event ) {
				self._viewState.notify( 'invalid' );
			} )
			.on( 'valueviewchange.' + this.variationBaseClass, function ( event ) {
				self._viewState.notify( self._valueView.value() ? 'valid' : 'invalid' );
			} );
		},

		/**
		 * Removes event handlers from the `valueview` widget's element.
		 *
		 * @private
		 */
		_removeEventHandlers: function () {
			this._valueView.element.off( '.' + this.variationBaseClass );
		},

		/**
		 * Creates and inserts a new `jQuery.valueview` while destroying the previously used
		 * `jQuery.valueview` instance.
		 *
		 * @private
		 *
		 * @param {dataValues.DataValue} dataValue
		 * @param {wikibase.dataTypes.DataType} [dataType] The `DataTypes` which the given `DataValue` has
		 *        been created for. Can be omitted but might result in a less specialized
		 *        `jQuery.valueview`.
		 * @param {string} [propertyId]
		 * @return {boolean} Whether a `jQuery.valueview` has actually been instantiated.
		 */
		_createNewValueView: function ( dataValue, dataType, propertyId ) {
			var $valueViewDom;

			if ( this._valueView ) {
				this._valueView.destroy();
				this._valueView = null;
				this.$viewPort.empty();
			}
			$valueViewDom = this.$viewPort.wrapInner( '<div/>' ).children();

			// Can't choose a view for displaying empty value without indication by data type
			// definition which kind of value should be creatable by the new valueview.
			// NOTE: We run into this situation if we have a Snak which is using a deleted property,
			//  so the DataType can not be determined while we still want to display the valueview.
			if ( !dataType && dataValue === null ) {
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
				dataValue,
				propertyId
			);

			return true;
		},

		/**
		 * @inheritdoc
		 */
		disable: function () {
			if ( this._valueView ) {
				this._valueView.disable();
			}
		},

		/**
		 * @inheritdoc
		 */
		enable: function () {
			if ( this._valueView ) {
				this._valueView.enable();
			}
		},

		/**
		 * @inheritdoc
		 */
		isFocusable: function () {
			return true;
		},

		/**
		 * @inheritdoc
		 */
		focus: function () {
			if ( this._valueView && this._viewState.isDisabled() === false ) {
				this._valueView.focus();
			}
		},

		/**
		 * @inheritdoc
		 */
		blur: function () {
			if ( this._valueView ) {
				this._valueView.blur();
			}
		}
	} );

}( dataValues ) );
