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

		var statement = this.value(),
			refs = statement ? statement.getReferences() : [];

		// only display statement related UI (references) if Claim essentials are defined already!
		if( this.value() ) {
			this.$refsHeading.text( mw.msg( 'wikibase-statementview-referencesheading', refs.length ) );
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
	 * @since 0.3
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
	}
} );

}( mediaWiki, wikibase, jQuery ) );
