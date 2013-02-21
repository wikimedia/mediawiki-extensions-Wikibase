/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
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
 * @event additem: Triggered before a list item will be added to the list.
 *        (1) {jQuery.Event}
 *        (2) {*|null} The value the new list item will represent. This can also be null in case a
 *            new, empty list item, not yet representing any value but ready for the user to enter
 *            a value, will be added.
 *        (3) {jQuery} the DOM node on which a widget representing the new list item's value will
 *            be initialized. The widget will be initialized on this DOM node after the DOM node is
 *            appended to the list, so events can bubble during widget initialization.
 *
 * @event itemadded: Triggered after a list item got added to the list.
 *        (1) {jQuery.Event}
 *        (2) {*|null} The value the new list item is representing. null for empty value.
 *        (3) {jQuery} The DOM node with the widget, representing the value.
 *
 * @event removeitem: Triggered before a list item will be removed from the list.
 *        (1) {jQuery.Event}
 *        (2) {*|null} The value of the list item which will be removed. null for empty value.
 *        (3) {jQuery} The list item's DOM node, which will be removed.
 *
 * @event itemremoved: Triggered after a list got removed from the list.
 *        (1) {jQuery.Event}
 *        (2) {*|null} The value of the list item which will be removed. null for empty value.
 *        (3) {jQuery} The list item's DOM node, removed.
 *
 * @event canceled: Triggered after canceling the process of adding a new item.
 *        (1) {jQuery.Event}
 */
