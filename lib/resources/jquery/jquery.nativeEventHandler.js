/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 *
 * Returns a function (outer function) which executes some given logic and does additional event
 * handling for a given event. The event handling is separated in up to three steps and handles
 * advanced features offered by jQuery. The following steps (handlers) in detail:
 *
 * - initial handler: Executes some initial logic which allows to cancel the whole process. All
 *   parameters given to the outer function are available as well as the related jQuery.Event
 *   object. The jQuery.Event object can be
 *   used to cancel all further event handlers (jQuery.Event.cancel()) or to prevent only the
 *   custom handlers to be
 *   executed (jQuery.Event.stopImmediatePropagation() or prevent only the native handler to be
 *   executed (jQuery.Event.preventDefault()). jQuery.Event.handlerArgs can be set to an array
 *   to define all arguments propagated to all handlers if not defined otherwise by one of the
 *   other ...HandlerArgs fields. jQuery.Event.customHandlerArgs can be set to an array to
 *   define all arguments propagated to custom handlers, the jQuery.Event object itself will
 *   always be given to the custom handlers. jQuery.Event.nativeHandlerArgs can be set to an
 *   array which acts as equivalent for the native handler.
 *
 * - custom handlers: These are all the handlers registered to the object with jQuery.fn.on().
 *   If not prevented by the initial handler, they will be executed right after the initial
 *   handler. By default this will get the same arguments as the initial handler, except if the
 *   initial handler has explicitly set jQuery.Event.customHandlerArgs.
 *
 * - native handler: Is the handler which executes the actual logic of the outer function which
 *   should also be what the defined event is all about.
 *
 * The native handler return value will be taken as return value for the outer function. If the
 * native handler never gets called, the return value of the outer function can either be the
 * last return value given by any custom handler (as long as the return value was not undefined)
 * or - if the custom handlers aren't called, if there are no custom handlers registered, if
 * 'allowCustomResult' is set to false or if returning of custom values is not supported by the
 * responsible event handler (which is the case when used within widgets) - the initial
 * handler's return value.
 *
 * The context the handlers are called in is usually the one of the outer function. The only
 * exception is for custom handlers while the system is used within jQuery widgets. In those
 * custom handlers, the context will be the widget's subject DOM node.
 *
 * NOTE: The native handler is available as 'nativeHandler' property of the returned function.
 *       The initial handler is available as 'initialHandler' property (just an empty function
 *       if not provided)
 *
 * @since 0.2
 *
 * @param eventName String
 * @param fn Function|Object if this is a function, then it will be taken as native handler and
 *        will be executed after custom event handlers are executed.
 *        If this is an object, this can hold properties defining the native as well as the
 *        initial handler as well as additional options for changing the event handling
 *        behavior. The following properties can be given (see full description about the
 *        different handlers above):
 *        - initially: should be a function representing the initial handler
 *        - natively: the actual native handler, 'function which makes the event happen'
 *        - allowCustomResult: if set to true, custom handlers result values will be returned
 *          by the outer function if the native handler won't be called (because of
 *          jQuery.preventDefault)
 *
 * @return Function
 *
 * @example
 * <code>
 * // Will focus the element and return true if focus has been set, false if the process failed.
 * // Will trigger 'focus' event if focus isn't set already.
 * SomeConstructor.prototype.focus = $.NativeEventHandler( 'focus', {
 *     // event: jQuery.Event which will be triggered after this if event.stopPropagation() not called
 *     // The other arguments are those who were given to the public, outer focus() function
 *     initially: function( event, highlight, someInternal ) {
 *         if( this.hasFocus() ) {
 *             event.cancel(); // focus is set already, stop everything...
 *             return true; // ... and let the outer focus() function return true
 *         }
 *         event.customHandlerArgs = [ highlight ]; // don't give the someInternal arg to custom event handlers
 *         return false; // will be returned by outer focus() if custom handlers call event.preventDefault()
 *     },
 *     natively: function( event, highlight, somethingInternal ) {
 *         // this will only be called after 'focus' event was called and default wasn't prevented
 *         doSomething();
 *         return true; // final return value for outer focus()
 *     }
 * } )
 * </code>
 */
