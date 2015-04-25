<?php
defined('BASEPATH') OR exit('No direct script access allowed');
set_time_limit(0);
use Intervention\Image\ImageManager;

class Processor extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->config('aws_sdk');
		$this->load->model(array('batch_model','log_model','json_model'));
		$this->load->library(array('aws_sdk'));
		$this->load->helper('html_helper');
	}

	public function run()
	{		
		// Save new batch images to db
		$this->batch_model->store_pending_images($this->json_model->get_batch());

		$manager = new ImageManager(array('driver' => 'imagick'));

		// Process 10 oending images at a time
		foreach($this->batch_model->get_pending(10) as $n){
			try{
				$key = $n['key'];
				$bucket = $this->config->item('aws_bucket');
				$src = 'https://s3.amazonaws.com/'.$bucket.'/'.$n['key'];

				foreach($this->json_model->get_formats() as $suffix=>$size){

					// Resize with Intervention
					if($size[0] && $size[1]){
						$img = $manager->make($src)->fit($size[0],$size[1]);
					}elseif($size[0]==0){
						$img = $manager->make($src)->resize(null,$size[1],function ($constraint) {$constraint->aspectRatio(); });

					}elseif($size[1]==0){
						$img = $manager->make($src)->resize($size[0],null,function ($constraint) {$constraint->aspectRatio(); });
					}
					
					$filename=$suffix.'/'.$key;

					// Delete file form S3 if it already exists
					if($this->aws_sdk->doesObjectExist($bucket,$filename)){
						$this->aws_sdk->deleteObject(array(
						    'Bucket' => $bucket,
						    'Key' => $filename
						));
					}

					// Upload to S3
					if(!$this->aws_sdk->doesObjectExist($bucket,$filename)){
						$aws_object=$this->aws_sdk->saveObject(array(
						    'Bucket'      => $bucket,
						    'Key'         => $filename,
						    'ACL'		  => 'public-read',
						    'Body'		  =>  $img->encode(null, 70),
						    'ContentType' => 'image/jpeg'
						))->toArray();
					}
				}

				$this->batch_model->mark($key,'complete');
			}catch (Exception $e){
				// Log any errors
				// $error = (string) $e;
				// $this->log_model->insert_unique(array('type'=>'error','message'=>'Image processing failed on '.$key,'details'=>$error));
			}
		}
		// echo 'complete';
	}
	public function status()
	{
		$pending = $this->batch_model->count_pending();
		echo "<pre>";
		echo print_r($pending);
		echo "</pre>";
		die();
		if($pending)echo "$pending pending".PHP_EOL;
		else echo "complete with ".$this->log_model->count_all()." errors".PHP_EOL;
	}
}
