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
			repoApiUrl = 'https://www.wikidata.org/w/api.php';
			var self = this;
			var suggestions = function ( term ) {

				var pId = 'P2302',//'P206',//'P17',//'P2302',//self.$input.closest( '.wikibase-snakview' ).data( 'snakview' ).propertyId(),
					language = self.$input.data( 'entityselector' ).options.language,
					//TODO: From constraint settings
					constraintsPId = 'P2302',
					constraintValueTypeId = 'Q21510865', //ValueTypeConstraint
					constraintValueTypeClassId = 'P2308', //Class
					constraintValueTypeRelationId = 'P2309',
					itemToProperty =  { 'Q21514624': 'P279', 'Q21503252': 'P31' },
					relationIds = [],
					classIds = [], //relation
					deferred = $.Deferred();


				//get constraints definition
				$.getJSON( repoApiUrl + '?action=wbgetclaims&format=json&entity=' + pId + '&property=' + constraintsPId ).then(
						function( d ){
							d.claims['P2302'].forEach( function( c ) {
								if( c.mainsnak.datavalue.value.id === constraintValueTypeId ) {
									classIds = classIds.concat (c.qualifiers[ constraintValueTypeClassId ].map( function(d) { return d.datavalue.value.id } ) );
									relationIds = relationIds.concat( c.qualifiers[ constraintValueTypeRelationId ].map( function(d) { return itemToProperty[d.datavalue.value.id]; } ) );
								}
							} );

							var search = term + ' ';
							relationIds.forEach( function( relationId ){
								search += classIds.map( function( id ){
									return 'haswbstatement:'+ relationId + '=' + id;
								} ).join( ' OR ' ) ;
							} );

							//do elastic search with haswbstatement filter
							$.getJSON( repoApiUrl + '?action=query&format=json&list=search&srsearch=' + encodeURIComponent( search ) ).then( function( r ){
								console.log( r );
								var ids = r.query.search.map( function( d ){
									return d.title;
								} );
								//get labels
								$.getJSON( repoApiUrl + '?action=wbgetentities&props=labels|descriptions&format=json&languages=' + language + '&ids=' + ids.join( '|' ) ).then( function( ld ){

									var data = [];
									ids.forEach( function( id ){
										data.push({
											id: id,
											title: "Item:" + id,
											  label: ld.entities[ id ][ 'labels' ][ language ].value + '(Suggestion)',
											  description: ld.entities[ id ][ 'descriptions' ][ language ].value
										});
									} )
									deferred.resolve( data );
								} );
							} );
						}
				)


				return deferred.promise();
			};


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
