( function () {
	'use strict';

	require( './jquery.wikibase.singlebuttontoolbar.js' );

	var PARENT = $.wikibase.singlebuttontoolbar;

	/**
	 * "Remove" toolbar widget by default offering a "remove" button.
	 *
	 * @class jQuery.wikibase.removetoolbar
	 * @extends jQuery.wikibase.singlebuttontoolbar
	 * @license GPL-2.0-or-later
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 *
	 * @param {Object} [options]
	 * @param {string} [options.label=mw.msg( 'wikibase-remove' )]
	 * @param {string} [options.eventName='remove']
	 * @param {string} [buttonCssClassSuffix='remove']
	 */
	$.widget( 'wikibase.removetoolbar', PARENT, {
		/**
		 * @see inheritdoc
		 */
		options: {
			label: mw.msg( 'wikibase-remove' ),
			eventName: 'remove',
			buttonCssClassSuffix: 'remove'
		}

	} );

}() );
