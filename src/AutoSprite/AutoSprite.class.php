<?php
/**
 * 
 * Css Sprites和CSS自动替换
 * 
 * 注意如下的一些情况：
 * 1、不支持background里有repeat的情况
 * 2、对于repeat-x和repeat-y的情况部分的支持，需要手工配合：
 * * repeat-x的时候需要垂直方向合并，并且使用了repeat-x的图片要是最宽的
 * * repeat-y的时候需要水平方向合并，并且使用了repeat-y的图片要是最高的
 * 3、把一个小图片作为一个很大容器的背景，这时需要在大容器里添加小容器，将背景图设置给小容器
 * 4、不支持一个selector里有多个background：
 * * 不支持浏览器的hack引起的多个background，如：background:url();_background:url(),这种情况使用selector的hack。
 * 5、图片混合合并模式比较耗时，最好是一些大小比较相似的图片使用这种模式
 * 6、不要将图片质量不高和图片质量非常高的一起合并，这样合并出来的图片会有质量问题
 * 7、CSS替换是基于本身CSS是合法的，不支持不合法的CSS进行替换
 * 
 *
 * @author welefen
 * @copyright 2011
 * @version 1.0
 *
 */
class AutoSprite {
	/**
	 * 
	 * 图片所在的目录
	 * @var string
	 */
	public $imgPath = '';
	/**
	 * 
	 * 需要合并的图片列表
	 * @var array
	 */
	public $imgFiles = array ();
	/**
	 * 
	 * 图片后缀名列表
	 * @var array
	 */
	public $imgExts = array ('png', 'jpg', 'gif', 'jpeg' );
	/**
	 * 
	 * css文件所在的目录
	 * @var string
	 */
	public $cssPath = '';
	/**
	 * 
	 * css文件列表
	 * @var array
	 */
	public $cssFiles = array ();
	/**
	 * 
	 * CSS文件保存路径，为空的话则覆盖原文件
	 * @var string
	 */
	public $cssSavePath = '';
	/**
	 * 
	 * 图片合并的方向
	 * 1=>水平方向， 2=>垂直方向， 0=>混合模式
	 * @var int
	 */
	public $direction = 1;
	/**
	 * 
	 * 合并时小图片之间的间距
	 * @var int
	 */
	public $margin = 0;
	/**
	 * 
	 * 合并图的背景
	 * @var string
	 */
	public $background = '';
	/**
	 * 
	 * 合成的图片是否透明
	 * @var boolean
	 */
	public $transparent = true;
	/**
	 * 
	 * 合并后的图片质量
	 * 0 - 100
	 * @var int
	 */
	public $quality = 75;
	/**
	 * 
	 * 合并后的图片色彩值
	 * 0 - 255， 0表示truecolor
	 * @var int
	 */
	public $colorsnum = 255;
	/**
	 * 
	 * 自动检测图片合并的方向
	 * @var boolean
	 */
	public $detectDirection = false; // 暂不考虑支持
	/**
	 * 
	 * 图片使用了repeat的列表，只支持单个repeat，如：repeat-x, repeat-y， 不支持repeat。
	 * 如： background:url(../img/a/a.png) repeat-x; 这个时候就要垂直方向合并了
	 * @var array
	 */
	public $repeatImgs = array ();
	/**
	 * 
	 * 图片使用了center列表，如
	 * background: url(../img/a/a.png) center left;
	 * @var array
	 */
	public $centerImgs = array ();
	/**
	 * 
	 * 合并后的图片存放路径
	 * @var string
	 */
	public $outputImgFile = '';
	/**
	 * 
	 * 查找CSS里引用的图片过滤函数
	 * @var string
	 */
	public $cssImgFilter = '';
	/**
	 * 
	 * 替换CSS文件里的图片的路径前缀
	 * @var string
	 */
	public $cssReplaceImgPrefixPath = '';
	/**
	 * 
	 * 当前类名
	 * @var string
	 */
	private static $_class = '';
	/**
	 * 小图片在大图片中所在的位置
	 */
	private $_imgFilesPattern = array ();
	
	public function __construct() {
		if (! self::checkExtension ( 'gd' ) && ! self::checkExtension ( 'imagick' )) {
			$this->throwException ( 'AutoSprite use gd or imagick, please install it', 2 );
		}
		self::$_class = get_class ( $this );
	}
	/**
	 * 
	 * 获取版本号
	 */
	public static function getVersion() {
		return '1.0.0';
	}
	/**
	 * 
	 * 合并图片，并返回小图片在合并图中所在的位置
	 * @param boolean $return
	 */
	public function generate($return = true) {
		if ($this->imgPath) {
			$this->imgFiles += self::getFiles ( $this->imgPath, join ( ',', $this->imgExts ), $this->imgPath );
		}
		$this->imgFiles = array_unique ( $this->imgFiles );
		self::checkFilesReadable ( $this->imgFiles );
		//小图片大于等于2个的时候才进行合并
		if (count ( $this->imgFiles ) < 2) {
			$this->throwException ( 'img files must greater than 2.', 5 );
		}
		//必须设置输出文件所在的位置
		if (! $this->outputImgFile) {
			$this->throwException ( 'output filename not leave blank.', 6 );
		}
		//如果输出的文件已经存在，则删除
		if (file_exists ( $this->outputImgFile )) {
			@unlink ( $this->outputImgFile );
		}
		self::loadClass ( 'AutoSprite_Generate' );
		$instance = new AutoSprite_Generate ();
		$result = $instance->generate ( array ('filelist' => $this->imgFiles, 'margin' => $this->margin, 'direction' => $this->direction, 'background' => $this->background, 'transparent' => $this->transparent, 'output' => $this->outputImgFile, 'quality' => $this->quality, 'colorsnum' => $this->colorsnum ) );
		if ($return) {
			return $result;
		} else {
			$this->_imgFilesPattern = $result;
		}
	
	}
	/**
	 * 
	 * 检测文件是否存在并且可读
	 * @param array $files
	 * @throws Exception
	 */
	public static function checkFilesReadable($files = array()) {
		foreach ( $files as $file ) {
			if (is_file ( $file )) {
				if (! is_readable ( $file )) {
					$this->throwException ( $file . ' is not readable, please change mode', 4 );
				}
			} else {
				$this->throwException ( $file . ' is not exist, please check', 3 );
			}
		}
	}
	/**
	 * 
	 * 替换CSS
	 */
	public function replaceCss() {
		$instance = self::loadClass ( 'AutoSprite_ReplaceCss', true );
		$instance->cssFiles = $this->cssFiles;
		$instance->cssPath = $this->cssPath;
		$instance->cssSavePath = $this->cssSavePath;
		$instance->imgFilesPattern = $this->_imgFilesPattern;
		$instance->imgPathFilter = $this->cssImgFilter;
		$instance->replaceImg = rtrim ( $this->cssReplaceImgPrefixPath, '/' ) . '/' . self::getFilename ( $this->outputImgFile );
		$instance->run ();
	}
	
