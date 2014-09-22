/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, $ ) {
	'use strict';

var PARENT = $.wikibase.singlebuttontoolbar;

/**
 * "Add" toolbar widget by default offering an "add" button.
 * @since 0.4
 * @extends jQuery.wikibase.singlebuttontoolbar
 *
 * @option {string} [label]
 *         Default: mw.msg( 'wikibase-add' )
 *
 * @option {string} [eventName]
 *         Default: 'add'
 */
$.widget( 'wikibase.addtoolbar', PARENT, {
	/**
	 * @see jQuery.wikibase.singlebuttontoolbar.options
	 */
	options: {
		label: mw.msg( 'wikibase-add' ),
		eventName: 'add'
	}
} );

}( mediaWiki, jQuery ) );
