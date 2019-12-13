import CombiningPermissionsRepository from '@/data-access/CombiningPermissionsRepository';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';
import PageEditPermissionErrorsRepository, {
	PermissionError,
	PermissionErrorProtectedPage,
	PermissionErrorType,
	PermissionErrorUnknown,
} from '@/definitions/data-access/PageEditPermissionErrorsRepository';
import {
	PageNotEditable,
	ProtectedReason,
	UnknownReason,
} from '@/definitions/data-access/BridgePermissionsRepository';

describe( 'CombiningPermissionsRepository', () => {

	it( 'passes through repo and client title to underlying service', async () => {
		const repoRepository = { getPermissionErrors: jest.fn( () => Promise.resolve( [] ) ) };
		const repoTitle = 'Repo title';
		const clientRepository = { getPermissionErrors: jest.fn( () => Promise.resolve( [] ) ) };
		const clientTitle = 'Client title';
		const repository = new CombiningPermissionsRepository(
			repoRepository,
			clientRepository,
		);

		await repository.canUseBridgeForItemAndPage( repoTitle, clientTitle );

		expect( repoRepository.getPermissionErrors ).toHaveBeenCalledWith( repoTitle );
		expect( clientRepository.getPermissionErrors ).toHaveBeenCalledWith( clientTitle );
	} );

	function mockPermissionErrorsRepository( errors: PermissionError[] = [] ): PageEditPermissionErrorsRepository {
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

	it( 'combines errors from repo and client', () => {
		const right = 'editprotected';
		const repoError: PermissionErrorProtectedPage = {
			type: PermissionErrorType.PROTECTED_PAGE,
			right,
			semiProtected: false,
		};
		const clientError: PermissionErrorUnknown = {
			type: PermissionErrorType.UNKNOWN,
			code: 'added-by-extension',
			messageKey: 'ext-some-message',
			messageParams: [ 'param' ],
		};
		const repository = new CombiningPermissionsRepository(
			mockPermissionErrorsRepository( [ repoError ] ),
			mockPermissionErrorsRepository( [ clientError ] ),
		);

		const expected: [ProtectedReason, UnknownReason] = [
			{
				type: PageNotEditable.ITEM_FULLY_PROTECTED,
				info: { right },
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

	// TODO test multiple errors from repo or multiple errors from client, once “blocked” errors exist

} );
