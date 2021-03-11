<?php

use Sami\Sami;
use Sami\RemoteRepository\GitHubRemoteRepository;
use Sami\Version\GitVersionCollection;
use Symfony\Component\Finder\Finder;

$dir = __DIR__ . '/src';
$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->exclude('Resources')
    ->in($dir);


return new Sami($iterator, array(
    'theme'  => 'default',
    //'versions'  => $versions,
    'title'  => 'IQMatch SeoBundle',
    'build_dir'  =>'/build/doc/api/%version%',
    'cache_dir'  => '/build/doc/cache/%version%',
    'default_opened_level' => 2,
));
