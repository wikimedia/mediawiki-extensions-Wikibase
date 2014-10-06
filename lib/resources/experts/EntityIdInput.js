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
	 * Valueview expert for wb.datamodel.EntityId. This is a simple expert, only handling the input,
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
				self = this,
				repoConfig = mw.config.get( 'wbRepo' ),
				repoApiUrl = repoConfig.url + repoConfig.scriptPath + '/api.php';

			$input.entityselector( {
				url: repoApiUrl,
				selectOnAutocomplete: true
			} );

			var value = this.viewState().value();
			var entityId = value && value.getPrefixedId( WB_ENTITIES_PREFIXMAP );

			this.$input.data( 'entityselector' ).selectedEntity( entityId );
			$input
			.on( 'eachchange.' + this.uiBaseClass, function( e ) {
				$( this ).data( 'entityselector' ).repositionMenu();
			} )
			.on( 'entityselectorselected.' + this.uiBaseClass, function( e ) {
				self._resizeInput();
				notifier.notify( 'change' );
			} );
		},

		/**
		 * @see jQuery.valueview.Expert.destroy
		 */
		destroy: function() {
			// Prevent error when issuing destroy twice:
			if( this.$input ) {
				// The entityselector may have already been destroyed by a parent component:
				var entityselector = this.$input.data( 'entityselector' );
				if( entityselector ) {
					entityselector.destroy();
				}
			}

			PARENT.prototype.destroy.call( this );
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
		}

	} );

}( mediaWiki, wikibase, jQuery, jQuery.valueview ) );
