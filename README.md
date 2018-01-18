# How to install
run in console
`composer require howkins/pjsbackup`

## Add these lines in laravel/config/app.php

Autoloaded Service Providers
`howkins\pjsbackup\FileSystemBackupServiceProvider::class,`

Class Aliases
`'Backup' => howkins\pjsbackup\Facade::class,`

# Example
```php
Backup::location('/home/dir', '/home/bkp')
  ->connect('sftp', array(
                      'host'=>'', 
                      'username'=>'', 
                      'password'=>'', 
                      'port'=>'22'))
  ->collect(array(
    "subdirectory/22782/"
  ))
  ->dump();
```

# Source code
[https://github.com/howkins/pjsbackup](url)
