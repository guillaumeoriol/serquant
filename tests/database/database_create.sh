#!/bin/bash

# $1=/c
# $2=DB_USERNAME
# $3=DB_PASSWORD
# $4=DB_ENCODING
# $5=DB_HOST
# $6=DB_DBNAME

##### VARIABLES ######
SQL_BIN="/opt/local/lib/mysql5/bin/mysql"


##### PROGRAMME #####
echo This script will:
echo 1. DROP the $6 database if it exists
echo 2. recreate the $6 database
echo 3. create tables

echo mysql --user=$2 --password=*** --host=$5 --execute="DROP DATABASE IF EXISTS $6";
$SQL_BIN -u $2 -p$3 -h $5 --execute="DROP DATABASE IF EXISTS $6";

echo mysql --user=$2 --password=*** --host=$5 --execute="CREATE DATABASE $6 DEFAULT CHARACTER SET $4 COLLATE utf8_general_ci";
$SQL_BIN -u $2 -p$3 -h $5 --execute="CREATE DATABASE $6 DEFAULT CHARACTER SET $4 COLLATE utf8_general_ci";
	
echo "mysql --user=$2 --password=*** --host=$5 $6 < schema.sql"
$SQL_BIN -u $2 -p$3 -h $5 $6 < schema.sql

echo The script completed successfully.