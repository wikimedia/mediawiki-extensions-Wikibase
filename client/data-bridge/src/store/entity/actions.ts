import EntityRevision from '@/datamodel/EntityRevision';
import { Store } from 'vuex';
import Application from '@/store/Application';
import { EntityState } from '@/store/entity';
import {
	ENTITY_INIT,
	ENTITY_SAVE,
	ENTITY_WRITE,
} from '@/store/entity/actionTypes';
import {
	ENTITY_UPDATE,
	ENTITY_REVISION_UPDATE,
} from '@/store/entity/mutationTypes';
import {
	STATEMENTS_INIT,
} from '@/store/statements/actionTypes';
import { Actions, Context, Getters } from 'vuex-smart-module';
import { EntityMutations } from '@/store/entity/mutations';
import { statementModule } from '@/store/statements';

export class EntityActions extends Actions<EntityState, Getters<EntityState>, EntityMutations, EntityActions> {
	private store!: Store<Application>;
	private statementsModule!: Context<typeof statementModule>;

	public $init( store: Store<Application> ): void {
		this.store = store;
		this.statementsModule = statementModule.context( store );
	}

	public [ ENTITY_INIT ](
		payload: { entity: string; revision?: number },
	): Promise<unknown> {
		return this.store.$services.get( 'readingEntityRepository' )
			.getEntity( payload.entity, payload.revision )
			.then( ( entityRevision: EntityRevision ) => this.dispatch( ENTITY_WRITE, entityRevision ) );
	}

	public [ ENTITY_SAVE ](): Promise<unknown> {
		const entityRevision = new EntityRevision(
			{
				id: this.state.id,
				statements: this.statementsModule.state[ this.state.id ],
			},
			this.state.baseRevision,
		);

		return this.store.$services.get( 'writingEntityRepository' )
			.saveEntity( entityRevision )
			.then( ( entityRevision: EntityRevision ) => this.dispatch( ENTITY_WRITE, entityRevision ) );
	}

	public [ ENTITY_WRITE ](
		entityRevision: EntityRevision,
	): Promise<unknown> {
		this.commit( ENTITY_REVISION_UPDATE, entityRevision.revisionId );
		this.commit( ENTITY_UPDATE, entityRevision.entity );

		return this.statementsModule.dispatch( STATEMENTS_INIT, {
			entityId: entityRevision.entity.id,
			statements: entityRevision.entity.statements,
		} );
	}
}
