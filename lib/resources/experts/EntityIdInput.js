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
		 * @see Query.valueview.experts.StringValue._init
		 */
		_init: function() {
			PARENT.prototype._init.call( this );

			// FIXME: Use SuggestedStringValue

			var notifier = this._viewNotifier,
				$input = this.$input,
				self = this;

			$input.entityselector( {
				url: mw.util.wikiScript( 'api' ),
				selectOnAutocomplete: true
			} );

			var value = this.viewState().value();
			var entityId = value && value.getPrefixedId( WB_ENTITIES_PREFIXMAP );
			var fetchedEntity, simpleEntity = null;

			if( entityId ) {
				simpleEntity = { id: entityId };
				fetchedEntity = wb.fetchedEntities[ entityId ];
				if( fetchedEntity ) {
					// entity selector requires very basic data only, but ID has to be set which is
					// not the case in the wb.fetchedEntities entity store.
					simpleEntity.label = fetchedEntity.getContent().getLabel();
					simpleEntity.url = fetchedEntity.getTitle().getUrl();
				}
			}

			this.$input.data( 'entityselector' ).selectedEntity( simpleEntity );
			$input
			.on( 'entityselectorselect', function( e, ui ) {
				self._resizeInput();
			} )
			.on( 'eachchange',
				function( e ) {
					$( this ).data( 'entityselector' ).repositionMenu();
				}
			)
			.on(
				'entityselectorselect entityselectoraftersetentity',
				function( e ) {
					notifier.notify( 'change' );
				}
			);
		},

		/**
		 * @see jQuery.valueview.Expert.rawValue
		 *
		 * @return string
		 */
		rawValue: function() {
			var entitySelector = this.$input.data( 'entityselector' ),
				selectedEntity = entitySelector.selectedEntity();

			return selectedEntity ? selectedEntity.id : '';
		},

		/**
		 * @see jQuery.valueview.Expert.valueCharacteristics
		 *
		 * TODO: remove this once the parsing is done via API
		 */
		valueCharacteristics: function() {
			return { prefixmap: WB_ENTITIES_PREFIXMAP };
		}
	} );

}( mediaWiki, wikibase, jQuery, jQuery.valueview ) );
