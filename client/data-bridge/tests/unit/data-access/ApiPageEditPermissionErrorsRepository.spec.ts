import ApiPageEditPermissionErrorsRepository from '@/data-access/ApiPageEditPermissionErrorsRepository';
import JQueryTechnicalError from '@/data-access/error/JQueryTechnicalError';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';
import TitleInvalid from '@/data-access/error/TitleInvalid';
import { PermissionErrorType } from '@/definitions/data-access/PageEditPermissionErrorsRepository';
import { mockMwApi } from '../../util/mocks';
import jqXHR = JQuery.jqXHR;

function expectError( promise: Promise<any> ): Promise<any> {
	return promise.then(
		( _ ) => {
			throw new Error( 'should not have resolved with a value' );
		},
		( error ) => error,
	);
}

describe( 'ApiPageEditPermissionErrorsRepository', () => {

	it( 'returns empty array when there are no errors', async () => {
		const title = 'Title';
		const api = mockMwApi( { query: {
			pages: [ {
				title,
				actions: { edit: [] },
			} ],
			restrictions: { semiprotectedlevels: [ 'autoconfirmed' ] },
		} } );
		const repo = new ApiPageEditPermissionErrorsRepository( api );

		const permissionErrors = await repo.getPermissionErrors( title );

		expect( permissionErrors ).toStrictEqual( [] );
	} );

	it( 'detects page protected error', async () => {
		const title = 'Title';
		const right = 'editprotected';
		const api = mockMwApi( { query: {
			pages: [ {
				title,
				actions: { edit: [ {
					code: 'protectedpage',
					key: 'protectedpagetext',
					params: [
						right,
						'edit',
					],
				} ] },
			} ],
			restrictions: { semiprotectedlevels: [ 'autoconfirmed' ] },
		} } );
		const repo = new ApiPageEditPermissionErrorsRepository( api );

		const permissionErrors = await repo.getPermissionErrors( title );

		expect( permissionErrors.length ).toBe( 1 );
		const permissionError = permissionErrors[ 0 ];
		expect( permissionError ).toStrictEqual( {
			type: PermissionErrorType.PROTECTED_PAGE,
			right,
			semiProtected: false,
		} );
	} );

	it( 'detects page semi-protected error with custom right', async () => {
		const title = 'Title';
		const right = 'extendedconfirmed';
		const api = mockMwApi( { query: {
			pages: [ {
				title,
				actions: { edit: [ {
					code: 'protectedpage',
					key: 'protectedpagetext',
					params: [
						right,
						'edit',
					],
				} ] },
			} ],
			restrictions: { semiprotectedlevels: [ 'autoconfirmed', right ] },
		} } );
		const repo = new ApiPageEditPermissionErrorsRepository( api );

		const permissionErrors = await repo.getPermissionErrors( title );

		expect( permissionErrors.length ).toBe( 1 );
		const permissionError = permissionErrors[ 0 ];
		expect( permissionError ).toStrictEqual( {
			type: PermissionErrorType.PROTECTED_PAGE,
			right,
			semiProtected: true,
		} );
	} );

	it( 'detects page semi-protected error with standard right', async () => {
		const title = 'Title';
		const right = 'editsemiprotected';
		const api = mockMwApi( { query: {
			pages: [ {
				title,
				actions: { edit: [ {
					code: 'protectedpage',
					key: 'protectedpagetext',
					params: [
						right,
						'edit',
					],
				} ] },
			} ],
			restrictions: { semiprotectedlevels: [ 'autoconfirmed' ] },
		} } );
		const repo = new ApiPageEditPermissionErrorsRepository( api );

		const permissionErrors = await repo.getPermissionErrors( title );

		expect( permissionErrors.length ).toBe( 1 );
		const permissionError = permissionErrors[ 0 ];
		expect( permissionError ).toStrictEqual( {
			type: PermissionErrorType.PROTECTED_PAGE,
			right,
			semiProtected: true,
		} );
	} );

	it( 'handles unrecognized error', async () => {
		const title = 'Title';
		const api = mockMwApi( { query: {
			pages: [ {
				title,
				actions: { edit: [ {
					code: 'added-by-extension',
					key: 'added-by-extension-message',
					params: [
						'param 1',
						'param 2',
					],
				} ] },
			} ],
			restrictions: { semiprotectedlevels: [ 'autoconfirmed' ] },
		} } );
		const repo = new ApiPageEditPermissionErrorsRepository( api );

		const permissionErrors = await repo.getPermissionErrors( title );

		expect( permissionErrors.length ).toBe( 1 );
		const permissionError = permissionErrors[ 0 ];
		expect( permissionError ).toStrictEqual( {
			type: PermissionErrorType.UNKNOWN,
			code: 'added-by-extension',
			messageKey: 'added-by-extension-message',
			messageParams: [ 'param 1', 'param 2' ],
		} );
	} );

	it( 'combines multiple errors', async () => {
		const title = 'Title';
		const right = 'editprotected';
		const api = mockMwApi( { query: {
			pages: [ {
				title,
				actions: { edit: [
					{
						code: 'protectedpage',
						key: 'protectedpagetext',
						params: [
							right,
							'edit',
						],
					},
					{
						code: 'added-by-extension',
						key: 'added-by-extension-message',
						params: [
							'param 1',
							'param 2',
						],
					},
				] },
			} ],
			restrictions: { semiprotectedlevels: [ 'autoconfirmed' ] },
		} } );
		const repo = new ApiPageEditPermissionErrorsRepository( api );

		const permissionErrors = await repo.getPermissionErrors( title );

		expect( permissionErrors.length ).toBe( 2 );
		const protectedError = permissionErrors[ 0 ];
		expect( protectedError ).toStrictEqual( {
			type: PermissionErrorType.PROTECTED_PAGE,
			right,
			semiProtected: false,
		} );
		const unknownError = permissionErrors[ 1 ];
		expect( unknownError ).toStrictEqual( {
			type: PermissionErrorType.UNKNOWN,
			code: 'added-by-extension',
			messageKey: 'added-by-extension-message',
			messageParams: [ 'param 1', 'param 2' ],
		} );
	} );

	it( 'follows normalized title', async () => {
		const originalTitle = 'title';
		const normalizedTitle = 'Title';
		const api = mockMwApi( { query: {
			normalized: [ {
				fromencoded: false,
				from: originalTitle,
				to: normalizedTitle,
			} ],
			pages: [ {
				title: normalizedTitle,
				actions: { edit: [] },
			} ],
			restrictions: { semiprotectedlevels: [ 'autoconfirmed' ] },
		} } );
		const repo = new ApiPageEditPermissionErrorsRepository( api );

		const permissionErrors = await repo.getPermissionErrors( originalTitle );

		expect( permissionErrors ).toStrictEqual( [] );
	} );

	it( 'rejects with jQuery error on API throw', async () => {
		const xhr = {} as jqXHR;
		const api = mockMwApi( undefined, xhr );
		const repo = new ApiPageEditPermissionErrorsRepository( api );

		const error = await expectError( repo.getPermissionErrors( '' ) );

		expect( error ).toStrictEqual( new JQueryTechnicalError( xhr ) );
	} );

	it( 'rejects with TechnicalProblem if API is missing page', async () => {
		const api = mockMwApi( { query: {
			pages: [],
			restrictions: { semiprotectedlevels: [ 'autoconfirmed' ] },
		} } );
		const repo = new ApiPageEditPermissionErrorsRepository( api );

		const error = await expectError( repo.getPermissionErrors( 'Title' ) );

		expect( error ).toBeInstanceOf( TechnicalProblem );
	} );

	it( 'rejects with TitleInvalid on invalid title', async () => {
		const title = '<invalid>';
		const api = mockMwApi( { query: {
			pages: [ {
				title,
				invalid: true,
				// invalidreason omitted
			} ],
			restrictions: { semiprotectedlevels: [ 'autoconfirmed' ] },
		} } );
		const repo = new ApiPageEditPermissionErrorsRepository( api );

		const error = await expectError( repo.getPermissionErrors( title ) );

		expect( error ).toStrictEqual( new TitleInvalid( title ) );
	} );

	it( 'rejects with TechnicalProblem if API does not return test actions', async () => {
		const title = 'Title';
		const api = mockMwApi( { query: {
			pages: [ { title } ],
			restrictions: { semiprotectedlevels: [ 'autoconfirmed' ] },
		} } );
		const repo = new ApiPageEditPermissionErrorsRepository( api );

		const error = await expectError( repo.getPermissionErrors( title ) );

		expect( error ).toBeInstanceOf( TechnicalProblem );
	} );

	it( 'rejects with TechnicalProblem if API does not return semi-protected levels', async () => {
		const title = 'Title';
		const api = mockMwApi( { query: { pages: [ {
			title,
			actions: { edit: [] },
		} ] } } );
		const repo = new ApiPageEditPermissionErrorsRepository( api );

		const error = await expectError( repo.getPermissionErrors( title ) );

		expect( error ).toBeInstanceOf( TechnicalProblem );
	} );

	it( 'rejects with TechnicalProblem if the API does not return raw errors', async () => {
		const title = 'Title';
		const api = mockMwApi( { query: {
			pages: [ {
				title,
				actions: { edit: [ {
					code: 'protectedpage',
					text: 'This page has been protected to prevent editing or other actions.',
				} ] },
			} ],
			restrictions: { semiprotectedlevels: [ 'autoconfirmed' ] },
		} } );
		const repo = new ApiPageEditPermissionErrorsRepository( api );

		const error = await expectError( repo.getPermissionErrors( title ) );

		expect( error ).toBeInstanceOf( TechnicalProblem );
	} );

} );
