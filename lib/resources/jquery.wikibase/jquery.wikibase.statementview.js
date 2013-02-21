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
	widgetName: 'wikibase-statementview',
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

			this.$references.listview( {
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
			} ).on( 'listviewitemadded listitemremoved', function( e, value, $li ) {
				self.drawReferencesCounter();
			} );

			this.$references.addtoolbar( {
				interactionWidgetName: 'listview',
				toolbarParentSelector: '.wb-listview-toolbar',
				addButtonLabel: mw.msg( 'wikibase-addreference' )
			} );

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
		}
	},

	/**
	 * @see $.widget.destroy
	 */
	destroy: function() {
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

}( mediaWiki, wikibase, jQuery ) );
