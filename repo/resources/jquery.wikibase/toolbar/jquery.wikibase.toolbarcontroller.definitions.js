/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $ ) {
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
	 * Toolbar definitions specify when and where to create and destroy toolbar widgets
	 * programmatically. In addition, definitions may also specify events the toolbars listens to
	 * on the nodes they are initialized on.
	 *
	 * @since 0.4
	 *
	 * @param {string} type The toolbar type (see toolbarcontroller options for available types).
	 * @param {Object|string} definitionOrId Object defining a toolbar that should be set or the id
	 *        of a toolbar definition that should be retrieved.
	 *        A toolbar definition has to contain the following attributes:
	 *        - {string} id
	 *          The toolbar definition's id which can be used to initialize the toolbar by passing
	 *          the id to the toolbarcontroller on initialization.
	 *        - {string|null} [selector]
	 *          The selector to locate the node the toolbar shall be initialized on. If null or
	 *          omitted, the event is expected to being triggered on the node the toolbar controller
	 *          is initialized on.
	 *        - {Object} events
	 *          An object containing custom events to react on keyed by one or more prefixed event
	 *          names (separated by a space). The assigned functions receive the following
	 *          parameters:
	 *          (1) {jQuery.Event} The original event object.
	 *              The event's data attribute is filled with an object containing information about
	 *              the registered toolbar:
	 *              { id: {string} <Toolbar id>, type: {string} <Toolbar type> }
	 *          (2) {jquery.wikibase.toolbarcontroller} The toolbarcontroller instance.
	 *          Since registering events is critical in terms of performance, those initially
	 *          defined event handlers should be limited to a minimum and dynamic event registration
	 *          using the toolbarcontroller's registerEventHandler() function should be preferred.
	 *        Toolbar definition structure:
	 *          {
	 *            id: <{string}>
	 *            [selector: <{string}>]
	 *            events: {
	 *              <{string} prefixed event name(s)>: <{Function} event handler>[,
	 *              ...]
	 *            }
	 *          }
	 * @return {Object|null} Toolbar definition or null if there is no definition with the given ID.
	 */
	MODULE.definition = function( type, definitionOrId ) {
		if ( typeof definitionOrId === 'string' ) {
			// GET existing definition
			return toolbarDefinitions[type] && toolbarDefinitions[type][definitionOrId]
				|| null;
		}

		if( !definitionOrId.id || !definitionOrId.events ) {
			throw new Error( 'id and events need to be specified to register a toolbar '
				+ 'definition' );
		} else if( !definitionOrId.selector ) {
			definitionOrId.selector = null;
		}

		if ( !toolbarDefinitions[type] ) {
			toolbarDefinitions[type] = {};
		}

		toolbarDefinitions[type][definitionOrId.id] = definitionOrId;

		return definitionOrId;
	};

}( jQuery ) );
