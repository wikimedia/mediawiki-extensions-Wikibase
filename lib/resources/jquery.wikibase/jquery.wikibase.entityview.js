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
	 * Section node containing the list of claims of the entity, this node has a $.claimlistview
	 * widget initialized.
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

		this.$claims = $( '<div/>' ).claimlistview( {
			value: this.option( 'value' ).claims,
			entityId: this.option( 'value' ).id
		} );

		// append all the stuff:
		// NOTE: doing this here will prevent events from bubbling during widget initializations!
		//       Shouldn't harm and will increase performance because DOM needs to render once only.
		this.element.append( $claimsHeading ).append( this.$claims );

		this._handleEditModeAffairs();
	},

	/**
	 * Will make this edit view keeping track over global edit mode and triggers global edit mode
	 * in case any member of this entity view enters edit mode.
	 * @since 0.3
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
		.on( 'snakviewstopediting claimviewremove', function( event ) {
			$( wb ).trigger( 'stopItemPageEditMode' );
		} );
	}
} );

}( mediaWiki, wikibase, jQuery ) );
