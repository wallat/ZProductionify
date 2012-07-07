<?php

/**
 * This is the client script which modified from EClientScript.
 * @see http://www.yiiframework.com/extension/eclientscript/
 *
 * Main changes from EClientScript
 *     - Eliminate the compress files options
 *     - Add option to determine wether to check exist every time
 */
class ZClientScript extends CClientScript {
    /**
     * Wether to re-check the file content when the file is already there.
     * If set to false, this class will check the timestamp of every incoming files.
     *
     * @var bool
     */
    public $skipExistingFile = false;

    /**
	 * @var combined script file name
	 */
	public $scriptFileName = 'script.js';

	/**
	 * @var combined css stylesheet file name
	 */
	public $cssFileName = 'style.css';

	/**
	 * @var boolean if to combine the script files or not
	 */
	public $combineScriptFiles = false;

	/**
	 * @var boolean if to combine the css files or not
	 */
	public $combineCssFiles = false;

	/**
	 * Combine css files and script files before renderHead.
	 * @param string the output to be inserted with scripts.
	 */
	public function renderHead(&$output) {
		if ($this->combineCssFiles)
			$this->combineCssFiles();

		if ($this->combineScriptFiles && $this->enableJavaScript)
			$this->combineScriptFiles(self::POS_HEAD);

		parent::renderHead($output);
	}

	/**
	 * Inserts the scripts at the beginning of the body section.
	 * @param string the output to be inserted with scripts.
	 */
	public function renderBodyBegin(&$output) {
		// $this->enableJavascript has been checked in parent::render()
		if ($this->combineScriptFiles)
			$this->combineScriptFiles(self::POS_BEGIN);

		parent::renderBodyBegin($output);
	}

	/**
	 * Inserts the scripts at the end of the body section.
	 * @param string the output to be inserted with scripts.
	 */
	public function renderBodyEnd(&$output) {
		// $this->enableJavascript has been checked in parent::render()
		if ($this->combineScriptFiles)
			$this->combineScriptFiles(self::POS_END);

		parent::renderBodyEnd($output);
	}

	/**
	 * Combine the CSS files, if cached enabled then cache the result so we won't have to do that
	 * Every time
	 */
	protected function combineCssFiles() {
		// Check the need for combination
		if (count($this->cssFiles) < 2)
			return;

		$cssFiles = array();
		$toBeCombined = array();
		foreach ($this->cssFiles as $url => $media) {
			$file = $this->getLocalPath($url);
			if ($file === false) {
				$cssFiles[$url] = $media;
			} else {
				$media = strtolower($media);
				if ($media === '')
					$media = 'all';
				if (!isset($toBeCombined[$media]))
					$toBeCombined[$media] = array();
				$toBeCombined[$media][$url] = $file;
			}
		}

		foreach ($toBeCombined as $media => $files) {
			if ($media === 'all')
				$media = '';

			if (count($files) === 1)
				$url = key($files);
			else {
				// get unique combined filename
				$fname = $this->getCombinedFileName($this->cssFileName, $files, $media);
				$fpath = Yii::app()->assetManager->basePath . DIRECTORY_SEPARATOR . $fname;

				// check exists file
				if (($valid=file_exists($fpath))) {
				    if ( !$this->skipExistingFile) {
				        $mtime = filemtime($fpath);
    					foreach ($files as $file) {
    						if ($mtime < filemtime($file)) {
    							$valid = false;
    							break;
    						}
    					}
				    }
				}

				// re-generate the file
				if (!$valid) {
					$urlRegex = '#url\s*\(\s*([\'"])?(?!/|http://)([^\'"\s])#i';
					$fileBuffer = '';
					foreach ($files as $url => $file) {
						$contents = file_get_contents($file);
						if ($contents) {
							// Reset relative url() in css file
							if (preg_match($urlRegex, $contents)) {
								$reurl = $this->getRelativeUrl(Yii::app()->assetManager->baseUrl, dirname($url));
								$contents = preg_replace($urlRegex, 'url(${1}' . $reurl . '/${2}', $contents);
							}
							// Append the contents to the fileBuffer
							$fileBuffer .= "/* {$url}";
							$fileBuffer .= " */\n";
							$fileBuffer .= $contents . "\n";
						}
					}
					file_put_contents($fpath, $fileBuffer);
				}

				// real url of combined file
				$url = Yii::app()->assetManager->baseUrl . '/' . $fname . '?' . filemtime($fpath);
			}

			$cssFiles[$url] = $media;
		}

		// use new cssFiles list replace old ones
		$this->cssFiles = $cssFiles;
	}

