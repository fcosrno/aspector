#!/usr/bin/env bash

apt-get update

# Install vim, curl and python
apt-get install -y vim curl python-software-properties

# Install PHP and others
apt-get install -y php5 php5-sqlite php5-gd php5-curl php5-mcrypt php5-memcached php5-cli php5-imagick

# Clean up
apt-get clean

# Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
composer install

# CodeIgniter
git clone https://github.com/bcit-ci/CodeIgniter.git vendor/CodeIgniter

# Redis
# http://redis.io/topics/quickstart
wget http://download.redis.io/redis-stable.tar.gz
tar xvzf redis-stable.tar.gz
cd redis-stable
make

sudo cp src/redis-server /usr/local/bin/
sudo cp src/redis-cli /usr/local/bin/

sudo mkdir /etc/redis
sudo mkdir /var/redis

sudo cp utils/redis_init_script /etc/init.d/redis_6379
sudo cp redis.conf /etc/redis/6379.conf
sudo mkdir /var/redis/6379

sudo update-rc.d redis_6379 defaults
/etc/init.d/redis_6379 start

# phpredis
# https://serverpilot.io/community/articles/how-to-install-the-php-redis-extension.html
sudo apt-get install gcc make autoconf libc-dev
sudo pecl install redis
sudo bash -c "echo extension=redis.so > /etc/php5/conf.d/redis.ini"

