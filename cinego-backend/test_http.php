<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('role', 'admin')->first();
$request = Illuminate\Http\Request::create('/api/admin/combos', 'GET');
$request->setUserResolver(function() use ($user) { return $user; });
$response = app()->handle($request);
dump($response->getStatusCode());
dump($response->getContent());
