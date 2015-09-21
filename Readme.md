# Magento File Storage Module

**Magento File Storage** is an extension that lets you use different storage backends for your magento media. Uses the [Flysystem](http://flysystem.thephpleague.com) filesystem abstraction to connect to storage backends.

## Install
* wget https://github.com/uaudio/magento-filestorage/releases/download/0.1.0/uaudio_storage-0.1.0.tgz
* ./mage install-file uaudio_storage-0.1.0.tgz
* composer -d app/code/community/Uaudio install 
* Refresh configuration cache

## AWS Setup
* Setup IAM user / or IAM role if on EC2
* Synchronzie media (php shell/uaudio/storage/synchronize.php --type storage_s3 --setting s3_access_key=KEY,s3_secret_key=SECRECT,s3_region=us-west-1,s3_bucket=BUCKET,s3_folder=PREFIX --clear --verbose
* Enable S3 storage System -> Configuration -> Advanced -> System
* Under Storage Configuration for Media - select Amazon S3
* General -> Web -> Base Media Url to S3
* Enable File Storage Metadata cache
* Flush Magento Cache

## Notes
* Only tested on Magento EE ver. 1.14.2.1 php v5.6
* Local module added for testing
* Caching only enabled for redis cache backend, module uses core cache setup

## New Storage Options
Include flysystem adapter ex. [composer require league/flysystem-aws-s3-v3]
In `MyModule/etc/config.xml`
```
<config>
    ...
    <uaudio_storage>
        <aws>
            <name>Amazon S3</name>
            <class>uaudio_storage/storage_s3</class>
        </aws>
    </uaudio_storage>
</config>
```
Add settings to MyModule/etc/system.xml
```
<config>
    ...
    <media_storage_configuration>
        <fields>
            <media_s3_access_key translate="label">
                <label>Access Key ID</label>
                <frontend_type>obscure</frontend_type>
                <backend_model>adminhtml/system_config_backend_encrypted</backend_model>
                <sort_order>200</sort_order>
                <show_in_default>1</show_in_default>
                <show_in_website>0</show_in_website>
                <show_in_store>0</show_in_store>
                <depends><media_storage>2</media_storage></depends>
            </media_s3_access_key>
            ...
        </fields>
    </media_storage_configuration>
</config>
```
Create MyModule/Model/Storage/MyStorage.php extend Uaudio_Storage_Model_Storage_Abstract Implement _getAdapter, settings methods

## TODO:
* Change name from storage to filestorage
* Fix package name in release
* Add create package script
* Test failure scenarios
* Verify exception returns are handled properly
* Implement for Dataflow, Downloadable, ImportExport, XmlConnect
* Add unit tests 
* Setup travis.ci testing
* Add tags to redis cache

