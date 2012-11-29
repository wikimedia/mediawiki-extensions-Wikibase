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
	 * @see jQuery.Widget._create
	 */
	_create: function() {
		var self = this,
			snak = this.option( 'value' );

		this.element.empty();
		this.element.addClass( this.widgetBaseClass );

		this.$snakValueDisplay = $( '<div/>' );

		if( snak === null ) {
			// no snak to be represented initially, serve edit view to create one.
			this.$property = this._buildPropertySelector();
		} else {
			this.value( snak );
		}

		this.element.append( this.$snakValueDisplay ).prepend( this.$property );

		if( snak === null ) {
			// trigger 'startEditing' event because of initial edit mode.
			// can't stop default in this case.
			this._trigger( 'startEditing' );
		}
	},

	/**
	 * Returns an input element with initialized entity selector for selecting entities.
	 *
	 * @return {jQuery}
	 */
	_buildPropertySelector: function() {
		var self = this;
		return $( '<input/>' ).entityselector( {
			url: mw.util.wikiScript( 'api' ),
			language: mw.config.get( 'wgUserLanguage' ),
			type: 'property'
		} )
		.on( 'entityselectorselect', function( e, ui ) {
			// entity chosen in entity selector but we still need the data type of the entity, so
			// we have to make a separate API call:
			var api = new wb.Api();

			api.getEntities( ui.item.id, null ).done( function( response ) {
				var dataTypeId = response.entities[ ui.item.id ].datatype, // TODO: include datatype into search API result
					dataType = dt.getDataType( dataTypeId );

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
				var entitySelector = this.$property.data( 'entityselector' );

				if( entitySelector ) {
					// must be the first time we leave edit mode!
					var propertyLabel = entitySelector.value();
					entitySelector.destroy();
					this.$property.replaceWith( $( '<div/>' ).text( propertyLabel ) );
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
		var creationMode = this.$property.data( 'entityselector' ) !== undefined;
		var valueView = this._getValueView();
		return creationMode || ( valueView !== null && valueView.isInEditMode() );
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
		var propertyId = this.$property.entityselector( 'selectedEntity' ).id;
		var dataValue = this._getValueView().value();

		if( dataValue === null ) {
			return null;
		}
		return new wb.PropertyValueSnak( propertyId, dataValue );
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

		if( this.$property && this.$property.data( 'entityselector' ) ) {
			// entity selector still active, first editing with choosing entity:
			this.$property.entityselector( wb.PropertyValueSnak.TYPE, snak.getPropertyId() );
		} else {
			// display property name as text only, not editable
			this.$property = $( '<div/>' ).text( property.label );
		}

		this._updateValueViewDom( snak.getType(), dataType, value );

		if( snak.getType() === wb.PropertyValueSnak.TYPE ) {
			this._getValueView().value( snak.getValue() );
		}
	}
} );

}( mediaWiki, wikibase, dataValues, dataTypes, jQuery ) );
