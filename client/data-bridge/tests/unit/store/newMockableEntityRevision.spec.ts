import {
	Snak,
	StatementMap,
} from '@wmde/wikibase-datamodel-types';
import newMockableEntityRevision from './newMockableEntityRevision';

describe( 'newMockableEntityRevision', () => {
	it( 'contains a entity', () => {
		const entityRevision = newMockableEntityRevision();
		expect( entityRevision.entity ).toBeDefined();
	} );

	describe( 'Entity', () => {
		describe( 'id', () => {
			it( 'is set to Q1 by default', () => {
				const entityRevision = newMockableEntityRevision();
				expect( entityRevision.entity.id ).toBe( 'Q1' );
			} );

			it( 'is set as passed', () => {
				const id = 'Q4711';
				const entityRevision = newMockableEntityRevision( { id } );
				expect( entityRevision.entity.id ).toBe( id );
			} );
		} );

		describe( 'statements', () => {
			it( 'contains a empty by default', () => {
				const entityRevision = newMockableEntityRevision();
				expect( entityRevision.entity.statements ).toStrictEqual( {} );
			} );

			it( 'is set as passed', () => {
				const statements: StatementMap = {
					P23: [ {
						type: 'statement',
						id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
						rank: 'normal',
						mainsnak: {} as Snak,
					} ],
				};

				const entityRevision = newMockableEntityRevision( { statements } );
				expect( entityRevision.entity.statements ).toBe( statements );
			} );
		} );
	} );

	describe( 'revisionId', () => {
		it( 'is set to 0 by default', () => {
			const entityRevision = newMockableEntityRevision();
			expect( entityRevision.revisionId ).toBe( 0 );
		} );

		it( 'are set as passed', () => {
			const revisionId = 23;
			const entityRevision = newMockableEntityRevision( { revisionId } );
			expect( entityRevision.revisionId ).toBe( revisionId );
		} );
	} );
} );
