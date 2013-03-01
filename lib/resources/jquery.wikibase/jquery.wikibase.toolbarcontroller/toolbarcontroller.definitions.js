/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, $ ) {
	'use strict';

	var MODULE = $.wikibase.toolbarcontroller;

	/**
	 * Toolbar definitions
	 * @type {Object}
	 */
	var toolbarDefinitions = {};

	/**
	 * Registers/Gets a toolbar definition.
	 *
	 * The simplest way to specify a toolbar definition is to reference a widget that interfaces
	 * a certain toolbar by having implemented the methods required by the toolbar type:
	 * $.wikibase.toolbarcontroller.definition(
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
	 * $.wikibase.toolbarcontroller.definition(
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
	 * @since 0.4
	 *
	 * @param {string} type The toolbar type (see options for available types)
	 * @param {Object} toolbarDefinitionOrId Object defining a toolbar that should be set or a
	 *        toolbar id/widget name to get a registered toolbar definition.
	 * @return {Object} Toolbar definition
	 */
	MODULE.definition = function( type, toolbarDefinitionOrId ) {
		if ( typeof toolbarDefinitionOrId === 'string' ) {
			// GET existing definition
			return toolbarDefinitions[type][toolbarDefinitionOrId];
		}
		// SET new definition
		var toolbarDefinition = toolbarDefinitionOrId,
			id = toolbarDefinition.id || toolbarDefinition.widget.name;

		if ( !id ) {
			throw new Error( 'jquery.wikibase.toolbarcontroller: Either an id or a widget ' +
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
	};

}( mediaWiki, wikibase, jQuery ) );
