/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function( $ ) {
	'use strict';

	var PARENT =  $.ui.TemplatedWidget;

/**
 * TemplatedWidget enhanced with editing capabilities.
 * @constructor
 * @abstract
 * @extends jQuery.ui.TemplatedWidget
 * @since 0.5
 *
 * @option {*} [value]
 *
 * @event afterstartediting
 *        Triggered after having started the widget's edit mode and edit mode has been rendered.
 *        - {jQuery.Event}
 *
 * @event stopediting
 *        Triggered when stopping the widget's edit mode, immediately before re-drawing.
 *        - {jQuery.Event}
 *        - {boolean} dropValue
 *          Whether the widget's value will be reset to the one from before starting edit mode.
 *
 * @event afterstopediting
 *        Triggered after having stopped the widget's edit mode and non-edit mode is redrawn.
 *        - {jQuery.Event}
 *        - {boolean} dropValue
 *          Whether the widget's value has been reset to the one from before starting edit mode.
 *
 * @event change
 *        Triggered whenever the widget's value is changed.
 *        - {jQuery.Event} event
 *
 * @event toggleerror
 *        Triggered when an error occurred or has been resolved.
 *        - {jQuery.Event}
 *        - {Error|undefined}
 */
$.widget( 'ui.EditableTemplatedWidget', PARENT, {
	/**
	 * @see jQuery.ui.TemplatedWidget.options
	 */
	options: $.extend( true, {}, PARENT.prototype.options, {
		value: null
	} ),

	/**
	 * @see jQuery.ui.TemplatedWidget._create
	 */
	_create: function() {
		this.element.data( 'EditableTemplatedWidget', this );
		PARENT.prototype._create.call( this );
	},

	/**
	 * @see jQuery.ui.TemplatedWidget.destroy
	 */
	destroy: function() {
		this.element.removeClass( 'wb-edit' );
		PARENT.prototype.destroy.call( this );
	},

	/**
	 * Draws the widget according to whether it is in edit mode or not.
	 *
	 * @return {Object} jQuery.Promise
	 *         No resolved parameters.
	 *         Rejected parameters:
	 *         - {Error}
	 */
	draw: util.abstractMember,

	/**
	 * Starts the widget's edit mode.
	 *
	 * @return {Object} jQuery.Promise
	 *         No resolved parameters.
	 *         Rejected parameters:
	 *         - {Error}
	 */
	startEditing: function() {
		var deferred = $.Deferred();

		if ( this.isInEditMode() ) {
			return deferred.resolve().promise();
		}

		var self = this;

		self.element.addClass( 'wb-edit' );

		this.draw()
		.done( function() {
			self._trigger( 'afterstartediting' );
			deferred.resolve();
		} )
		.fail( function( error ) {
			deferred.reject( error );
		} );

		return deferred.promise();
	},

	/**
	 * Stops the widget's edit mode.
	 *
	 * @param {boolean} dropValue
	 * @return {Object} jQuery.Promise
	 *         Resolved parameters:
	 *         - {boolean} dropValue
	 *         Rejected parameters:
	 *         - {Error}
	 */
	stopEditing: function( dropValue ) {
		var done = $.Deferred().resolve().promise();
		if ( !this.isInEditMode() ) {
			return done;
		}
		this._trigger( 'stopediting', null, [dropValue] );
		this._afterStopEditing( dropValue );
		return done;
	},

	/**
	 * @param {boolean} dropValue
	 * @return {Object} jQuery.Promise
	 *         No resolved parameters.
	 *         Rejected parameters:
	 *         - {Error}
	 */
	_afterStopEditing: function( dropValue ) {
		var self = this,
			deferred = $.Deferred();

		self.element.removeClass( 'wb-edit' );

		this.draw()
		.done( function() {
			self.enable();
			self._trigger( 'afterstopediting', null, [dropValue] );
			deferred.resolve();
		} )
		.fail( function( error ) {
			self.setError( error );
			deferred.reject( error );
		} );

		return deferred.promise();
	},

	/**
	 * Returns whether the widget is in edit mode.
	 */
	isInEditMode: function() {
		return this.element.hasClass( 'wb-edit' );
	},

	/**
	 * Sets/Gets the widget's current value.
	 * When the widget is in edit mode, this.option( 'value' ) may be used to retrieve the widget's
	 * value from before edit mode has been started.
	 *
	 * @param {*} [value]
	 * @return {*|undefined}
	 */
	value: util.abstractMember,

	/**
	 * Toggles error state.
	 *
	 * @param {Error} [error]
	 */
	setError: function( error ) {
		if ( error ) {
			this.element.addClass( 'wb-error' );
			this._trigger( 'toggleerror', null, [error] );
		} else {
			this.removeError();
			this._trigger( 'toggleerror', null, [null] );
		}
	},

	/**
	 * Removes error state without triggering an event.
	 */
	removeError: function() {
		this.element.removeClass( 'wb-error' );
	},

	/**
	 * Sets or removes notification.
	 *
	 * @param {jQuery} [$content]
	 * @param {string} [additionalCssClasses]
	 * @return {jQuery|null}
	 */
	notification: function( $content, additionalCssClasses ) {
		if ( !this._$notification ) {
			this._$notification = $( '<div/>' ).closeable( {
				encapsulate: true
			} );
		}
		this._$notification.data( 'closeable' ).setContent( $content, additionalCssClasses );
		return this._$notification;
	},

	/**
	 * Get a help message related to editing
	 *
	 * @return {Object} jQuery promise
	 *         Resolved parameters:
	 *         - {string}
	 *         No rejected parameters.
	 */
	getHelpMessage: function() {
		return $.Deferred().resolve( this.options.helpMessage ).promise();
	},

	/**
	 * @var {null|Function} A function notifying about an error or null if the toolbar should notify
	 */
	doErrorNotification: null
} );

}( jQuery ) );
