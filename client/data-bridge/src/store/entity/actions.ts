import Entity from '@/datamodel/Entity';
import EntityRevision from '@/datamodel/EntityRevision';
import { Store } from 'vuex';
import Application from '@/store/Application';
import { EntityState } from '@/store/entity';
import { Actions, Context, Getters } from 'vuex-smart-module';
import { EntityMutations } from '@/store/entity/mutations';
import { statementModule } from '@/store/statements';
import StatementMap from '@/datamodel/StatementMap';

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

	public entitySave(
		statements: StatementMap,
	): Promise<void> {
		const entityId = this.state.id;
		const entity = new Entity( entityId, statements );
		const base = new EntityRevision(
			new Entity( entityId, this.statementsModule.state[ entityId ] ),
			this.state.baseRevision,
		);

		return this.store.$services.get( 'writingEntityRepository' )
			.saveEntity( entity, base )
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
