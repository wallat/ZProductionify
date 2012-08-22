ZProductionify
==============

Full package for make your Yii website into Online

### What's this

This is a full package to make your Yii application onto production mode. By using this package, you can do

1. Compress js, css by using yui compressor
2. Compress less into css by using [ZLessCompiler][ZLessCompiler]
3. Compress png files by using [pngcrush][pngcrush]
4. Directly read the production assets in the production mode.

### How it does these

This package contains one command and two components

* `/ZProductionify/commands/PrepareProductionCommand.php`

    This scripts help you to prepare the production assets folder.
    It does :

    1. foreach assets (main assets and the widget assets), create the assets production folder (delete it if it is already there)
    2. Use [ZLessCompiler][ZLessCompiler] to compress every css into css files.
    3. Use yui compressor to compress every js and css files and output them to the production folder.
    4. Use [pngcrush][pngcrush] to compress every png files and output them to the production folder.
    5. Directly copy the remaining files to the production folder. ex. jpgs, flashes.
    6. Add those production folders to the git repository.
    7. Commit git.

* `/ZProductionify/components/ZAssetManager.php`

    This scripts help you:

    1. Use production asset folders rather than the dev folders.

        The production asset folder should locate the original asset folder with name `production`

        For example, if you have a asset with two files like following

            assets/
            ├── script.js
            └── style.less

        Then you need to prepare a production folder which under the original assets folder with structure like following

            assets/
            ├── production
            │   ├── script.js
            │   └── style.css
            ├── script.js
            └── style.less

        ZAssetManager will read this production folder whe in the production mode.

    2. Create the different hash value when publish assets base on the given version number. Thus the client will not use the old cache scripts when there are newers.

* `/ZProductionify/components/ZClientScript.php`

    This is the client script which modified from [EClientScript][EClientScript]. It will allow you to automatically **combine** all script files and css files into a single (or several) script or css files.

    Main changes from EClientScript

    * Eliminate the compress files options
    * Add option to determine wether to check exist every time

### How to use this package

* First you need to install the [pngcrush][pngcrush] in order to compress images.

* You also need the [ZLessCompiler][ZLessCompiler] in order to compress less files.

* Copy this package into your `/protected/extensions` folder.

* Update your `/protected/config/main.php` like this

        return array(
            'components' => array(
                'assetManager' => array(
                    'class' => 'application.extensions.ZProductionify.components.ZAssetManager',
                    'version' => INSTANCE_IDENTIFIER, // the unique version number. Recommand git version number
                    'useProductionAssets' => false, // use true in production
                    'skipExistingFolder' => false, // use true in production
                ),
                'clientScript' => array(
                    'class' => 'application.extensions.ZProductionify.components.ZClientScript',
                    'coreScriptPosition' => CClientScript::POS_END,
                    'combineScriptFiles' => false,  // use true in production
                    'combineCssFiles' => false,     // use true in production
                    'skipExistingFile' => false,    // use true in production
                ),
            )
        );

* Add following block into `/protected/config/console.php`

        return array(
            // add this block
            'commandMap' => array(
                'pp'=>array(
                   'class'=>'application.extensions.ZProductionify.commands.PrepareProductionCommand',
                ),
            )
        );

* From now, you can always use command `/protected/yiic pp` to prepare the production asset folder.

* After prepared production assets, you can set the `useProductionAssets` value to `true` in order to use that assets.


### Links

* [EClientScript][EClientScript]
* [pngcrush][pngcrush]
* [Yii framework](http://www.yiiframework.com/)
* [YUI compressor](http://developer.yahoo.com/yui/compressor/)
* [ZLessCompiler][ZLessCompiler]

### Credits

* [好搜宅](http://www.howso.com.tw)
* [酷皮](http://www.coolpics.com.tw)

[EClientScript]: (http://www.yiiframework.com/extension/eclientscript/)
[pngcrush]: (http://pmt.sourceforge.net/pngcrush/)
[ZLessCompiler]: (https://github.com/wallat/ZLessCompiler)
