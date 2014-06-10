/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( wb, $ ) {
	'use strict';

/**
 * View for displaying an entire wikibase entity.
 * @since 0.3
 *
 * @option {wikibase.Entity} value
 *
 * @option {wikibase.store.EntityStore} entityStore
 *
 * TODO: this is far from complete, right now this only serves the functionality to display an
 *       entity's claims (and statements in case of an item).
 */
$.widget( 'wikibase.entityview', {
	widgetName: 'wikibase-entityview',

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
		value: null,
		entityStore: null
	},

	/**
	 * @see jQuery.Widget._create
	 */
	_create: function() {
		var entity = this.option( 'value' );

		this.$claims = $( '.wb-claimgrouplistview', this.element ).first();
		if( this.$claims.length === 0 ) {
			this.$claims = $( '<div/>' ).appendTo( this.element );
		}

		this.$claims.claimgrouplistview( {
			value: entity.getClaims(),
			entityType: entity.getType(),
			entityStore: this.option( 'entityStore' )
		} );

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
		 * Helper which returns a handler for global edit mode event to disable/enable this entity
		 * view's toolbars but not the one of the edit widget currently active.
		 *
		 * @param {string} action
		 * @return {function}
		 */
		var toolbarStatesSetter = function( action ) {
			function findToolbars( $range ) {
				var $wbToolbars = $range.find( '.wikibase-toolbar' ),
					$wbToolbarGroups = $wbToolbars.find( $wbToolbars );

				return $wbToolbars
					// Filter out toolbar groups:
					.not( $wbToolbarGroups )
					// Re-add "new UI" toolbars:
					// TODO Improve selection mechanism as soon as old UI classes have
					//  converted or get rid of this toolbarStatesSetter.
					.add( $wbToolbarGroups.filter(
						function() {
							var $toolbarNode = $( this.parentNode.parentNode );
							return $toolbarNode.hasClass( 'wb-edittoolbar' )
								|| $toolbarNode.hasClass( 'wb-removetoolbar' )
								|| $toolbarNode.hasClass( 'wb-addtoolbar' );
						}
					) );
			}

			return function( event, origin, options ) {
				// TODO: at some point, this should rather disable/enable the widgets for editing,
				//       there could be other ways for entering edit mode than using the toolbar!

				// Whether action shall influence sub-toolbars of origin:
				// TODO: "exclusive" option/variable restricts arrangement of toolbars. Interaction
				//       between toolbars should be managed via the toolbar controller.
				var originToolbars = null;
				if ( options ) {
					if ( options.exclusive === false ) {
						originToolbars = findToolbars( $( origin ) );
					} else if ( typeof options.exclusive === 'string' ) {
						originToolbars = $( origin ).find( options.exclusive );
					}
				}

				// find and disable/enable all toolbars in this edit view except,...
				findToolbars( self.element ).each( function() {
					var $toolbar = $( this ),
						toolbar = $toolbar.data( 'toolbar' );
					// ... don't disable toolbar if it has an edit group which is in edit mode
					// or if the toolbar is a sub-element of the origin.
					if (
						$toolbar.children( '.wikibase-toolbareditgroup-ineditmode' ).length === 0
						&& ( !originToolbars || $.inArray( this, originToolbars ) === -1 )
						// Checking if toolbar is defined is done for the purpose of debugging only;
						// Toolbar may only be undefined under some weird circumstances, e.g. when
						// doing $( 'body' ).empty() for debugging.
						&& toolbar
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
					exclusive: '.wb-claim-qualifiers .wikibase-toolbar',
					wbCopyrightWarningGravity: 'sw'
				}
			] );
		} )
		.on( 'referenceviewafterstartediting', function( event ) {
			$( wb ).trigger( 'startItemPageEditMode', [
				event.target,
				{
					exclusive: false,
					wbCopyrightWarningGravity: 'sw'
				}
			] );
		} )
		.on( 'snakviewstopediting', function( event, dropValue ) {
			// snak view got already removed from the DOM on "snakviewafterstopediting"
			if ( dropValue ) {
				// Return true on dropValue === false as well as dropValue === undefined
				$( wb ).trigger( 'stopItemPageEditMode', [
					event.target,
					{ save: dropValue !== true }
				] );
			}
		} )
		.on( 'statementviewafterstopediting claimlistviewafterremove '
				+ 'referenceviewafterstopediting statementviewafterremove',
			function( event, dropValue ) {
				// Return true on dropValue === false as well as dropValue === undefined
				$( wb ).trigger( 'stopItemPageEditMode', [
					event.target,
					{ save: dropValue !== true }
				] );
			}
		);
	}
} );

// We have to override this here because $.widget sets it no matter what's in
// the prototype
$.wikibase.entityview.prototype.widgetBaseClass = 'wb-entityview';

}( wikibase, jQuery ) );
