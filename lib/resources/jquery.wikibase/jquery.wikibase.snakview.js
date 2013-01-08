/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( mw, wb, dv, dt, $ ) {
	'use strict';

	var PARENT = $.TemplatedWidget;

/**
 * View for displaying and editing Wikibase Snaks.
 * @since 0.3
 *
 * @option value {wb.Snak|null} The snak this view should represent. If set to null (by default),
 *         an empty view will be served, ready to take some input by the user. The value can be
 *         overwritten later, by using the value() function.
 *
 * @option predefined {Object} Allows to pre-define certain aspects of the Snak to be created.
 *         Can be used to only allow creation of Snaks using a certain pre-defined Property.
 *         This option will be overruled by the value option in case of a contradiction.
 *         The following fields can be set:
 *         - predefined.property {Number} a property ID, will prevent users from choosing a property.
 *         - predefined.snakType {String} TODO: implement!
 *
 * @event startediting: Called before edit mode gets initialized.
 *        (1) {jQuery.Event} event
 *
 * @event stopediting: Called before edit mode gets closed.
 *        (1) {jQuery.Event} event
 *        (2) {Boolean} dropValue Will be false if the value will be kept.
 *
 * @event afterstopediting: Called after edit mode got closed.
 *        (1) {jQuery.Event} event
 *        (2) {Boolean} droppedValue false if the value edited during edit mode has been preserved.
 */
$.widget( 'wikibase.snakview', PARENT, {
	widgetName: 'wikibase-snakview',
	widgetBaseClass: 'wb-snakview',
	widgetTemplate: 'wb-snak',
	widgetTemplateShortCuts: {
		'$property': '.wb-snak-property',
		'$snakValue': '.wb-snak-value',
		'$snakTypeSelector': '.wb-snak-typeselector'
	},

	/**
	 * (Additional) default options
	 * @see jQuery.Widget.options
	 */
	options: {
		value: null,
		predefined: {
			property: false,
			snakType: false // not yet implemented, see option description todo
		}
	},

	/**
	 * The DOM node of the entity selector for choosing a property or the node with plain text of
	 * the properties label. This is a selector widget only the first time in edit mode.
	 * @type jQuery
	 */
	$property: null,

	/**
	 * The DOM node of the Snak's value or some message if the value is not supported.
	 * TODO later we will support 'novalue' and 'somevalue' snaks which will probably be displayed
	 *      in this node as well somehow.
	 * @type jQuery
	 */
	$snakValue: null,

	/**
	 * The DOM node of the type selector.
	 * @type jQuery
	 */
	$snakTypeSelector: null,

	/**
	 * The Snak represented by this view. This is null if no valid Snak is constructed yet.
	 * @type {wb.Snak|null}
	 */
	_snak: null,

	/**
	 * @type Boolean
	 */
	_isInEditMode: false,

	/**
	 * Caching whether to move the focus from the property input to the value input after pressing
	 * the TAB key.
	 * @type Boolean
	 */
	_tabToValueView: false,

	/**
	 * @see jQuery.Widget._create
	 */
	_create: function() {
		// apply template to this.element:
		PARENT.prototype._create.call( this );

		this._snak = this.option( 'value' );

		this._drawValue( this._snak );

		if( this._snak === null ) {
			// if no Snak is represented, offer UI to build one
			this.startEditing();
		}
	},

	/**
	 * Returns an input element with initialized entity selector for selecting entities.
	 * @since 0.3
	 *
	 * @return {jQuery}
	 */
	_buildPropertySelector: function() {
		var self = this,
			language = mw.config.get( 'wgUserLanguage' );

		return $( '<input/>' ).entityselector( {
			url: mw.util.wikiScript( 'api' ),
			language: language,
			type: 'property',
			wikibase: wb
		} )
		.prop( 'placeholder', mw.msg( 'wikibase-snakview-property-input-placeholder' ) )
		.on( 'blur', function( event ) {
			self._tabToValueView = false;
		} )
		.eachchange( function( oldValue ) {
			// remove invalid value view
			if ( self._getValueView() !== null ) {
				self._getValueView().destroy();
			}
		} )
		.on( 'entityselectorselect', function( e, ui ) {
			// entity chosen in entity selector but we still need the data type of the entity, so
			// we have to make a separate API call:
			var api = new wb.Api();

			// display spinner as long as the value view is loading
			self.$snakValue.empty().append(
				$( '<div/>' ).append( $( '<span/>' ).addClass( 'mw-small-spinner' ) )
			);

			api.getEntities( ui.item.id, null ).done( function( response ) {
				var entity = response.entities[ ui.item.id ],
					dataTypeId = entity.datatype, // TODO: include datatype into search API result
					dataType = dt.getDataType( dataTypeId ),
					label;

				if( entity.labels && entity.labels[ language ] ) {
					label = entity.labels[ language ].value;
				}

				// update local store with newest information about selected property
				// TODO: create more sophisticated local store interface rather than accessing
				//       wb.entities directly
				var property = wb.entities[ ui.item.id ] = {
					label: label,
					datatype: dataType.getId(),
					url: ui.item.url
				};

				if( dataType ) {
					// display a view for creating a value of that data type:
					// TODO: this is just PropertyValueSnak specific
					self._drawDataValue( property, null );

					// Since it takes a while for the value view to gather its data from the API,
					// the property might not be valid anymore aborting the rendering of the value
					// view.
					if ( self._getValueView() !== null && self._tabToValueView ) {
						self._getValueView().focus();
					}
				} else {
					// TODO: display a message that the property has a data type unknown to the UI
				}
			} );
		} );
	},

	/**
	 * @see $.widget.destroy
	 */
	destroy: function() {
		this.element.removeClass( this.widgetBaseClass );
		$.Widget.prototype.destroy.call( this );
	},

	/**
	 * Starts the edit mode where the snak can be edited.
	 * @since 0.3
	 *
	 * @return {undefined} (allows chaining widget calls)
	 */
	startEditing: $.NativeEventHandler( 'startEditing', {
		// don't start edit mode or trigger event if in edit mode already:
		initially: function( e ) {
			if( this.isInEditMode() ) {
				e.cancel();
			}
		},
		// start edit mode if event doesn't prevent default:
		natively: function( e ) {
			var self = this;

			this._isInEditMode = true;
			this._drawValue( this._snak );

			// attach keyboard input events
			this.element.on( 'keydown', function( event ) {
				var propertySelector = self._getPropertySelector();

				self._leavePropertyInput = false;

				if ( event.keyCode === $.ui.keyCode.ESCAPE ) {
					self.cancelEditing();
				} else if ( event.keyCode === $.ui.keyCode.ENTER ) {
					if ( !propertySelector || event.target !== propertySelector.element[0] ) {
						self.stopEditing();
					}
				} else if ( event.keyCode === $.ui.keyCode.TAB && self._getValueView() === null ) {
					// When pressing TAB in the property input element while the value input element
					// does not yet exist, we assume that the user wants to auto-complete/select the
					// currently suggested property and tab into the value element. Since the API
					// needs to be queried to construct the correct value input, the intended action
					// needs to be cached and triggered as soon as the value input has been created.
					if ( propertySelector && event.target === propertySelector.element[0] ) {
						if ( self._getPropertySelector().validateInput() ) {
							// Have the current property input validated instead of just blurring
							// since the value input element will not appear if the property is not
							// valid and setting the focus would remain unresolved.
							// We do not blur automatically. If the user decides to manually focus
							// another element while the value input element is loading, cancel the
							// cached tab event.
							self._getPropertySelector().widget().data( 'menu' ).select(
								$.Event( 'programmatic' )
							);
							self._tabToValueView = true;
							event.preventDefault();
						} else if ( self._getPropertySelector().selectedEntity() !== null ) {
							// A property has already been selected (e.g. selecting a suggestion
							// with the keyboard's arrow keys and then pressing TAB).
							self._tabToValueView = true;
							event.preventDefault();
						}
					}
				}
				// no point in propagating event after having destroyed the event's target
				if ( !self.isInEditMode() ) {
					event.stopImmediatePropagation();
				}
			} );

			if ( this._getPropertySelector() !== null ) {
				this._getPropertySelector().element.focus();
			} else {
				this._getValueView().focus();
			}
		}
	} ),

	/**
	 * Ends the edit mode where the snak can be edited.
	 * @since 0.3
	 *
	 * @param {Boolean} [dropValue] If true, the value from before edit mode has been started will
	 *        be reinstated. false by default. Consider using cancelEditing() instead.
	 * @return {undefined} (allows chaining widget calls)
	 */
	stopEditing: $.NativeEventHandler( 'stopEditing', {
		// don't stop edit mode or trigger event if not in edit mode currently:
		initially: function( e, dropValue ) {
			if( !this.isInEditMode() ) {
				e.cancel();
			}
			e.handlerArgs = [ !!dropValue ]; // just make sure this is a Boolean for event handlers
		},
		// start edit mode if custom event handlers didn't prevent default:
		natively: function( e, dropValue ) {
			// TODO: This is PropertyValueSnak specific, consider other Snak types
			var valueView = this._getValueView();

			if( valueView === null ) {
				this._trigger( 'afterStopEditing', null, [ dropValue ] );
				return; // no value view, e.g. because no valid data type is chosen
			}
			this._isInEditMode = false;
			valueView.stopEditing( dropValue );

			if( valueView.value() === null || dropValue ) {
				this._trigger( 'afterStopEditing', null, [ dropValue ] );
				return; // no value (shouldn't happen) or cancel, can't update
			}

			var snak = new wb.PropertyValueSnak( this._getPropertyId(), valueView.value() );
			this._setValue( snak );

			this._trigger( 'afterStopEditing', null, [ dropValue ] );
		}
	} ),

	/**
	 * short-cut for stopEditing( false ). Closes the edit view and restores the value from before
	 * the edit mode has been started.
	 * @since 0.3
	 *
	 * @return {undefined} (allows chaining widget calls)
	 */
	cancelEditing: function() {
		return this.stopEditing( true ); // stop editing and drop value
	},

	/**
	 * Returns whether the Snak is editable at the moment.
	 * @since 0.3
	 *
	 * @return Boolean
	 */
	isInEditMode: function() {
		return this._isInEditMode;
	},

	/**
	 * Returns the property selector for choosing the Snak's property. Returns null if the Snak is
	 * created already and has a Property (once created, the Property is immutable). Also returns
	 * null if predefined.property option is set.
	 * @since 0.3
	 *
	 * @return {jQuery.wikibase.entityselector|null}
	 */
	_getPropertySelector: function() {
		if( this.$property ) {
			return this.$property.children().first().data( 'entityselector' ) || null;
		}
		return null;
	},

	/**
	 * Returns the value view widget object or null if it is not initialized
	 * @since 0.3
	 *
	 * @return {$.valueview.Widget|null}
	 */
	_getValueView: function() {
		return this.$snakValue.children().first().data( 'valueview' ) || null;
	},

	/**
	 * Returns the current Snak represented by the view or null in case the view is in edit mode,
	 * also allows to set the view to represent a given Snak.
	 *
	 * @since 0.3
	 *
	 * @return {wb.Snak|null}
	 */
	value: function( value ) {
		if( value === undefined ) {
			return this._getValue();
		}
		if( !( value instanceof wb.Snak ) ) {
			throw new Error( 'The given value has to be an instance of wikibase.Snak' );
		}
		return this._setValue( value );
	},

	/**
	 * Private getter for this.value()
	 * @since 0.3
	 *
	 * @return wb.Snak|null
	 */
	_getValue: function() {
		if( ! this._getValueView() ) {
			return null;
		}
		var propertyId = this._getPropertyId();
		var dataValue = this._getValueView().value();

		if( propertyId === null || dataValue === null ) {
			return null;
		}
		return new wb.PropertyValueSnak( propertyId, dataValue );
	},

	/**
	 * Returns the property ID of the property chosen for this Snak or null if none is set.
	 * Equal to .value().getPropertyId() but might be set while .value() still returns null, e.g.
	 * if property has been selected or pre-defined while value or Snak type are not yet set.
	 * @since 0.3
	 *
	 * TODO implement setter functionality for this
	 *
	 * @return String|null
	 */
	propertyId: function() {
		return this._getPropertyId();
	},

	/**
	 * Private getter for this.propertyId()
	 * @since 0.3
	 *
	 * @return String|null
	 */
	_getPropertyId: function() {
		// return user-chosen property ID
		var propertySelector = this._getPropertySelector();
		if( propertySelector ) {
			var selectedEntity = propertySelector.selectedEntity();
			return selectedEntity ? selectedEntity.id : null;
		}

		// no selector, perhaps Snak is defined already
		if( this._snak ) {
			return this._snak.getPropertyId();
		}

		// if set, return pre-defined property ID
		var predefinedPropertyId = this.option( 'predefined' ).property;
		if( predefinedPropertyId ) {
			return predefinedPropertyId;
		}

		return null;
	},

	/**
	 * Will update the view to represent a given Snak or nothing but an empty form instead.
	 * @since 0.3
	 *
	 * @param {wb.Snak|null} snak
	 */
	_setValue: function( snak ) {
		this._snak = snak;
		this._drawValue( snak );
	},

	/**
	 * @since 0.4
	 * @param {wb.Snak|null} snak
	 */
	_drawValue: function() {
		this._drawProperty();
		this._drawSnakTypeSelector();
		this._drawDataValue();
	},

	/**
	 * Will make sure the current Snak's property is displayed properly. If not Snak is set, then
	 * this will serve the input form for the Snak's property (except if the property is set per the
	 * 'predefined' option).
	 * @since 0.4
	 */
	_drawProperty: function() {
		var $propertyDom,
			propertyId = this._getPropertyId(),
			property = propertyId ? wb.entities[ propertyId ] : null,
			propertyLabel = '';

		if( property ) {
			propertyLabel = property.label || propertyId;
		}

		if( property || !this.isInEditMode() ) {
			// property set and can't be changed afterwards, only display label
			$propertyDom = $( document.createTextNode( propertyLabel ) );
			// TODO: display nice label with link here, just like claimlistview
		} else {
			// no property set for this Snak, serve edit view to specify it:
			var propertySelector = this._getPropertySelector();

			// TODO: use selectedEntity() or other command to set selected entity in both cases!
			//       When asking _getValue(), _getPropertyId() will return null because it asks the
			//       widget which doesn't know that the val() set here actually is an entity.
			if( propertySelector ) {
				// property selector in DOM already, just replace current value
				propertySelector.widget().val( propertyLabel );
				return;
			}
			// property selector in DOM already, just remove current value
			$propertyDom = this._buildPropertySelector().val( propertyLabel );
		}

		this.$property.empty().append( $propertyDom );
	},

	/**
	 * Will update the selector for choosing the Snak type.
	 * @since 0.4
	 */
	_drawSnakTypeSelector: function() {
		// TODO: implement, remove during non-edit mode, display in edit mode!
		//this._buildSnakTypeSelector()
		this.$snakTypeSelector.empty();
	},

	/**
	 * Will change the view to display a certain data value. If the DOM to represent a value is not
	 * yet inserted, this will take care of its insertion.
	 *
	 * TODO: when we implement Snak types, this should be moved into something like a
	 *       PropertyValueSnak strategy.
	 */
	_drawDataValue: function() {
		var valueView = this._getValueView(),
			propertyId = this._getPropertyId(),
			dataValue = this._snak ? this._snak.getValue() : null;

		if( !propertyId ) {
			// no property selected yet, display empty
			this.$snakValue.empty();
			if( valueView ) {
				valueView.destroy(); // clean up existing valueview widget
			}
			return;
		}

		var dataType = dt.getDataType( wb.entities[ propertyId ].datatype );

		if( !valueView
			// if new data type has different data value type, we have to refresh view
			// TODO: this is a little flawed since there could be special widgets per DataType,
			//       see next TODO as well!
			|| valueView.dataValueType !== dataType.getDataValueType()
		) {
			var $valueViewDom = $( '<div/>' );
			this.$snakValue.empty().append( $valueViewDom );

			if( !$.valueview.canChooseView( dataType ) ) {
				// display message instead if there is no input method for data values of that type
				$valueViewDom
				.text( mw.msg( 'wikibase-snakview-unsupporteddatatype', dataType.getLabel() ) )
				.addClass( this.widgetBaseClass + '-unsupporteddatatype' );

				return; // nothing else we can do
			}

			// TODO: use something like an 'editview' and just change its data type rather than
			//       initializing this over and over again and doing the checks.
			$valueViewDom.valueview( { on: dataType } );
			valueView = $valueViewDom.data( 'valueview' );
			// TODO: if value can be set initially some day, do it here for performance
		}

		// display current Snak's data value in our valueview
		valueView.value( dataValue );

		// valueview will take care of setting value in edit or non edit mode
		valueView[ ( this.isInEditMode() ? 'start' : 'stop' ) + 'Editing' ]();
	}

// TODO: implement selector for Snak type
//	_drawSnakType: function() {},
} );

}( mediaWiki, wikibase, dataValues, dataTypes, jQuery ) );
