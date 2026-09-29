#!/usr/bin/env bash
# Cria o banco usado pela suíte de testes, separado do banco de desenvolvimento.
# Executado pelo MySQL apenas na primeira inicialização do volume.

mysql --user=root --password="$MYSQL_ROOT_PASSWORD" <<-EOSQL
    CREATE DATABASE IF NOT EXISTS testing;
    GRANT ALL PRIVILEGES ON \`testing\`.* TO '$MYSQL_USER'@'%';
EOSQL
