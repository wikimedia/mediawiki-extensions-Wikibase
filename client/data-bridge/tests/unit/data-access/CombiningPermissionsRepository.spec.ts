import CombiningPermissionsRepository from '@/data-access/CombiningPermissionsRepository';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';
import PageEditPermissionErrorsRepository, {
	PermissionError,
	PermissionErrorCascadeProtectedPage,
	PermissionErrorBlockedUser,
	PermissionErrorProtectedPage,
	PermissionErrorType,
	PermissionErrorUnknown,
} from '@/definitions/data-access/PageEditPermissionErrorsRepository';
import {
	BlockReason,
	CascadeProtectedReason,
	PageNotEditable,
	ProtectedReason,
	UnknownReason,
} from '@/definitions/data-access/BridgePermissionsRepository';

describe( 'CombiningPermissionsRepository', () => {

	it( 'passes through repo and client title to underlying service', async () => {
		const repoRepository = { getPermissionErrors: jest.fn().mockResolvedValue( [] ) };
		const repoTitle = 'Repo title';
		const clientRepository = { getPermissionErrors: jest.fn().mockResolvedValue( [] ) };
		const clientTitle = 'Client title';
		const repository = new CombiningPermissionsRepository(
			repoRepository,
			clientRepository,
		);

		await repository.canUseBridgeForItemAndPage( repoTitle, clientTitle );

		expect( repoRepository.getPermissionErrors ).toHaveBeenCalledWith( repoTitle );
		expect( clientRepository.getPermissionErrors ).toHaveBeenCalledWith( clientTitle );
	} );

	function mockPermissionErrorsRepository(
		errors: PermissionError[] = [],
	): PageEditPermissionErrorsRepository {
		return {
			getPermissionErrors( _title: string ): Promise<PermissionError[]> {
				return Promise.resolve( errors );
			},
		};
	}

	it( 'detects page protected on repo', () => {
		const right = 'editprotected';
		const error: PermissionErrorProtectedPage = {
			type: PermissionErrorType.PROTECTED_PAGE,
			right,
			semiProtected: false,
		};
		const repository = new CombiningPermissionsRepository(
			mockPermissionErrorsRepository( [ error ] ),
			mockPermissionErrorsRepository(),
		);

		const expected: ProtectedReason = {
			type: PageNotEditable.ITEM_FULLY_PROTECTED,
			info: { right },
		};

		return expect( repository.canUseBridgeForItemAndPage( 'Repo title', 'Client title' ) )
			.resolves
			.toStrictEqual( [ expected ] );
	} );

	it( 'detects page semi-protected on repo', () => {
		const right = 'editsemiprotected';
		const error: PermissionErrorProtectedPage = {
			type: PermissionErrorType.PROTECTED_PAGE,
			right,
			semiProtected: true,
		};
		const repository = new CombiningPermissionsRepository(
			mockPermissionErrorsRepository( [ error ] ),
			mockPermissionErrorsRepository(),
		);

		const expected: ProtectedReason = {
			type: PageNotEditable.ITEM_SEMI_PROTECTED,
			info: { right },
		};

		return expect( repository.canUseBridgeForItemAndPage( 'Repo title', 'Client title' ) )
			.resolves
			.toStrictEqual( [ expected ] );
	} );

	it( 'detects page cascade-protected on repo', () => {
		const pages = [ 'Page A', 'Category:Category B' ];
		const error: PermissionErrorCascadeProtectedPage = {
			type: PermissionErrorType.CASCADE_PROTECTED_PAGE,
			pages,
		};
		const repository = new CombiningPermissionsRepository(
			mockPermissionErrorsRepository( [ error ] ),
			mockPermissionErrorsRepository(),
		);

		const expected: CascadeProtectedReason = {
			type: PageNotEditable.ITEM_CASCADE_PROTECTED,
			info: { pages },
		};
		return expect( repository.canUseBridgeForItemAndPage( 'Repo title', 'Client title' ) )
			.resolves
			.toStrictEqual( [ expected ] );
	} );

	it( 'detects user blocked on repo', () => {
		const error: PermissionErrorBlockedUser = {
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
		};
		const repository = new CombiningPermissionsRepository(
			mockPermissionErrorsRepository( [ error ] ),
			mockPermissionErrorsRepository(),
		);
		const expected: BlockReason = {
			type: PageNotEditable.BLOCKED_ON_REPO_ITEM,
			info: {
				blockId: 456,
				blockedBy: 'ServerAdmin',
				blockedById: 789,
				blockReason: 'Testing for T239336',
				blockedTimestamp: '2019-12-12T11:50:39Z',
				blockExpiry: 'infinite',
				blockPartial: false,
			},
		};
		return expect( repository.canUseBridgeForItemAndPage( 'Repo title', 'Client title' ) )
			.resolves
			.toStrictEqual( [ expected ] );
	} );

	it( 'handles unknown error on repo', () => {
		const error: PermissionErrorUnknown = {
			type: PermissionErrorType.UNKNOWN,
			code: 'added-by-extension',
			messageKey: 'ext-some-message',
			messageParams: [ 'param' ],
		};
		const repository = new CombiningPermissionsRepository(
			mockPermissionErrorsRepository( [ error ] ),
			mockPermissionErrorsRepository(),
		);

		const expected: UnknownReason = {
			type: PageNotEditable.UNKNOWN,
			info: {
				code: 'added-by-extension',
				messageKey: 'ext-some-message',
				messageParams: [ 'param' ],
			},
		};

		return expect( repository.canUseBridgeForItemAndPage( 'Repo title', 'Client title' ) )
			.resolves
			.toStrictEqual( [ expected ] );
	} );

	it( 'detects page cascade-protected on client', () => {
		const pages = [ 'Page A', 'Category:Category B' ];
		const error: PermissionErrorCascadeProtectedPage = {
			type: PermissionErrorType.CASCADE_PROTECTED_PAGE,
			pages,
		};
		const repository = new CombiningPermissionsRepository(
			mockPermissionErrorsRepository(),
			mockPermissionErrorsRepository( [ error ] ),
		);

		const expected: CascadeProtectedReason = {
			type: PageNotEditable.PAGE_CASCADE_PROTECTED,
			info: { pages },
		};

		return expect( repository.canUseBridgeForItemAndPage( 'Repo title', 'Client title' ) )
			.resolves
			.toStrictEqual( [ expected ] );
	} );

	it( 'throws if page protected on client', () => {
		const right = 'editprotected';
		const error: PermissionErrorProtectedPage = {
			type: PermissionErrorType.PROTECTED_PAGE,
			right,
			semiProtected: false,
		};
		const repository = new CombiningPermissionsRepository(
			mockPermissionErrorsRepository(),
			mockPermissionErrorsRepository( [ error ] ),
		);

		return expect( repository.canUseBridgeForItemAndPage( 'Repo title', 'Client title' ) )
			.rejects
			.toStrictEqual(
				new TechnicalProblem( 'Data Bridge should never have been opened on this protected page!' ),
			);
	} );

	it( 'detects user blocked on client', () => {
		const error: PermissionErrorBlockedUser = {
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
		};
		const repository = new CombiningPermissionsRepository(
			mockPermissionErrorsRepository(),
			mockPermissionErrorsRepository( [ error ] ),
		);

		const expected: BlockReason = {
			type: PageNotEditable.BLOCKED_ON_CLIENT_PAGE,
			info: {
				blockId: 456,
				blockedBy: 'ServerAdmin',
				blockedById: 789,
				blockReason: 'Testing for T239336',
				blockedTimestamp: '2019-12-12T11:50:39Z',
				blockExpiry: 'infinite',
				blockPartial: false,
			},
		};

		return expect( repository.canUseBridgeForItemAndPage( 'Repo title', 'Client title' ) )
			.resolves
			.toStrictEqual( [ expected ] );
	} );

	it( 'handles unknown error on client', () => {
		const error: PermissionErrorUnknown = {
			type: PermissionErrorType.UNKNOWN,
			code: 'added-by-extension',
			messageKey: 'ext-some-message',
			messageParams: [ 'param' ],
		};
		const repository = new CombiningPermissionsRepository(
			mockPermissionErrorsRepository(),
			mockPermissionErrorsRepository( [ error ] ),
		);

		const expected: UnknownReason = {
			type: PageNotEditable.UNKNOWN,
			info: {
				code: 'added-by-extension',
				messageKey: 'ext-some-message',
				messageParams: [ 'param' ],
			},
		};

		return expect( repository.canUseBridgeForItemAndPage( 'Repo title', 'Client title' ) )
			.resolves
			.toStrictEqual( [ expected ] );
	} );

	it( 'combines multiple errors from repo and client', () => {
		const right = 'editprotected';
		const repoError1: PermissionErrorProtectedPage = {
			type: PermissionErrorType.PROTECTED_PAGE,
			right,
			semiProtected: false,
		};
		const repoPages = [ 'Wikidata:Main Page' ];
		const repoError2: PermissionErrorCascadeProtectedPage = {
			type: PermissionErrorType.CASCADE_PROTECTED_PAGE,
			pages: repoPages,
		};
		const clientPages = [ 'Wikipedia:Main Page' ];
		const clientError1: PermissionErrorCascadeProtectedPage = {
			type: PermissionErrorType.CASCADE_PROTECTED_PAGE,
			pages: clientPages,
		};
		const clientError2: PermissionErrorUnknown = {
			type: PermissionErrorType.UNKNOWN,
			code: 'added-by-extension',
			messageKey: 'ext-some-message',
			messageParams: [ 'param' ],
		};
		const repository = new CombiningPermissionsRepository(
			mockPermissionErrorsRepository( [ repoError1, repoError2 ] ),
			mockPermissionErrorsRepository( [ clientError1, clientError2 ] ),
		);

		const expected: [ProtectedReason, CascadeProtectedReason, CascadeProtectedReason, UnknownReason] = [
			{
				type: PageNotEditable.ITEM_FULLY_PROTECTED,
				info: { right },
			},
			{
				type: PageNotEditable.ITEM_CASCADE_PROTECTED,
				info: { pages: repoPages },
			},
			{
				type: PageNotEditable.PAGE_CASCADE_PROTECTED,
				info: { pages: clientPages },
			},
			{
				type: PageNotEditable.UNKNOWN,
				info: {
					code: 'added-by-extension',
					messageKey: 'ext-some-message',
					messageParams: [ 'param' ],
				},
			},
		];

		return expect( repository.canUseBridgeForItemAndPage( 'Repo title', 'Client title' ) )
			.resolves
			.toStrictEqual( expected );
	} );

} );
