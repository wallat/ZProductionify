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
     * Set this value to true only when you already created production folders in every asset folder. 
     * The the assetManager will read the assets.production rather than assets folder.
     *
     * @var bool
     */
    public $useProductionAssets = false;
    
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
        
        return parent::publish($path, $hashByName, $level, $forceCopy);
    }
    
    /**
     * The newer version also add the given version number when creating hash
     *
     * @see http://www.yiiframework.com/doc/api/1.1/CAssetManager#hash-detail
     */
	protected function hash($path) {
		return sprintf('%x',crc32($path.Yii::getVersion().$this->version));
	}
}
