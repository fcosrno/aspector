<?php
defined('BASEPATH') OR exit('No direct script access allowed');
set_time_limit(0);
use Intervention\Image\ImageManager;

class Processor extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->config('aws_sdk');
		$this->load->model(array('json_model'));
		$this->load->library(array('aws_sdk'));
		$this->load->helper(array('html','file'));
		$this->load->driver('cache', array('adapter' => 'redis'));
	}
	public function reset()
	{
		foreach(array('queue','total','progress') as $var){
			$this->cache->delete($var);
		}
	}

	public function _url_exists($url)
	{
		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_exec($ch);
		$retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if($retcode=='200')return true;
	}

	public function run()
	{		
		// Queue items from batch.json
		if ( ! $queue = $this->cache->get('queue')){
			$this->cache->save('queue', file_get_contents('./../db/batch.json'), 31557600 );
			$count = count(json_decode($this->cache->get('queue')));
			$this->cache->save('total',$count , 31557600 );
		}else return;

		$manager = new ImageManager(array('driver' => 'imagick'));

		// Process all queued images
		try{
			foreach(json_decode($this->cache->get('queue')) as $key){

				$this->cache->increment('progress');

				$bucket = $this->config->item('aws_bucket');
				$src = 'https://s3.amazonaws.com/'.$bucket.'/'.$key;

				// If the file doesn't exists, skip to the next
				if(!$this->_url_exists($src))continue;

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

					echo 'Progress '.floor(($this->cache->get('progress')/$this->cache->get('total'))*100).'% : https://s3.amazonaws.com/'.$bucket.'/'.$filename.PHP_EOL;
					
					$this->_s3_rewrite($img->encode(null, 70),$bucket,$filename);
				}
			}
		}catch (Exception $e){
			echo "$e".PHP_EOL;
		}
	}
	public function _s3_rewrite($resource,$bucket,$filename)
	{
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
			    'Body'		  =>  $resource,
			    'ContentType' => 'image/jpeg'
			))->toArray();
		}
	}
}
