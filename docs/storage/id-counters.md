# Entity ID Counters

Entity IDs are created based on counters maintained by Wikibase in the [wb_id_counters] table.
 - Some extensions also use this table, such as Lexeme.
 - Other extensions such as EntitySchema choose not to use the table (instead creating their own).

You can view the table spec at [wb_id_counters].

### Code

The [IdGenerator] interface is generally used when interacting with this storage.
 - [SqlIdGenerator] is the oldest ID generator implementation that works for all DB types.
 - [UpsertSqlIdGenerator] is a newer 'better' generator that only works for MySql.

The [IdGenerator] implementation to be used can be switched using the [repo_idGenerator] setting.
A setting of `auto` means using the `UpsertSqlIdGenerator` if the database is a MySQL database and the `SqlIdGenerator` otherwise.

### Write scaling

A separate db connection can be used for both [IdGenerator] implementations by setting the [idGeneratorSeparateDbConnection] setting.

Both [UpsertSqlIdGenerator] and [idGeneratorSeparateDbConnection] may be desired for repositories that have a high number of creates and writes.

```php
$wgWBRepoSettings['idGenerator'] = 'mysql-upsert';
$wgWBRepoSettings['idGeneratorSeparateDbConnection'] = true;
```

[IdGenerator]: @ref Wikibase::Repo::Store::IdGenerator
[idGeneratorSeparateDbConnection]: @ref repo_idGeneratorSeparateDbConnection
[SqlIdGenerator]: @ref Wikibase::Repo::Store::Sql::SqlIdGenerator
[UpsertSqlIdGenerator]: @ref Wikibase::Repo::Store::Sql::UpsertSqlIdGenerator
[repo_idGenerator]: @ref repo_idGenerator "idGenerator"
[wb_id_counters]: @ref docs_sql_wb_id_counters
