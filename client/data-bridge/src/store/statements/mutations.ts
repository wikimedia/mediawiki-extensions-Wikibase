import { StatementMap } from '@wmde/wikibase-datamodel-types';
import { StatementState } from './StatementState';
import { Mutations } from 'vuex-smart-module';
import EntityId from '@/datamodel/EntityId';
import clone from '@/store/clone';

export class StatementMutations extends Mutations<StatementState> {
	public setStatements(
		payload: { entityId: EntityId; statements: StatementMap },
	): void {
		this.state[ payload.entityId ] = clone( payload.statements );
	}

	public reset(): void {
		for ( const propertyId of Object.getOwnPropertyNames( this.state ) ) {
			delete this.state[ propertyId ];
		}
	}
}
