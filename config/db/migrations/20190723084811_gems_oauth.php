<?php

use Phinx\Migration\AbstractMigration;

class GemsOauth extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    addCustomColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Any other destructive changes will result in an error when trying to
     * rollback the migration.
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        $accessTokens = $this->table('gems__oauth_access_tokens', ['id' => 'access_token_id', 'signed' => false]);
        $accessTokens
            ->addColumn('id', 'string', ['limit' => 100])
            ->addColumn('user_id', 'string', ['limit' => 255])
            ->addColumn('client_id', 'string', ['limit' => 255])
            ->addColumn('scopes', 'text', ['null' => true])
            ->addColumn('revoked', 'boolean')
            ->addColumn('expires_at', 'datetime')
            ->addColumn('changed', 'timestamp')
            ->addColumn('changed_by', 'biginteger')
            ->addColumn('created', 'timestamp')
            ->addColumn('created_by', 'biginteger')
            ->addIndex(['id', 'user_id'])
            ->create();

        $authCodes = $this->table('gems__oauth_auth_codes', ['id' => 'auth_code_id', 'signed' => false]);
        $authCodes
            ->addColumn('id', 'string', ['limit' => 100])
            ->addColumn('user_id', 'string', ['limit' => 255])
            ->addColumn('client_id', 'string', ['limit' => 255])
            ->addColumn('scopes', 'text', ['null' => true])
            ->addColumn('redirect', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('revoked', 'boolean')
            ->addColumn('expires_at', 'datetime')
            ->addIndex(['id', 'user_id'])
            ->create();

        $clients = $this->table('gems__oauth_clients', ['signed' => false]);
        $clients
            ->addColumn('user_id', 'string', ['limit' => 255])
            ->addColumn('name', 'string', ['limit' => 255])
            ->addColumn('secret', 'string', ['limit' => 255])
            ->addColumn('redirect', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('active', 'boolean')
            ->addColumn('changed', 'timestamp')
            ->addColumn('changed_by', 'biginteger')
            ->addColumn('created', 'timestamp')
            ->addColumn('created_by', 'biginteger')
            ->addIndex(['user_id'])
            ->create();

        $refreshTokens = $this->table('gems__oauth_refresh_tokens', ['id' => 'refresh_token_id', 'signed' => false]);
        $refreshTokens
            ->addColumn('id', 'string', ['limit' => 100])
            ->addColumn('access_token_id', 'string', ['limit' => 100])
            ->addColumn('revoked', 'boolean')
            ->addColumn('expires_at', 'datetime', ['null' => true])
            ->addIndex(['id', 'access_token_id'])
            ->create();

        $clients = $this->table('gems__oauth_clients', ['signed' => false]);
        $clients
            ->addColumn('name', 'string', ['limit' => 255])
            ->addColumn('description', 'string', ['limit' => 255])
            ->addColumn('active', 'boolean')
            ->addColumn('changed', 'timestamp')
            ->addColumn('changed_by', 'biginteger')
            ->addColumn('created', 'timestamp')
            ->addColumn('created_by', 'biginteger')
            ->addIndex(['name'])
            ->create();
    }
}
