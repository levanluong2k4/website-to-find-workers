<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create('/api/ho-so-tho/stats', 'GET');
$request->setUserResolver(function() { return \App\Models\User::find(30); });
$response = $kernel->handle($request);
echo $response->getContent();
