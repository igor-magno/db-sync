## db-sync

Esse e um projeto simples, criado para ajudar devs que estão trabalhando com migração ou sincronização de banco de dados.

## Bancos suportados

* mysql

## Requisitos

* Docker ou PHP 8.1

## Recursos disponíveis

* Comparação entre dois bancos de dados (Compara a diferença da estrutura e não dos dados).

## Passos para executar comparação entre dois bancos de dados

### Com Docker

1 - Realize o clone do projeto:
```
git clone https://github.com/igor-magno/db-sync.git
```
2 - Acesse a pasta do projeto:
```
cd db-sync
```
3 - Suba o contêiner com o comando:
```
docker-compose up -d
```
4 - Acesse o shell do contêiner com o comando:
```
docker exec -it (nome ou id do conteiner) bash
```
5 - Execute o script de comparação passando os dados de conexão:
```
php db-compare.php host_db_1,port_db_1,db_name_db_1,user_db_1,password_db_1 host_db_2,port_db_2,db_name_db_2,user_db_2,password_db_2
```

### Com PHP 8.1 corretamente instalado

1 - Realize o clone do projeto:
```
git clone https://github.com/igor-magno/db-sync.git
```
2 - Acesse a pasta do projeto:
```
cd db-sync
```
3 - Execute o script de comparação passando os dados de conexão:
```
php db-compare.php host_db_1,port_db_1,db_name_db_1,user_db_1,password_db_1 host_db_2,port_db_2,db_name_db_2,user_db_2,password_db_2
```