$.widget( 'wikibase.listview', PARENT, {
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
			'$listItems': '.wb-listview-items'
		},
		value: null,
		listItemAdapter: null
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
			liAfterRemoveEvent = this._lia.prefixedEvent( 'afterremove' );

		// apply template to this.element:
		PARENT.prototype._create.call( this );

		this._createList(); // fill this.$listItems

		// remove list item after remove operation has finished
		this.element.on( liAfterRemoveEvent, function( e ) {
			var $listItem = $( e.target );
			self.removeItem( $listItem );
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
		}
		PARENT.prototype._setOption.call( this, key, value );
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
			this.addItem( items[i] );
		}
	},

	/**
	 * Returns all list item nodes.
	 *
	 * @since 0.4
	 *
	 * @return {jQuery}
	 */
	items: function() {
		return this.$listItems.children();
	},

	/**
	 * Returns all list items which have a value not considered empty (not null).
	 *
	 * @since 0.4
	 *
	 * @return {jQuery}
	 */
	nonEmptyItems: function() {
		var lia = this._lia;
		return this.items().filter( function( i ) {
			return !!lia.liValue( $( this ) );
		} );
	},

	/**
	 * Returns the list item adapter object to deal with this list's list items.
	 * @return {jQuery.wikibase.listview.ListItemAdapter}
	 */
	listItemAdapter: function() {
		return this._lia;
	},

	/**
	 * Adds one list item into the list and renders it in the view.
	 * @since 0.4
	 *
	 * @triggers additem
	 * @triggers itemadded If default was not prevented by 'additem' event.
	 *
	 * @param {*} value
	 * @return {jQuery} The DOM node representing the value. If default was prevented in the
	 *         'additem' event, the node will be returned even though not appended to the list.
	 */
	addItem: $.NativeEventHandler( 'additem', {
		initially: function( event, value ) {
			// in custom handlers, we provide the DOM node without initialized value widget because
			// we want to initialize widget AFTER the node is in the DOM, so we can have events
			// triggered during widget initialization bubble up the DOM!
			var $newLi = $( '<div/>' );
			event.handlerArgs = [ value || null, $newLi ];
			return $newLi;
		},
		natively: function( event, value, $newLi ) {
			// first insert DOM so value widget's events can already bubble during initialization!
			this.$listItems.append( $newLi );
			this._lia.newListItem( $newLi, value );

			$newLi.edittoolbar( {
				interactionWidgetName: this._lia.liInstance( $newLi ).widgetName,
				toolbarParentSelector: '.wb-claim-toolbar',
				enableRemove: !!value
			} );

			this._trigger( 'itemadded', null, [ value, $newLi ] );
		}
	} ),

	/**
	 * Removes one list item from the list and renders the update in the view.
	 * @since 0.4
	 *
	 * @triggers removeitem
	 * @triggers itemremoved If default was not prevented by 'removeitem' event.
	 *
	 * @param {jQuery} $itemNode The list item's node to be removed
	 */
	removeItem: $.NativeEventHandler( 'removeitem', {
		initially: function( event, $itemNode ) {
			// check whether given node actually is in this list. If not, fail!
			if( !$itemNode.parent( this.$listItems ).length ) {
				throw new Error( 'The given node is not an element in this list' );
			}
			// even though this information is kind of redundant since the value can be accessed
			// within custom events by using listview.listItemAdapter().liValue( $itemNode), we
			// provide the value here for convenience and for consistent event argument order in all
			// add/remove events
			var value = this._lia.liValue( $itemNode );
			event.handlerArgs = [ value, $itemNode ];
		},
		natively: function( event, value, $itemNode ) {
			// destroy widget representing the list item's value and remove node from list:
			this._lia.liInstance( $itemNode ).destroy();

			// Trigger itemremoved event before removing the DOM node to be able to query for its
			// context.
			this._trigger( 'itemremoved', null, [ value, $itemNode ] );

			$itemNode.remove();
		}
	} ),

	/**
	 * Will insert a new list member into the list. The new list member will be a Widget of the type
	 * displayed in the list, but without value, so the user can specify a value.
	 *
	 * @since 0.4
	 */
	enterNewItem: function() {
		var self = this,
			$newLi = this.addItem();

		this._lia.liInstance( $newLi ).startEditing();

		// first time the claim (or at least a part of it) drops out of edit mode:
		// TODO: not nice to use the event of the main snak, perhaps the claimview could offer one!
		$newLi.on( this._lia.prefixedEvent( 'stopediting' ), function( event, dropValue ) {
			var newSnak = self._lia.liInstance( $newLi ).$mainSnak.data( 'snakview' ).snak();

			if ( self.__continueStopEditing ) {
				self.__continueStopEditing = false;
				$newLi.off( self._lia.prefixedEvent( 'remove' ) );
				return;
			}

			if ( !dropValue ) {
				event.preventDefault();
			} else {
				self.element.removeClass( 'wb-error' );
			}

			if( !dropValue && newSnak ) {
				// temporary claim that is just used for saving; claimview will create its
				// own claim object after saving has been successful
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
					wb.getRevisionStore().setClaimRevision(
						pageInfo.lastrevid, statementGuid
					);

					// Continue stopEditing event by triggering it again skipping
					// claimlistview's API call.
					self.__continueStopEditing = true;

					self._lia.liInstance( $( event.target ) ).stopEditing( dropValue );

					// Replace the new list item which was initially initialized as empty by
					// destroying it and adding a new list item with the value provided by the API.
					self.removeItem( $newLi );
					self.addItem( newRefWithHash ); // display new reference with final hash
				} )
				.fail( function( errorCode, details ) {
					var listview = self._lia.liInstance( $newLi ),
						error = wb.RepoApiError.newFromApiResponse(
							errorCode, details, 'save'
						);

					listview.enable();
					listview.element.addClass( 'wb-error' );

					listview._trigger( 'toggleerror', null, [error] );
				} );
			}
		} )
		.on( this._lia.prefixedEvent( 'afterstopediting' ), function( event, dropValue ) {
			if( dropValue || !self._lia.liInstance( $newLi ).$mainSnak.data( 'snakview' ).snak() ) {
				self.removeItem( $newLi );

				self.element.find( '.wb-claim-section' )
					.not( ':has( >.wb-edit )' ).removeClass( 'wb-edit' );

				self._trigger( 'canceled' );
			}
		} );
	}

} );

}( mediaWiki, wikibase, jQuery ) );
