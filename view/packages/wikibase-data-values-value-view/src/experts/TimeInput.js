( function( $, vv, TimeValue ) {
	'use strict';

	var PARENT = vv.experts.StringValue;

	/**
	 * @ignore
	 *
	 * @return {Object[]} [{ value: <{number}>, label: <{string}>}, ...]
	 */
	function getPrecisionValues() {
		var precisionValues = [],
			dayPrecision = TimeValue.getPrecisionById( 'DAY' );
		$.each( TimeValue.PRECISIONS, function( precisionValue, precision ) {
			if ( precisionValue <= dayPrecision ) {
				// TODO: Remove this check as soon as time values are supported.
				precisionValues.unshift( { value: precisionValue, label: precision.text } );
			}
		} );
		return precisionValues;
	}

	/**
	 * @ignore
	 *
	 * @param {util.MessageProvider} messageProvider
	 * @return {Object[]} [{ value: <{string}>, label: <{string}>}, ...]
	 */
	function getCalendarValues( messageProvider ) {
		var calendarValues = [];
		$.each( TimeValue.CALENDARS, function( key, uri ) {
			var label = messageProvider.getMessage(
				'valueview-expert-timevalue-calendar-' + key.toLowerCase()
			) || key.toLowerCase();
			calendarValues.push( { value: uri, label: label } );
		} );
		return calendarValues;
	}

	/**
	 * `Valueview` expert handling input of `Time` values.
	 * @class jQuery.valueview.experts.TimeInput
	 * @extends jQuery.valueview.experts.StringValue
	 * @since 0.1
	 * @licence GNU GPL v2+
	 * @author Daniel Werner < daniel.werner@wikimedia.de >
	 * @author H. Snater < mediawiki@snater.com >
	 */
	vv.experts.TimeInput = vv.expert( 'TimeInput', PARENT, function() {
		PARENT.apply( this, arguments );

		var self = this;

		this.preview = new vv.ExpertExtender.Preview(
			function() {
				return self.viewState().getFormattedValue();
			},
			this._messageProvider
		);

		var precisionMsgKey = 'valueview-expert-timeinput-precision';
		var $precisionContainer = $( '<div/>' )
			.addClass( this.uiBaseClass + '-precisioncontainer' )
			.append( $( '<div/>' ).text( this._messageProvider.getMessage( precisionMsgKey ) ) );

		this.precisionRotator = new vv.ExpertExtender.Listrotator(
			this.uiBaseClass + '-precision',
			getPrecisionValues(),
			$.proxy( this._onRotatorChange, this ),
			function() {
				var value = self.viewState().value();
				return value && value.getOption( 'precision' );
			}
		);

		var calendarMsgKey = 'valueview-expert-timeinput-calendar';
		var $calendarContainer = $( '<div/>' )
			.addClass( this.uiBaseClass + '-calendarcontainer' )
			.append( $( '<div/>' ).text( this._messageProvider.getMessage( calendarMsgKey ) ) );

		this.calendarRotator = new vv.ExpertExtender.Listrotator(
			this.uiBaseClass + '-calendar',
			getCalendarValues( this._messageProvider ),
			$.proxy( this._onRotatorChange, this ),
			function() {
				var value = self.viewState().value();
				return value && value.getOption( 'calendarModel' );
			}
		);

		var inputExtender = new vv.ExpertExtender(
			this.$input,
			[
				this.preview,
				new vv.ExpertExtender.CalendarHint(
					this._messageProvider,
					function() {
						return self.viewState().value();
					},
					function( value ) {
						// FIXME: Do not use private function:
						self.calendarRotator.rotator._setValue( value );
						self.calendarRotator.rotator.value( value );
						self.calendarRotator.rotator.activate();
					}
				),
				new vv.ExpertExtender.Toggler(
					this._messageProvider,
					$precisionContainer.add( $calendarContainer )
				),
				new vv.ExpertExtender.Container(
					$precisionContainer,
					this.precisionRotator
				),
				new vv.ExpertExtender.Container(
					$calendarContainer,
					this.calendarRotator
				)
			]
		);

		this.addExtension( inputExtender );
	}, {

		/**
		 * @inheritdoc
		 * @protected
		 */
		_options: {
			messages: {
				'valueview-expert-timeinput-precision': 'Precision',
				'valueview-expert-timeinput-calendar': 'Calendar'
			}
		},

		/**
		 * The preview widget.
		 * @property {jQuery.valueview.ExpertExtender.Preview}
		 */
		preview: null,

		/**
		 * @property {jQuery.valueview.ExpertExtender.Listrotator}
		 */
		precisionRotator: null,

		/**
		 * @property {jQuery.valueview.ExpertExtender.Listrotator}
		 */
		calendarRotator: null,

		/**
		 * @protected
		 */
		_onRotatorChange: function() {
			this._viewNotifier.notify( 'change' );
		},

		/**
		 * @inheritdoc
		 */
		destroy: function() {
			this.preview = null;
			this.precisionRotator = null;
			this.calendarRotator = null;

			PARENT.prototype.destroy.call( this ); // empties viewport
		},

		/**
		 * @inheritdoc
		 */
		valueCharacteristics: function() {
			var options = {},
				precision = this.precisionRotator && this.precisionRotator.getValue() || null,
				calendarUri = this.calendarRotator && this.calendarRotator.getValue() || null;

			if ( precision !== null ) {
				options.precision = precision;
			}
			if ( calendarUri !== null ) {
				options.calendar = calendarUri;
			}

			return options;
		}
	} );

}( jQuery, jQuery.valueview, dataValues.TimeValue ) );