	/**
	 * Combine script files, we combine them based on their position, each is combined in a separate file
	 * to load the required data in the required location.
	 * @param $type CClientScript the type of script files currently combined
	 */
	protected function combineScriptFiles($type = self::POS_HEAD) {
		// Check the need for combination
		if (!isset($this->scriptFiles[$type]) || count($this->scriptFiles[$type]) < 2)
			return;

		$scriptFiles = array();
		$toBeCombined = array();
		foreach ($this->scriptFiles[$type] as $url) {
			$file = $this->getLocalPath($url);
			if ($file === false)
				$scriptFiles[$url] = $url;
			else
				$toBeCombined[$url] = $file;
		}

		if (count($toBeCombined) === 1) {
			$url = key($toBeCombined);
			$scriptFiles[$url] = $url;
		} else if (count($toBeCombined) > 1) {
			// get unique combined filename
			$fname = $this->getCombinedFileName($this->scriptFileName, array_values($toBeCombined), $type);
			$fpath = Yii::app()->assetManager->basePath . DIRECTORY_SEPARATOR . $fname;

			// check exists file
			if (($valid=file_exists($fpath))) {
			    if ( !$this->skipExistingFile) {
			    	$mtime = filemtime($fpath);
    				foreach ($toBeCombined as $file) {
    					if ($mtime < filemtime($file)) {
    						$valid = false;
    						break;
    					}
    				}
			    }
			}

			// re-generate the file
			if (!$valid) {
				$fileBuffer = '';
				foreach ($toBeCombined as $url => $file) {
					$contents = file_get_contents($file);
					if ($contents) {
						// Append the contents to the fileBuffer
						$fileBuffer .= "/*** {$url} ***/\n";
						$fileBuffer .= $contents . "\n\n";
					}
				}
				file_put_contents($fpath, $fileBuffer);
			}

			// add the combined file into scriptFiles
			$url = Yii::app()->assetManager->baseUrl . '/' . $fname . '?' . filemtime($fpath);;
			$scriptFiles[$url] = $url;
		}
		// use new scriptFiles list replace old ones
		$this->scriptFiles[$type] = $scriptFiles;
	}

	/**
	 * Get realpath of published file via its url, refer to {link: CAssetManager}
	 * @return string local file path for this script or css url
	 */
	private function getLocalPath($url) {
		$basePath = dirname(Yii::app()->request->scriptFile) . DIRECTORY_SEPARATOR;
		$baseUrl = Yii::app()->request->baseUrl . '/';
		if (!strncmp($url, $baseUrl, strlen($baseUrl))) {
			$url = $basePath . substr($url, strlen($baseUrl));
			return $url;
		}
		return false;
	}

	/**
	 * Calculate the relative url
	 * @param string $from source url, begin with slash and not end width slash.
	 * @param string $to dest url
	 * @return string result relative url
	 */
	private function getRelativeUrl($from, $to) {
		$relative = '';
		while (true) {
			if ($from === $to)
				return $relative;
			else if ($from === dirname($from))
				return $relative . substr($to, 1);
			if (!strncmp($from . '/', $to, strlen($from) + 1))
				return $relative . substr($to, strlen($from) + 1);

			$from = dirname($from);
			$relative .= '../';
		}
	}

	/**
	 * Get unique filename for combined files
	 * @param string $name default filename
	 * @param array $files files to be combined
	 * @param string $type css media or script position
	 * @return string unique filename
	 */
	private function getCombinedFileName($name, $files, $type = '') {
		$pos = strrpos($name, '.');
		if (!$pos)
			$pos = strlen($pos);

		$hash = sprintf('%x', crc32(implode('+', $files)));

		$ret = substr($name, 0, $pos);
		if ($type !== '')
			$ret .= '-' . $type;
		$ret .= '-'.$hash.substr($name, $pos);

		return $ret;
	}
}
