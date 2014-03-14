/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, vv, time ) {
	'use strict';

	var Time = time.Time,
		timeSettings = time.settings;

	var PARENT = vv.Expert;

	/**
	 * Valueview expert handling input of time values.
	 *
	 * @since 0.1
	 *
	 * @constructor
	 * @extends jQuery.valueview.Expert
	 *
	 * @option {Object} messages Default messages used by the widget. The keys correspond to
	 *         mediaWiki message keys since these will be picked when in MediaWiki environment and
	 *         the mediaWiki JavaScript object has been passed to the expert constructor.
	 */
	vv.experts.TimeInput = vv.expert( 'TimeInput', PARENT, {

		/**
		 * Default options
		 * @type {Object}
		 */
		_options: {
			messages: {
				'valueview-expert-timeinput-precision': 'Precision',
				'valueview-expert-timeinput-calendar': 'Calendar',
				'valueview-expert-advancedadjustments': 'advanced adjustments'
			}
		},

		/**
		 * The the input element's node.
		 * @type {jQuery}
		 */
		$input: null,

		/**
		 * Caches a new value (or null for no value) set by _setRawValue() until draw() displaying
		 * the new value has been called. The use of this, basically, is a structural improvement
		 * which allows moving setting the displayed value to the draw() method which is supposed to
		 * handle all visual manners.
		 * @type {string|null|false}
		 */
		_newValue: null,

		/**
		 * The preview widget.
		 * @type {jQuery.ui.preview}
		 */
		preview: null,

		/**
		 * Container node for precision input and label.
		 * @type {jQuery}
		 */
		$precisionContainer: null,

		/**
		 * Node of the widget used to specify the precision.
		 * @type {jQuery}
		 */
		$precision: null,

		/**
		 * Container node for calendar input and label.
		 * @type {jQuery}
		 */
		$calendarContainer: null,

		/**
		 * Node of the widget used to specify the calendar.
		 * @type {jQuery}
		 */
		$calendar: null,

		/**
		 * Node of the hint giving information about the automatically selected calendar.
		 * @type {jQuery}
		 */
		$calendarhint: null,

		/**
		 * @see jQuery.valueview.Expert._init
		 */
		_init: function() {
			var self = this;

			this.$input = $( '<input/>', {
				type: 'text',
				'class': this.uiBaseClass + '-input valueview-input'
			} )
			.appendTo( this.$viewPort )
			.inputextender( {
				initCallback: function( $extension ) {
					self._initInputExtender( $extension );
					// $extension not yet in DOM, so draw() would not update rotators. Call draw
					// as soon as toggle has been done, from this point on the inputextender's
					// extension() is available in draw().
					self.$input.one( 'inputextenderaftertoggle', function( event ) {
						self.draw();
					} );
				},
				contentAnimationEvents: 'toggleranimation'
			} )
			.on( 'eachchange', function( event, oldValue ) {
				self._viewNotifier.notify( 'change' );
			} );
		},

		/**
		 * Initializes the input extender with the required content into the given DOM element.
		 *
		 * TODO: Split this up. Share code with similar experts (GlobeCoordinate).
		 *
		 * @param {jQuery} $extension
		 */
		_initInputExtender: function( $extension ) {
			var self = this,
				listrotatorEvents = 'listrotatorauto listrotatorselected'
					.replace( /(\w+)/g, '$1.' + this.uiBaseClass );

			this.$precisionContainer = $( '<div/>' )
			.addClass( this.uiBaseClass + '-precisioncontainer' )
			.append(
				$( '<div/>' ).text(
					this._messageProvider.getMessage( 'valueview-expert-timeinput-precision' )
				)
			);

			var precisionValues = [];
			$.each( timeSettings.precisiontexts, function( i, text ) {
				if( i <= Time.PRECISION.DAY ) {
					// TODO: Remove this check as soon as time values are supported.
					precisionValues.push( { value: i, label: text } );
				}
			} );

			this.$precision = $( '<div/>' )
			.addClass( this.uiBaseClass + '-precision' )
			.listrotator( { values: precisionValues.reverse(), deferInit: true } )
			.on( listrotatorEvents,	function( event, newPrecisionLevel ) {
				var currentValue = self.viewState().value();

				if( currentValue ) {
					var currentPrecision = currentValue.getValue().precision();

					if( newPrecisionLevel === currentPrecision ) {
						// Listrotator has been rotated automatically or the value covering the new
						// precision has already been generated.
						return;
					}
				}

				self._updateValue();
			} )
			.appendTo( this.$precisionContainer );

			this.$calendarContainer = $( '<div/>' )
			.addClass( this.uiBaseClass + '-calendarcontainer' )
			.append(
				$( '<div/>' ).text(
					this._messageProvider.getMessage( 'valueview-expert-timeinput-calendar' )
				)
			);

			var calendarValues = [];
			$.each( timeSettings.calendarnames, function( calendarKey, calendarTerms ) {
				var label = self._messageProvider.getMessage(
					'valueview-expert-timevalue-calendar-' + calendarTerms[0].toLowerCase()
				);
				calendarValues.push( { value: calendarTerms[0], label: label } );
			} );
			this.$calendar = $( '<div/>' )
			.listrotator( { values: calendarValues, deferInit: true } )
			.on( listrotatorEvents,	function( event, newValue ) {
				var currentValue = self.viewState().value();

				if( currentValue ) {
					var currentCalendar = currentValue.getValue().calendar();

					if( newValue === currentCalendar ) {
						// Listrotator has been rotated automatically or the value covering the new
						// precision has already been generated.
						return;
					}
				}

				self._updateValue();
			} )
			.appendTo( this.$calendarContainer );

			var $toggler = $( '<a/>' )
			.addClass( this.uiBaseClass + '-advancedtoggler' )
			.text( this._messageProvider.getMessage( 'valueview-expert-advancedadjustments' ) );

			this.$calendarhint = $( '<div/>' )
			.addClass( this.uiBaseClass + '-calendarhint' )
			.append( $( '<span/>' ).addClass( this.uiBaseClass + '-calendarhint-message' ) )
			.append(
				$( '<a/>' )
				.addClass( this.uiBaseClass + '-calendarhint-switch ui-state-default' )
				.attr( 'href', 'javascript:void(0);' )
			)
			.hide();

			var messageProvider = null;
			if( mediaWiki && mediaWiki.msg && util && util.MessageProvider ) {
				messageProvider = new util.MessageProvider( {
					messageGetter: mediaWiki.msg,
					prefix: 'valueview-preview-'
				} );
			}

			var $preview = $( '<div/>' ).preview( {
				$input: this.$input,
				messageProvider: messageProvider
			} );
			this.preview = $preview.data( 'preview' );

			// Append everything since the following actions require the fully initialized DOM.
			$extension.append( [
				$preview,
				this.$calendarhint,
				$toggler,
				this.$precisionContainer,
				this.$calendarContainer
			] );

			this.$precision.data( 'listrotator' ).initWidths();
			this.$calendar.data( 'listrotator' ).initWidths();

			var $subjects = this.$precisionContainer.add( this.$calendarContainer );
			$subjects.css( 'display', 'none' );
			$toggler.toggler( { $subject: $subjects } );
		},

		/**
		 * @see jQuery.valueview.Expert.destroy
		 */
		destroy: function() {
			if( !this.$input ) {
				return; // destroyed already
			}

			if( this.preview ) {
				this.preview.destroy();
				this.preview.element.remove();
			}

			var inputExtender = this.$input.data( 'inputextender' );
			if( inputExtender ) {
				// TODO: implement a init/destroy callback for input extender's extension instead,
				//  only called when necessary.
				if( inputExtender.$extension ) {
					// Explicitly destroy calendar and precision list rotators:
					inputExtender.$extension.find( ':ui-listrotator' ).listrotator( 'destroy' );
					inputExtender.$extension.find( this.uiBaseClass + '-advancedtoggler' )
						.toggler( 'destroy' );
				}
				inputExtender.destroy();
			}

			this.$input.off( 'eachchange' );

			this.$input = null;
			this.$precision = null;
			this.$precisionContainer = null;
			this.$calendar = null;
			this.$calendarContainer = null;

			PARENT.prototype.destroy.call( this ); // empties viewport
		},

		/**
		 * Builds a time.Time object from the widget's current input and advanced adjustments.
		 *
		 * @return {time.Time}
		 */
		_updateValue: function() {
			var value = new Time( this.$input.val(), this.valueCharacteristics() );

			this._setRawValue( value );
			this._updatePreview();
			this._updateCalendarHint( value );
			this._viewNotifier.notify( 'change' );

			return value;
		},

		/**
		 * @see jQuery.valueview.Expert.valueCharacteristics
		 */
		valueCharacteristics: function() {
			var value = this.viewState().value();
			if( value ) {
				value = value.getValue();
			}

			var options = {},
				precision = this.$precision && this.$precision.data( 'listrotator' ).value(),
				calendarname = this.$calendar && this.$calendar.data( 'listrotator' ).value();

			options.precision = precision || value && value.precision();
			options.calendar = calendarname ? this._calendarNameToUri( calendarname ) : ( value && value.calendarURI() );

			return options;
		},

		_calendarNameToUri: function( calendarname ) {
			return new Time( { calendarname: calendarname, precision: 0, year: 0 } ).calendarURI();
		},

		/**
		 * Updates the preview.
		 */
		_updatePreview: function() {
			this.preview.update( this._viewState.getFormattedValue() );
		},

		/**
		 * Updates the calendar hint message.
		 *
		 * @param {time.Time} [value] Message will get hidden when omitted.
		 */
		_updateCalendarHint: function( value ) {
			// When no value is specified or loaded in non-MediaWiki context, no message shall be
			// displayed.
			var msg = null;

			if( value ) {
				msg = this._messageProvider.getMessage(
					'valueview-expert-timeinput-calendarhint-' + value.calendar().toLowerCase()
				);
			}

			if( !msg ) {
				return;
			}

			if( value && value.year() > 1581 && value.year() < 1930 && value.precision() > 10 ) {
				var self = this;

				var otherCalendar = ( value.calendar() === Time.CALENDAR.GREGORIAN )
					? Time.CALENDAR.JULIAN
					: Time.CALENDAR.GREGORIAN;

				this.$calendarhint.children( '.' + this.uiBaseClass + '-calendarhint-message' )
				.text( msg );

				msg = this._messageProvider.getMessage(
					'valueview-expert-timeinput-calendarhint-switch-' + otherCalendar.toLowerCase()
				);
				if( msg ) {
					this.$calendarhint.children( '.' + this.uiBaseClass + '-calendarhint-switch' )
					.off( 'click.' + this.uiBaseClass )
					.on( 'click.' + this.uiBaseClass, function( event ) {
						var listrotator = self.$calendar.data( 'listrotator' );

							listrotator.element.one( 'listrotatorselected', function ( event ) {
								self._updateValue();
							} );

							self.$calendar.data( 'listrotator' ).rotate( otherCalendar );
						} )
					.html( msg );
				}

				this.$calendarhint.show();
			} else {
				this.$calendarhint.hide();
			}
		},

		/**
		 * @see jQuery.valueview.Expert._getRawValue
		 *
		 * @return {string|null}
		 */
		_getRawValue: function() {
			return ( ( this._newValue !== false )
				? this._newValue
				: this.$input.val() ) || null;
		},

		/**
		 * @see jQuery.valueview.Expert._setRawValue
		 *
		 * @param {time.Time|string|null} time
		 */
		_setRawValue: function( rawValue ) {
			// FIXME: The only case in which this is not Time is when you cancel the
			// edit and then start editing again.
			if( rawValue instanceof Time ) {
				rawValue = rawValue.text();
			}
			else if( typeof rawValue !== 'string' ) {
				rawValue = null;
			}
			this._newValue = rawValue;
		},

		/**
		 * @see jQuery.valueview.Expert.rawValueCompare
		 */
		rawValueCompare: function( time1, time2 ) {
			if( time2 === undefined ) {
				time2 = this._getRawValue();
			}

			if( time1 === null && time2 === null ) {
				return true;
			}

			// FIXME: This is fragile, but I think it does the right thing
			if( time1 instanceof Time ) {
				time1 = time1.text();
			}

			if( time2 instanceof Time ) {
				time2 = time2.text();
			}

			return time1 === time2;
		},

		/**
		 * @see jQuery.valueview.Expert.draw
		 */
		draw: function() {
			var value = this.viewState().value();
			if( value ) {
				value = value.getValue();
			}

			this.$input.prop( 'disabled', this._viewState.isDisabled() );

			if( this._newValue !== false ) {
				if( !this.$input.val() ) {
					this.$input.val( this._newValue );
				}
				this._newValue = false;
			}

			var considerInputExtender = this.$input.data( 'inputextender' ).extensionIsVisible();

			if( considerInputExtender ) {
				this._updateCalendarHint( value );
				if( value ) {
					this.$precision.data( 'listrotator' ).rotate( value.precision() );
					this.$calendar.data( 'listrotator' ).rotate( value.calendar() );
				}

				this._updatePreview();
			}
		},

		/**
		 * @see jQuery.valueview.Expert.focus
		 */
		focus: function() {
			this.$input.focusAt( 'end' );
		},

		/**
		 * @see jQuery.valueview.Expert.blur
		 */
		blur: function() {
			this.$input.blur();
		}
	} );

}( jQuery, jQuery.valueview, time ) );
