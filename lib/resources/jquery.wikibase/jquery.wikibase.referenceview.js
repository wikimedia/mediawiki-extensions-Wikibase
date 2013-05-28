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

	var PARENT = $.wikibase.snaklistview;

/**
 * View for displaying and editing Wikibase Statements.
 *
 * @option statementGuid {string} (REQUIRED) The GUID of the statement the reference belongs to.
 *
 * @event toggleerror: Triggered when an error occurred or is resolved.
 *        (1) {jQuery.Event} event
 *        (2) {wb.RepoApiError|undefined} wb.RepoApiError object if an error occurred, undefined if
 *            the current error state is resolved.
 *
 * @since 0.4
 * @extends jQuery.wikibase.snaklistview
 */
$.widget( 'wikibase.referenceview', PARENT, {
	widgetBaseClass: 'wb-referenceview',

	/**
	 * (Additional) default options.
	 * @see jQuery.Widget.options
	 */
	options: {
		statementGuid: null
	},

	/**
	 * Reference object represented by this view.
	 * @type {wb.Reference}
	 */
	_reference: null,

	/**
	 * Node of the "add" toolbar used to add snaks to the reference. The node exists only while in
	 * edit mode.
	 * @type {jQuery}
	 */
	$addToolbar: null,

	/**
	 * @see jQuery.wikibase.snaklistview._create
	 */
	_create: function() {
		var self = this;

		if ( !this.option( 'statementGuid' ) ) {
			throw new Error( 'Statement GUID required to initialize a reference view.' );
		}

		if ( this.option( 'value' ) ) {
			this._reference = this.option( 'value' );
			// Overwrite the value since the parent snaklistview widget require a wb.SnakList
			// object:
			this.options.value = this._reference.getSnaks();
		}
		PARENT.prototype._create.call( this );

		this._updateReferenceHashClass( this.value() );
	},

	/**
	 * @see jQuery.wikibase.snaklistview._attachEditModeEventHandlers
	 */
	_attachEditModeEventHandlers: function() {
		var self = this;

		this.element.one( this.widgetName + 'stopediting.' + this.widgetName, function( event, dropValue ) {
			self.disable();

			if ( !dropValue ) {
				event.preventDefault();
				self._saveReferenceApiCall()
				.done( function( savedObject, pageInfo ) {
					self.element.off( self.widgetName + 'stopediting.' + self.widgetName );
					self.stopEditing( dropValue );
					if ( self.$listview ) {
						self._attachEditModeEventHandlers();
					}
				} )
				.fail( function( errorCode, details ) {
					var error = wb.RepoApiError.newFromApiResponse(
							errorCode, details, 'save'
						);

					self.enable();

					self._attachEditModeEventHandlers();

					self.setError( error );
				} );
			}
		} );

		PARENT.prototype._attachEditModeEventHandlers.call( this );
	},

	/**
	 * @see jQuery.wikibase.snaklistview._detachEditModeEventHandlers
	 */
	_detachEditModeEventHandlers: function() {
		this.element.off( this.widgetName + 'stopediting.' + this.widgetName );
		PARENT.prototype._detachEditModeEventHandlers.call( this );
	},

	/**
	 * Will update the 'wb-reference-<hash>' class on the widget's root element to a given
	 * reference's hash. If null is given or if the reference has no hash, 'wb-reference-new' will
	 * be added as class.
	 *
	 * @param {wb.Reference|null} reference
	 */
	_updateReferenceHashClass: function( reference ) {
		var refHash = reference && reference.getHash() || 'new';

		this.element.removeClassByRegex( /wb-reference-.+/ );
		this.element.addClass( 'wb-reference-' + refHash );

		this.element.removeClassByRegex( new RegExp( this.widgetBaseClass ) + '-.+' );
		this.element.addClass( this.widgetBaseClass + '-' + refHash );
	},

	/**
	 * Sets/Returns the current reference represented by the view. In case of an empty reference
	 * view, without any snak values set yet, null will be returned.
	 * @see jQuery.wikibase.snaklistview.value
	 * @since 0.4
	 *
	 * @param {wb.Reference} [reference] New reference to be set
	 * @return {wb.Reference|null}
	 */
	value: function( reference ) {
		if ( reference ) {
			if ( !( reference instanceof wb.Reference ) ) {
				throw new Error( 'Value has to be an instance of wikibase.Reference' );
			}
			this._reference = reference;
			return this._reference;
		} else {
			var snakList = PARENT.prototype.value.call( this );

			if ( this._reference ) {
				return new wb.Reference( snakList || [], this._reference.getHash() );
			} else if ( snakList ) {
				return new wb.Reference( snakList );
			} else {
				return null;
			}
		}
	},

	/**
	 * Triggers the API call to save the reference.
	 * @since 0.4
	 *
	 * @return {jQuery.promise}
	 */
	_saveReferenceApiCall: function() {
		var self = this,
			guid = this.option( 'statementGuid' ),
			abstractedApi = new wb.AbstractedRepoApi(),
			revStore = wb.getRevisionStore();

		return abstractedApi.setReference(
			guid,
			this.value().getSnaks(),
			revStore.getClaimRevision( guid ),
			this.value().getHash() || null
		).done( function( savedReference, pageInfo ) {
			// update revision store
			revStore.setClaimRevision( pageInfo.lastrevid, guid );

			self._reference = savedReference;
			self._snakList = self._reference.getSnaks();
			self._updateReferenceHashClass( savedReference );
		} );
	},

	/**
	 * Sets/removes error state from the widget.
	 * @since 0.4
	 *
	 * @param {wb.RepoApiError} [error]
	 */
	setError: function( error ) {
		if ( error ) {
			this.element.addClass( 'wb-error' );
			this._trigger( 'toggleerror', null, [ error ] );
		} else {
			this.element.removeClass( 'wb-error' );
			this._trigger( 'toggleerror' );
		}
	}

} );

