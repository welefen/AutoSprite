<?php
class AutoSprite_Generate_Imagick {

	private $_support_image_type = array ();

	public $generateInstance = null;

	public $options = array ();

	public function __construct() {
		try {
			$imageick = new Imagick ();
			$image_support_formats = $imageick->queryFormats ();
			foreach ( array (
				'GIF', 
				'PNG', 
				'JPG' 
			) as $type ) {
				if (in_array ( $type, $image_support_formats )) {
					$this->_support_image_type [] = $type;
				}
			}
		} catch ( Exception $e ) {
			error_log ( $e->getMessage () );
		}
	}

	/**
	 * set options
	 * @param array $options
	 */
	public function setOptions($options) {
		$this->options = $this->_sprites_instance->formatOptions ( $options );
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
		$output_im->compositeImage ( $current_file_im, $current_file_im->getImageCompose (), $offset_x, $offset_y );
		$current_file_im->destroy ();
	}

	/**
	 * 创建出示的output文件
	 * @param array $size
	 * @param string $background
	 * @param boolean $transparent
	 */
	public function createOutputFile($size) {
		$background = $this->_sprites_instance->formatColor ( $this->options ['background'] );
		$output_ext = $this->_sprites_instance->getImageExt ( $this->options ['output'] );
		$sprite_image = new Imagick ();
		if (! $background) {
			$background = $this->options ['transparent'] ? '000000' : 'ffffff';
		}
		$sprite_image->newImage ( $size ['width'], $size ['height'], new ImagickPixel ( "#$background", $output_ext ) );
		if ($this->options ['transparent']) {
			$sprite_image->paintTransparentImage ( new ImagickPixel ( "#$background" ), 0.0, 0 );
		}
		return $sprite_image;
	}

	/**
	 * create an image from file
	 * @param string $file
	 */
	public function createImageFromFile($file) {
		$im = new Imagick ();
		try {
			$im->readImage ( $file );
		} catch ( Exception $e ) {
			$size = getimagesize ( $file );
			$im->newImage ( $size [0], $size [1], new ImagickPixel ( '#ffffff' ) );
		}
		return $im;
	}

	/**
	 * save image
	 * @param image resource $im
	 * @param string $file
	 */
	public function saveImage($im) {
		//$this->_sprites_instance->mkdir(dirname($this->options['output']));
		$file_ext = $this->_sprites_instance->getImageExt ( $this->options ['output'] );
		if (in_array ( $file_ext, array (
			'GIF', 
			'PNG' 
		) ) && $this->options ['colorsnum']) {
			$im->quantizeImage ( $this->options ['colorsnum'], Imagick::COLORSPACE_RGB, 0, false, false );
		}
		if ($file_ext == 'JPG') {
			$im->setCompression ( Imagick::COMPRESSION_JPEG );
			$im->SetCompressionQuality ( $this->options ['quality'] );
		}
		$im->writeImage ( $this->options ['output'] );
	}
}