( function( $  ) {
	'use strict';

	var NEH_OPTIONS = [
		'natively',
		'nativeHandler',
		'initially',
		'initialHandler',
		'allowCustomResult'
	];

	$.NativeEventHandler = function( eventName, fn ) {
		var initialFn = function() {},
			allowCustomResult = false,
			handler;

		if( !$.isFunction( fn ) ) { // not just a native handler but additional callbacks/options
			// check for spelling errors in definition object
			$.each( fn, function( key ) {
				if( $.inArray( key, NEH_OPTIONS ) === -1 ) {
					throw new Error( 'Unknown native event handler option "' + key + '"' );
				}
			} );

			// get options
			allowCustomResult = fn.allowCustomResult !== undefined ?
				fn.allowCustomResult :
				allowCustomResult;

			// get handlers
			initialFn = fn.initially || fn.initialHandler || initialFn;
			fn = fn.natively || fn.nativeHandler;

			// make sure we have a native handler or fail
			if( !$.isFunction( fn ) ) {
				throw new Error( 'No native handler function provided' );
			}
		}

		/**
		 * The returned function handling all the stages of handlers.
		 * 1. initial, 2. custom, 3. native
		 * @return mixed
		 */
		handler = function() {
			var event = $.Event( eventName, {
					handlerArgs: false,
					customHandlerArgs: false,
					nativeHandlerArgs: false,
					cancel: function() {
						event.stopImmediatePropagation();
						event.preventDefault();
					}
				} ),
				args = $( arguments ).toArray(),

				// does all the preparation and can cancel the whole thing
				ret = handler.initialHandler.apply( this, [ event ].concat( args ) ),

				defaultPreventedByWidget = false,
				// store this so custom callbacks can't interfere with internal default prevention:
				defaultPreventedEarly = event.isDefaultPrevented(),
				// allow for different arguments for custom/native event handlers:
				handlerArgs = event.handlerArgs || args,
				customHandlerArgs = event.customHandlerArgs || handlerArgs,
				nativeHandlerArgs = event.nativeHandlerArgs || handlerArgs,
				nativeRet;

			// don't reveal this to custom handlers
			event.handlerArgs = event.customHandlerArgs = event.nativeHandlerArgs = event.cancel = undefined;

			// trigger all registered events (custom handlers)
			// this might be prevented by the initial handler for some reason
			if( !event.isImmediatePropagationStopped() ) {
				if( $.Widget && ( this instanceof $.Widget ) ) {
					// use the $.Widget's native trigger mechanisms.
					// $.Widget._trigger will use its own event but return false if prevented.
					// Also, context of custom handlers will be the DOM node rather than the widget.
					defaultPreventedByWidget = !this._trigger( event.type, null, customHandlerArgs );
					// TODO: attach our own event as some field of the widget's event
				} else {
					// Don't use trigger(); it might end up in an endless loop since it would try to
					// execute a function named after the event in the object
					$( this ).triggerHandler( event, customHandlerArgs );
				}

				// if desired for this event, let custom handlers last return value overwrite
				// initial handlers one.
				if( allowCustomResult && event.result !== undefined ) {
					ret = event.result;
				}
			}

			// initial handler and custom handlers can prevent native event from being executed
			if( !defaultPreventedEarly &&
				( !defaultPreventedByWidget && !event.isDefaultPrevented() )
			) {
				// call native handler for the event
				// give event as first argument just like jQuery does for custom handlers!
				nativeRet = handler.nativeHandler.apply( this, [ event ].concat( nativeHandlerArgs ) );

				// if native handler returns undefined, return previously gathered return value
				ret = nativeRet !== undefined ? nativeRet : ret; // might be the same value
			}

			return ret; // whatever the initial or custom handler(s) returned last (ignoring undefined)
		};

		/**
		 * @var Function
		 *
		 * @param event {jQuery.Event} the event which is about to be triggered
		 * @param args {Array} arguments which will be applied to the native handler as well as to
		 *        the custom callbacks (except if the return value is an array in which case these
		 *        values will be used for custom callbacks).
		 * @return {undefined|Boolean|Array} if undefined or true the native handler as well as all
		 *         custom callbacks will be executed. If false, the whole event will be cancelled.
		 *         If an array is returned, its contents will be applied to the custom callbacks as
		 *         parameters, the native handler will still receive the arguments of the args array.
		 */
		handler.initialHandler = initialFn;

		/**
		 * @var Function
		 * Holds the pure functionality of the native event handler
		 */
		handler.nativeHandler = fn; // for outside world and inheritance

		return handler;
	};

}( jQuery ) );
