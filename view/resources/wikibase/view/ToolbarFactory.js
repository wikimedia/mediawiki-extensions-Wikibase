( function () {
	'use strict';

	require( '../../jquery/wikibase/toolbar/jquery.wikibase.addtoolbar.js' );
	require( '../../jquery/wikibase/toolbar/jquery.wikibase.edittoolbar.js' );
	require( '../../jquery/wikibase/toolbar/jquery.wikibase.removetoolbar.js' );

	/**
	 * A factory for creating toolbars
	 *
	 * @class wikibase.view.ToolbarFactory
	 * @license GPL-2.0-or-later
	 * @author Adrian Heine <adrian.heine@wikimedia.de>
	 * @constructor
	 */
	var SELF = wikibase.view.ToolbarFactory = function ToolbarFactory() {};

	/**
	 * Create a addtoolbar
	 *
	 * @param {Object} options
	 * @param {jQuery} $dom
	 * @return {jQuery.wikibase.addtoolbar} The addtoolbar
	 */
	SELF.prototype.getAddToolbar = function ( options, $dom ) {
		return this._getToolbar( 'add', $dom, options );
	};

	/**
	 * Create an edittoolbar
	 *
	 * @param {Object} options
	 * @param {jQuery} $dom
	 * @return {jQuery.wikibase.edittoolbar} The edittoolbar
	 */
	SELF.prototype.getEditToolbar = function ( options, $dom ) {
		return this._getToolbar( 'edit', $dom, options );
	};

	/**
	 * Create a removetoolbar
	 *
	 * @param {Object} options
	 * @param {jQuery} $dom
	 * @return {jQuery.wikibase.removetoolbar} The removetoolbar
	 */
	SELF.prototype.getRemoveToolbar = function ( options, $dom ) {
		return this._getToolbar( 'remove', $dom, options );
	};

	/**
	 * Find or append a toolbar container
	 *
	 * @param {jQuery} $root
	 * @return {jQuery} The toolbar container
	 */
	SELF.prototype.getToolbarContainer = function ( $root ) {
		var $container = $root.children( '.wikibase-toolbar-container' ).first();
		if ( $container.length === 0 ) {
			$container = $( '<div>' ).appendTo( $root );
		}
		return $container;
	};

	/**
	 * @private
	 * @return {Object} The constructed toolbar
	 * @throws {Error} If there is no toolbar with the given name
	 */
	SELF.prototype._getToolbar = function ( toolbarType, $dom, options ) {
		var toolbarName = toolbarType + 'toolbar';
		if ( !$.wikibase[ toolbarName ] ) {
			throw new Error( 'Toolbar ' + toolbarName + ' does not exist' );
		}

		$dom[ toolbarName ]( options );

		return $dom.data( toolbarName );
	};

	module.exports = SELF;

}( wikibase ) );
