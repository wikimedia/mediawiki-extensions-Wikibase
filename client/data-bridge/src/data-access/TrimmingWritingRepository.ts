import {
	Statement,
	StatementMap,
} from '@wmde/wikibase-datamodel-types';
import ApiWritingRepository from '@/data-access/ApiWritingRepository';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';
import Entity from '@/datamodel/Entity';
import EntityRevision from '@/datamodel/EntityRevision';
import WritingEntityRepository from '@/definitions/data-access/WritingEntityRepository';
import deepEqual from 'deep-equal';

function statementListById( statementList: readonly Statement[] ): Record<string, Statement> {
	const statementsById: Record<string, Statement> = {};
	for ( const statement of statementList ) {
		if ( statement.id ) {
			statementsById[ statement.id ] = statement;
		}
	}
	return statementsById;
}

/**
 * A {@link WritingEntityRepository} that compares the old and new entity data
 * and sends only parts that changed to an underlying {@link ApiWritingRepository}.
 */
export default class TrimmingWritingRepository implements WritingEntityRepository {
	private apiWritingRepository: ApiWritingRepository;

	public constructor( apiWritingRepository: ApiWritingRepository ) {
		this.apiWritingRepository = apiWritingRepository;
	}

	public async saveEntity( entity: Entity, base?: EntityRevision, assertUser = true ): Promise<EntityRevision> {
		if ( base ) {
			entity = this.trimEntity( entity, base.entity );
		}
		return this.apiWritingRepository.saveEntity( entity, base, assertUser );
	}

	private trimEntity( newEntity: Entity, baseEntity: Entity ): Entity {
		if ( newEntity.id !== baseEntity.id ) {
			throw new TechnicalProblem( 'Entity ID mismatch' );
		}

		const trimmedStatementMap = this.trimStatementMap( newEntity.statements, baseEntity.statements );
		return new Entity( newEntity.id, trimmedStatementMap );
	}

	private trimStatementMap( newStatementMap: StatementMap, baseStatementMap: StatementMap ): StatementMap {
		const trimmedStatementMap: StatementMap = {};
		const propertyIds = new Set( [ ...Object.keys( newStatementMap ), ...Object.keys( baseStatementMap ) ] );
		for ( const propertyId of propertyIds ) {
			if ( propertyId in baseStatementMap ) {
				const baseStatementGroup = baseStatementMap[ propertyId ];
				if ( propertyId in newStatementMap ) {
					const newStatementGroup = newStatementMap[ propertyId ];
					const trimmedStatementGroup = this.trimStatementGroup( newStatementGroup, baseStatementGroup );
					if ( trimmedStatementGroup !== null ) {
						trimmedStatementMap[ propertyId ] = trimmedStatementGroup;
					}
				} else {
					throw new TechnicalProblem( 'Cannot remove statement group' );
				}
			} else {
				// newStatementGroup must exist or else we wouldn’t be in this loop
				trimmedStatementMap[ propertyId ] = newStatementMap[ propertyId ];
			}
		}
		return trimmedStatementMap;
	}

	private trimStatementGroup(
		newStatements: readonly Statement[],
		baseStatements: readonly Statement[],
	): Statement[] | null {
		const baseStatementsById = statementListById( baseStatements );
		const trimmedStatementsGroup = [];
		for ( const newStatement of newStatements ) {
			if ( !newStatement.id ) {
				trimmedStatementsGroup.push( newStatement );
				continue;
			}
			const baseStatement = baseStatementsById[ newStatement.id ];
			delete baseStatementsById[ newStatement.id ];
			if ( baseStatement ) {
				const trimmedStatement = this.trimStatement( newStatement, baseStatement );
				if ( trimmedStatement !== null ) {
					trimmedStatementsGroup.push( trimmedStatement );
				}
			} else {
				trimmedStatementsGroup.push( newStatement );
			}
		}
		const unusedBaseStatementIds = Object.keys( baseStatementsById );
		if ( unusedBaseStatementIds.length ) {
			throw new TechnicalProblem( 'Cannot remove statement' );
		}
		return trimmedStatementsGroup.length ? trimmedStatementsGroup : null;
	}

	private trimStatement( newStatement: Statement, baseStatement: Statement ): Statement | null {
		// statement parts cannot be omitted, so there’s no need to go into any more detail here
		if ( deepEqual( newStatement, baseStatement ) ) {
			return null;
		} else {
			return newStatement;
		}
	}

}
