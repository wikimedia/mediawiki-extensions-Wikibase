( function( $, wb ) {
	'use strict';

	var MODULE = wb.view;

	/**
	 * A factory for creating toolbars
	 *
	 * @class wikibase.view.ToolbarFactory
	 * @license GPL-2.0+
	 * @since 0.5
	 * @author Adrian Heine <adrian.heine@wikimedia.de>
	 * @constructor
	 */
	var SELF = MODULE.ToolbarFactory = function ToolbarFactory() {};

	/**
	 * Create an edittoolbar
	 *
	 * @param {Object} options
	 * @param {jQuery} $dom
	 * @return {jQuery.wikibase.edittoolbar} The edittoolbar
	 **/
	SELF.prototype.getEditToolbar = function( options, $dom ) {
		return this._getToolbar( 'edit', $dom, options );
	};

	/**
	 * Find or append a toolbar container
	 *
	 * @param {jQuery} $root
	 * @return {jQuery} The toolbar container
	 **/
	SELF.prototype.getToolbarContainer = function( $root ) {
		var $container = $root.children( '.wikibase-toolbar-container' ).first();
		if ( $container.length === 0 ) {
			$container = $( '<div/>' ).appendTo( $root );
		}
		return $container;
	};

	/**
	 * @private
	 * @return {Object} The constructed toolbar
	 * @throws {Error} If there is no toolbar with the given name
	 **/
	SELF.prototype._getToolbar = function( toolbarType, $dom, options ) {
		var toolbarName = toolbarType + 'toolbar';
		if ( !$.wikibase[ toolbarName ] ) {
			throw new Error( 'Toolbar ' + toolbarName + ' does not exist' );
		}

		$dom[ toolbarName ]( options );

		return $dom.data( toolbarName );
	};

}( jQuery, wikibase ) );
