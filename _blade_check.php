<?php
require 'vendor/autoload.php';
$fs = new Illuminate\Filesystem\Filesystem();
$blade = new Illuminate\View\Compilers\BladeCompiler($fs, sys_get_temp_dir());
$src = file_get_contents('resources/views/public/partials/event-booking-ticket-options.blade.php');
$blade->compileString($src);
echo "BLADE_OK\n";
