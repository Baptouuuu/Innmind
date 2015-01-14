#! /bin/bash
docker run -d --name mysql -e MYSQL_ROOT_PASSWORD=root mysql:5.6.22
docker run -d --name neo4j -p 7474:7474 tpires/neo4j
docker run -d --name mq -e RABBITMQ_NODENAME=rabbit rabbitmq:3-management
docker run -d --name innmind --link mysql:mysql --link neo4j:neo4j --link mq:mq -p 8080:80 -v $PWD:/var/www -e DOCKER=true innmind ./docker/init.sh
