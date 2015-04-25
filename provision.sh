#!/usr/bin/env bash

apt-get update

# Install vim, curl and python
apt-get install -y vim curl python-software-properties

# Install PHP and others
apt-get install -y php5 php5-sqlite php5-gd php5-curl php5-mcrypt php5-memcached php5-cli php5-imagick

# Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# Clean up
apt-get clean
