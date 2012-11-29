/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( mw, wb, dv, dt, $ ) {
	'use strict';

/**
 * View for displaying and editing Wikibase Snaks.
 * @since 0.3
 *
 * @option {wb.Snak|null} The snak this view should represent. If set to null, an empty view will be
 *         served, ready to take some input by the user. The value can be overwritten later, by
 *         using the value() function.
 *
 * @event startediting: Called before edit mode gets initialized.
 *        (1) {jQuery.Event} event
 *
 * @event stopediting: Called before edit mode gets closed.
 *        (1) {jQuery.Event} event
 *        (2) {Boolean} dropValue Will be false if the value will be kept.
 */
$.widget( 'wikibase.snakview', {
	widgetName: 'wikibase-snakview',
	widgetBaseClass: 'wb-snakview',

	/**
	 * (Additional) default options
	 * @see jQuery.Widget.options
	 */
	options: {
		value: null
	},

	/**
	 * The DOM node of the entity selector for choosing a property or the node with plain text of
	 * the properties label. This is a selector widget only the first time in edit mode.
	 * @type jQuery
	 */
	$property: null,

	/**
	 * The DOM node of the
	 */
	$snakValueDisplay: null,

	/**
	 * The Snak represented by this view
	 * @type {wb.Snak}
	 */
	_snak: null,

	/**
	 * @see jQuery.Widget._create
	 */
	_create: function() {
		this._snak = this.option( 'value' );

		this.element.addClass( this.widgetBaseClass );

		this.element.applyTemplate( 'wb-snak',
			'', // additional classes for root node
			'', // .wb-snak-property
			''  // .wb-snak-value
		);
		this.$property = this.element.find( '.wb-snak-property' );
		this.$snakValueDisplay = this.element.find( '.wb-snak-value' );

		if( this._snak === null ) {
			// no snak to be represented initially, serve edit view to create one.
			this.$property.append( this._buildPropertySelector() );
			// trigger 'startEditing' event because of initial edit mode.
			// can't stop default in this case.
			this._trigger( 'startEditing' );
		} else {
			this.value( this._snak );
		}
	},

	/**
	 * Returns an input element with initialized entity selector for selecting entities.
	 *
	 * @return {jQuery}
	 */
	_buildPropertySelector: function() {
		var self = this,
			language = mw.config.get( 'wgUserLanguage' );

		return $( '<input/>' ).entityselector( {
			url: mw.util.wikiScript( 'api' ),
			language: language,
			type: 'property'
		} )
		.on( 'entityselectorselect', function( e, ui ) {
			// entity chosen in entity selector but we still need the data type of the entity, so
			// we have to make a separate API call:
			var api = new wb.Api();

			api.getEntities( ui.item.id, null ).done( function( response ) {
				var entity = response.entities[ ui.item.id ],
					dataTypeId = entity.datatype, // TODO: include datatype into search API result
					dataType = dt.getDataType( dataTypeId );

				wb.properties[ ui.item.id ] = {
					label: entity.labels[ language ].value,
					datatype: dataType.getId()
				};

				if( dataType ) {
					// display a view for creating a value of that data type:
					self._updateValueViewDom( wb.PropertyValueSnak.TYPE, dataType );
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
	 * Will update the view for displaying and editing (in case of property-value-snak) the
	 * current value.
	 *
	 * TODO/FIXME: this function and stuff centered around it is not really well structured.
	 *
	 * @param {String} snakType
	 * @param {dt.DataType} dataType
	 * @param {dv.DataValue} [value]
	 */
	_updateValueViewDom: function( snakType, dataType, value ) {
		var $viewNode = $( '<div/>' ),
			valueView = this._getValueView();

		if( !$.valueview.canChooseView( dataType ) ) {
			$viewNode
			.text( mw.msg( 'wikibase-snak-unsupporteddatatype', dataType.getLabel() ) )
			.addClass( this.widgetBaseClass + '-unsupporteddatatype' );
		}
		else if( snakType === wb.PropertyValueSnak.TYPE ) {
			// if new data type has same data value type, we can keep the view
			// TODO: this is not entirely true since there could be special widgets per DataType,
			//       see next TODO as well.
			if( valueView && valueView.dataValueType === dataType.getDataValueType() ) {
				return;
			}

			// TODO: use the 'editview' and just change its data type rather than initializing this
			//       over and over again.
			$viewNode.valueview( { on: dataType } );
			if( value ) {
				$viewNode.valueview( 'value', value );
			} else {
				$viewNode.valueview( 'startEditing' );
			}
		}

		this.$snakValueDisplay.empty().append( $viewNode );
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
			this._getValueView().startEditing();
		}
	} ),

	/**
	 * Ends the edit mode where the snak can be edited.
	 * @since 0.3
	 *
	 * @param {Boolean} [dropValue] If true, the value from before edit mode has been started will
	 *                  be reinstated. false by default. Consider using cancelEditing() instead.
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
			if( this._getValueView() === null ) {
				return; // no value view, e.g. because no valid data type is chosen
			}
			this._getValueView().stopEditing( dropValue );

			if( !this._getValueView().isInEditMode() ) {
				// destroy property selector, this edit tool will only be available initially so it
				// can be destroyed since we don't need it in future initializations of the edit modes
				var entitySelector = this._getPropertySelector();

				if( entitySelector ) {
					// must be the first time we leave edit mode!
					var propertyLabel = entitySelector.value();
					entitySelector.destroy();
					this.$property.empty().text( propertyLabel );
				}
			}
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
		var creationMode = this._getPropertySelector() !== null;
		var valueView = this._getValueView();
		return creationMode || ( valueView !== null && valueView.isInEditMode() );
	},

	/**
	 * Returns the property selector for choosing the Snak's property. Returns null if the Snak is
	 * created already and has a Property (once created, the Property is immutable).
	 */
	_getPropertySelector: function() {
		if( this.$property ) {
			return this.$property.children().first().data( 'entityselector' ) || null;
		}
		return null;
	},

	/**
	 * Returns the value view widget object or null if it is not initialized
	 *
	 * @return {$.valueview.Widget}
	 */
	_getValueView: function() {
		return this.$snakValueDisplay.children().first().data( 'valueview' ) || null;
	},

	/**
	 * Returns the current Snak represented by the view, also allows to set the view to represent a
	 * given Snak.
	 *
	 * @return {wb.Snak}
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
	 * Returns the current Snak represented by the view or null in case the view is in edit mode
	 * for constructing a new Snak.
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
	 * Returns the property ID of the property chosen for this Snak
	 */
	_getPropertyId: function() {
		var propertySelector = this._getPropertySelector();
		if( propertySelector !== null ) {
			return propertySelector.selectedEntity().id;
		}
		return this._snak ? this._snak.getPropertyId() : null;
	},

	/**
	 * Will set the Snak to a given one.
	 *
	 * @param {wb.Snak} snak
	 */
	_setValue: function( snak ) {
		// get information about property used in this snak
		var property = wb.properties[ snak.getPropertyId() ],
			dataType = dt.getDataType( property.datatype ),
		// value to be displayed:
			value = snak.getType() === wb.PropertyValueSnak.TYPE ? snak.getValue() : undefined;

		if( this._getPropertySelector() ) {
			// entity selector still active, first editing with choosing entity:
			this._getPropertySelector().widget()
				.entityselector( wb.PropertyValueSnak.TYPE, snak.getPropertyId() );
		} else {
			// display property name as text only, not editable
			this.$property = $( '<div/>' ).text( property.label );
		}

		this._updateValueViewDom( snak.getType(), dataType, value );

		if( snak.getType() === wb.PropertyValueSnak.TYPE ) {
			this._getValueView().value( snak.getValue() );
		}
		this._snak = snak;
	}
} );

}( mediaWiki, wikibase, dataValues, dataTypes, jQuery ) );
