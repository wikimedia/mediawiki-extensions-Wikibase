const $ = require( '../../../../lib/resources/packages/wikibase-api/node_modules/jquery/dist/jquery.js' );

const withCaptcha = require( '../../../../view/resources/wikibase/captcha/postCaptcha.js' );
const captcha = require( '../../../../view/resources/wikibase/captcha/resolveCaptcha.js' );

describe( 'classic CAPTCHA save handling', () => {
	let api, widget;
	let previousDollar;

	const descriptor = { type: 'hcaptcha' };
	const blockedResponse = {
		errors: [
			{
				code: 'captcha-required',
				data: { captcha: descriptor }
			}
		]
	};
	const submission = {
		captchaid: '',
		captchaword: 'test-token'
	};
	const params = {
		action: 'wbsetclaim',
		claim: '{"id":"Q2$test","value":"Blue"}',
		baserevid: 123
	};

	function success( response ) {
		return $.Deferred().resolve( response ).promise();
	}

	function failure( code, response ) {
		return $.Deferred().reject( code, response ).promise();
	}

	beforeEach( () => {
		previousDollar = global.$;
		// eslint-disable-next-line no-jquery/variable-pattern
		global.$ = $;

		widget = {
			updateForFailure: jest.fn().mockResolvedValue( false ),
			renderCaptcha: jest.fn().mockResolvedValue( undefined ),
			getCaptchaDataForSubmission: jest.fn().mockResolvedValue( submission )
		};

		mw.libs = {
			confirmEdit: {
				CaptchaWidget: jest.fn().mockImplementation( () => widget )
			}
		};

		mw.loader.getState.mockReturnValue( 'ready' );
		mw.loader.using.mockResolvedValue( () => captcha );

		api = new mw.Api();
		api.assertCurrentUser.mockImplementation( ( request ) => request );
	} );

	afterEach( () => {
		global.$ = previousDollar;
	} );

	describe( 'when captcha is needed for edits', () => {
		beforeEach( () => {
			// simulates 'wgConfirmEditCaptchaNeededForGenericEdit' being enabled
			mw.config.get.mockReturnValue( 'hcaptcha' );
		} );

		it( 'resubmits a blocked edit with the CAPTCHA token and original data', async () => {
			const saved = {
				success: 1,
				claim: { id: 'Q2$test' }
			};

			api.postWithToken.mockReturnValue(
				failure( 'captcha-required', blockedResponse )
			);
			api.postWithEditToken.mockReturnValue( success( saved ) );

			const result = await withCaptcha( api ).postWithToken( 'csrf', params );

			const originalAndCaptchaParams = Object.assign( {}, params, submission );

			expect( api.postWithToken ).toHaveBeenCalledWith( 'csrf', originalAndCaptchaParams );
			expect( mw.libs.confirmEdit.CaptchaWidget ).toHaveBeenCalledTimes( 2 );
			expect( widget.updateForFailure ).toHaveBeenCalledWith( descriptor );
			expect( api.postWithEditToken ).toHaveBeenCalledWith( originalAndCaptchaParams );
			expect( result ).toBe( saved );
			expect( params ).not.toHaveProperty( 'captchaword' );
		} );

		it( 'preserves both rejection arguments when the retry fails', async () => {
			const response = {
				errors: [ { code: 'assertanonfailed' } ]
			};

			api.postWithToken.mockReturnValue(
				failure( 'captcha-required', blockedResponse )
			);
			api.postWithEditToken.mockReturnValue(
				failure( 'assertanonfailed', response )
			);

			const rejection = await new Promise( ( resolve, reject ) => {
				withCaptcha( api ).postWithToken( 'csrf', params ).then(
					() => reject( new Error( 'Expected the save to fail' ) ),
					( code, details ) => resolve( { code, details } )
				);
			} );

			expect( rejection ).toEqual( {
				code: 'assertanonfailed',
				details: response
			} );
		} );
	} );

	describe( 'when captcha is NOT needed for edits', () => {
		beforeEach( () => {
			// simulates 'wgConfirmEditCaptchaNeededForGenericEdit' being false
			mw.config.get.mockReturnValue( false );
		} );

		// The server can still require a CAPTCHA, like for an AbuseFilter match.
		it( 'sends no CAPTCHA up front but resubmits with one when the server asks', async () => {
			const saved = {
				success: 1,
				claim: { id: 'Q2$test' }
			};

			api.postWithToken.mockReturnValue(
				failure( 'captcha-required', blockedResponse )
			);
			api.postWithEditToken.mockReturnValue( success( saved ) );

			const result = await withCaptcha( api ).postWithToken( 'csrf', params );

			expect( api.postWithToken ).toHaveBeenCalledWith( 'csrf', params );
			expect( mw.libs.confirmEdit.CaptchaWidget ).toHaveBeenCalledTimes( 1 );
			expect( widget.updateForFailure ).toHaveBeenCalledWith( descriptor );
			expect( api.postWithEditToken ).toHaveBeenCalledWith(
				Object.assign( {}, params, submission )
			);
			expect( result ).toBe( saved );
		} );

		it( 'rejects without resubmitting when the failure is not a CAPTCHA error', async () => {
			const response = {
				errors: [ { code: 'editconflict' } ]
			};

			api.postWithToken.mockReturnValue( failure( 'editconflict', response ) );

			const rejection = await new Promise( ( resolve, reject ) => {
				withCaptcha( api ).postWithToken( 'csrf', params ).then(
					() => reject( new Error( 'Expected the save to fail' ) ),
					( code, details ) => resolve( { code, details } )
				);
			} );

			expect( rejection ).toEqual( {
				code: 'editconflict',
				details: response
			} );
			expect( api.postWithToken ).toHaveBeenCalledTimes( 1 );
			expect( mw.libs.confirmEdit.CaptchaWidget ).not.toHaveBeenCalled();
			expect( api.postWithEditToken ).not.toHaveBeenCalled();
		} );
	} );
} );
