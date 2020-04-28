import EntityRevision from '@/datamodel/EntityRevision';

/**
 * A repository for reading a specific revision of an entity.
 */
interface ReadingEntityRevisionRepository {
	/**
	 * Get the specified revision of the entity.
	 *
	 * Implementations may also implement {@link ReadingEntityRepository},
	 * which effectively makes the `revision` parameter optional
	 * (omitting it returns the latest revision instead of a specific one).
	 *
	 * @param id The entity ID.
	 * @param revision The revision ID.
	 */
	getEntity( id: string, revision: number ): Promise<EntityRevision>;
}

export default ReadingEntityRevisionRepository;