	public function run() {
		$this->generate ( false );
		$this->replaceCss ();
	}
	/**
	 * 
	 * 检查扩展是否已经加载
	 * @param string $ext
	 */
	public static function checkExtension($ext) {
		if (! $ext)
			return false;
		if (extension_loaded ( $ext ))
			return true;
		$prefix = (PHP_SHLIB_SUFFIX === 'dll') ? 'php_' : '';
		$extname = $prefix . $ext . '.' . PHP_SHLIB_SUFFIX;
		if (dl ( $extname ))
			return true;
		return false;
	}
	/**
	 * 
	 * 加载一个类
	 * @param string $class
	 */
	public static function loadClass($class = '', $new = false) {
		if (class_exists ( $class )) {
			if ($new)
				return new $class ();
			return $class;
		}
		$cclass = str_replace ( '_', ' ', $class );
		$cclass = str_replace ( ' ', '/', ucwords ( $cclass ) );
		$path = dirname ( __FILE__ ) . '/' . $cclass . '.class.php';
		if (is_file ( $path )) {
			require_once $path;
			if ($new)
				return new $class ();
			return $class;
		} else {
			self::throwException ( $class . ' is not found', 1 );
		}
	}
	/**
	 * 
	 * 获取某个目录下的特定文件类型的文件列表
	 * 如果目录不存在或者目录下文件为空，则返回一个空数组
	 * 目录不存在的时候不会报错，主要是为了后续处理方便
	 * @param string $dir
	 * @param string $extName
	 * @param string $prefix
	 */
	public static function getFiles($dir, $extName = '', $prefix = '') {
		if (is_dir ( $dir )) {
			$files = scandir ( $dir );
			$result = array ();
			for($i = 0, $count = count ( $files ); $i < $count; $i ++) {
				if ($files [$i] == '.' || $files [$i] == '..' || $files [$i] == '.svn')
					continue;
				$file = $dir . '/' . $files [$i];
				if (is_dir ( $file )) {
					$result = array_merge ( $result, self::getFiles ( $file, $extName, $prefix . $files [$i] . '/' ) );
				} else {
					$extNameNow = self::getExtName ( $files [$i] );
					if (! $extName || $extName && strpos ( ',' . $extName . ',', ',' . $extNameNow . ',' ) !== false) {
						$result [] = $prefix . $files [$i];
					}
				}
			}
			return $result;
		}
		return array ();
	}
	/**
	 * 创建目录
	 * @param string $dir
	 * @param int $mode
	 */
	public static function mkdir($dir, $mode = 0777) {
		if (is_dir ( $dir ) || @mkdir ( $dir, $mode, true ))
			return true;
		if (! mkdir ( dirname ( $dir ), $mode ))
			return false;
		return @mkdir ( $dir, $mode, true );
	}
	/**
	 * 
	 * 获取文件内容
	 * @param string $file
	 */
	public static function getFileContent($file) {
		if (file_exists ( $file ) && is_readable ( $file )) {
			$content = file_get_contents ( $file );
			return $content;
		} else {
			self::throwException ( $file . ' is not exist or can not readable', 10 );
		}
	}
	/**
	 * 
	 * 保存文件内容
	 * @param string $file
	 * @param string $content
	 */
	public static function setFileContent($file, $content = '') {
		self::mkdir ( dirname ( $file ) );
		if (file_exists ( $file ) && ! is_writable ( $file )) {
			self::throwException ( $file . ' can not writeable', 11 );
		}
		return file_put_contents ( $file, $content );
	}
	/**
	 * 
	 * 获取文件的后缀名
	 * @param string $filename
	 */
	public static function getExtName($filename) {
		$afterExplode = explode ( '.', basename ( $filename ) );
		return strtolower ( end ( $afterExplode ) );
	}
	/**
	 * 
	 * 获取文件名
	 * @param string $filename
	 */
	public static function getFilename($filename) {
		$afterExplode = explode ( '/', basename ( $filename ) );
		return strtolower ( end ( $afterExplode ) );
	}
	/**
	 * 
	 * 抛出异常
	 * @param string $message
	 * @param int $code
	 * @param boolean $class
	 * @throws Exception
	 */
	public static function throwException($message, $code = 1, $class = true) {
		if ($class) {
			$message = self::$_class . ': ' . $message;
		}
		throw new Exception ( $message, $code );
	}
}