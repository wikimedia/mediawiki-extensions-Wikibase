/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( mw, wb, $ ) {
	'use strict';

	var PARENT =  $.TemplatedWidget;

/**
 * View for displaying and editing several list items, each represented by another widget.
 * @since 0.4
 *
 * @option {*|null} value The values displayed by this view. Each value is represented by a widget
 *         defined in the 'listItemAdapter' option. If 'value' is null, this view will only display
 *         an add button, for adding new values (at least if 'showAddButton' is not set to false).
 *
 * @option {jQuery.wikibase.listview.ListItemAdapter} listItemAdapter (required) Can not
 *         be changed after initialization.
 *
 * @option {boolean|string} showAddButton Whether or not a 'add' button should be displayed for the
 *         user to add new values to the list. If this is a string, it defines the button's label.
 */
$.widget( 'wikibase.listview', PARENT, {
	/**
	 * Node of the toolbar
	 * @type jQuery
	 */
	$toolbar: null,

	/**
	 * Section node containing the list items
	 * @type jQuery
	 */
	$listItems: null,

	/**
	 * Short cut for 'listItemAdapter' option
	 * @type jQuery.wikibase.listview.ListItemAdapter
	 */
	_lia: null,

	/**
	 * (Additional) default options
	 * @see jQuery.Widget.options
	 */
	options: {
		template: 'wb-listview',
		templateParams: [
			'', // list items
			'' // toolbar
		],
		templateShortCuts: {
			'$listItems': '.wb-listview-items',
			'$toolbar': '.wb-listview-toolbar'
		},
		value: null,
		listItemAdapter: null,
		showAddButton: true
	},

	/**
	 * @see jQuery.Widget._create
	 */
	_create: function() {
		this._lia = this.options.listItemAdapter; // create short-cut for this

		if( typeof this._lia !== 'object'
			|| !( this._lia instanceof $.wikibase.listview.ListItemAdapter )
		) {
			throw new Error( "Option 'listItemAdapter' has to be an instance of $.wikibase." +
				"listview.ListItemAdapter" );
		}

		var self = this,
			liRemoveEvent = this._lia.prefixedEvent( 'remove' );

		// apply template to this.element:
		PARENT.prototype._create.call( this );

		this._createList(); // fill this.$listItems
		this._createToolbar();

		// remove list item after remove event got triggered by its toolbar:
		this.element.on( liRemoveEvent, function( e ) {
			var $listItem = $( e.target );
			self._lia.listItemInstanceByDom( $listItem ).destroy();

			// Focus the next list item's "edit" button. The user might want to alter that list
			// item as well.
			var nextToolbar = $listItem.next().find( '.wb-ui-toolbar' ).data( 'wb-toolbar' );
			if( $listItem.next().hasClass( 'wb-claim-add' ) ) { // TODO: claim specific!
				nextToolbar.btnAdd.setFocus();
			} else {
				nextToolbar.editGroup.btnEdit.setFocus();
			}
			$listItem.remove();
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
	 * @see jQuery.widget._setOption
	 * We are using this to disallow changing the 'listItemAdapter' option afterwards
	 */
	_setOption: function( key, value ) {
		if( key === 'listItemAdapter' ) {
			throw new Error( 'Can not change the ListItemAdapter after initialization' );
		} else if( key === 'showAddButton' ) {
			var toolbar = this.$toolbar.find( '.wb-ui-toolbar' ).data('wb-toolbar' ),
				addBtnLabel = typeof value === 'string' ? value : mw.msg( 'wikibase-add' );

			toolbar.btnAdd.setContent( addBtnLabel );
			toolbar.innerGroup[ value ? 'addElement' : 'removeElement' ]( toolbar.btnAdd );
		}
		PARENT.prototype._setOption.call( this, key, value );
	},

	/**
	 * Instantiates a new widget which can be used as item for this list.
	 *
	 * @param {jQuery} element
	 * @param {Object} options
	 * @return jQuery.Widget
	 */
	_lmwInstantiate: function( element, options ) {
		return new this._lia( options || {}, element[0] );
	},

	/**
	 * Will fill this.$listItems with sections DOM, all sections will already contain their related
	 * list items DOM.
	 *
	 * @since 0.4
	 */
	_createList: function() {
		var i, items = this.option( 'value' );

		// initialize view for each of the list item values:
		for( i in items ) {
			this._insertListItem( items[i] );
		}
	},

	/**
	 * Creates the toolbar holding the 'add' button for adding new list items.
	 *
	 * @since 0.4
	 */
	_createToolbar: function() {
		// display 'add' button at the end of the list:
		var self = this,
			toolbar = this._buildToolbar();

		$( toolbar.btnAdd ).on( 'action', function( event ) {
			self.enterNewListItem();
		} );

		toolbar.appendTo( $( '<div/>' ).addClass( 'wb-editsection' ).appendTo( this.$toolbar ) );

		// will append 'add' button to DOM or not, depending on related option:
		this._setOption( 'showAddButton', this.options.showAddButton );
	},

	/**
	 * Creates a toolbar with an 'add' button
	 */
	_buildToolbar: function() {
		// display 'add' button at the end of the list:
		var toolbar = new wb.ui.Toolbar();

		toolbar.innerGroup = new wb.ui.Toolbar.Group();
		toolbar.btnAdd = new wb.ui.Toolbar.Button( mw.msg( 'wikibase-add' ) );
		toolbar.addElement( toolbar.innerGroup );

		return toolbar;
	},

	/**
	 * Adds one list item into the list and renders it in the view.
	 * @since 0.4
	 *
	 * @param {*} value
	 */
	_insertListItem: function( value ) {
		var $newLi = $( '<div/>' );
		this._lia.newListMember( $newLi, value );
		this.$listItems.append( $newLi ); // NOTE: events will not bubble before this point!
	},

	/**
	 * Will insert a new list member into the list. The new list member will be a Widget of the type
	 * displayed in the list, but without value, so the user can specify a value.
	 *
	 * @since 0.4
	 */
	enterNewListItem: function() {
		var self = this,
			$newLi = $( '<div/>' ),
			options = {};

		// insert reference before toolbar with add button
		this.$listItems.append( $newLi );

		// initialize view after node is in DOM, so the 'startediting' event can bubble
		//this._lmwInstantiate( $newLi, options ).element.addClass( 'wb-claim-new' );

		this._lia.newListMember( $newLi );

		// first time the claim (or at least a part of it) drops out of edit mode:
		// TODO: not nice to use the event of the main snak, perhaps the claimview could offer one!
		$newLi.on( 'snakviewstopediting', function( event, dropValue, newSnak ) {
			if ( self.__continueStopEditing ) {
				self.__continueStopEditing = false;
				$newLi.off( 'snakviewstopediting' );
				return;
			}

			if ( !dropValue ) {
				event.preventDefault();
			} else {
				self.element.removeClass( 'wb-error' );
			}

			// TODO: right now, if the claim is not valid (e.g. because data type not yet
			//       supported), the edit mode will close when saving without showing any hint!

			/**
			 * Find the section node of a given (new) claim node.
			 *
			 * @param {jQuery} $newLi
			 * @return {jQuery}
			 */
			function findClaimSection( $newLi ) {
				var $claimSection = null;
				self.$listItems.children().each( function( i, claimSection ) {
					if ( claimSection === $newLi.parent()[0] ) {
						$claimSection = $( claimSection );
						return false;
					}
				} );
				return $claimSection;
			}

			if( dropValue || !newSnak ) {
				// if new claim is canceled before saved, or if it is invalid, we simply remove
				// and forget about it after having figured out which "add" link to re-set the
				// focus on
				if ( $newLi.parent()[0] === self.$listItems[0] ) {
					self.$toolbar.find( '.wb-ui-toolbar' ).data( 'wb-toolbar' ).btnAdd.setFocus();
				} else {
					findClaimSection( $newLi ).find( '.wb-claim-add .wb-ui-toolbar' )
						.data( 'wb-toolbar' ).btnAdd.setFocus();
				}
				self._lia.liInstance( $newLi ).destroy();
				$newLi.remove();

				self.element.find( '.wb-claim-section' )
					.not( ':has( >.wb-edit )' ).removeClass( 'wb-edit' );
			} else {
				// temporary claim that is just used for saving; claimview will create its own claim
				// object after saving has been successful
				var reference = new wb.Reference( newSnak );

				// TODO: add newly created claim to model of represented entity!

				var api = new wb.RepoApi(),
					snaks = reference.getSnaks(),
					statementGuid = self.option( 'statementGuid' );

				api.setReference(
					statementGuid,
					snaks,
					wb.getRevisionStore().getClaimRevision( statementGuid )
				).done( function( newRefWithHash, pageInfo ) {
					// update revision store:
					wb.getRevisionStore().setClaimRevision( pageInfo.lastrevid, statementGuid );

					// Continue stopEditing event by triggering it again skipping claimlistview's
					// API call.
					self.__continueStopEditing = true;

					$( event.target ).data( 'snakview' ).stopEditing( dropValue );

					// TODO: Depending on how the actual interaction flow of adding a new claim will
					// be, the focus should probably be set to somewhere more elegant like the new
					// claim's "add qualifiers" link.
					if ( $newLi.parent()[0] === self.$listItems[0] ) {
						self.$toolbar.find( '.wb-ui-toolbar' ).data( 'wb-toolbar' ).btnAdd.setFocus();
					} else {
						findClaimSection( $newLi ).find( '.wb-claim-add .wb-ui-toolbar' )
							.data( 'wb-toolbar' ).btnAdd.setFocus();
					}

					// destroy new claim input form and add claim to this list
					self._lia.liInstance( $newLi ).destroy();
					$newLi.remove();
					self._insertListItem( newRefWithHash ); // display new reference with final hash
				} )
				.fail( function( errorCode, details ) {
					var $newClaim = self.element.find( '.wb-claim-new' )
							.filter( '.' + self._lia.prefixedClass() ),
						claimview = self._lia.liInstance( $newLi ),
						toolbar = $newLi.find( '.wb-ui-toolbar' ).data( 'wb-toolbar' ),
						btnSave = toolbar.editGroup.btnSave,
						error = wb.RepoApiError.newFromApiResponse( errorCode, details, 'save' );

					claimview.toggleActionMessage( function() {
						claimview.displayError( error, btnSave );
						claimview.enable();
					} );
				} );
			}
		} );
	}
} );

}( mediaWiki, wikibase, jQuery ) );
