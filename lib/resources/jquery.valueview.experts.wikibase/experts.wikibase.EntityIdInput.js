/**
 * @file
 * @ingroup WikibaseLib
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( mw, wb, dv, vp, $, vv ) {
	'use strict';

	// temporarily define a hard coded prefix map until we get this from the server
	var WB_ENTITIES_PREFIXMAP = {
		'q': 'item',
		'p': 'property'
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
	vv.experts.wikibase.EntityIdInput = vv.expert( 'wikibaseentityidinput', PARENT, {
		/**
		 * @see Query.valueview.experts.StringValue._init
		 */
		_init: function() {
			PARENT.prototype._init.call( this );

			var notifier = this._viewNotifier,
				$input = this.$input,
				self = this;

			$input
			.entityselector( {
				url: mw.util.wikiScript( 'api' ),
				selectOnAutocomplete: true
			} )
			.eachchange( function( event, oldValue ) {
				$( this ).data( 'entityselector' ).repositionMenu();
			} )
			.on( 'entityselectorselect', function( e, ui ) {
				var itemData = {
					id: ui.item.id,
					label: {}
				};
				itemData.label[ mw.config.get( 'wgUserLanguage' ) ] = ui.item.label;

				// update local store with newest information about selected item
				// TODO: create more sophisticated local store interface rather than accessing
				//       wb.fetchedEntities directly
				wb.fetchedEntities[ ui.item.id ] = new wb.store.FetchedContent( {
					// TODO: *terrible* solution to use regex, entityselector should provide title
					title: new mw.Title( ui.item.url.match( /[^\/]+$/ )[0] ),
					content: new wb.Item( itemData )
				} );

				self._resizeInput();
			} )
			.on(
				// "aftersetentity": When setting the entity programmatically (editing an existing
				// Snak).
				// "response": Each time an API query returns (the input value gets auto-completed).
				// "close": After having selected an entity by clicking on a suggestion list item.
				'entityselectoraftersetentity entityselectorresponse entityselectorclose',
				function( e ) {
					var expand = $( this ).data( 'AutoExpandInput' );
					expand && expand.expand();
					$( this ).data( 'entityselector' ).repositionMenu();
				}
			);

			$input.on( 'entityselectorselect', function( event, response ) {
				notifier.notify( 'change' ); // here in addition to 'eachchange' from StringValue expert
			} );
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

			var fetchedEntity = entityId ? wb.fetchedEntities[ entityId ] : null,
				simpleEntity = null;

			if( fetchedEntity ) {
				// entity selector requires very basic data only, but ID has to be set which is
				// not the case in the wb.fetchedEntities entity store.
				simpleEntity = {
					label: fetchedEntity.getContent().getLabel(),
					id: entityId,
					url: fetchedEntity.getTitle().getUrl()
				};
			}

			this.$input.data( 'entityselector' ).selectedEntity( simpleEntity );
			this._resizeInput();
			// TODO: entityselector should just be able to handle wb.Entity without making it a
			//  dependency there.
		},

		/**
		 * @see Query.valueview.Expert.parser
		 */
		parser: function() {
			return new wb.EntityIdParser( {
				'prefixmap': WB_ENTITIES_PREFIXMAP
			} );
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

}( mediaWiki, wikibase, dataValues, valueParsers, jQuery, jQuery.valueview ) );
