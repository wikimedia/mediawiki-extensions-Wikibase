/**
 * Wikibase site selector widget
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.2
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( mw, wb, dv, dt, $, undefined ) {
	'use strict';

/**
 * View for displaying and editing Wikibase Snaks.
 */
$.widget( 'wikibase.snakview', {
	widgetName: 'wikibase-snakview',

	/**
	 * (Additional) default options
	 * @see jQuery.Widget.options
	 */
	options: {},

	/**
	 * The DOM node of the entity selector for choosing a property.
	 * @type jQuery
	 */
	$propertySelector: null,

	/**
	 * The DOM node of the
	 */
	$snakValueDisplay: null,

	/**
	 * @see jQuery.Widget._create
	 */
	_create: function() {
		var self = this;
		this.element.empty();

		this.$snakValueDisplay = $( '<div/>' );

		this.$propertySelector = $( '<input/>' ).entityselector( {
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

		this.element.append( this.$snakValueDisplay ).prepend( this.$propertySelector );
	},

	/**
	 * Will return the view for displaying and editing (in case of property-value-snak) the
	 * current value.
	 *
	 * @param {String} snakType
	 * @param {dt.DataType|dv.DataValue} dataType
	 */
	_updateValueViewDom: function( snakType, dataType ) {
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
			$viewNode.valueview( { on: dataType } ).valueview( 'startEditing' );
		}

		this.$snakValueDisplay.empty().append( $viewNode );
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
	 * Returns the current Snak represented by the view.
	 *
	 * @return wb.Snak
	 */
	_getValue: function() {
		if( ! this._getValueView() ) {
			return null;
		}
		var propertyId = this.$propertySelector.entityselector( 'selectedEntity' ).id;
		var dataValue = this._getValueView().value();
		return new wb.PropertyValueSnak( propertyId, dataValue );
	},

	/**
	 * Will set the Snak to a given one.
	 *
	 * @param {wb.Snak} snak
	 */
	_setValue: function( snak ) {
		// TODO: have to get the type from the property here rather than building the view based
		//       on the DataValue!

		this._updateValueViewDom( snak.getType(), snak.getValue() );
		this._getValueView().value( snak.getValue() );

		this.$propertySelector.entityselector( wb.PropertyValueSnak.TYPE, snak.getPropertyId() );
	}
} );

}( mediaWiki, wikibase, dataValues, dataTypes, jQuery ) );
