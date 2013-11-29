/**
 * @file
 * @ingroup ValueView
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 */
( function( dv, $, vv, time ) {
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
	vv.experts.TimeInput = vv.expert( 'timeinput', PARENT, {

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
		 * @type {time.Time|null|false}
		 */
		_newValue: null,

		/**
		 * The preview widget.
		 * @type {jQuery.valueview.preview}
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
			.timeinput( { mediaWiki: this._options.mediaWiki } )
			.inputextender( {
				initCallback: function( $extension ) {
					self._initInputExtender( $extension );
					// $extension not yet in DOM, so draw() would not update rotators. Call draw
					// as soon as toggle has been done, from this point on the inputextender's
					// extension() is available in draw().
					self.$input.one( 'inputextenderaftertoggle', function( event ) {
						self.draw();
						var value = self.rawValue();
						if( value !== null ) {
							self.$precision.data( 'listrotator' ).rotate( value.precision() );
							self.$calendar.data( 'listrotator' ).rotate( value.calendar() );
						}
					} );
				},
				contentAnimationEvents: 'toggleranimation'
			} )
			.on( 'timeinputupdate.' + this.uiBaseClass, function( event, value ) {
				if( self.$input.data( 'inputextender' ).extensionIsActive() ) {
					self._updateCalendarHint( value );
					if( value ) {
						self.$precision.data( 'listrotator' ).rotate( value.precision() );
						self.$calendar.data( 'listrotator' ).rotate( value.calendar() );
					}
					self._updatePreview();
				}
				self._newValue = false; // value, not yet handled by draw(), is outdated now
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
			var self = this;

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
			.on(
				'listrotatorauto.' + this.uiBaseClass + ' listrotatorselected.' + this.uiBaseClass,
				function( event, newValue ) {
					var rawValue = self._getRawValue();

					if( rawValue === null || newValue === rawValue.precision() ) {
						// Listrotator has been rotated automatically, the value covering the new
						// precision has already been generated or the current input is invalid.
						return;
					}

					var overwrite = {};

					if( event.type === 'listrotatorauto' ) {
						overwrite.precision = undefined;
					}

					var value = self._updateValue( overwrite );

					if( event.type === 'listrotatorauto' ) {
						$( this ).data( 'listrotator' ).rotate( value.precision() );
					}
				}
			)
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
			.on(
				'listrotatorauto.' + this.uiBaseClass + ' listrotatorselected.' + this.uiBaseClass,
				function( event, newValue ) {
					var rawValue = self._getRawValue();

					if( rawValue === null || newValue === rawValue.calendar() ) {
						// Listrotator has been rotated automatically, the value covering the new
						// precision has already been generated or the current input is invalid.
						return;
					}

					var overwrite = {};

					if( event.type === 'listrotatorauto' ) {
						overwrite.calendarname = undefined;
					}

					var value = self._updateValue( overwrite );

					if( event.type === 'listrotatorauto' ) {
						$( this ).data( 'listrotator' ).rotate( value.calendar() );
					}
				}
			)
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

			var $preview = $( '<div/>' ).preview( { $input: this.$input } );
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

			var timeInput = this.$input.data( 'timeinput' );
			if( timeInput ) {
				timeInput.destroy();
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
		 * @param {Object} [overwrites] Values that should be used instead of the ones picked from
		 *        the input elements.
		 * @return {time.Time}
		 */
		_updateValue: function( overwrites ) {
			overwrites = overwrites || {};

			var options = {},
				precision = ( overwrites.hasOwnProperty( 'precision' ) )
					? overwrites.precision
					: this.$precision.data( 'listrotator' ).value(),
				calendarname = ( overwrites.hasOwnProperty( 'calendarname' ) )
					? overwrites.calendarname
					: this.$calendar.data( 'listrotator' ).value(),
				value = null;

			if( precision !== undefined ) {
				options.precision = precision;
			}

			if( calendarname !== undefined ) {
				options.calendarname = calendarname;
			}

			value = new Time( this.$input.val(), options );

			this._setRawValue( value );
			this._updatePreview();
			this._updateCalendarHint( value );
			this._viewNotifier.notify( 'change' );

			return value;
		},

		/**
		 * Updates the preview.
		 */
		_updatePreview: function() {
			var rawValue = this._getRawValue(),
				options = {};

			if ( this._options.mediaWiki ) {
				options.format = this._options.mediaWiki.user.options.get( 'date' );
			}

			this.preview.update( ( rawValue ) ? rawValue.text( options ) : null );
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
		 * @return {time.Time|null}
		 */
		_getRawValue: function() {
			return ( this._newValue !== false )
				? this._newValue
				: this.$input.data( 'timeinput' ).value();
		},

		/**
		 * @see jQuery.valueview.Expert._setRawValue
		 *
		 * @param {time.Time|null} time
		 */
		_setRawValue: function( time ) {
			if( !( time instanceof Time ) ) {
				time = null;
			}
			this._newValue = time;
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

			if( !( time1 instanceof Time ) || !( time2 instanceof Time ) ) {
				return false;
			}

			return time1.precision() === time2.precision()
				&& time1.iso8601() === time2.iso8601();
		},

		/**
		 * @see jQuery.valueview.Expert.draw
		 */
		draw: function() {
			if( this._viewState.isDisabled() ) {
				this.$input.data( 'timeinput' ).disable();
			} else {
				this.$input.data( 'timeinput' ).enable();
			}

			var considerInputExtender = this.$input.data( 'inputextender' ).extensionIsVisible();

			if( this._newValue !== false ) {
				this.$input.data( 'timeinput' ).value( this._newValue );

				if( considerInputExtender ) {
					this._updateCalendarHint( this._newValue );
					if( this._newValue !== null ) {
						this.$precision.data( 'listrotator' ).value( this._newValue.precision() );
						this.$calendar.data( 'listrotator' ).value( this._newValue.calendar() );
					}
				}
				this._newValue = false;
			}

			if( considerInputExtender ) {
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

}( dataValues, jQuery, jQuery.valueview, time ) );
