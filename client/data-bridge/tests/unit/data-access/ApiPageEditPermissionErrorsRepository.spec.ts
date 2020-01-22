import ApiPageEditPermissionErrorsRepository from '@/data-access/ApiPageEditPermissionErrorsRepository';
import JQueryTechnicalError from '@/data-access/error/JQueryTechnicalError';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';
import TitleInvalid from '@/data-access/error/TitleInvalid';
import { PermissionErrorType } from '@/definitions/data-access/PageEditPermissionErrorsRepository';
import { mockApi } from '../../util/mocks';
import jqXHR = JQuery.jqXHR;

describe( 'ApiPageEditPermissionErrorsRepository', () => {

	it( 'returns empty array when there are no errors', async () => {
		const title = 'Title';
		const api = mockApi( { query: {
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
		const api = mockApi( { query: {
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

		expect( permissionErrors ).toHaveLength( 1 );
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
		const api = mockApi( { query: {
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

		expect( permissionErrors ).toHaveLength( 1 );
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
		const api = mockApi( { query: {
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

		expect( permissionErrors ).toHaveLength( 1 );
		const permissionError = permissionErrors[ 0 ];
		expect( permissionError ).toStrictEqual( {
			type: PermissionErrorType.PROTECTED_PAGE,
			right,
			semiProtected: true,
		} );
	} );

	it( 'detects page cascade-protected error', async () => {
		const title = 'Title';
		const pages = [ 'Art', 'Category:Cat' ];
		const api = mockApi( { query: {
			pages: [ {
				title,
				actions: { edit: [ {
					code: 'cascadeprotected',
					key: 'cascadeprotected',
					params: [
						pages.length,
						pages.reduce( ( wikitext, page ) => `${wikitext}* [[:${page}]]\n`, '' ),
						'edit',
					],
				} ] },
			} ],
			restrictions: { semiprotectedlevels: [ 'autoconfirmed' ] },
		} } );
		const repo = new ApiPageEditPermissionErrorsRepository( api );

		const permissionErrors = await repo.getPermissionErrors( title );

		expect( permissionErrors ).toHaveLength( 1 );
		const permissionError = permissionErrors[ 0 ];
		expect( permissionError ).toStrictEqual( {
			type: PermissionErrorType.CASCADE_PROTECTED_PAGE,
			pages,
		} );
	} );

	it( 'detects user blocked on title error', async () => {
		const title = 'Title';
		const api = mockApi( { query: {
			pages: [ {
				title,
				actions: { edit: [ {
					code: 'blocked',
					key: 'apierror-blocked',
					params: [],
					data: {
						blockinfo: {
							blockid: 456,
							blockedby: 'ServerAdmin',
							blockedbyid: 789,
							blockreason: 'Testing for T239336',
							blockedtimestamp: '2019-12-12T11:50:39Z',
							blockexpiry: 'infinite',
							blockpartial: false,
						},
					},
				} ] },
			} ],
			restrictions: { semiprotectedlevels: [ 'autoconfirmed' ] },
		} } );
		const repo = new ApiPageEditPermissionErrorsRepository( api );
		const permissionErrors = await repo.getPermissionErrors( title );
		expect( permissionErrors ).toHaveLength( 1 );
		const permissionError = permissionErrors[ 0 ];
		expect( permissionError ).toStrictEqual( {
			type: PermissionErrorType.BLOCKED,
			blockinfo: {
				blockid: 456,
				blockedby: 'ServerAdmin',
				blockedbyid: 789,
				blockreason: 'Testing for T239336',
				blockedtimestamp: '2019-12-12T11:50:39Z',
				blockexpiry: 'infinite',
				blockpartial: false,
			},
		} );
	} );

	it( 'handles unrecognized error', async () => {
		const title = 'Title';
		const api = mockApi( { query: {
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

		expect( permissionErrors ).toHaveLength( 1 );
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
		const api = mockApi( { query: {
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

		expect( permissionErrors ).toHaveLength( 2 );
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

	it( 'passes through rejection from underlying API', () => {
		const rejection = new JQueryTechnicalError( {} as jqXHR );
		const api = mockApi( undefined, rejection );
		const repo = new ApiPageEditPermissionErrorsRepository( api );

		return expect( repo.getPermissionErrors( '' ) )
			.rejects
			.toBe( rejection );
	} );

	it( 'rejects with TechnicalProblem if API is missing page', () => {
		const api = mockApi( { query: {
			pages: [],
			restrictions: { semiprotectedlevels: [ 'autoconfirmed' ] },
		} } );
		const repo = new ApiPageEditPermissionErrorsRepository( api );

		return expect( repo.getPermissionErrors( 'Title' ) )
			.rejects
			.toBeInstanceOf( TechnicalProblem );
	} );

	it( 'rejects with TitleInvalid on invalid title', () => {
		const title = '<invalid>';
		const api = mockApi( { query: {
			pages: [ {
				title,
				invalid: true,
				// invalidreason omitted
			} ],
			restrictions: { semiprotectedlevels: [ 'autoconfirmed' ] },
		} } );
		const repo = new ApiPageEditPermissionErrorsRepository( api );

		return expect( repo.getPermissionErrors( title ) )
			.rejects
			.toStrictEqual( new TitleInvalid( title ) );
	} );

	it( 'rejects with TechnicalProblem if API does not return test actions', () => {
		const title = 'Title';
		const api = mockApi( { query: {
			pages: [ { title } ],
			restrictions: { semiprotectedlevels: [ 'autoconfirmed' ] },
		} } );
		const repo = new ApiPageEditPermissionErrorsRepository( api );

		return expect( repo.getPermissionErrors( title ) )
			.rejects
			.toBeInstanceOf( TechnicalProblem );
	} );

	it( 'rejects with TechnicalProblem if API does not return semi-protected levels', () => {
		const title = 'Title';
		const api = mockApi( { query: { pages: [ {
			title,
			actions: { edit: [] },
		} ] } } );
		const repo = new ApiPageEditPermissionErrorsRepository( api );

		return expect( repo.getPermissionErrors( title ) )
			.rejects
			.toBeInstanceOf( TechnicalProblem );
	} );

	it( 'rejects with TechnicalProblem if the API does not return raw errors', () => {
		const title = 'Title';
		const api = mockApi( { query: {
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

		return expect( repo.getPermissionErrors( title ) )
			.rejects
			.toBeInstanceOf( TechnicalProblem );
	} );

	it.each( [
		[ '* [[:Page A]]', 1, 'missing trailing newline' ],
		[ '[[:Page A]]\n', 1, 'no list syntax' ],
		[ '* Page A\n', 1, 'no wikilink' ],
		[ '* [[:Page A]]\n* [[:Page B]]\n', 1, 'length mismatch' ],
	] )( 'rejects with TechnicalProblem if the API returns bad cascade-protected pages', ( pages, length ) => {
		const title = 'Title';
		const api = mockApi( { query: {
			pages: [ {
				title,
				actions: { edit: [ {
					code: 'cascadeprotected',
					key: 'cascadeprotected',
					params: [
						length,
						pages,
						'edit',
					],
				} ] },
			} ],
			restrictions: { semiprotectedlevels: [ 'autoconfirmed' ] },
		} } );
		const repo = new ApiPageEditPermissionErrorsRepository( api );

		return expect( repo.getPermissionErrors( title ) )
			.rejects
			.toBeInstanceOf( TechnicalProblem );
	} );

} );
