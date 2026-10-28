const utils = require( '../../resources/wikibase.wbui2025/utils.js' );
const { ErrorObject } = require( '../../resources/wikibase.wbui2025/api/api.js' );

describe( 'extractErrorMessage', () => {
	it( 'returns fallback message unless error object supplied', () => {
		expect( utils.extractErrorMessage( null, 'fallback' ) ).toBe( 'fallback' );
	} );

	it( 'returns fallback message if error object contains no error data', () => {
		const error = new ErrorObject( 'code', 'some message', null );
		expect( utils.extractErrorMessage( error, 'fallback' ) ).toBe( 'fallback' );
	} );

	it( 'returns generic error message with error text if api errors present', () => {
		const errors = [
			{ code: 'fail-save', text: 'the save failed' }
		];
		const error = new ErrorObject( 'code', 'some message', { errors } );
		expect( utils.extractErrorMessage( error, 'fallback' ) ).toBe(
			'wikibase-error-save-generic\nthe save failed'
		);
	} );

	it( 'returns captcha error message with error text if captcha errors present', () => {
		const errors = [
			{ code: 'fail-save', text: 'the save failed' },
			{ code: 'captcha-edit-fail', text: 'something about a captcha' }
		];
		const error = new ErrorObject( 'code', 'some message', { errors } );
		expect( utils.extractErrorMessage( error, 'fallback' ) ).toBe( 'wikibase-captcha-failed' );
	} );
} );