// Register toolbars:
$.wikibase.toolbarcontroller.definition( 'addtoolbar', {
	id: 'referenceview-snakview',
	selector: '.wb-statement-references .wb-referenceview',
	events: {
		referenceviewstartediting: 'create',
		referenceviewafterstopediting: 'destroy',
		referenceviewchange: function( event ) {
			var referenceview = $( event.target ).data( 'referenceview' ),
				addToolbar = $( event.target ).data( 'addtoolbar' );
			if ( addToolbar ) {
				addToolbar.toolbar[referenceview.isValid() ? 'enable' : 'disable']();
			}
		},
		referenceviewdisable: function( event ) {
			$( event.target ).data( 'addtoolbar' ).toolbar.disable();
		},
		referenceviewenable: function( event ) {
			var addToolbar = $( event.target ).data( 'addtoolbar' );
			// "add" toolbar might be remove already.
			if ( addToolbar ) {
				addToolbar.toolbar.enable();
			}
		}
	},
	options: {
		customAction: function( event, $parent ) {
			$parent.data( 'referenceview' ).enterNewItem();
		},
		eventPrefix: $.wikibase.referenceview.prototype.widgetEventPrefix
	}
} );

$.wikibase.toolbarcontroller.definition( 'removetoolbar', {
	id: 'referenceview-snakview-remove',
	selector: '.wb-statement-references .wb-referenceview',
	events: {
		'snakviewstartediting snakviewcreate listviewitemadded listviewitemremoved': function( event ) {
			var $target = $( event.target );
			if ( event.type.indexOf( 'snakview' ) !== -1 ) {
				// Create toolbar for each snakview widget:
				var $referenceviewNode = $target.closest( '.wb-referenceview' ),
					referenceview = $referenceviewNode.data( 'referenceview' );
				$target.removetoolbar( {
					action: function( event ) {
						referenceview._listview.removeItem( $target );
					}
				} );
			}

			// If there is only one snakview widget, disable its "remove" link:
			var listview = $target.closest( '.wb-referenceview' ).data( 'referenceview' )._listview;
			if ( listview.items() ) {
				var firstItemToolbar = $( listview.items()[0] ).data( 'removetoolbar' );
				if ( firstItemToolbar ) {
					firstItemToolbar.toolbar[
						( listview.items().length === 1 ) ? 'disable' : 'enable'
					]();
				}
			}
		},
		referenceviewafterstopediting: function( event ) {
			// Destroy the snakview toolbars:
			var $referenceviewNode = $( event.target );
			$.each( $referenceviewNode.find( '.wb-snakview' ), function( i, snakviewNode ) {
				var $snakviewNode = $( snakviewNode );
				// TODO: "if" should not be required. referenceviewafterstopediting should be fired
				// once only.
				if ( $snakviewNode.data( 'removetoolbar' ) ) {
					$snakviewNode.data( 'removetoolbar' ).destroy();
					$snakviewNode.children( '.wb-removetoolbar' ).remove();
				}
			} );
		},
		'referenceviewdisable referenceviewenable': function( event ) {
			var referenceview = $( event.target ).data( 'referenceview' ),
				listview = referenceview._listview,
				lia = listview.listItemAdapter(),
				action = ( event.type.indexOf( 'disable' ) !== -1 ) ? 'disable' : 'enable';

			$.each( listview.items(), function( i, item ) {
				var $item = $( item );
				// Item might be about to be removed not being a list item instance.
				if ( lia.liInstance( $item ) && $item.data( 'removetoolbar' ) ) {
					$item.data( 'removetoolbar' ).toolbar[action]();
				}
			} );
		}
	}
} );

}( mediaWiki, wikibase, jQuery ) );
