#! /bin/bash
echo "Killing containers..."
docker kill innmind
docker kill mq
docker kill neo4j
docker kill mysql
echo "Removing containers..."
docker rm innmind
docker rm mq
docker rm neo4j
docker rm mysql
