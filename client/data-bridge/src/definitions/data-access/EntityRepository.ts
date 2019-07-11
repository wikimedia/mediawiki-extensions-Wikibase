import EntityRevision from '@/datamodel/EntityRevision';

interface EntityRepository {
	getEntity( id: string, revision?: number ): Promise<EntityRevision>;
}

export default EntityRepository;
