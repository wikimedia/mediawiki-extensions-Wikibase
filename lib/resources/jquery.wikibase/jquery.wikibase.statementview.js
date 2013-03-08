/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( mw, wb, $ ) {
	'use strict';

	var PARENT = $.wikibase.claimview;

/**
 * View for displaying and editing Wikibase Statements.
 *
 * @since 0.4
 * @extends jQuery.wikibase.claimview
 */
$.widget( 'wikibase.statementview', PARENT, {
	widgetBaseClass: 'wb-statementview',

	options: {
		template: 'wb-statement',
		templateParams: [
			'wb-last', // class: wb-first|wb-last
			function() { // class='wb-claim-$2'
				return ( this._claim && this._claim.getGuid() ) || 'new';
			},
			'', // .wb-claim-mainsnak
			'',  // edit section DOM
			'',
			''
		],
		templateShortCuts: {
			'$mainSnak': '.wb-claim-mainsnak',
			'$toolbar': '.wb-claim-toolbar',
			'$refsHeading': '.wb-statement-references-heading',
			'$references': '.wb-statement-references'
		}
	},

	/**
	 * The DOM node of the references heading, displaying the number of sources and acts as button
	 * to toggle the references visibility.
	 * @type jQuery
	 */
	$refsHeading: null,

	/**
	 * DOM node holding the Statement's references.
	 * @type jQuery
	 */
	$references: null,

	/**
	 * @see jQuery.claimview._create
	 */
	_create: function() {
		// add claimview class as well, so we inherit basic CSS rules:
		this.element.addClass( 'wb-claimview' );
		PARENT.prototype._create.call( this );

		var self = this,
			statement = this.value(),
			refs = statement ? statement.getReferences() : [];

		// only display statement related UI (references) if Claim essentials are defined already!
		if( this.value() ) {
			var statementGuid = this.value().getGuid();

			this.$references.append( mw.template( 'wb-snaklistview',
				'', // listview placeholder
				'' // toolbar placeholder
			) );

			var $snaklistview = this.$references.find( '.wb-snaklistview-listview' );
			$snaklistview.listview( {
				listItemAdapter: new $.wikibase.listview.ListItemAdapter( {
					listItemWidget: $.wikibase.referenceview,
					listItemWidgetValueAccessor: 'value',
					newItemOptionsFn: function( value ) {
						return {
							value: value || null,
							statementGuid: statementGuid,
							locked: {
								mainSnak: {
									// when editing existing reference, don't allow changing property!
									property: !!value
								}
							}
						};
					}
				} ),
				statementGuid: statementGuid, // not set for new Statement!
				value: refs,
				showAddButton: mw.msg( 'wikibase-addreference' )
			} )
			.on( 'listviewitemadded listviewitemremoved', function( e, value, $li ) {
				self.drawReferencesCounter();
			} )
			.on( 'listviewenternewitem', function( event, $newLi ) {
				var lv = self.$references.data( 'listview' ),
					lia = lv.listItemAdapter(),
					liInstance = lia.liInstance( $newLi );

				// first time the claim (or at least a part of it) drops out of edit mode:
				// TODO: not nice to use the event of the main snak, perhaps the claimview could
				// offer one!
				$newLi.on( lia.prefixedEvent( 'stopediting' ), function( event, dropValue ) {
					var newSnak = liInstance.$mainSnak.data( 'snakview' ).snak();

					if ( self.__continueStopEditing ) {
						self.__continueStopEditing = false;
						$newLi.off( lia.prefixedEvent( 'remove' ) );
						return;
					}

					if ( !dropValue ) {
						event.preventDefault();
					} else {
						lv.element.removeClass( 'wb-error' );
					}

					if( !dropValue && newSnak ) {
						// temporary claim that is just used for saving; claimview will create its
						// own claim object after saving has been successful
						var reference = new wb.Reference( newSnak );

						// TODO: add newly created claim to model of represented entity!

						var api = new wb.RepoApi(),
							snaks = reference.getSnaks(),
							statementGuid = lv.option( 'statementGuid' );

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

								lia.liInstance( $( event.target ) ).stopEditing( dropValue );

							// Replace the new list item which was initially initialized as empty by
							// destroying it and adding a new list item with the value provided by the API.
								lv.removeItem( $newLi );
								lv.addItem( newRefWithHash ); // display new reference with final hash
						} )
						.fail( function( errorCode, details ) {
							var error = wb.RepoApiError.newFromApiResponse(
									errorCode, details, 'save'
								);

							liInstance.enable();
							liInstance.element.addClass( 'wb-error' );

							liInstance._trigger( 'toggleerror', null, [error] );
						} );
					}
				} )
				.on( lia.prefixedEvent( 'afterstopediting' ), function( event, dropValue ) {
					if( dropValue || !liInstance.$mainSnak.data( 'snakview' ).snak() ) {
						lv.removeItem( $newLi );

						lv.element.find( '.wb-claim-section' )
							.not( ':has( >.wb-edit )' ).removeClass( 'wb-edit' );
					}
				} );

			} );

			// Forward query for listview reference.
			this.$references.data(
				'listview',
				this.$references.find( '.wb-snaklistview-listview' ).data( 'listview' )
			);
			// Simulate $.ui.Widget create call. (Will become obsolete as soon as referenceview's
			// inheritance is resolved.)
			this.$references.children( '.wb-snaklistview' ).trigger( 'snaklistviewcreate' );

			// Collapse references if there is at least one.
			if ( this.$references.data( 'listview' ).items().length > 0 ) {
				this.$references.css( 'display', 'none' );
			}

			// toggle for references section:
			var $toggle = wb.utilities.ui.buildToggle(
				mw.msg( // will be overwritten by drawReferencesCounter() immediately!
					'wikibase-statementview-referencesheading-pendingcountersubject',
					refs.length ),
				this.$references
			);
			this.$refsHeading.append( $toggle );

			// replace heading with nice counter:
			this.drawReferencesCounter();
		} else {
			this.element.addClass( this.widgetBaseClass + '-new' );
		}
	},

	/**
	 * @see $.widget.destroy
	 */
	destroy: function() {
		this.element.removeClass( this.widgetBaseClass + '-new' );
		this.element.removeClass( 'wb-claimview' );
		PARENT.prototype.destroy.call( this );
	},

	/**
	 * Returns the current Statement represented by the view. If null is returned, than this is a
	 * fresh view where a new Statement is being constructed.
	 *
	 * @since 0.4
	 *
	 * @return {wb.Statement|null}
	 */
	value: function() {
		var claim = this._claim;

		if( !claim ) {
			return null;
		}
		if( !( claim instanceof wb.Statement ) ) {
			return new wb.Statement( claim.getMainSnak(), [], [], 0, claim.getGuid() );
		}
		return claim;
	},

	/**
	 * Will update the references counter in the DOM.
	 *
	 * @since 0.4
	 */
	drawReferencesCounter: function() {
		var listView = this.$references.data( 'listview' ),
			numberOfValues = listView.nonEmptyItems().length,
			numberOfPendingValues = listView.items().length - numberOfValues;

		// build a nice counter, displaying fixed and pending values:
		var $counterMsg = wb.utilities.ui.buildPendingCounter(
			numberOfValues,
			numberOfPendingValues,
			'wikibase-statementview-referencesheading-pendingcountersubject',
			'wikibase-statementview-referencesheading-pendingcountertooltip' );

		// update counter, don't touch the toggle!
		this.$refsHeading.find( '.wb-toggle-label' ).empty().append( $counterMsg );
	}
} );

// Register toolbars:
$.wikibase.toolbarcontroller.definition( 'edittoolbar', {
	widget: {
		name: 'wikibase.statementview',
		prototype: $.wikibase.statementview.prototype
	},
	options: {
		interactionWidgetName: $.wikibase.statementview.prototype.widgetName,
		toolbarParentSelector: '.wb-statement-claim .wb-claim-toolbar'
	}
} );

$.wikibase.toolbarcontroller.definition( 'addtoolbar', {
	id: 'references',
	selector: '.wb-statement-references-container',
	eventPrefix: 'snaklistview',
	baseClass: 'wb-referenceview',
	options: {
		toolbarParentSelector: '.wb-statement-references .wb-snaklistview > .wb-listview-toolbar',
		customAction: function( event, $parent ) {
			var statementView = $parent.closest( '.wb-statementview' ).data( 'statementview' );
			statementView.$references.data( 'listview' ).enterNewItem();
		},
		eventPrefix: $.wikibase.statementview.prototype.widgetEventPrefix,
		addButtonLabel: mw.msg( 'wikibase-addreference' )
	}
} );

}( mediaWiki, wikibase, jQuery ) );
