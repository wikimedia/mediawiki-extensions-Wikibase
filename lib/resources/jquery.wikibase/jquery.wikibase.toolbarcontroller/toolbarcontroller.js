/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, $ ) {
	'use strict';

	/**
	 * Toolbar controller widget
	 *
	 * The toolbar controller initializes and manages toolbar widgets. Toolbar definitions are
	 * registered via the jQuery.toolbarcontroller.definition() method. When initializing the
	 * toolbar controller, the ids or widget names of the registered toolbar definitions that the
	 * controller shall initialize are passed as options.
	 *
	 * @since 0.4
	 *
	 * @option addtoolbar {string[]} List of toolbar definition ids/widget names that are registered
	 *         as "addtoolbars" and shall be initialized.
	 *         Default: []
	 *
	 * @option edittoolbar {string[]} List of toolbar definition ids/widget names that are
	 *         registered as "edittoolbars" and shall be initialized.
	 *         Default: []
	 */
	$.widget( 'wikibase.toolbarcontroller', {
		/**
		 * Options
		 * @type {Object}
		 */
		options: {
			addtoolbar: [],
			edittoolbar: []
		},

		/**
		 * @see jQuery.Widget._create
		 */
		_create: function() {
			this.initToolbars();
		},

		/**
		 * Initializes the toolbars for the nodes that are descendants of the node the toolbar
		 * controller is initialized on.
		 * @since 0.4
		 */
		initToolbars: function() {
			var self = this;

			$.each( ['addtoolbar', 'edittoolbar'], function( i, type ) {
				$.each( self.options[type], function( j, id ) {
					var def = $.wikibase.toolbarcontroller.definition( type, id );
					self.element
					.find( def.selector || ':' + def.widget.fullName )[type]( def.options );
				} );
			} );

			this.initEventListeners();
		},

		/**
		 * Initializes event listeners for all defined toolbars.
		 * @since 0.4
		 */
		initEventListeners: function() {
			var self = this;

			this.element.off( '.' + this.widgetName );

			$.each( ['addtoolbar', 'edittoolbar'], function( i, type ) {
				$.each( self.options[type], function( j, id ) {
					var def = $.wikibase.toolbarcontroller.definition( type, id ),
						eventPrefix = def.eventPrefix || def.widget.prototype.widgetEventPrefix,
						baseClass = def.baseClass || def.widget.prototype.widgetBaseClass;

					self.element.on( eventPrefix + 'create.' + self.widgetName, function( event ) {
						if ( type === 'addtoolbar' ) {
							self.initToolbars();
						} else {
							var $target = $( event.target ),
								isPending = $target.hasClass( baseClass + '-new' )
									|| $target.find( baseClass + '-new' ).length > 0;

							$( event.target ).edittoolbar(
								$.extend( {}, def.options, { enableRemove: !isPending } )
							);
						}
					} );

				} );
			} );

		}

	} );

}( mediaWiki, wikibase, jQuery ) );
