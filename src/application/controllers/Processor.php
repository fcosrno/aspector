<?php
defined('BASEPATH') OR exit('No direct script access allowed');
set_time_limit(0);
use Intervention\Image\ImageManager;

class Processor extends CI_Controller {

	private $rewrite;
	private $format;
	private $chunks;
	private $debug=true;

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
	public function get_chunks()
	{
		echo $this->chunks.PHP_EOL;
	}
	public function reset()
	{
		$files = glob('application/cache/*');
		foreach($files as $file){
			if(is_file($file) && $file!='application/cache/index.html'){
				unlink($file);
			}
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

	public function run($chunk)
	{	
		$chunk--;
		if($chunk>=$this->chunks)die("The chunk you requested surpasses the number of chunks defined in config.json".PHP_EOL);

		$queue = json_decode(file_get_contents('./../batch.json'));
		$queue_total = count($queue);

		$chunk_length = ceil($queue_total/$this->chunks);
		$chunk_offset = ceil($chunk_length*$chunk);
		if(($chunk+1)==$this->chunks)$chunk_length = ceil($queue_total - $chunk_offset);

		$queue = array_slice($queue, $chunk_offset,$chunk_length);

		if($this->cache->file->get('progress'.$chunk))$queue=array_slice($queue, $this->cache->file->get('progress'.$chunk),null,true);
		
		try{
			$manager = new ImageManager(array('driver' => 'imagick'));
			foreach($queue as $key=>$source){

				$this->cache->file->save('progress'.$chunk,$key,31557600);

				$bucket = $this->config->item('aws_bucket');
				$src = 'https://s3.amazonaws.com/'.$bucket.'/'.$source;

				// If the file doesn't exist or if its invalid, skip to the next
				if(!$this->_url_exists($src))continue;
				if(!$this->_is_valid_image($src))continue;
				if(!$this->_is_readable_binary($src))continue;

				foreach($this->format as $suffix=>$size){
					
					$filename=$suffix.'/'.$source;

					if(!$this->rewrite){
						if($this->_url_exists('https://s3.amazonaws.com/'.$bucket.'/'.$filename)){
							echo "File exists! Skipping ".$this->cache->file->get('progress'.$chunk)." of ".$queue_total.PHP_EOL;
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
					
					echo 'Progress '.floor((($this->cache->file->get('progress'.$chunk)+1)/$queue_total)*100).'%: https://s3.amazonaws.com/'.$bucket.'/'.$filename.PHP_EOL;
					
					if(!$this->debug)$this->_s3_rewrite($img->encode(null, 70),$bucket,$filename);
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
