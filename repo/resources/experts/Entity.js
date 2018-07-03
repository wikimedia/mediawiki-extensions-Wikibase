( function ( mw, wb, $, vv ) {
	'use strict';

	var MODULE = wb.experts,
		PARENT = vv.experts.StringValue;

	/**
	 * `valueview` `Expert` for specifying a reference to a Wikibase `Entity`.
	 * @class wikibase.experts.Entity
	 * @extends jQuery.valueview.experts.StringValue
	 * @abstract
	 * @uses jQuery.wikibase.entityselector
	 * @license GPL-2.0-or-later
	 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
	 */
	var SELF = MODULE.Entity = vv.expert( 'wikibaseentity', PARENT, {
		/**
		 * @inheritdoc
		 *
		 * @throws {Error} when called because this `Expert` is meant to be abstract.
		 */
		_init: function () {
			throw new Error( 'Abstract Entity id expert cannot be instantiated directly' );
		},

		/**
		 * @protected
		 */
		_initEntityExpert: function () {
			PARENT.prototype._init.call( this );

			// FIXME: Use SuggestedStringValue

			var notifier = this._viewNotifier,
				self = this,
				repoConfig = mw.config.get( 'wbRepo' ),
				repoApiUrl = repoConfig.url + repoConfig.scriptPath + '/api.php';

			this._initEntityselector( repoApiUrl );

			var value = this.viewState().value(),
				entityId = value && value.getSerialization();

			this.$input.data( 'entityselector' ).selectedEntity( entityId );

			this.$input
			.on( 'eachchange.' + this.uiBaseClass, function ( e ) {
				$( this ).data( 'entityselector' ).repositionMenu();
			} )
			.on( 'entityselectorselected.' + this.uiBaseClass, function ( e ) {
				self._resizeInput();
				notifier.notify( 'change' );
			} );
		},

		/**
		 * Initializes a `jQuery.wikibase.entityselector` instance on the `Expert`'s input element.
		 *
		 * @abstract
		 * @protected
		 *
		 * @param {string} repoApiUrl
		 */
		_initEntityselector: function ( repoApiUrl ) {
			var suggestions = function ( term ) {

				var data = [],
				deferred = $.Deferred();
				data[0] = {repository: "",
						suggestion: true,
						id: "Q2",
						concepturi: "https://localhost/mediawiki/index.php/Item:Q2",
						title: "Item:Q2",
						  label: "Suggestion",
						  description: "This is a suggested item"
					};
				data[1] = {repository: "",
						id: "Q2",
						concepturi: "https://localhost/mediawiki/index.php/Item:Q2",
						title: "Item:Q2",
						  label: "Suggestion 2",
						  description: "This is a suggested2 item"
					};
				data[2] = {repository: "",
						suggestionEnd: true,
						id: "Q2",
						concepturi: "https://localhost/mediawiki/index.php/Item:Q2",
						title: "Item:Q2",
						  label: "Suggestion 3",
						  description: "This is a suggested3 item"
					};

				deferred.resolve( data );
				return deferred.promise();
			};

			//repoApiUrl = 'https://www.wikidata.org/w/api.php';
			this.$input.entityselector( {
				url: repoApiUrl,
				type: this.constructor.TYPE,
				selectOnAutocomplete: true,
				suggestions: suggestions
			} );
		},

		/**
		 * @inheritdoc
		 */
		destroy: function () {
			// Prevent error when issuing destroy twice:
			if ( this.$input ) {
				// The entityselector may have already been destroyed by a parent component:
				var entityselector = this.$input.data( 'entityselector' );
				if ( entityselector ) {
					entityselector.destroy();
				}
			}

			PARENT.prototype.destroy.call( this );
		},

		/**
		 * @inheritdoc
		 *
		 * @return {string}
		 */
		rawValue: function () {
			var entitySelector = this.$input.data( 'entityselector' ),
				selectedEntity = entitySelector.selectedEntity();

			return selectedEntity ? selectedEntity.id : '';
		}
	} );

	/**
	 * `Entity` type this `Expert` supports.
	 * @property {string} [TYPE=null]
	 * @static
	 */
	SELF.TYPE = null;

}( mediaWiki, wikibase, jQuery, jQuery.valueview ) );
