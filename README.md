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

Copy config-example.json into config.json (will be gitignored) and define your stuff there. Keep reading for more on these files.

	cp config-example.json config.json

Copy the aws_sdk example file and then edit it. Include your AWS keys and bucket in there (it will be gitignored). Do not edit the original example file.
	
	cp src/application/config/aws_sdk-example.php src/application/config/aws_sdk.php 

### Usage

Run the included run.sh with bash to process every image.

	bash /home/ubuntu/aspector/run.sh

To reset, run the following after changing your batch file.

	/home/ubuntu/aspector/src/index.php processor reset

If something goes wrong, an error will be thrown to the terminal. You can always rerun the processor and it will continue where it left off. 

### How it works

To be documented...

### Requirements

All requirements will be installed when running `provision.sh`.

- PHP >= 5.3
- CodeIgniter
- AWS PHP SDK
- Intervention Image

### Roadmap

- config.json
	- prefix or suffix (bucket/xm/file.jpg or bucket/file_xm.jpg)
	- convert to format: jpg, png, gif, etc.
	- optimization quality
- Batch from URL instead of bucket key
- Remove CI dependency


