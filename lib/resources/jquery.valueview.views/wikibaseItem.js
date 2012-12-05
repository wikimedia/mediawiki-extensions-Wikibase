/**
 * Widget for editing values of the Wikibase specific item DataType.
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( mw, dv, vp, dt, $ ) {
	'use strict';

	var PARENT = $.valueview.LinkedSingleInputWidget;

	$.valueview.widget( 'wikibaseitem', PARENT, {
		/**
		 * @see jQuery.valueview.Widget.dataTypeId
		 */
		dataTypeId: 'wikibase-item',

		/**
		 * @see jQuery.Widget._create
		 */
		_create: function() {
			// provide parser for strings
			this.valueParser = new vp.StringParser();

			PARENT.prototype._create.call( this );
		},

		/**
		 * Builds the input element for editing, ready to be inserted into the DOM.
		 *
		 * @return {jQuery}
		 * @private
		 */
		_buildInputDom: function() {
			var self = this;

			return $( '<input/>', {
				'class': this.widgetBaseClass + '-input',
				'type': 'text',
				'placeholder': this.option( 'inputPlaceholder' )
			} )
			.eachchange()
			.entityselector( {
				url: mw.util.wikiScript( 'api' ),
				language: mw.config.get( 'wgUserLanguage' ),
				type: 'item'
			} ).on( 'entityselectorselect', function( e, ui ) {
				self._value = new dv.StringValue( ui.item.id );
			} );
		},

		/**
		 * @see $.valueview.LinkedSingleInputWidget._getLinkHrefFromValue
		 */
		_getLinkHrefFromValue: function( value ) {
			return ''; // TODO
		},

		/**
		 * @see $.valueview.LinkedSingleInputWidget._getLinkTextFromValue
		 */
		_getLinkTextFromValue: function( value ) {
			return value === null ? '' : value.getValue();
		}
	} );

}( mediaWiki, dataValues, valueParsers, dataTypes, jQuery ) );
