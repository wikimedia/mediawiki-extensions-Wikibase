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
			url: 'http://localhost/wikidata/api.php', // TODO: do this right!
			language: mw.config.get( 'wgUserLanguage' ),
			type: 'property'
		} )
		.on( 'entityselectorselect', function( e, ui ) {
			var dataTypeId = ui.item.datatype || 'commonsMedia', // TODO: include datatype into search API result
				dataType = dt.getDataType( dataTypeId );

			if( dataType ) {
				// display a view for creating a value of that data type:
				self._updateValueViewDom( 'value', dataType );
			} else {
				// TODO: display a message that the property has a data type unknown to the UI
			}
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
		var $viewNode,
			valueView = this._getValueView();

		if( snakType === 'value' ) {
			// if new data type has same data value type, we can keep the view
			// TODO: this is not entirely true since there could be special widgets per DataType,
			//       see next TODO as well.
			if( valueView && valueView.dataValueType === dataType.getDataValueType() ) {
				return;
			}

			// TODO: use the 'editview' and just change its data type rather than initializing this
			//       over and over again.
			$viewNode = $( '<div/>' ).valueview( { on: dataType } ).valueview( 'startEditing' );
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

		this._updateValueViewDom( snak.TYPE, snak.getValue() );
		this._getValueView().value( snak.getValue() );

		this.$propertySelector.entityselector( 'value', snak.getPropertyId() );
	}
} );

}( mediaWiki, wikibase, dataValues, dataTypes, jQuery ) );
