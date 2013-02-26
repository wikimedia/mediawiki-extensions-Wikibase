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
	 * Toolbar definitions
	 * @type {Object}
	 */
	var toolbarDefinitions = {};

	/**
	 * Toolbar controller widget
	 * @since 0.4
	 *
	 * The toolbar controller initializes and manages toolbar widgets. Toolbar definitions are
	 * registered via the definition() method. When initializing the toolbar controller, the ids
	 * or widget names of the registered toolbar definitions that the controller shall initialize
	 * are passed as options.
	 * The simplest way to specify a toolbar definition is to reference a widget that interfaces
	 * a certain toolbar by having implemented the methods required by the toolbar type:
	 * $.wikibase.toolbarcontroller.prototype.definition(
	 *   'addtoolbar', // the toolbar type
	 *   {
	 *     widget: { // the referenced widget that needs to be able to interface to the toolbar type
	 *       name: 'wikibase.claimlistview', // <namespace>.<name> of the widget
	 *       prototype: $.wikibase.claimlistview.prototype
	 *     },
	 *     options: { // options passed to the toolbar
	 *       interactionWidgetName: $.wikibase.claimlistview.prototype.widgetName,
	 *       toolbarParentSelector: '.wb-claims-toolbar'
	 *     }
	 *   }
	 * );
	 * A toolbar may also be defined on a plain jQuery node which requires specifying some
	 * information that would have been extracted from the widget:
	 * $.wikibase.toolbarcontroller.prototype.definition(
	 *   'addtoolbar',
	 *   {
	 *     id: 'claimsection',
	 *     selector: '.wb-claim-section', // selector to access the node from the toolbar
	 *                                    // controller's node
	 *     eventPrefix: 'claimsection',
	 *     baseClass: widgetPrototype.widgetBaseClass,
	 *     options: { // options passed to the toolbar
	 *       toolbarParentSelector: '.wb-claim-add .wb-claim-toolbar',
	 *       customAction: function( event, $parent ) {
	 *         $parent.closest( '.wb-claimlistview' ).data( 'claimlistview' )
	 *         .enterNewClaimInSection( $parent.data( 'wb-propertyId' ) );
	 *       },
	 *       eventPrefix: widgetPrototype.widgetEventPrefix
	 *     }
	 *   }
	 * );
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
		 * Registers/Gets a toolbar definition.
		 * @since 0.4
		 *
		 * @param {string} type The toolbar type (see options for available types)
		 * @param {Object} toolbarDefinition Object defining a toolbar that should be set or a
		 *        toolbar id/widget name to get a registered toolbar definition
		 * @return {Object} Toolbar definition
		 */
		definition: function( type, toolbarDefinition ) {
			if ( typeof toolbarDefinition === 'string' ) {
				return toolbarDefinitions[type][toolbarDefinition];
			} else {
				var id = toolbarDefinition.id || toolbarDefinition.widget.name;

				if ( !id ) {
					throw Error( 'jquery.wikibase.toolbarcontroller: Either an id or a widget ' +
						'name is necessary to register a toolbar' );
				}

				if ( toolbarDefinition.widget ) {
					var widget = toolbarDefinition.widget;
					widget.namespace = widget.name.split( '.' )[ 0 ];
					widget.name = widget.name.split( '.' )[ 1 ];
					widget.fullName = widget.namespace + '-' + widget.name;
					id = widget.prototype.widgetName;
				}

				if ( !toolbarDefinitions[type] ) {
					toolbarDefinitions[type] = {};
				}

				toolbarDefinitions[type][id] = toolbarDefinition;

				return toolbarDefinition;
			}
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
					var def = self.definition( type, id );
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
					var def = self.definition( type, id ),
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
