<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('role', 'admin')->first();
$token = $user->createToken('test')->plainTextToken;

$client = new \GuzzleHttp\Client();
try {
    $res = $client->request('GET', 'http://127.0.0.1:8000/api/admin/combos', [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ]
    ]);
    echo $res->getStatusCode() . "\n";
    echo $res->getBody();
} catch (\GuzzleHttp\Exception\ClientException $e) {
    echo $e->getResponse()->getStatusCode() . "\n";
    echo $e->getResponse()->getBody();
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
