/**
 * Widget for editing values of the Wikibase specific item DataType.
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( mw, wb, dv, vp, dt, $ ) {
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
			var self = this,
				language = mw.config.get( 'wgUserLanguage' );

			return $( '<input/>', {
				'class': this.widgetBaseClass + '-input',
				'type': 'text',
				'placeholder': this.option( 'inputPlaceholder' )
			} )
			.eachchange()
			.entityselector( {
				url: mw.util.wikiScript( 'api' ),
				language: language,
				type: 'item'
			} ).on( 'entityselectorselect', function( e, ui ) {
				self._value = new dv.StringValue( ui.item.id );

				// update local store with newest information about selected item
				// TODO: create more sophisticated local store interface rather than accessing
				//       wb.entities directly
				wb.entities[ ui.item.id ] = {
					label: ui.item.label
				};
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
		_getLinkContentFromValue: function( value ) {
			if( !value ) {
				return '';
			}
			var itemId = value.getValue(),
				item = wb.entities[ itemId ];

			if( !item ) {
				return itemId;
			}

			if( !item.label ) {
				return $( document.createTextNode( itemId + ' ' ) ).add(
					$( '<span/>' ).text( '(' + mw.msg( 'wikibase-label-empty' ) + ')' )
				);
			}

			return item.label;
		}
	} );

}( mediaWiki, wikibase, dataValues, valueParsers, dataTypes, jQuery ) );
