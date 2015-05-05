<?php
defined('BASEPATH') OR exit('No direct script access allowed');
set_time_limit(0);
use Intervention\Image\ImageManager;

class Processor extends CI_Controller {

	private $rewrite;
	private $format;
	private $chunks;

	function __construct()
	{
		parent::__construct();
		$this->load->config('aws_sdk');
		$this->load->model(array('json_model'));
		$this->load->library(array('aws_sdk'));
		$this->load->helper(array('html','file'));
		$this->load->driver('cache');

		// Settings
		$config = $this->json_model->get_config();
		foreach($this->json_model->get_config() as $key=>$value){
			$this->{$key} = $value;
		}
	}
	public function reset()
	{
		foreach(array('progress') as $var){
			$this->cache->file->delete($var);
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

	public function _is_readable_binary($url)
	{
		$array = @getimagesize($url);
		if(!empty($array))return true;
	}
	public function _is_valid_image($url)
	{
		if(@exif_imagetype($url))return true;
	}

	public function run()
	{		
		$queue = json_decode(file_get_contents('./../db/batch.json'));
		$queue_total = count($queue);
		if($this->cache->file->get('progress'))$queue=array_slice($queue, $this->cache->file->get('progress'),null,true);
		
		try{
			$manager = new ImageManager(array('driver' => 'imagick'));
			foreach($queue as $key=>$source){

				$this->cache->file->save('progress',$key,31557600);

				$bucket = $this->config->item('aws_bucket');
				$src = 'https://s3.amazonaws.com/'.$bucket.'/'.$source;

				// If the file doesn't exist or if its invalid, skip to the next
				if(!$this->_url_exists($src))continue;
				if(!$this->_is_valid_image($src))continue;
				if(!$this->_is_readable_binary($src))continue;

				foreach($this->json_model->get_formats() as $suffix=>$size){
					
					$filename=$suffix.'/'.$source;

					if(!$this->rewrite){
						if($this->_url_exists('https://s3.amazonaws.com/'.$bucket.'/'.$filename)){
							echo "File exists! Skipping ".$this->cache->file->get('progress')." of ".$queue_total.PHP_EOL;
							continue;
						}
					}

					// Resize with Intervention
					if($size[0] && $size[1]){
						$img = $manager->make($src)->fit($size[0],$size[1]);
					}elseif($size[0]==0){
						$img = $manager->make($src)->resize(null,$size[1],function ($constraint) {$constraint->aspectRatio(); });

					}elseif($size[1]==0){
						$img = $manager->make($src)->resize($size[0],null,function ($constraint) {$constraint->aspectRatio(); });
					}
					
					echo 'Progress '.floor((($this->cache->file->get('progress')+1)/$queue_total)*100).'%: https://s3.amazonaws.com/'.$bucket.'/'.$filename.PHP_EOL;
					
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
