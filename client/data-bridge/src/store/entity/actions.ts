import EntityRevision from '@/datamodel/EntityRevision';
import { Store } from 'vuex';
import Application from '@/store/Application';
import { EntityState } from '@/store/entity';
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

	public entityInit(
		payload: { entity: string; revision?: number },
	): Promise<void> {
		return this.store.$services.get( 'readingEntityRepository' )
			.getEntity( payload.entity, payload.revision )
			.then( ( entityRevision: EntityRevision ) => this.dispatch( 'entityWrite', entityRevision ) );
	}

	public entitySave(): Promise<void> {
		const entityRevision = new EntityRevision(
			{
				id: this.state.id,
				statements: this.statementsModule.state[ this.state.id ],
			},
			this.state.baseRevision,
		);

		return this.store.$services.get( 'writingEntityRepository' )
			.saveEntity( entityRevision )
			.then( ( entityRevision: EntityRevision ) => this.dispatch( 'entityWrite', entityRevision ) );
	}

	public entityWrite(
		entityRevision: EntityRevision,
	): Promise<void> {
		this.commit( 'updateRevision', entityRevision.revisionId );
		this.commit( 'updateEntity', entityRevision.entity );

		return this.statementsModule.dispatch( 'initStatements', {
			entityId: entityRevision.entity.id,
			statements: entityRevision.entity.statements,
		} );
	}
}
