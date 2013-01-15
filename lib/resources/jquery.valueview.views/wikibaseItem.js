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
			this.valueParser = new vp.EntityIdParser();

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

			return $( '<textarea/>', {
				'class': this.widgetBaseClass + '-input',
				'type': 'text',
				'placeholder': this.option( 'inputPlaceholder' )
			} )
			.inputAutoExpand( { expandWidth: false, expandHeight:true, suppressNewLine: true } )
			.entityselector( {
				url: mw.util.wikiScript( 'api' ),
				language: language,
				type: 'item',
				entityStore: wb.entities
			} )
			.eachchange( function( event, oldValue ) {
				$( this ).data( 'entityselector' ).repositionMenu();
			} )
			.on( 'entityselectorselect', function( e, ui ) {
				self._value = new dv.StringValue( ui.item.id );

				// update local store with newest information about selected item
				// TODO: create more sophisticated local store interface rather than accessing
				//       wb.entities directly
				wb.entities[ ui.item.id ] = {
					label: ui.item.label,
					url: ui.item.url
				};
			} )
			.on(
				// "aftersetentity": When setting the entity programmatically (editing an existing
				// Snak).
				// "response": Each time an API query returns (the input value gets auto-completed).
				// "close": After having selected an entity by clicking on a suggestion list item.
				'entityselectoraftersetentity entityselectorresponse entityselectorclose',
				function( e ) {
					$( this ).data( 'AutoExpandInput' ).expand();
					$( this ).data( 'entityselector' ).repositionMenu();
				}
			);
		},

		/**
		 * @see jQuery.valueview.Widget._displayValue
		 *
		 * @TODO: At the moment, it is possible, to save any random string for a wikibase item.
		 *        Therefore, value.getValue() does not necessarily return the item id but can also
		 *        return the plain label string. This causes an error trying to fill the entity
		 *        selector with selectedEntity() which expects an item id.
		 */
		_displayValue: function( value ) {
			if( this.$input ) {
				var entityId = value === null ? null : value.getValue(),
					entity = entityId ? wb.entities[ entityId ] : null,
					simpleEntity = null;

				if( entity ) {
					// entity selector requires very basic data only, but ID has to be set which is
					// not the case in the wb.entities entity store.
					simpleEntity = {
						label: entity.label,
						id: entityId
					};
				}

				// in edit mode:
				this.$input.data( 'entityselector' ).selectedEntity( simpleEntity );
			} else {
				// in static mode:
				PARENT.prototype._displayValue.call( this, value );
			}
		},

		/**
		 * @see $.valueview.LinkedSingleInputWidget._getLinkHrefFromValue
		 */
		_getLinkHrefFromValue: function( value ) {
			if( !value ) {
				return '';
			}
			var itemId = value.getValue(),
				item = wb.entities[ itemId ],
				url = item ? item.url : false;

			return url || '';
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
