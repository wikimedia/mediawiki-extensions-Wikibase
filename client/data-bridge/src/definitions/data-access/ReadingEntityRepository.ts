import EntityRevision from '@/datamodel/EntityRevision';

/**
 * A repository for reading the latest revision of an entity.
 */
interface ReadingEntityRepository {
	/**
	 * Get the latest revision of an entity.
	 *
	 * Implementations may also implement {@link ReadingEntityRevisionRepository},
	 * which effectively adds an optional `revision` parameter to this method.
	 *
	 * @param id The entity ID.
	 */
	getEntity( id: string ): Promise<EntityRevision>;
}

export default ReadingEntityRepository;
