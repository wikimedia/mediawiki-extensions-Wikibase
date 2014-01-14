/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
wikibase.experts = wikibase.experts || {};

( function( mw, wb, $, vv ) {
	'use strict';

	// temporarily define a hard coded prefix map until we get this from the server
	var WB_ENTITIES_PREFIXMAP = {
		'Q': 'item',
		'P': 'property'
	};

	var PARENT = vv.experts.StringValue;

	/**
	 * Valueview expert for wikibase.EntityId. This is a simple expert, only handling the input,
	 * based on the StringValue input but with the jQuery.wikibase.entityselector for convenience.
	 *
	 * @since 0.4
	 *
	 * @constructor
	 * @extends jQuery.valueview.experts.StringValue
	 */
	wb.experts.EntityIdInput = vv.expert( 'wikibaseentityidinput', PARENT, {
		/**
		 * Field used to remember a value as current value while the value can't be displayed by
		 * the entity selector as current value. Used if an Entity not in the system is set as
		 * current value. false implies that the current value is the one of the entity selector.
		 * @type {false|string}
		 */
		_actualValue: false,

		/**
		 * @see Query.valueview.experts.StringValue._init
		 */
		_init: function() {
			PARENT.prototype._init.call( this );

			var notifier = this._viewNotifier,
				$input = this.$input,
				self = this;

			$input.entityselector( {
				url: mw.util.wikiScript( 'api' ),
				selectOnAutocomplete: true
			} )
			.on( 'entityselectorselect', function( e, ui ) {
				self._resizeInput();
			} )
			.on(
				// "aftersetentity": When setting the entity programmatically (editing an existing
				// Snak).
				// "response": Each time an API query returns (the input value gets auto-completed).
				// "close": After having selected an entity by clicking on a suggestion list item.
				'entityselectoraftersetentity entityselectorresponse entityselectorclose'
					+ ' eachchange',
				function( e ) {
					self._resizeInput();
					$( this ).data( 'entityselector' ).repositionMenu();
				}
			)
			.on(
				'eachchange entityselectorselect entityselectoraftersetentity',
				function( e ) {
					// Entity selector's value is actual value after change.
					self._actualValue = false;

					if( e.type !== 'eachchange' ) {
						// Already registered to 'eachchange' in StringValue expert.
						notifier.notify( 'change' );
					}
				}
			);
		},

		/**
		 * @see Query.valueview.Expert._getRawValue
		 *
		 * @return string
		 *
		 * TODO: get/setRawValue should be consistent. Right now one takes a DataValue object while
		 *       the other returns a string!
		 */
		_getRawValue: function() {
			if( this._actualValue !== false ) {
				// Empty input field is displayed because some entity, not in the system, is set as
				// current value.
				return this._actualValue;
			}
			var entitySelector = this.$input.data( 'entityselector' ),
				selectedEntity = entitySelector.selectedEntity();

			return selectedEntity ? selectedEntity.id : null;
		},

		/**
		 * @see Query.valueview.Expert._setRawValue
		 *
		 * @return wb.EntityId
		 */
		_setRawValue: function( rawValue ) {
			var entityId = rawValue instanceof wb.EntityId
				? rawValue.getPrefixedId( WB_ENTITIES_PREFIXMAP )
				// TODO: be consistent with get/set, don't check for string and EntityId!
				: ( ( typeof rawValue === 'string' && rawValue ) ? rawValue : null );

			this.$input.data( 'entityselector' ).selectedEntity( entityId );
			this._resizeInput();
		},

		/**
		 * @see Query.valueview.experts.StringValue.draw
		 */
		draw: function() {
			this._newValue = false; // we use the entityselector to manage the value immediately
			PARENT.prototype.draw.call( this );

			// Make sure entityselector is closed in non-edit mode:
			if( !this._viewState.isInEditMode() ) {
				this.$input.data( 'entityselector' ).close();
			}
		}
	} );

}( mediaWiki, wikibase, jQuery, jQuery.valueview ) );
