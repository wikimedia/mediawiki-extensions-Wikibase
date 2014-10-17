/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, $ ) {
	'use strict';

var PARENT = $.wikibase.singlebuttontoolbar;

/**
 * "Remove" toolbar widget by default offering a "remove" button.
 * @since 0.4
 * @extends jQuery.wikibase.singlebuttontoolbar
 *
 * @option {string} [label]
 *         Default: mw.msg( 'wikibase-remove' )
 *
 * @option {string} [eventName]
 *         Default: 'remove'
 */
$.widget( 'wikibase.removetoolbar', PARENT, {
	/**
	 * @see jQuery.wikibase.singlebuttontoolbar.options
	 */
	options: {
		label: mw.msg( 'wikibase-remove' ),
		eventName: 'remove',
		buttonCssClassSuffix: 'remove'
	}
} );

}( mediaWiki, jQuery ) );
