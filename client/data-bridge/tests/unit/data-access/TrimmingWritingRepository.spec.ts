import {
	Snak,
	Statement,
} from '@wmde/wikibase-datamodel-types';
import ApiWritingRepository from '@/data-access/ApiWritingRepository';
import TrimmingWritingRepository from '@/data-access/TrimmingWritingRepository';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';
import Entity from '@/datamodel/Entity';
import EntityRevision from '@/datamodel/EntityRevision';

describe( 'TrimmingWritingRepository', () => {
	it( 'delegates to inner service without base revision', async () => {
		const response: EntityRevision = {
			entity: { id: 'Q123', statements: { 'P123': [] } },
			revisionId: 123,
		};
		const inner: ApiWritingRepository = {
			saveEntity: jest.fn().mockResolvedValue( response ),
		} as unknown as ApiWritingRepository;
		const repo = new TrimmingWritingRepository( inner );
		const entity: Entity = { id: 'Q456', statements: { 'P456': [] } };

		const actualResponse = await repo.saveEntity( entity );

		expect( actualResponse ).toBe( response );
		expect( inner.saveEntity ).toHaveBeenCalledTimes( 1 );
		expect( inner.saveEntity ).toHaveBeenCalledWith( entity, undefined, true );
	} );

	const snak1: Snak = { snaktype: 'value', datavalue: { type: 'string', value: 'snak1' } } as Snak;
	const snak2: Snak = { snaktype: 'value', datavalue: { type: 'string', value: 'snak2' } } as Snak;
	const statement1 = { id: 'Q1$2bec00c2-8e99-4aef-a47b-058f94b28e01', mainsnak: snak1 } as Statement;
	const statement2 = { id: 'Q1$5be18482-f2b6-4690-8b3f-2022440c884f', mainsnak: snak2 } as Statement;
	const id = 'Q1'; // entity ID, named “id” to abbreviate entity literals below

	it.each( [
		// description, new entity, base entity, expected trimmed entity
		[
			'nothing to trim, empty statements',
			{ id, statements: {} },
			{ id, statements: {} },
			{ id, statements: {} },
		],
		[
			'nothing to trim, edit adds empty statement group',
			{ id, statements: { 'P1': [] } },
			{ id, statements: {} },
			{ id, statements: { 'P1': [] } },
		],
		[
			'nothing to trim, edit adds new statement',
			{ id, statements: { 'P1': [ { /* no id */ type: 'statement' } as Statement ] } },
			{ id, statements: {} },
			{ id, statements: { 'P1': [ { type: 'statement' } as Statement ] } },
		],
		[
			'nothing to trim, edit changes statement value',
			{ id, statements: { 'P1': [ { id: statement1.id, mainsnak: snak2 } as Statement ] } },
			{ id, statements: { 'P1': [ { id: statement1.id, mainsnak: snak1 } as Statement ] } },
			{ id, statements: { 'P1': [ { id: statement1.id, mainsnak: snak2 } as Statement ] } },
		],
		[
			'nothing to trim, edit adds multiple new statements mixing ones with and without ID',
			{ id, statements: { 'P1': [
				statement1,
				{ /* no id */ type: 'statement' } as Statement,
				statement2,
				{ /* no id */ type: 'statement', rank: 'preferred' } as Statement,
			] } },
			{ id, statements: {} },
			{ id, statements: { 'P1': [
				statement1,
				{ /* no id */ type: 'statement' } as Statement,
				statement2,
				{ /* no id */ type: 'statement', rank: 'preferred' } as Statement,
			] } },
		],
		[
			'remove empty statement group',
			{ id, statements: { 'P1': [] } },
			{ id, statements: { 'P1': [] } },
			{ id, statements: {} },
		],
		[
			'remove unchanged statement group',
			{ id, statements: { 'P1': [ statement1 ] } },
			{ id, statements: { 'P1': [ statement1 ] } },
			{ id, statements: {} },
		],
		[
			'remove unchanged statement, edit adds new statement with ID',
			{ id, statements: { 'P1': [ statement1, statement2 ] } },
			{ id, statements: { 'P1': [ statement1 ] } },
			{ id, statements: { 'P1': [ statement2 ] } },
		],
		[
			'remove unchanged statement, edit adds new statement without ID',
			{ id, statements: { 'P1': [ statement1, { /* no id */ rank: 'preferred' } as Statement ] } },
			{ id, statements: { 'P1': [ statement1 ] } },
			{ id, statements: { 'P1': [ { rank: 'preferred' } as Statement ] } },
		],
		[
			'remove unchanged statement, edit adds new statement and demotes old statement',
			{ id, statements: { 'P1': [
				statement1,
				{ id: statement2.id, rank: 'normal' } as Statement,
				{ /* no id */ rank: 'preferred' } as Statement,
			] } },
			{ id, statements: { 'P1': [
				statement1,
				{ id: statement2.id, rank: 'preferred' } as Statement,
			] } },
			{ id, statements: { 'P1': [
				{ id: statement2.id, rank: 'normal' } as Statement,
				{ rank: 'preferred' } as Statement,
			] } },
		],
	] )( 'trims entity (%s)', async ( _desc: string, newEntity: Entity, baseEntity: Entity, trimmed: Entity ) => {
		const inner: ApiWritingRepository = { saveEntity: jest.fn() } as unknown as ApiWritingRepository;
		const base = new EntityRevision( baseEntity, 123 );
		const repo = new TrimmingWritingRepository( inner );
		const assertUser = true;

		await repo.saveEntity( newEntity, base, assertUser );

		expect( inner.saveEntity ).toHaveBeenCalledWith( trimmed, base, assertUser );
	} );

	it.each( [
		// expected error, new entity, base entity
		[
			'Entity ID mismatch',
			{ id: 'Q1', statements: {} },
			{ id: 'Q2', statements: {} },
		],
		[
			'Cannot remove statement group',
			{ id, statements: {} },
			{ id, statements: { 'P1': [] } },
		],
		[
			'Cannot remove statement',
			{ id, statements: { 'P1': [] } },
			{ id, statements: { 'P1': [ { id: 'P1$1' } as Statement ] } },
		],
	] )( 'rejects invalid data (%s)', ( error: string, newEntity: Entity, baseEntity: Entity ) => {
		const inner: ApiWritingRepository = { saveEntity: jest.fn() } as unknown as ApiWritingRepository;
		const base = new EntityRevision( baseEntity, 123 );
		const repo = new TrimmingWritingRepository( inner );
		const assertUser = true;

		return expect( repo.saveEntity( newEntity, base, assertUser ) )
			.rejects.toStrictEqual( new TechnicalProblem( error ) );
	} );
} );
