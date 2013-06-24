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
	 *     widgetName: 'wikibase.claimlistview',// <namespace>.<name> of the referenced widget that
	 *                                          // needs to be able to interface to the toolbar type
	 *     options: { // options passed to the toolbar
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
	 *        A toolbar definition may contain the following attributes:
	 *        - widgetName
	 *          The full name of the widget the toolbar shall interact with. Having defined an
	 *          interaction widget, no other attributes (except fot the options) need to be defined.
	 *        - options
	 *          Options passed to the toolbar widget.
	 *        - id
	 *          The toolbar definition's id which can be used to initialize the toolbar defined by
	 *          the definition.
	 *          If an interaction widget is defined, the id the widget's name (without the
	 *          namespace) is used as id.
	 *        - selector
	 *          The selector to locate the node the toolbar shall be initialized on.
	 *          If an interaction widget is set, the widget selector will be used.
	 *        - eventPrefix
	 *          Prefix the events the toolbar shall listen to will be prefixed with. This only
	 *          refers to the "create" event that should trigger the toolbar creation.
	 *          If an interaction widget is defined, the widget's event prefix is used as event
	 *          prefix.
	 *        - baseClass
	 *          The base class is used to detect whether the toolbar shall be initialized in a
	 *          "pending" state (e.g. without a remove link). It refers to the css class of the
	 *          toolbar parent's object.
	 *          If an interaction widget is defined, the widget's base class is used as base class.
	 *        - events
	 *          An object containing custom events to react on:
	 *          {
	 *            <{string} unprefixed event name>: <{string|function} keyword or function>[,
	 *            ...]
	 *          }
	 *          Basically, keywords are placeholders for default functions: "create" will create the
	 *          toolbar, "destroy" will destroy it.
	 *          Event parameters:
	 *          (1) {jQuery.Event} Event
	 *          (2) {jQuery} Node the toolbar has be initialized on
	 *          Example:
	 *          {
	 *            startediting: 'create',
	 *            afterstopediting: 'destroy',
	 *            change: function( event ) {
	 *              var referenceView = $( event.target ).data( 'referenceview' ),
	 *              addToolbar = $( event.target ).data( 'addtoolbar' );
	 *              if ( toolbar ) {
	 *                addToolbar.toolbar[referenceView.isValid() ? 'enable' : 'disable']();
	 *              }
	 *            }
	 *          }
	 * @return {Object} Toolbar definition
	 */
	MODULE.definition = function( type, toolbarDefinitionOrId ) {
		if ( typeof toolbarDefinitionOrId === 'string' ) {
			// GET existing definition
			return toolbarDefinitions[type][toolbarDefinitionOrId];
		}
		// SET new definition
		var toolbarDefinition = toolbarDefinitionOrId,
			id = toolbarDefinition.id || toolbarDefinition.widgetName;

		if ( !id ) {
			throw new Error( 'jquery.wikibase.toolbarcontroller: Either an id or a widget ' +
				'name is necessary to register a toolbar' );
		}

		if ( toolbarDefinition.widgetName ) {
			var name = toolbarDefinition.widgetName,
				widget = {};

			widget.namespace = name.split( '.' )[ 0 ];
			widget.name = name.split( '.' )[ 1 ];
			widget.fullName = widget.namespace + '-' + widget.name;
			widget.prototype = $[ widget.namespace ][ widget.name ].prototype;
			toolbarDefinition.widget = widget;

			id = widget.name;
		}

		if ( !toolbarDefinitions[type] ) {
			toolbarDefinitions[type] = {};
		}

		toolbarDefinitions[type][id] = toolbarDefinition;

		return toolbarDefinition;
	};

}( mediaWiki, wikibase, jQuery ) );
