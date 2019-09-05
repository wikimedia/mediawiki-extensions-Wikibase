import EntityRevision from '@/datamodel/EntityRevision';

interface ReadingEntityRepository {
	getEntity( id: string, revision?: number ): Promise<EntityRevision>;
}

export default ReadingEntityRepository;
