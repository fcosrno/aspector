# Aspector

An AWS instance to process images from anywhere into an S3 bucket.

## Provision the machine

Setup Ubuntu box with PHP, Codeigniter, etc.

	...

Install the app... ???

### Setup

Log into machine. Define the images you want to process in `/var/www/aspector/src/db/batch.json`. Define the sizes desired in `/var/www/aspector/src/db/format.json`

Include your AWS keys and bucket in the Apache configuration file. Don't save them in the project or it may get commited.

### Process

To run the processor just run this command. It will process 10 files.

	php index.php processor run

You can set up cron to run every minute:
	
	crontab -e
	0 * * * * php /var/www/aspector/src/index.php processor run


