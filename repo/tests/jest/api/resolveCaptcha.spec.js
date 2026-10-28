/**
 * This code was originally co-authored by Claude Opus 4.8 - see I199985c8716f7aa9305ac107eec308fad06e0a3b
 * and massively re-written.
 *
 * @license GPL-2.0-or-later
 */

const {
	extractCaptchaData,
	getCaptchaIfRequired,
	resolveCaptchaAndResubmit,
	isCaptchaRequired
} = require( '../../../../view/resources/wikibase/captcha/resolveCaptcha.js' );

// mw.Api rejects jQuery-style with two arguments (code, response); native Promises only
// carry one. This models the two-argument rejection so the retry logic can be exercised.
function jqReject( code, response ) {
	return {
		then( onFulfilled, onRejected ) {
			if ( !onRejected ) {
				return jqReject( code, response );
			}
			return Promise.resolve().then( () => onRejected( code, response ) );
		}
	};
}

mw.loader = {
	getState: jest.fn(),
	using: jest.fn().mockResolvedValue()
};

mw.libs = {
	confirmEdit: {
		CaptchaWidget: jest.fn()
	}
};

mw.config = new Map();

const {
	assertCurrentUser: assertCurrentUserMock,
	postWithEditToken: postWithEditTokenMock
} = mw.Api.prototype;
assertCurrentUserMock.mockImplementation( ( params ) => params );

// original function resolves with an object representing new content or edit api params
postWithEditTokenMock.mockResolvedValue( {} );

const captchaNeededConfig = 'wgConfirmEditCaptchaNeededForGenericEdit';

// These two objects follow the type definition for `CaptchaSubmissionData` defined in`ext.confirmEdit.CaptchaWidget`,
// and are used to mock the resolved value of getCaptchaDataForSubmission
const captchaData = {
	captchaid: '',
	captchaword: 'a-token'
};
const captchaDataWithForceShow = {
	captchaid: '',
	captchaword: 'a-token',
	wgConfirmEditForceShowCaptcha: true
};

// Follows the `captcha` descriptor in a failed API response, as read by CaptchaWidget#updateForFailure.
const captchaDescriptor = { type: 'hcaptcha', key: 'a-sitekey' };

const widget = {
	updateForFailure: jest.fn().mockResolvedValue( false ),
	renderCaptcha: jest.fn( () => Promise.resolve() ),
	getCaptchaDataForSubmission: jest.fn().mockResolvedValue( captchaData )
};

describe( 'extractCaptchaData', () => {
	it( 'returns null for a missing response', () => {
		expect( extractCaptchaData( null ) ).toBeNull();
		expect( extractCaptchaData( undefined ) ).toBeNull();
	} );

	it( 'reads response.error.captcha (default/bc errorformat)', () => {
		const captcha = { type: 'hcaptcha' };
		expect( extractCaptchaData( { error: { captcha } } ) ).toBe( captcha );
	} );

	it( 'scans response.errors[] for the first .data.captcha (html errorformat)', () => {
		const response = {
			errors: [
				{ code: 'some-other-error' },
				{ data: {} },
				{ data: { captcha: captchaDescriptor } },
				{ data: { captcha: { type: 'some-other-captcha' } } }
			]
		};
		expect( extractCaptchaData( response ) ).toBe( captchaDescriptor );
	} );

	it( 'returns null when no captcha descriptor is present', () => {
		expect( extractCaptchaData( { errors: [ { code: 'some-other-error' } ] } ) ).toBeNull();
		expect( extractCaptchaData( {} ) ).toBeNull();
	} );
} );

