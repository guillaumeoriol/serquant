@echo off

# %1=/c
# %2=DB_USERNAME
# %3=DB_PASSWORD
# %4=DB_ENCODING
# %5=DB_HOST
# %6=DB_DBNAME

# mysql should be in Windows path

##### PROGRAMME #####
echo This script will:
echo 1. DROP the %6 database if it exists
echo 2. recreate the %6 database
echo 3. create tables
echo.

echo.
echo mysql --user=%2 --password=*** --default-character-set=%4 --host=%5 --execute="DROP DATABASE IF EXISTS %6";
mysql --user=%2 --password=%3 --default-character-set=%4 --host=%5 --execute="DROP DATABASE IF EXISTS %6";

echo mysql --user=%2 --password=*** --default-character-set=%4 --host=%5 --execute="CREATE DATABASE %6 DEFAULT CHARACTER SET %4 COLLATE utf8_general_ci";
mysql --user=%2 --password=%3 --default-character-set=%4 --host=%5 --execute="CREATE DATABASE %6 DEFAULT CHARACTER SET %4 COLLATE utf8_general_ci";

echo mysql --user=%2 --password=*** --default-character-set=%4 --host=%5 %6 ^< schema.sql
mysql --user=%2 --password=%3 --default-character-set=%4 --host=%5 %6 < schema.sql

echo.
echo The script completed successfully.
