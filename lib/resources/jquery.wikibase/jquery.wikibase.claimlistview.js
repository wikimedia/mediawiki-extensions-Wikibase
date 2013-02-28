/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( mw, wb, $ ) {
	'use strict';

	var PARENT = $.TemplatedWidget;

/**
 * View for displaying and editing several Wikibase Claims.
 * @since 0.3
 *
 * @option {String} entityId (required) The ID of the entity, new Claims should be added to.
 *
 * @option {wb.Claim[]|null} value The claims displayed by this view. If this is null, this view
 *         will only display an add button, for adding new claims.
 *
 * @option {Function} listMembersWidget The constructor of the widget used for the list members.
 *
 * @event itemadded: Triggered after a list item got added to the list.
 *        (1) {jQuery.Event}
 *        (2) {wb.Snak|null} The snak added to the list.
 *        (3) {jQuery} The DOM node with the widget, representing the value.
 *
 * @event canceled: Triggered after canceling the process of adding a new claim.
 *        (1) {jQuery.Event}
 */
$.widget( 'wikibase.claimlistview', PARENT, {
	widgetName: 'wikibase-claimlistview',
	widgetBaseClass: 'wb-claimlistview',

	/**
	 * Section node containing the list of claims of the entity
	 * @type jQuery
	 */
	$claims: null,

	/**
	 * (Additional) default options
	 * @see jQuery.Widget.options
	 */
	options: {
		template: 'wb-claims-section',
		templateParams: [
			'',
			''
		],
		templateShortCuts: {
			'$claims': '.wb-claims'
		},
		entityId: null,
		value: null,
		listMembersWidget: $.wikibase.claimview
	},

	/**
	 * @see jQuery.Widget._create
	 */
	_create: function() {
		var self = this,
			lmwAfterRemoveEvent = this._lmwEvent( 'afterremove' ),
			lmwErrorEvent = this._lmwEvent( 'toggleerror' ),
			lmwStartEditingEvent = this._lmwEvent( 'startediting' ),
			lmwAfterStopEditingEvent = this._lmwEvent( 'afterstopediting' );

		// apply template to this.element:
		PARENT.prototype._create.call( this );

		this._createClaims(); // build this.$claims

		// remove Claim (and eventually section) after remove operation has finished
		this.element.on( lmwAfterRemoveEvent, function( e ) {
			var $claim = $( e.target ),
				$claimsSection = $claim.parents( '.wb-claim-section' );

			self._lmwInstance( $claim ).destroy();

			if( $claimsSection.find( '.' + self._lmwClass() ).length > 0 ) {
				// TODO: Re-implement the following focus logic:
				// Focus the next claim container's "edit" button. The user might want to alter that
				// claim as well. Furthermore, the browser's viewport will not be scrolled when
				// having a long claim section and directly focusing the section's "add" button.
				// Only if there are no following claim containers, the section's "add" button will
				// be focused.
				$claim.remove(); // other claims displayed in this section, leave section alone
			} else {
				$claimsSection.remove(); // remove whole section because last claim removed
				// When removing a whole section, there is no meaningful focus point, so no focus
				// is set programmatically. We could easily set the focus on the general "add new
				// claim" button; However, if the "add" button is not in the browser's viewport,
				// (probably not intended) automatic scrolling will occur. The only more or less
				// valid focus point might simply be the next focusable element but at this stage,
				// all buttons are still disabled.
			}
		} )
		// make sure to add/remove 'wb-edit' class to sections when in edit mode:
		.on(
			[ lmwAfterStopEditingEvent, lmwStartEditingEvent, lmwAfterRemoveEvent ].join( ' ' ),
			function( e ) {
				if( e.type === lmwStartEditingEvent ) {
					$( e.target ).parents( '.wb-claim-section' ).addClass( 'wb-edit' );
				} else {
					// remove 'wb-edit' from all section nodes if the section itself has not child
					// nodes with 'wb-edit' still set. This is necessary because of how we remove new
					// Claims added to an existing section. Also required if we want multiple edit modes.
					self.element.find( '.wb-claim-section' )
						.not( ':has( >.wb-edit )' ).removeClass( 'wb-edit' );
					// NOTE: could be performance optimized with edit counter per section
				}
			}
		)
		.on( lmwErrorEvent, function( e, error ) {
			var $claimSection = $( e.target ).closest( '.wb-claim-section' );

			if( error && error instanceof wb.RepoApiError ) {
				$claimSection.addClass( 'wb-error' );
			}
			// explicitly check whether any error still set (considering multiple edit modes)
			else if( $claimSection.has( '>.wb-error' ).length === 0 ) {
				$claimSection.removeClass( 'wb-error' );
			}
		} );
	},

	/**
	 * @see $.widget.destroy
	 */
	destroy: function() {
		this.element.removeClass( this.widgetBaseClass );
		$.Widget.prototype.destroy.call( this );
	},

	/**
	 * Returns the given string but prefixed with the used list members widget's event prefix.
	 *
	 * @param {String} [name]
	 * @return String
	 */
	_lmwEvent: function( name ) {
		return this.options.listMembersWidget.prototype.widgetEventPrefix + ( name || '' );
	},

	/**
	 * Returns the given string but prefixed with the used list members base class.
	 *
	 * @param {String} [name]
	 * @return String
	 */
	_lmwClass: function( name ) {
		return this.options.listMembersWidget.prototype.widgetBaseClass + ( name || '' );
	},

	/**
	 * Instantiates a new widget which can be used as item for this list.
	 *
	 * @param {jQuery} element
	 * @param {Object} options
	 * @return jQuery.Widget
	 */
	_lmwInstantiate: function( element, options ) {
		return new this.options.listMembersWidget( options || {}, element[0] );
	},

	/**
	 * Returns the widget node's instance of the list member widget. If none is initiated on the
	 * given node, null will be returned.
	 *
	 * @param {jQuery} element
	 * @return jQuery.Widget|null
	 */
	_lmwInstance: function( element ) {
		return element.data( this.options.listMembersWidget.prototype.widgetName ) || null;
	},

	/**
	 * Will build this.$claims but doesn't append it to the widgets main node yet.
	 * @since 0.3
	 */
	_createClaims: function() {
		var i, self = this,
			claims = this.option( 'value' );

		// initialize view for each of the claims:
		for( i in claims ) {
			this._addClaim( claims[i] );
		}
	},

	/**
	 * Adds one claim to the list and renders it in the view.
	 * @since 0.3
	 *
	 * @param {wb.Claim} claim
	 */
	_addClaim: function( claim ) {
		var propertyId = claim.getMainSnak().getPropertyId(),
			$newClaim = this._lmwInstantiate( $( '<div/>' ), {
				value: claim,
				locked: {
					mainSnak: {
						property: true
					}
				}
			} ).element;

		this._insertClaimRow( propertyId, $newClaim );
	},

	/**
	 * Inserts some content (usually a Claim) as a row in one of the Claim sections.
	 * @since 0.4
	 *
	 * @param {String} claimSectionPropertyId
	 * @param {jQuery} $content
	 */
	_insertClaimRow: function( claimSectionPropertyId, $content ) {
		// get the section we want to insert stuff in:
		var $claimRows = this._serveClaimSection( claimSectionPropertyId );

		if ( this._lmwInstance( $content ) ) {
			$content.edittoolbar( {
				interactionWidgetName: this._lmwInstance( $content ).widgetName,
				toolbarParentSelector: '.wb-statement-claim .wb-claim-toolbar'
			} );
		}

		// insert before last child node in that list (there is at least one node always, holding the 'add)
		$claimRows.children( '.wb-claim-container' ).last().before( $content );

		// TODO: optimize, don't do this every time, perhaps even use a CSS solution
		//       take a look at http://reference.sitepoint.com/css/adjacentsiblingselector
		var $claims = $claimRows.not( '.wb-claim-add' ).add( $content );

		$claims.removeClass( 'wb-first wb-last' );
		$claims.first().addClass( 'wb-first' );
		$claims.last().addClass( 'wb-last' );
	},

	/**
	 * Returns the section a claim has to be displayed in, depending on its Main Snak's property ID.
	 * If a section with Claims having this property as a main snak exists already, then this
	 * section will be returned. Otherwise, a new section will be created.
	 * @since 0.3
	 *
	 * @param {String} mainSnakPropertyId
	 * @return jQuery
	 */
	_serveClaimSection: function( mainSnakPropertyId ) {
		// query for existing section in this view:
		var $section = this.$claims.children( '.wb-claim-section-' + mainSnakPropertyId ).first();

		if( $section.length === 0 ) {
			var fetchedProperty = wb.fetchedEntities[ mainSnakPropertyId ],
				$addClaim,
				self = this;

			if( !fetchedProperty ) {
				// Property info not in local store.
				// This should not ever happen if used properly, but make sure!
				throw new Error( 'Information for Property "' + mainSnakPropertyId + '" not found '
					+ 'in local store' );
			}

			// TODO: use another template for this, this isn't really a claim!
			$addClaim = mw.template( 'wb-claim',
				'wb-claim-add',
				'add', // class='wb-claim-$2'
				'', // .wb-claim-mainsnak
				'' // .wb-claim-toolbar
			);

			// create new section for first Claim in this section
			$section = mw.template( 'wb-claim-section',
				mainSnakPropertyId, // main Snak's property ID
				wb.utilities.ui.buildLinkToEntityPage( // property name
					fetchedProperty.getContent(),
					fetchedProperty.getTitle().getUrl() ),
				$addClaim // claim
			).appendTo( this.$claims );

			$section.addtoolbar( {
				toolbarParentSelector: '.wb-claim-add .wb-claim-toolbar',
				action: function() { self.enterNewClaimInSection( mainSnakPropertyId ) },
				eventPrefix: this.widgetEventPrefix
			} );
		}

		return $section;
	},

	/**
	 * Finds the section node of a given claim node.
	 *
	 * @param {jQuery} $claim
	 * @return {jQuery}
	 */
	findClaimSection: function( $claim ) {
		var $claimSection = null;
		this.$claims.children().each( function( i, claimSection ) {
			if ( claimSection === $claim.parent()[0] ) {
				$claimSection = $( claimSection );
				return false;
			}
		} );
		return $claimSection;
	},

	/**
	 * Will serve the view for defining a new Claim.
	 * @since 0.3
	 *
	 * @param {Number} [sectionPropertyId] The new Claim's Main Snak property. If omitted, the
	 *        property will have to be chosen by the user.
	 */
	enterNewClaim: function( sectionPropertyId ) {
		var self = this,
			$newClaim = $( '<div/>' ),
			options = {
				predefined: {},
				locked: {
					mainSnak: {
						property: ( sectionPropertyId ) ? true : false
					}
				}
			};

		if( sectionPropertyId ) {
			// only allow creation of Claim with Main Snak using that one property ID
			options.predefined.mainSnak = {
				property: sectionPropertyId
			};
		}

		if( sectionPropertyId ) {
			// insert claim to its section
			this._insertClaimRow( sectionPropertyId, $newClaim );
		} else {
			// insert Claim before toolbar with add button
			this.$claims.append( $newClaim );
		}

		// initialize view after node is in DOM, so the 'startediting' event can bubble
		this._lmwInstantiate( $newClaim, options ).element.addClass( 'wb-claim-new' );

		$newClaim.edittoolbar( {
			interactionWidgetName: this._lmwInstance( $newClaim ).widgetName,
			toolbarParentSelector: '.wb-statement-claim .wb-claim-toolbar',
			enableRemove: false
		} );

		this._lmwInstance( $newClaim ).startEditing();

		// first time the claim (or at least a part of it) drops out of edit mode:
		// TODO: not nice to use the event of the main snak, perhaps the claimview could offer one!
		$newClaim
		.on( this._lmwEvent( 'stopediting' ), function( event, dropValue ) {
			var newSnak = self._lmwInstance( $newClaim ).$mainSnak.data( 'snakview' ).snak();

			if ( self._lmwInstance( $newClaim ).isSaveDisabled() && !dropValue ) {
				event.preventDefault();
				return;
			}

			if ( self.__continueStopEditing ) {
				self.__continueStopEditing = false;
				$newClaim.off( self._lmwEvent( 'stopediting' ) );
				return;
			}

			if ( !dropValue ) {
				event.preventDefault();
			} else {
				self.element.removeClass( 'wb-error' );
			}

			// TODO: right now, if the claim is not valid (e.g. because data type not yet
			//       supported), the edit mode will close when saving without showing any hint!

			if( !dropValue && newSnak ) {
				// TODO: either change toggleActionMessage to do the disabling, then execute the
				//       callback and finally execute the toggle animation, OR separate the
				//       disabling and call it manually, then do the API call, then toggle.
				var api = new wb.RepoApi();

				api.createClaim(
					self.option( 'entityId' ),
					wb.getRevisionStore().getBaseRevision(),
					newSnak
				)
				.done( function( newClaimWithGUID, pageInfo ) {
					wb.getRevisionStore().setClaimRevision(
						pageInfo.lastrevid,
						newClaimWithGUID.getGuid()
					);

					// Continue stopEditing event by triggering it again skipping
					// claimlistview's API call.
					self.__continueStopEditing = true;

					self._lmwInstance( $( event.target ) ).stopEditing( dropValue );

					self._trigger( 'itemadded', null, [ newSnak, $newClaim ] );

					// destroy new claim input form and add claim to this list
					self._lmwInstance( $newClaim ).destroy();
					$newClaim.remove();
					self._addClaim( newClaimWithGUID ); // display new claim with final GUID
					// TODO: add newly created claim to model of represented entity!
				} )
				.fail( function( errorCode, details ) {
					var claimview = self._lmwInstance( $newClaim ),
						error = wb.RepoApiError.newFromApiResponse(
							errorCode, details, 'save'
						);

					claimview.enable();
					claimview.element.addClass( 'wb-error' );

					claimview._trigger( 'toggleerror', null, [error] );
				} );
			}
		} )
		.on( this._lmwEvent( 'afterstopediting' ), function( event, dropValue ) {
			if( dropValue || !self._lmwInstance( $newClaim ).$mainSnak.data( 'snakview' ).snak() ) {
				// if new claim is canceled before saved, or if it is invalid, we simply remove
				// and forget about it
				self._trigger( 'canceled', null, [ $newClaim ] );

				self._lmwInstance( $newClaim ).destroy();
				$newClaim.remove();
				self.element.find( '.wb-claim-section' )
					.not( ':has( >.wb-edit )' ).removeClass( 'wb-edit' );
			}
		} );
	},

	/**
	 * Initializes adding a new item to the list; At the moment, just a generically named shortcut
	 * to this.enterNewClaim() which may be used by external components (e.g. a toolbar).
	 */
	enterNewItem: function() {
		this.enterNewClaim();
	},

	/**
	 * Will serve the view for defining a new Claim whose Main Snak will have a certain, pre-defined
	 * property. This means the input form for the new Claim will be inserted right into the section
	 * where the Claim will be displayed later. Also, the property won't be changeable for the user.
	 * @since 0.4
	 *
	 * @param {Number} sectionPropertyId The new Claim's Main Snak property.
	 */
	enterNewClaimInSection: function( sectionPropertyId ) {
		this.enterNewClaim( sectionPropertyId );
	}

} );

}( mediaWiki, wikibase, jQuery ) );
