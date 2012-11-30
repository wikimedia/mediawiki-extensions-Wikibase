/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( mw, wb, $ ) {
	'use strict';

/**
 * View for displaying and editing several Wikibase Claims.
 * @since 0.3
 *
 * @option {wb.Claim[]|null} value The claims displayed by this view. If this is null, this view
 *         will only display an add button, for adding new claims.
 * @option {String} entityId The ID of the entity, new Claims should be added to.
 */
$.widget( 'wikibase.claimlistview', {
	widgetName: 'wikibase-claimlistview',
	widgetBaseClass: 'wb-claimlistview',

	/**
	 * Node of the toolbar
	 * @type jQuery
	 */
	$toolbar: null,

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
		value: null
	},

	/**
	 * @see jQuery.Widget._create
	 */
	_create: function() {
		this.element.addClass( this.widgetBaseClass );
		this.element.applyTemplate( 'wb-claims-section',
			'', // .wb-claims
			''  // edit section DOM
		);

		this.$claims = this.element.find( '.wb-claims' );
		this.$toolbar = this.element.find( '.wb-claims-toolbar' );

		this._createClaims(); // build this.$claims
		this._createToolbar();
	},

	/**
	 * @see $.widget.destroy
	 */
	destroy: function() {
		this.element.removeClass( this.widgetBaseClass );
		$.Widget.prototype.destroy.call( this );
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
	 * Creates the toolbar holding the 'add' button for adding new claims.
	 * @since 0.3
	 */
	_createToolbar: function() {
		// display 'add' button at the end of the claims:
		var self = this,
			toolbar = new wb.ui.Toolbar();

		toolbar.innerGroup = new wb.ui.Toolbar.Group();
		toolbar.btnAdd = new wb.ui.Toolbar.Button( mw.msg( 'wikibase-add' ) );
		$( toolbar.btnAdd ).on( 'action', function( event ) {
			self.enterNewClaim();
		} );
		toolbar.innerGroup.addElement( toolbar.btnAdd );
		toolbar.addElement( toolbar.innerGroup );
		toolbar.appendTo( $( '<div/>' ).addClass( 'wb-editsection' ).appendTo( this.$toolbar ) );
	},

	/**
	 * Adds one claim to the list and renders it in the view.
	 * @since 0.3
	 *
	 * @param {wb.Claim} claim
	 */
	_addClaim: function( claim ) {
		var propertyId = claim.getMainSnak().getPropertyId(),
			$claimSection = this._serveClaimSection( propertyId ),
			$claim = $( '<div/>' ).claimview( { 'value': claim } );

		// add new claim to the end
		$claimSection.append( $claim );

		// TODO: optimize, don't do this every time, perhaps even use a CSS solution
		//       take a look at http://reference.sitepoint.com/css/adjacentsiblingselector
		var $claims = $claimSection.children( '.wb-claim-container' );

		$claims.removeClass( 'wb-first wb-last' );
		$claims.first().addClass( 'wb-first' );
		$claims.last().addClass( 'wb-last' );
	},

	/**
	 * Returns the section a claim had to be displayed in, depending on its Main Snak's property ID.
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
			var property = wb.properties[ mainSnakPropertyId ];

			if( !property ) {
				// Property info not in local store.
				// This should not ever happen if used properly, but make sure!
				throw new Error( 'Information for Property "' + mainSnakPropertyId + '" not found in local store' );
			}

			// create new section for first claim in this section
			$section = $( mw.template( 'wb-claim-section',
				mainSnakPropertyId, // main snak's property ID
				mw.html.escape( property.label ), // property name
				'' // claim
			) ).appendTo( this.$claims );
		}

		return $section;
	},

	/**
	 * Will serve the view for a new claim.
	 * @since 0.3
	 */
	enterNewClaim: function() {
		var self = this,
			$newClaim = $( '<div/>' );

		// insert claim before toolbar with add button:
		this.$claims.append( $newClaim );

		// initialize view after node is in DOM, so the 'startediting' event can bubble
		$newClaim.claimview();

		// first time the claim (or at least a part of it) drops out of edit mode:
		// TODO: not nice to use the event of the main snak, perhaps the claimview could offer one!
		$newClaim.one( 'snakviewstopediting', function( event, dropValue ) {
			// TODO: right now, if the claim is not valid (e.g. because data type not yet supported,
			//       the edit mode will close when saving without showing any hint!
			var claim = $newClaim.claimview( 'value' );

			if( dropValue || claim === null ) {
				// if new claim is canceled before saved, or if it is invalid, we simply remove
				// and forget about it
				$newClaim.claimview( 'destroy' ).remove();
			} else {
				// TODO: add newly created claim to model of represented entity!
				event.preventDefault();

				var api = new wb.Api(),
					mainSnak = claim.getMainSnak();

				// TODO: use a proper base rev id!
				api.createClaim( self.option( 'entityId' ), 0, mainSnak )
				.done( function() {
					$( event.target ).data( 'snakview' ).stopEditing();

					// destroy new claim input form and add claim to this list
					$newClaim.claimview( 'destroy' ).remove();
					self._addClaim( claim );
				} );
				// TODO: error handling
			}
		} );
	}
} );

}( mediaWiki, wikibase, jQuery ) );
