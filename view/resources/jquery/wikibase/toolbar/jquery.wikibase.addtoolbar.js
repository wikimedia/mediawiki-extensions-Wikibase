/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function () {
	'use strict';

	require( './jquery.wikibase.singlebuttontoolbar.js' );

	var PARENT = $.wikibase.singlebuttontoolbar;

	/**
	 * "Add" toolbar widget by default offering an "add" button.
	 *
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
			eventName: 'add',
			buttonCssClassSuffix: 'add'
		}
	} );

}() );
