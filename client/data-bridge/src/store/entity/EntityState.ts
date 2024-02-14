import EntityId from '@/datamodel/EntityId';

export class EntityState {
	public id: EntityId = '';
	public baseRevision = 0;
	public tempUserRedirectUrl: URL|null = null;
}
