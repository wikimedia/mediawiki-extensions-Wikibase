/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( mw, wb, dv, dt, $ ) {
	'use strict';

/**
 * View for displaying an entire wikibase entity.
 * @since 0.3
 *
 * TODO: this is far from complete, right now this only serves the functionality to display an
 *       entity's claims (and statements in case of an item).
 */
$.widget( 'wikibase.entityview', {
	widgetName: 'wikibase-entityview',
	widgetBaseClass: 'wb-entityview',

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
		this.element.empty();

		var $claimsHeading =
			$( mw.template( 'wb-section-heading', mw.msg( 'wikibase-statements' ) ) );

		this._createClaims(); // build this.$claims

		// append all the stuff:
		this.element.append( $claimsHeading ).append( this.$claims );

		this._handleEditModeAffairs();
	},

	/**
	 * Will make this edit view keeping track over global edit mode and triggers global edit mode
	 * in case any member of this entity view enters edit mode.
	 */
	_handleEditModeAffairs: function() {
		var self = this;
		/**
		 * helper which returns a handler for global edit mode event to disable/enable this entity
		 * views toolbars but not the one of the edit widget active currently.
		 * @return Function
		 */
		var toolbarStatesSetter = function( action ) {
			return function( event, origin ) {
				// TODO: at some point, this should rather disable/enable the widgets for editing,
				//       there could be other ways for entering edit mode than using the toolbar!
				// find and disable/enable all toolbars in this edit view except,...
				self.element.find( '.wb-ui-toolbar' ).each( function() {
					var $toolbar = $( this );
					// ... don't disable toolbar if it has an edit group which is in edit mode!
					if( $toolbar.children( '.wb-ui-toolbar-editgroup-ineditmode' ).length === 0 ) {
						$toolbar.data( 'wb-toolbar' )[ action ]();
					}
				} );
			};
		};

		// disable/enable all toolbars when starting/ending an edit mode:
		$( wb )
		.on( 'startItemPageEditMode', toolbarStatesSetter( 'disable' ) )
		.on( 'stopItemPageEditMode', toolbarStatesSetter( 'enable' ) );

		// if any of the snaks enters edit mode, trigger global edit mode. This is necessary for
		// compatibility with old PropertyEditTool which is still used for label, description etc.
		// TODO: this should rather listen to 'valueviewstartediting' once implemented!
		$( this.element )
		.on( 'snakviewstartediting', function( event ) { // event bubbles all the way to
			$( wb ).trigger( 'startItemPageEditMode' );
		} )
		.on( 'snakviewstopediting', function( event ) {
			$( wb ).trigger( 'stopItemPageEditMode' );
		} );
	},

	/**
	 * Will build this.$claims but dosn't append it to the widgets main node yet.
	 * @since 0.3
	 */
	_createClaims: function() {
		var i, self = this;

		this.$claims = $( '<div/>', {
			'class': this.widgetBaseClass + '-claims'
		} );

		var claims = this.option( 'value' ).claims;

		// initialize view for each of the claims:
		for( i in claims ) {
			$( '<div/>' ).claimview( { 'value': claims[i] } ).appendTo( this.$claims );
		}
		// TODO: 'claim groups', claims should be grouped by their property

		// display 'add' button at the end of the claims:
		var toolbar = new wb.ui.Toolbar();

		toolbar.innerGroup = new wb.ui.Toolbar.Group();
		toolbar.btnAdd = new wb.ui.Toolbar.Button( mw.msg( 'wikibase-add' ) );
		$( toolbar.btnAdd ).on( 'action', function( event ) {
			self.enterNewClaim();
		} );
		toolbar.innerGroup.addElement( toolbar.btnAdd );
		toolbar.addElement( toolbar.innerGroup );
		toolbar.appendTo( $( '<div/>' ).addClass( 'wb-editsection' ).appendTo( this.$claims ) );
	},

	/**
	 * Will serve the view for a new claim.
	 * @since 0.3
	 */
	enterNewClaim: function() {
		var self = this,
			$newClaim = $( '<div/>' );

		// insert claim before toolbar with add button:
		this.$claims.children( '.wb-editsection' ).last().before( $newClaim );

		// initialize view after node is in DOM, so the 'startediting' event can bubble
		$newClaim.claimview();

		// first time the claim (or at least a part of it) drops out of edit mode:
		// TODO: not nice to use the event of the main snak, perhaps the claimview could offer one!
		$newClaim.one( 'snakviewstopediting', function( event, dropValue ) {
			if( dropValue ) {
				// if new claim is canceled before saved, we simply remove it
				$newClaim.claimview( 'destroy' ).remove();
			} else {
				// TODO: add newly created claim to model of represented entity!
				event.preventDefault();

				var api = new wb.Api(),
					mainSnak = $newClaim.claimview( 'value' ).getMainSnak();

				// TODO: use a proper base rev id!
				api.createClaim( self.option( 'value' ).id, 0, mainSnak )
				.done( function() {
					$( event.target ).data( 'snakview' ).stopEditing();
				} );
				// TODO: error handling
			}
		} );
	}
} );

}( mediaWiki, wikibase, dataValues, dataTypes, jQuery ) );
