<?php

require __DIR__.'/ImageException.php';
/**
 * @author artisticphoenix
 * @subpackage Image
 *
 */
Class Image{
	
	const MODE_CROP = 'crop';
	const MODE_STRETCH = 'stretch';
	const MODE_SHRINK = 'shirnk';
	const MODE_FILL = 'fill';
	
	const POS_TOP = 1;
	const POS_BOTTOM = 2;
	
	const POS_LEFT = 4;
	const POS_RIGHT = 8;
	
	public $img_src = '';
	public $image = null;
	public $image_type = null;
	
	/**
	 * 
	 * @param string $src 
	 */
	public function __construct($file_src=null, $string_src=null){
		if(!is_null($file_src)){
			$this->setImage($file_src);
		}
	}
	
	/**
	 * is the image loaded
	 * @return boolean
	 */
	public function isLoaded(){
		return !is_null($this->image);	
	}
	
	/**
	 * load an image file
	 * @param filepath $file_src
	 * @throws ImageException
	 */
	public function setImage($file_src){
		if(!is_null($file_src)){
			if(!is_file($file_src)){
				throw new ImageException('[ '.$file_src.' ]', ImageException::ER_NO_FILE);
			}
			
			$this->img_src = $file_src;
			$this->image_type = exif_imagetype($this->img_src);
			switch($this->image_type)
			{
				case IMAGETYPE_GIF:
					$this->image = imagecreatefromgif($file_src);
				break;
				case IMAGETYPE_JPEG:
					$this->image = imagecreatefromjpeg($file_src);
				break;
				case IMAGETYPE_PNG:
					$this->image = imagecreatefrompng($file_src);
					imagealphablending($this->image, true); // setting alpha blending on
					imagesavealpha($this->image, true); // save alphablending setting (important)
				break;
				case IMAGETYPE_WBMP:
					$this->image = imagecreatefromwbmp($file_src);
				break;
				default:
					throw new ImageException('', ImageException::ER_MIME);
			}
		}
	}
	
	/**
	 * get the image height - y
	 * @return number
	 */
	public function height(){
		return imagesy($this->image);
	}
	
	/**
	 * get the image width - x
	 * @return number
	 */
	public function width(){
		return imagesx($this->image);
	}
	
	/**
	 * get the aspect of image
	 * @return number
	 */
	public function getAspect(){
		return $this->width() / $this->height();
	}
	
	/**
	 * get the image info
	 * @return array:
	 */
	public function getInfo(){
		return $this->image_info;
	}
	
	//@todo
	public function calc_offset($src_width, $src_height, $dest_width, $dest_height, $location=0){
		
		
		
		if($location & self::POS_TOP){ //bitwise &
			
		}else if($location & self::POS_BOTTOM){
			
		}else{
			$centreY = round($dest_height / 2);
			$y = $centreY - $src_height / 2;
		}
		
		if($location & self::POS_LEFT){
				
		}else if($location & self::POS_RIGHT){
				
		}else{
			$centreX = round($dest_width / 2);
			$x = $centreX - $src_width / 2;
		}
		return array('x'=>$x, 'y'=>$y);
	}
	
	/**
	 * get the greatest dimension
	 * @return string
	 */
	public function greaterDim(){
		if($this->width() > $this->height()){
			return 'width';
		}else if($this->width() < $this->height()){
			return 'height';
		}else{
			return 'equal';
		}
	}
	
	/**
	 * get the image orientation
	 * @return string
	 */
	public function getOrientation(){
		$dim = $this->GreaterDim();
		if($dim == 'height'){
			return 'portrait';
		}else if($dim == 'width'){
			return 'landscape';
		}else{
			return 'equal';		
		}
	}
	
	///transforms//////
	/**
	 * fit the image in a box
	 * @param number $width
	 * @param number $height
	 * @param string $mode
	 */
	public function fitBox($width, $height, $mode=false, $position=32){
		
		switch ($mode){
			case self::MODE_CROP:
				//shrink the shortest dim to fit in the box - then center crop the exess
				if($this->width() <= $this->height() && $this->width() > $width){
					$this->resizeToWidth($width);
				}else if($this->width() > $this->height() && $this->height() > $height){
					$this->resizeToHeight($height);
				}else{
					print_rr('TODO');
				}

				$this->cropTo($width, $height);
			break;
			case self::MODE_FILL:
				$this->fitBox($width, $height, self::MODE_SHRINK);
				$this->expandCanvaseTo($width, $height);
			break;
			case self::MODE_SHRINK:
				$this->resizeToHeight($height);
				//shrink to fit in the box bounds
				if($this->width() > $width){
					$this->resizeToWidth($width);
				}	
			break;
			case self::MODE_STRETCH:
			default:
				//stretch the image to fit in the box bounds
				$this->resize($width, $height);
			break;
		}
		
	}
	
	/**
	 * scale image by percent (eg. 100 - full size)
	 * @param int $scale
	 */
	function scale($scale) {
		$width = $this->width() * $scale / 100;
		$height = $this->height() * $scale / 100;
		$this->resize($width, $height);
	}
	
	/**
	 * resize image to height
	 * @param number $height
	 */
	function resizeToHeight($height) {
		$ratio = $height / $this->height();
		$width = $this->width() * $ratio;
		$this->resize($width, $height);
	}
	
	/**
	 * resize image to width
	 * @param number $width
	 */
	function resizeToWidth($width) {
		$ratio = $width / $this->width();
		$height = $this->height() * $ratio;
		$this->resize($width, $height);
	}
	

	/**
	 * resize image by width and height (stretch)
	 * @param number $width
	 * @param number $height
	 */
	function resize($width,$height) {
		$new_image = $this->createCanvas($width, $height);
		imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->width(), $this->height());
		$this->image = $new_image;
	} 
	
	/**
	 * 
	 * @param number $width
	 * @param unknown $height
	 * @return resource
	 */
	function createCanvas($width, $height){
		$new_image = imagecreatetruecolor($width, $height);
		
		if($this->image_type==IMAGETYPE_PNG || $this->image_type==IMAGETYPE_GIF){
			imagealphablending($new_image, false);
			imagesavealpha($new_image, true);
			if($this->image_type==IMAGETYPE_GIF){
				$new_image = imagecreate($width, $height);
			}
		}
		$white = imagecolorallocate($new_image, 255, 255, 255);
		imagefill( $new_image, 0, 0, $white );
		return $new_image;
	}
	
	/**
	 * 
	 * @param int $dst_x - x-coordinate of destination point.
	 * @param int $dst_y - y-coordinate of destination point.
	 * @param int $src_x - x-coordinate of source point.
	 * @param int $src_y - y-coordinate of source point.
	 * @param number $width - Source width.
	 * @param number $height - Source height.
	 */
	function crop($dst_x, $dst_y, $src_x, $src_y, $width, $height){
		$new_image = $this->createCanvas($width, $height);
		imagecopy ( $new_image, $this->image, $dst_x, $dst_y, $src_x, $src_y, $width, $height );
		$this->image = $new_image;
	}
	
	/**
	 * 
	 * @param number $width
	 * @param number $height
	 * @param int $position - @todo
	 */
	function cropTo($width, $height, $position=32){
		$centreX = round($this->width() / 2);
		$centreY = round($this->height() / 2);
		$x = $centreX - $width / 2;
		$y = $centreY - $height / 2;
		if ($x < 0) $x = 0;
		if ($y < 0) $y = 0;
		$this->crop(0, 0, $x, $y, $width, $height);
	}
	
	
	function expandCanvaseTo($width, $height, $position=32){
		$centreX = round($width / 2);
		$x = $centreX - $this->width() / 2;
		
		$centreY = round($height / 2);
		$y = $centreY - $this->height() / 2;
		
		if ($x < 0) $x = 0;
		if ($y < 0) $y = 0;
		$new_image = $this->createCanvas($width, $height);
		imagecopy ( $new_image, $this->image, $x, $y, 0, 0, $this->width(), $this->height() );
		$this->image = $new_image;
	}
	
	/**
	 * //@todo test and update this
	 * @param unknown $degrees
	 * @param number $bg
	 */
	function rotate($degrees, $bg=0){
		$this->image = imagerotate($this->image, $degrees, $bg);
		//imagecolortransparent($this->image, imagecolorallocate($this->image, 0, 0, 0));
	}
	
	/**
	 * 
	 * @param string $watermark_src - path to watermark image
	 */
	function watermarkImage($watermark_src) {
		$WaterMark = new self($watermark_src);

		//make sure the watermark will always fit in the image confines
		if($this->width() < $WaterMark->width() || $this->height() < $WaterMark->height()){
			$WaterMark->fitBox($this->width(), $this->height(), self::MODE_SHRINK);
		}
		
		// Set the tile
		imagesettile($this->image, $WaterMark->image);
		// Make the image repeat
		imagefilledrectangle($this->image, 0, 0, $this->width(), $this->height(), IMG_COLOR_TILED);
	}
	
	//@todo http://us2.php.net/manual/en/function.imageflip.php
	
	
   /////////output////////////
   /**
    *  update old image file
    * @param number $res
    */
	function save($res=90){
		$this->Create($this->img_src, $res);
	}
	
	/**
	 * save image with filename dest - or save over file
	 * @param string $dest
	 * @param number $res
	 * @throws ImageException
	 */
	function create($dest, $res=90){	
		
		switch($this->image_type){
			case IMAGETYPE_GIF:
				imagegif($this->image, $dest, $res);
			break;
			case IMAGETYPE_JPEG:
				imagejpeg($this->image, $dest, $res);
			break;
			case IMAGETYPE_PNG:
				$res = ceil($res*0.1); //convert from 0-100 to 1-10
				imagepng($this->image, $dest, $res);
			break;
			case IMAGETYPE_WBMP:
				imagewbmp($this->image, $dest, $res);
			break;
			default:
				throw new ImageException('', ImageException::ER_MIME);
		}
	}
	
	/**
	 * render to browser
	 * @param number $res
	 * @throws ImageException
	 */
	function render($res=90){   
		switch($this->image_type){
			case IMAGETYPE_GIF:
				header('Content-Type: image/gif');
				imagegif($this->image, null, $res);
			break;
			case IMAGETYPE_JPEG:
				header('Content-Type: image/jpg');
				imagejpeg($this->image, null, $res);
			break;
			case IMAGETYPE_PNG:
				header('Content-Type: image/png');
				$res = ceil($res*0.1); //convert from 0-100 to 1-10
				imagepng($this->image, null, $res);
			break;
			case IMAGETYPE_WBMP:
				header('Content-Type: image/bmp');
				imagewbmp($this->image, null, $res);
			break;
			default:
				throw new ImageException('', ImageException::ER_MIME);
		}
	}  
	
	/**
	 * close image resorce
	 */
	function __distruct(){
		imagedestroy($this->image);
	}
   
}
/*
I came across the problem of having a page where any image could be uploaded, then I would need to work with it as a true color image with transparency. The problem came with palette images with transparency (e.g. GIF images), the transparent parts changed to black (no matter what color was actually representing transparent) when I used imagecopy to convert the image to true color.

To convert an image to true color with the transparency as well, the following code works (assuming $img is your image resource):

<?php
//Convert $img to truecolor
$w = imagesx($img);
$h = imagesy($img);
if (!imageistruecolor($img)) {
  $original_transparency = imagecolortransparent($img);
  //we have a transparent color
  if ($original_transparency >= 0) {
    //get the actual transparent color
    $rgb = imagecolorsforindex($img, $original_transparency);
    $original_transparency = ($rgb['red'] << 16) | ($rgb['green'] << 8) | $rgb['blue'];
    //change the transparent color to black, since transparent goes to black anyways (no way to remove transparency in GIF)
    imagecolortransparent($img, imagecolorallocate($img, 0, 0, 0));
  }
  //create truecolor image and transfer
  $truecolor = imagecreatetruecolor($w, $h);
  imagealphablending($img, false);
  imagesavealpha($img, true);
  imagecopy($truecolor, $img, 0, 0, 0, 0, $w, $h);
  imagedestroy($img);
  $img = $truecolor;
  //remake transparency (if there was transparency)
  if ($original_transparency >= 0) {
    imagealphablending($img, false);
    imagesavealpha($img, true);
    for ($x = 0; $x < $w; $x++)
      for ($y = 0; $y < $h; $y++)
        if (imagecolorat($img, $x, $y) == $original_transparency)
          imagesetpixel($img, $x, $y, 127 << 24);
  }
}
?>
*/
?>