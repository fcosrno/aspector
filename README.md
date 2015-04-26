# Aspector

An AWS instance to process images from anywhere into an S3 bucket.

## Provision the machine

	Ubuntu Server 14.04 LTS (HVM), SSD Volume Type - ami-d05e75b8

## Installation

First lets install git.

	sudo apt-get update && sudo apt-get install git

Clone this repo anywhere in your machine. Home is a good place.

	cd ~
	git clone https://github.com/fcosrno/aspector.git
	cd aspector

Install dependencies

	sudo sh provision.sh

### Setup

Copy db file examples and define with your stuff. Keep reading for more on these files.

	cp db/batch-example.json db/batch.json 

	cp db/format-example.json db/format.json 

Copy the aws_sdk example file and then edit it. Include your AWS keys and bucket in there. Do not edit the original example file.
	
	cp src/application/config/aws_sdk-example.php src/application/config/aws_sdk.php 

### Process

First, make sure you run init just to make the SQLite file.

	/home/ubuntu/aspector/src/index.php processor init

Then run the following. It will process 10 files at a time. Don't ask me why just yet.

	/home/ubuntu/aspector/src/index.php processor run

You can set up cron to run every minute:
	
	crontab -e
	0 * * * * php /home/ubuntu/aspector/src/index.php processor run

To check the progress:

	/home/ubuntu/aspector/src/index.php processor status

To start over, run reset, which truncates the table then init. Alternatively you can delete the database in db/data.sqlite instead of running reset.

	php /home/ubuntu/aspector/src/index.php processor reset
	php /home/ubuntu/aspector/src/index.php processor init