describe( 'resolveCaptchaAndResubmit', () => {

	it( 'when the widget module is not registered, it rejects without loading ConfirmEdit', async () => {
		mw.loader.getState.mockReturnValue( null );

		await expect( resolveCaptchaAndResubmit(
			{}, { captchaData: captchaDescriptor }
		) ).rejects.toThrow();
		expect( mw.loader.using ).not.toHaveBeenCalled();
	} );

	describe( 'when the widget module is registered', () => {
		beforeEach( () => {
			mw.loader.getState.mockReturnValue( 'registered' );
			mw.libs.confirmEdit.CaptchaWidget.mockReturnValue( widget );
		} );

		it( 'renders the widget and resubmits with the CAPTCHA response merged in', async () => {
			await resolveCaptchaAndResubmit(
				{ action: 'wbeditentity', id: 'Q1' },
				{ interfaceName: 'wikibase', captchaData: captchaDescriptor }
			);

			expect( widget.updateForFailure ).toHaveBeenCalledWith( captchaDescriptor );
			expect( widget.renderCaptcha ).toHaveBeenCalled();
			expect( postWithEditTokenMock ).toHaveBeenCalledWith( {
				action: 'wbeditentity',
				id: 'Q1',
				captchaid: '',
				captchaword: 'a-token'
			} );
		} );

		it( 'merges wgConfirmEditForceShowCaptcha into the resubmission when set', async () => {
			widget.getCaptchaDataForSubmission.mockResolvedValue( captchaDataWithForceShow );

			await resolveCaptchaAndResubmit(
				{ action: 'wbeditentity' }, { captchaData: captchaDescriptor }
			);

			expect( postWithEditTokenMock ).toHaveBeenCalledWith(
				expect.objectContaining( { wgConfirmEditForceShowCaptcha: true } )
			);
		} );

		it( 'retries once when a force-show escalation returns another CAPTCHA error', async () => {
			const response = { entity: {} };
			const escalation = { errors: [ { data: { captcha: { type: 'hcaptcha', error: 'forceshowcaptcha' } } } ] };
			postWithEditTokenMock
				.mockImplementationOnce( () => jqReject( 'failed-save', escalation ) )
				.mockImplementationOnce( () => Promise.resolve( response ) );

			const result = await resolveCaptchaAndResubmit(
				{ action: 'wbeditentity' }, { captchaData: captchaDescriptor }
			);

			expect( result ).toBe( response );
			expect( postWithEditTokenMock ).toHaveBeenCalledTimes( 2 );
		} );

		it( 'gives up after a single automatic retry and surfaces the error', async () => {
			const escalation = { errors: [ { data: { captcha: { type: 'hcaptcha' } } } ] };
			postWithEditTokenMock.mockImplementation( () => jqReject( 'failed-save', escalation ) );

			await expect( resolveCaptchaAndResubmit(
				{ action: 'wbeditentity' }, { captchaData: captchaDescriptor }
			) ).rejects.toBeDefined();
			expect( postWithEditTokenMock ).toHaveBeenCalledTimes( 2 );
		} );
	} );
} );

describe( 'isCaptchaRequired', () => {

	it( 'returns false if module is not loaded', () => {
		mw.loader.getState.mockReturnValue( null );
		mw.config.set( captchaNeededConfig, 'hcaptcha' );
		expect( isCaptchaRequired() ).toBe( false );
	} );

	it( 'returns false if module is loaded, but captcha is not defined for edit', () => {
		mw.loader.getState.mockReturnValue( 'registered' );
		mw.config.set( captchaNeededConfig, null );
		expect( isCaptchaRequired() ).toBe( false );
	} );

	it( 'returns true if captcha is defined for edit and module loaded', () => {
		mw.loader.getState.mockReturnValue( 'registered' );
		mw.config.set( captchaNeededConfig, 'hcaptcha' );
		expect( isCaptchaRequired() ).toBe( true );
	} );

} );

describe( 'getCaptchaIfRequired', () => {

	it( 'returns null without loading ConfirmEdit when the widget module is absent', async () => {
		mw.loader.getState.mockReturnValue( null );

		await expect( getCaptchaIfRequired() ).resolves.toBeNull();
		expect( mw.loader.using ).not.toHaveBeenCalled();
		expect( mw.libs.confirmEdit.CaptchaWidget ).not.toHaveBeenCalled();
	} );

	describe( 'when the widget module is registered', () => {
		beforeEach( () => {
			mw.loader.getState.mockReturnValue( 'registered' );
			mw.libs.confirmEdit.CaptchaWidget.mockReturnValue( widget );
		} );

		it( 'returns null and renders nothing when no generic-edit CAPTCHA is required', async () => {
			mw.config.set( captchaNeededConfig, false );

			await expect( getCaptchaIfRequired() ).resolves.toBeNull();
			expect( widget.renderCaptcha ).not.toHaveBeenCalled();
		} );

		describe( 'when capture for generic edit is configured', () => {
			beforeEach( () => {
				mw.config.set( captchaNeededConfig, 'hcaptcha' );
			} );

			it( 'renders the CAPTCHA and returns the response to attach to the first save', async () => {
				widget.getCaptchaDataForSubmission.mockResolvedValue( captchaData );
				const params = await getCaptchaIfRequired( { interfaceName: 'wikibase' } );

				expect( widget.renderCaptcha ).toHaveBeenCalled();
				expect( params ).toEqual( captchaData );
			} );

			it( 'when wgConfirmEditForceShowCaptcha is provided, it is included in the returned params', async () => {
				widget.getCaptchaDataForSubmission.mockResolvedValue( captchaDataWithForceShow );

				const params = await getCaptchaIfRequired();
				expect( params ).toMatchObject( { wgConfirmEditForceShowCaptcha: true } );
			} );
		} );
	} );
} );
