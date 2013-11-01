/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, $ ) {
	'use strict';

	/**
	 * Available toolbar types.
	 * TODO: create a registry for allowing adding additional toolbar types
	 *
	 * @type {string[]}
	 */
	var TOOLBAR_TYPES = ['addtoolbar', 'edittoolbar', 'removetoolbar', 'movetoolbar'];

	/**
	 * Toolbar controller widget
	 *
	 * The toolbar controller initializes and manages toolbar widgets. Toolbar definitions are
	 * registered via the jQuery.toolbarcontroller.definition() method. When initializing the
	 * toolbar controller, the ids of the registered toolbar definitions that the controller shall
	 * initialize are passed as options.
	 * The toolbar controller does not parse the existing DOM structure when being initialized, it
	 * listens to the events registered by a toolbar definition. In order to have the desired
	 * toolbars created by the controller, it needs to be initialized on some parent node of the DOM
	 * structure it should manage toolbars in before the events defined in toolbar definitions are
	 * triggered (e.g. before a widget, a toolbar shall interact with, is created).
	 *
	 * @since 0.4
	 *
	 * @option addtoolbar {string[]} List of toolbar definition ids/widget names that are registered
	 *         as "add" toolbars and shall be initialized.
	 *         Default: []
	 *
	 * @option edittoolbar {string[]} List of toolbar definition ids/widget names that are
	 *         registered as "edit" toolbars and shall be initialized.
	 *         Default: []
	 *
	 * @option removetoolbar {string[]} List of toolbar definition ids/widget names that are
	 *         registered as "remove" toolbars and shall be initialized.
	 *         Default: []
	 *
	 * @option movetoolbar {string[]} List of toolbar definition ids/widget names that are
	 *         registered as "move" toolbars and shall be initialized.
	 *         Default: []
	 */
	$.widget( 'wikibase.toolbarcontroller', {
		/**
		 * Options
		 * @type {Object}
		 */
		options: {
			addtoolbar: [],
			edittoolbar: [],
			removetoolbar: [],
			movetoolbar: []
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
		 *
		 * @since 0.4
		 * @param {boolean} [isPending] Whether element that triggered the toolbar
		 *        (re-)initialization is in a pending state.
		 *
		 * @throws {Error} in case a given toolbar ID is not registered for the toolbar type given.
		 * @throws {Error} if the callback provided in an event definition is not a function.
		 */
		initToolbars: function( isPending ) {
			var self = this;

			$.each( TOOLBAR_TYPES, function( i, type ) {
				$.each( self.options[type], function( j, id ) {
					var def = $.wikibase.toolbarcontroller.definition( type, id );
					if( !def ) {
						throw new Error( 'Missing toolbar controller definition for "' + id + '"' );
					}

					var options = type === 'edittoolbar' && isPending ?
						$.extend( {}, def.options, { enableRemove: !isPending } )
						: def.options;

					// The node the toolbar shall be initialized on.
					var $initNode = self.element.find( def.selector || ':' + def.widget.fullName );

					if ( def.events ) {
						// Detach all event handlers first in order to not end up with having the
						// handler attached multiple times. This cannot be done along with
						// re-attaching the handlers since multiple event handlers may be registered
						// for the same event.
						// The namespace needs to be very specific since instances of the the same
						// toolbar may listen to the same event(s) on the same node.
						$.each( def.events, function( eventNames, callback ) {
							var namespacedEvents = assignNamespaces(
								eventNames, [self.widgetName, self.widgetName + type + id]
							);
							$initNode.off( namespacedEvents );
						} );

						// Attach event handlers for toolbars that shall be created upon certain
						// events:
						$.each( def.events, function( eventNames, callback ) {
							var namespacedEvents = assignNamespaces(
								eventNames, [self.widgetName, self.widgetName + type + id]
							);

							if( !$.isFunction( callback ) ) {
								throw new Error( 'No callback or known default action given for '
									+ 'event "' + eventNames + '"' );
							}

							self.element.on( namespacedEvents, function( event ) {
								var callbacks = self._findCallbacks( event.type );
								if( callbacks ) {
									for( var i = 0; i < callbacks.length; i++ ) {
										callbacks[i]( event, $( event.target ) );
									}
								}
							} );

						} );
					}

				} );
			} );

		},

		/**
		 * Finds the event handler callback(s) for a certain event type.
		 * @since 0.5
		 *
		 * @param {string} eventType
		 * @return {Function[]|null}
		 */
		_findCallbacks: function( eventType ) {
			var self = this,
				callbacks = [];

			$.each( TOOLBAR_TYPES, function( i, type ) {
				$.each( self.options[type], function( j, id ) {
					var def = $.wikibase.toolbarcontroller.definition( type, id );

					$.each( def.events, function( eventNames, c ) {
						if( $.inArray( eventType, eventNames.split( ' ' ) ) !== -1 ) {
							callbacks.push( c );
							return false;
						}
					} );
				} );
			} );

			return ( callbacks.length ) ? callbacks : null;
		}

	} );

	/**
	 * Assigns namespaces to event names passed in as a string.
	 * @since 0.4
	 *
	 * @param {string} eventNames
	 * @param {string[]} namespaces
	 * @return {string}
	 */
	function assignNamespaces( eventNames, namespaces ) {
		eventNames = eventNames.split( ' ' );

		// Add an empty string to assign namespaces to the last non-empty event name via join():
		eventNames.push( '' );

		return eventNames.join( '.' + namespaces.join( '.' ) + ' ' );
	}

}( mediaWiki, wikibase, jQuery ) );
