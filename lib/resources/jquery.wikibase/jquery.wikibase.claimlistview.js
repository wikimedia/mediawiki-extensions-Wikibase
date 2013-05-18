/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( mw, wb, $ ) {
	'use strict';

	var PARENT = $.TemplatedWidget,
		widgetPrototype;

/**
 * View for displaying and editing several Wikibase Claims.
 * @since 0.3
 * @extends jQuery.TemplatedWidget
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
 *
 * @event afterremove: Triggered after a claim(view) has been removed from the claimlist(view).
 *        (1) {jQuery.Event}
 */
$.widget( 'wikibase.claimlistview', PARENT, {
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
		template: 'wb-claimlist',
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
		var self = this;

		// apply template to this.element:
		PARENT.prototype._create.call( this );

		this._createClaims(); // build this.$claims

		// remove Claim (and eventually section) after remove operation has finished
		this.element
		// make sure to add/remove 'wb-edit' class to sections when in edit mode:
		.on(
			this._lmwEvent( 'startediting' ) + ' ' + this._lmwEvent( 'afterstopediting' ),
			function( event ) {
				self._toggleEditState(
					event.type === self._lmwEvent( 'startediting' ),
					$( event.target )
				);
			}
		)
		.on( this._lmwEvent( 'toggleerror' ), function( e, error ) {
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
	 * Toggles edit state visually by assigning/removing wb-edit css class.
	 *
	 * @param {boolean} [toggleOn=false] Indicates whether to switch edit state on or off.
	 * @param {jQuery} [$origin] The list member widget's element responsible for toggling the edit
	 *        state. Needs to be set when assigning edit state.
	 */
	_toggleEditState: function( toggleOn, $origin ) {
		if( toggleOn ) {
			$origin.parents( '.wb-claim-section' ).addClass( 'wb-edit' );
		} else {
			// remove 'wb-edit' from all section nodes if the section itself has not child
			// nodes with 'wb-edit' still set. This is necessary because of how we remove new
			// Claims added to an existing section. Also required if we want multiple edit modes.
			this.element.find( '.wb-claim-section' )
				.not( ':has( >.wb-edit )' ).removeClass( 'wb-edit' );
			// NOTE: could be performance optimized with edit counter per section
		}
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
		var i,
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
			$newClaim = $( '<div/>' );

		this._insertClaimRow( propertyId, $newClaim );

		// Instantiate list member widget after adding its element node to the DOM to be able to
		// listen to widget "create" events.
		this._lmwInstantiate( $newClaim, {
			value: claim,
			locked: {
				mainSnak: {
					property: true
				}
			}
		} );
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
		var $claimSection = this._serveClaimSection( claimSectionPropertyId ),

			// Insert new row after the list's last claim. (There may be external components on the
			// same DOM level which is why the new row should not be just appended to the the section.)
			$claimviews = $claimSection.children( '.wb-claimview' );

		if ( $claimviews.length ) {
			$claimviews.last().after( $content );
		} else {
			$claimSection.prepend( $content );
		}
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
		var $section = this.$claims.children( '.wb-claim-section-' + mainSnakPropertyId ).first(),
			fetchedProperty;

		if( $section.length === 0 ) {
			// Create new section (as $section) for first Claim in this section:
			fetchedProperty = wb.fetchedEntities[ mainSnakPropertyId ];

			if( fetchedProperty ) {
				$section = mw.template( 'wb-claim-section',
					mainSnakPropertyId, // main Snak's property ID
					wb.utilities.ui.buildLinkToEntityPage( // property name
						fetchedProperty.getContent(),
						fetchedProperty.getTitle().getUrl()
					),
					'' // claims
				);
			} else {
				// Property info not in local store.
				// Can happen if the property got deleted, but Claims still exist!
				$section = mw.template( 'wb-claim-section',
					mainSnakPropertyId, // main Snak's property ID
					wb.utilities.ui.buildMissingEntityInfo( mainSnakPropertyId, wb.Property ),
					'' // claims
				);

			}
			$section.data( 'wb-propertyId', mainSnakPropertyId ).appendTo( this.$claims );

			$section.trigger( 'claimsectioncreate' );
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
		var newClaimWithGuid,
			self = this,
			$newClaim = $( '<div>' ),
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
			// Entering a completely new claim (including defining a property).
			this.$claims.append( $newClaim );
		}

		// initialize view after node is in DOM, so the 'startediting' event can bubble
		this._lmwInstantiate( $newClaim, options ).element.addClass( 'wb-claim-new' );

		this._lmwInstance( $newClaim ).startEditing();

		// first time the claim (or at least a part of it) drops out of edit mode:
		// TODO: not nice to use the event of the main snak, perhaps the claimview could offer one!
		$newClaim
		.on( this._lmwEvent( 'afterstopediting' ), function( event, dropValue ) {
			var snak = self._lmwInstance( $newClaim ).$mainSnak.data( 'snakview' ).snak();

			if( dropValue || !snak ) {
				// if new claim is canceled before saved, or if it is invalid, we simply remove
				// and forget about it
				self._trigger( 'canceled', null, [ null, $newClaim ] );

				self._lmwInstance( $newClaim ).destroy();
				$newClaim.remove();
				self.element.find( '.wb-claim-section' )
					.not( ':has( >.wb-edit )' ).removeClass( 'wb-edit' );
			} else {
				newClaimWithGuid = self._lmwInstance( $newClaim ).value();

				self._trigger( 'itemadded', null, [ snak, $newClaim ] );

				// Destroy new claim input form and add claim to this list:
				self._lmwInstance( $newClaim ).destroy();
				$newClaim.remove();

				// Display new claim with final GUID:
				self._addClaim( newClaimWithGuid );
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
	},

	/**
	 * Removes a claimview widget from the claim list.
	 * @since 0.4
	 *
	 * @param {$.wikibase.claimview} claimview
	 */
	remove: function( claimview ) {
		var self = this;

		this._removeClaimApiCall( claimview.value() )
		.done( function( savedClaim, pageInfo ) {
			/*jshint unused:false */

			// NOTE: we don't update rev store here! If we want uniqueness for Claims, this
			//  might be an issue at a later point and we would need a solution then

			var $claim = claimview.element,
				$claimsSection = $claim.closest( '.wb-claim-section' );

			claimview.destroy();

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

			self._toggleEditState();

			self._trigger( 'afterremove' );
		} ).fail( function( errorCode, details ) {
			var error = wb.RepoApiError.newFromApiResponse( errorCode, details, 'remove' );

			claimview.enable();
			claimview.setError( error );
		} );
	},

	/**
	 * Triggers the API call to remove a claim.
	 * @since 0.4
	 *
	 * @return {jQuery.Promise}
	 */
	_removeClaimApiCall: function( claim ) {
		var guid = claim.getGuid(),
			abstractedApi = new wb.AbstractedRepoApi(),
			revStore = wb.getRevisionStore();

		return abstractedApi.removeClaim(
			guid,
			revStore.getClaimRevision( guid )
		);
	}

} );

// Register toolbars:
widgetPrototype = $.wikibase.claimlistview.prototype;

$.wikibase.toolbarcontroller.definition( 'addtoolbar', {
	widgetName: 'wikibase.claimlistview',
	options: {
		interactionWidgetName: widgetPrototype.widgetName
	}
} );

$.wikibase.toolbarcontroller.definition( 'addtoolbar', {
	id: 'claimsection',
	selector: '.wb-claim-section',
	eventPrefix: 'claimsection',
	baseClass: widgetPrototype.widgetBaseClass,
	options: {
		customAction: function( event, $parent ) {
			$parent.closest( '.wb-claimlistview' ).data( 'claimlistview' ).enterNewClaimInSection(
				$parent.data( 'wb-propertyId' )
			);
		},
		eventPrefix: widgetPrototype.widgetEventPrefix
	}
} );

$.wikibase.toolbarcontroller.definition( 'edittoolbar', {
	widgetName: 'wikibase.statementview',
	events: {
		statementviewchange: function( event ) {
			var $target = $( event.target ),
				statementview = $target.data( 'statementview' ),
				enable = statementview.isValid() && !statementview.isInitialValue(),
				edittoolbar = $target.data( 'edittoolbar' );

			if ( statementview._qualifiers && (
				!statementview._qualifiers.isValid() ||
				statementview._qualifiers.isInitialValue() && statementview.isInitialValue()
			) ) {
				enable = false;
			}

			edittoolbar.toolbar.editGroup.btnSave[ enable ? 'enable' : 'disable' ]();
		}
	},
	options: {
		interactionWidgetName: $.wikibase.statementview.prototype.widgetName,
		parentWidgetFullName: 'wikibase.claimlistview',
		toolbarParentSelector: '.wb-statement-claim'
	}
} );

}( mediaWiki, wikibase, jQuery ) );
