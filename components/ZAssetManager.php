<?php
/**
 * Help you to prepare production mode
 *
 * Changes from original CAssetManager:
 *   - Publish the production folder when useProductionAssets is true
 *   - Add the version number when creating hash
 */
class ZAssetManager extends CAssetManager {
    /**
     * @var array published assets
     */
    protected $_published=array();

    /**
     * Set this value to true only when you already created production folders in every asset folder.
     * The the assetManager will read the assets.production rather than assets folder.
     *
     * @var bool
     */
    public $useProductionAssets = false;

    /**
     * Wether to skip copying the existing folder.
     * If this option is true, it will check all the modification time of each file
     * recursively. It did takes some time to do it.
     *
     * Recommand use true in the production;
     *
     * @var boolean
     */
    public $skipExistingFolder = false;

    /**
     * Use this value to create different hash between different version systems.
     *
     * @var string
     */
    public $version = '';

    /**
     * Publish the production folder when useProductionAssets is true
     *
     * @see http://www.yiiframework.com/doc/api/1.1/CAssetManager#publish-detail
     */
    public function publish($path,$hashByName=false,$level=-1,$forceCopy=false) {
        if ($this->useProductionAssets) {
            $path = str_replace(DIRECTORY_SEPARATOR.'assets', DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'production', $path);
        }

        return $this->better_publish($path, $hashByName, $level, $forceCopy);
    }

    /**
     * The newer version also add the given version number when creating hash
     *
     * @see http://www.yiiframework.com/doc/api/1.1/CAssetManager#hash-detail
     */
	protected function hash($path) {
		return sprintf('%x',crc32($path.Yii::getVersion().$this->version));
	}

    /**
     * This function is almost the same as the original one.
     * But it additional checks the lastest modifiy time of folders to
     * ensure the new files will be published.
     *
     * @see http://www.yiiframework.com/doc/api/1.1/CAssetManager#publish-detail
     */
    public function better_publish($path,$hashByName=false,$level=-1,$forceCopy=false) {
        if(isset($this->_published[$path]))
            return $this->_published[$path];
        else if(($src=realpath($path))!==false) {
            if(is_file($src)) {
                $dir=$this->hash($hashByName ? basename($src) : dirname($src));
                $fileName=basename($src);
                $dstDir=$this->getBasePath().DIRECTORY_SEPARATOR.$dir;
                $dstFile=$dstDir.DIRECTORY_SEPARATOR.$fileName;

                if($this->linkAssets) {
                    if(!is_file($dstFile)) {
                        if(!is_dir($dstDir)) {
                            mkdir($dstDir);
                            @chmod($dstDir, $this->newDirMode);
                        }
                        symlink($src,$dstFile);
                    }
                } else if(@filemtime($dstFile)<@filemtime($src) || $forceCopy) {
                    if(!is_dir($dstDir)) {
                        mkdir($dstDir);
                        @chmod($dstDir, $this->newDirMode);
                    }
                    copy($src,$dstFile);
                    @chmod($dstFile, $this->newFileMode);
                }

                return $this->_published[$path]=$this->getBaseUrl()."/$dir/$fileName";
            } else if(is_dir($src)) {
                $dir=$this->hash($hashByName ? basename($src) : $src);
                $dstDir=$this->getBasePath().DIRECTORY_SEPARATOR.$dir;

                if($this->linkAssets) {
                    if(!is_dir($dstDir))
                        symlink($src,$dstDir);
                } else if ( !$this->skipExistingFolder && is_dir($dstDir)) {
                    $srcMTime = self::filemtime_r($src);
                    $dstMTime = self::filemtime_r($dstDir);

                    if ($srcMTime>=$dstMTime) {
                        CFileHelper::copyDirectory($src,$dstDir,array(
                            'exclude'=>$this->excludeFiles,
                            'level'=>$level,
                            'newDirMode'=>$this->newDirMode,
                            'newFileMode'=>$this->newFileMode,
                        ));
                    }
                } else if( !is_dir($dstDir) || $forceCopy) {
                    CFileHelper::copyDirectory($src,$dstDir,array(
                        'exclude'=>$this->excludeFiles,
                        'level'=>$level,
                        'newDirMode'=>$this->newDirMode,
                        'newFileMode'=>$this->newFileMode,
                    ));
                }

                return $this->_published[$path]=$this->getBaseUrl().'/'.$dir;
            }
        }
        throw new CException(Yii::t('yii','The asset "{asset}" to be published does not exist.',
            array('{asset}'=>$path)));
    }

    /**
     * Retrieve the folder lastest modification time recursively
     *
     * @see {http://php.net/manual/fr/function.filemtime.php}
     *
     * @param  string $path The target path. Could be file or folder.
     * @return ineteger timestamp
     */
    public static function filemtime_r($path) {
        if (!file_exists($path))
            return 0;

        if (is_file($path))
            return filemtime($path);

        $ret = 0;
        foreach (glob($path."/*") as $fn) {
            $dstt = self::filemtime_r($fn);
            if ($dstt > $ret)
               $ret = $dstt;
        }

        return $ret;
    }
}
