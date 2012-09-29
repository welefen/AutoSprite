<?php
/**
 * 
 * 使用GD库来生成图片
 * @author welefen
 *
 */
class AutoSprite_Generate_Gd {

	private $_support_image_type = array ();

	public $generateInstance = null;

	public $options = array ();

	public function __construct() {
		$gd_info = gd_info ();
		foreach ( array (
			'PNG', 
			'GIF', 
			'JPG' 
		) as $name ) {
			if ($gd_info [$name . ' Support'] || $gd_info [$name . ' Create Support']) {
				$this->_support_image_type [] = $name;
			}
		}
	}

	/**
	 * set options
	 * @param array $options
	 */
	public function setOptions($options) {
		$this->options = $this->generateInstance->formatOptions ( $options );
	}

	/**
	 * 
	 * @param image resource $output_im
	 * @param image resource $current_file_im
	 * @param int $offset_x
	 * @param int $offset_y
	 * @param array $current_file_size
	 */
	public function imagecopy($output_im, $current_file_im, $offset_x, $offset_y, $current_file_size) {
		imagecopy ( $output_im, $current_file_im, $offset_x, $offset_y, 0, 0, $current_file_size [0], $current_file_size [1] );
		imagedestroy ( $current_file_im );
	}

	/**
	 * 创建出示的output文件
	 * @param array $size
	 * @param string $background
	 * @param boolean $transparent
	 */
	public function createOutputFile($size) {
		$background = $this->generateInstance->formatColor ( $this->options ['background'] );
		$sprite_image = null;
		if ($this->options ['transparent']) {
			$sprite_image = imagecreatetruecolor ( $size ['width'], $size ['height'] );
			imagealphablending ( $sprite_image, false );
			imagesavealpha ( $sprite_image, true );
		} else {
			$sprite_image = imagecreate ( $size ['width'], $size ['height'] );
		}
		if (empty ( $background )) {
			$background = 'ffffff';
		}
		$bgColor = $this->generateInstance->getRGBColor ( $background );
		if ($this->options ['transparent']) {
			$bgColor = imagecolorallocatealpha ( $sprite_image, $bgColor ['R'], $bgColor ['G'], $bgColor ['B'], 127 );
		} else {
			$bgColor = imagecolorallocate ( $sprite_image, $bgColor ['R'], $bgColor ['G'], $bgColor ['B'] );
		}
		imagefill ( $sprite_image, 0, 0, $bgColor );
		return $sprite_image;
	}

	/**
	 * create an image from a file
	 * @param string $file
	 */
	public function createImageFromFile($file) {
		$file_ext = $this->generateInstance->getImageExt ( $file );
		switch ($file_ext) {
			case 'JPG' :
			case 'JPEG' :
				return @imagecreatefromjpeg ( $file );
			case 'GIF' :
				return @imagecreatefromgif ( $file );
			case 'PNG' :
				return @imagecreatefrompng ( $file );
		}
		return null;
	}

	/**
	 * save image
	 * @param image resource $im
	 * @param string $file
	 */
	public function saveImage($im) {
		$file_ext = $this->generateInstance->getImageExt ( $this->options ['output'] );
		if (in_array ( $file_ext, array (
			'GIF', 
			'PNG' 
		) ) && $this->options ['colorsnum'] !== true) {
			imagetruecolortopalette ( $im, false, $this->options ['colorsnum'] );
		}
		switch ($file_ext) {
			case 'JPG' :
			case 'JPEG' :
				return @imagejpeg ( $im, $this->options ['output'], $this->options ['quality'] );
			case 'GIF' :
				if ($this->options ['transparent'] && $this->options ['colorsnum'] > 256) {
					imagetruecolortopalette ( $im, true, 256 );
				}
				return @imagegif ( $im, $this->options ['output'] );
			case 'PNG' :
				return @imagepng ( $im, $this->options ['output'] );
		}
		return null;
	}
}