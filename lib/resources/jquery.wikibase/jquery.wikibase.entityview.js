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

		var entity = this.option( 'value' ),
			$claimsHeading =
				$( mw.template( 'wb-section-heading', mw.msg( 'wikibase-statements' ), '' ) );

		this.$claims = $( '<div/>' ).claimlistview( {
			value: entity.getClaims(),
			entityId: entity.getId(),
			listMembersWidget: $.wikibase.statementview
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
		var self = this,
			toolbarStatesSetter;
		/**
		 * Helper which returns a handler for global edit mode event to disable/enable this entity
		 * view's toolbars but not the one of the edit widget currently active.
		 *
		 * @param {string} action
		 * @return {function}
		 */
		toolbarStatesSetter = function( action ) {
			return function( event, origin, options ) {
				// TODO: at some point, this should rather disable/enable the widgets for editing,
				//       there could be other ways for entering edit mode than using the toolbar!

				// Whether action shall influence sub-toolbars of origin:
				// TODO: "exclusive" option/variable restricts arrangement of toolbars. Interaction
				//       between toolbars should be managed via the toolbar controller.
				var originToolbars = null;
				if ( options ) {
					if ( typeof options.exclusive === 'boolean' && !options.exclusive ) {
						originToolbars = $( origin ).find( '.wb-ui-toolbar' );
					} else if ( $.isArray( options.exclusive ) !== -1 ) {
						originToolbars = $( origin ).find( options.exclusive );
					}
				}

				// find and disable/enable all toolbars in this edit view except,...
				self.element.find( '.wb-ui-toolbar' ).each( function() {
					var $toolbar = $( this ),
						toolbar = $toolbar.data( 'wb-toolbar' );
					// ... don't disable toolbar if it has an edit group which is in edit mode
					// or if the toolbar is a sub-element of the origin.
					if (
						$toolbar.children( '.wb-ui-toolbar-editgroup-ineditmode' ).length === 0 &&
						( !originToolbars || $.inArray( this, originToolbars ) === -1 ) &&
						// Checking if toolbar is defined is done for the purpose of debugging only;
						// Toolbar may only be undefined under some weird circumstances, e.g. when
						// doing $( 'body' ).empty() for debugging.
						toolbar
					) {
						toolbar[ action ]();
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
		.on( 'statementviewafterstartediting', function( event ) {
			$( wb ).trigger( 'startItemPageEditMode', [
				event.target,
				{
					exclusive: '.wb-claim-qualifiers .wb-ui-toolbar',
					wbCopyrightWarningGravity: 'sw'
				}
			] );
		} )
		.on( 'referenceviewafterstartediting', function( event ) {
			$( wb ).trigger(
				'startItemPageEditMode',
				[ event.target, { exclusive: false, wbCopyrightWarningGravity: 'sw' } ]
			);
		} )
		.on( 'snakviewstopediting', function( event, dropValue ) {
			// snak view got already removed from the DOM on "snakviewafterstopediting"
			if ( dropValue ) {
				$( wb ).trigger( 'stopItemPageEditMode', [event.target] );
			}
		} )
		.on( 'statementviewafterstopediting claimlistviewafterremove ' +
				'referenceviewafterstopediting statementviewafterremove',
			function( event ) {
				$( wb ).trigger( 'stopItemPageEditMode', [event.target] );
			}
		);
	}
} );

}( mediaWiki, wikibase, jQuery ) );
