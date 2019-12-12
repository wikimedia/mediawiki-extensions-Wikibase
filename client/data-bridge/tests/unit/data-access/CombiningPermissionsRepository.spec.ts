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
} from '@/definitions/data-access/PageEditPermissionsRepository';

describe( 'CombiningPermissionsRepository', () => {

	it( 'passes through repo and client title to underlying service', async () => {
		const repoRepository = { getPermissionErrors: jest.fn( () => Promise.resolve( [] ) ) };
		const repoTitle = 'Repo title';
		const clientRepository = { getPermissionErrors: jest.fn( () => Promise.resolve( [] ) ) };
		const clientTitle = 'Client title';
		const repository = new CombiningPermissionsRepository(
			repoRepository,
			repoTitle,
			clientRepository,
			clientTitle,
		);

		await repository.isUserAllowedToEditPage();

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
			'Repo title',
			mockPermissionErrorsRepository(),
			'Client title',
		);

		const expected: ProtectedReason = {
			type: PageNotEditable.ITEM_FULLY_PROTECTED,
			info: { right },
		};

		return expect( repository.isUserAllowedToEditPage() )
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
			'Repo title',
			mockPermissionErrorsRepository(),
			'Client title',
		);

		const expected: ProtectedReason = {
			type: PageNotEditable.ITEM_SEMI_PROTECTED,
			info: { right },
		};

		return expect( repository.isUserAllowedToEditPage() )
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
			'Repo title',
			mockPermissionErrorsRepository(),
			'Client title',
		);

		const expected: UnknownReason = {
			type: PageNotEditable.UNKNOWN,
			info: {
				code: 'added-by-extension',
				messageKey: 'ext-some-message',
				messageParams: [ 'param' ],
			},
		};

		return expect( repository.isUserAllowedToEditPage() )
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
			'Repo title',
			mockPermissionErrorsRepository( [ error ] ),
			'Client title',
		);

		return expect( repository.isUserAllowedToEditPage() )
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
			'Repo title',
			mockPermissionErrorsRepository( [ error ] ),
			'Client title',
		);

		const expected: UnknownReason = {
			type: PageNotEditable.UNKNOWN,
			info: {
				code: 'added-by-extension',
				messageKey: 'ext-some-message',
				messageParams: [ 'param' ],
			},
		};

		return expect( repository.isUserAllowedToEditPage() )
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
			'Repo title',
			mockPermissionErrorsRepository( [ clientError ] ),
			'Client title',
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

		return expect( repository.isUserAllowedToEditPage() )
			.resolves
			.toStrictEqual( expected );
	} );

	// TODO test multiple errors from repo or multiple errors from client, once “blocked” errors exist

} );
