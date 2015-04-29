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

### Usage

Run the following from the CLI to process every image.

	/home/ubuntu/aspector/src/index.php processor run

To reset, run the following after changing your batch file.

	/home/ubuntu/aspector/src/index.php processor reset

If something goes wrong, an error will be thrown to the terminal. You an always rerun the processor and it will continue where it left off. 

### How it works

To be documented...

### Requirements

All requirements will be installed when running `provision.sh`.

- PHP >= 5.3
- CodeIgniter
- Redis
- AWS PHP SDK
- Intervention Image

### Roadmap

- config.json
	- rewrite (bool)
	- prefix or suffix (bucket/xm/file.jpg or bucket/file_xm.jpg)
	- convert to format: jpg, png, gif, etc.
	- optimization quality
- Batch from URL instead of bucket key
- Remove CI dependency


