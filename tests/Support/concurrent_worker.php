<?php

declare(strict_types=1);

putenv('APP_ENV=testing');
putenv('DB_CONNECTION=pgsql');
putenv('DB_HOST=localhost');
putenv('DB_PORT=5432');
putenv('DB_DATABASE=olivosdb_testing');
putenv('DB_USERNAME=postgres');
putenv('DB_PASSWORD=admin123');
putenv('BROADCAST_CONNECTION=null');
putenv('CACHE_STORE=array');
putenv('QUEUE_CONNECTION=sync');

require dirname(__DIR__, 2) . '/vendor/autoload.php';
$app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ConsumptionRequest;
use App\Models\ConsumptionRequestDetail;
use App\Models\ConsumptionRequestModification;
use App\Models\User;
use App\Services\ConsumptionRequestDispatchService;
use App\Services\ConsumptionRequestModificationService;
use Illuminate\Support\Facades\Auth;

$payload = json_decode((string) ($argv[1] ?? '{}'), true, 512, JSON_THROW_ON_ERROR);
$mode = (string) $payload['mode'];

try {
    $modService = app(ConsumptionRequestModificationService::class);
    $dispatchService = app(ConsumptionRequestDispatchService::class);

    if ($mode === 'request') {
        $req = ConsumptionRequest::withoutGlobalScopes()->findOrFail($payload['request_id']);
        $det = ConsumptionRequestDetail::findOrFail($payload['detail_id']);
        $mod = $modService->request(
            consumptionRequest: $req,
            detail: $det,
            toProductId: (string) $payload['to_product_id'],
            requestedQuantity: $payload['qty'],
            reason: (string) $payload['reason'],
            requestedByUserId: (string) $payload['user_id']
        );
        echo json_encode(['ok' => true, 'id' => $mod->id, 'status' => $mod->status->value]);
        exit(0);
    }

    if ($mode === 'approve') {
        $mod = ConsumptionRequestModification::findOrFail($payload['modification_id']);
        $res = $modService->approve($mod, (string) $payload['user_id'], 'Aprobación concurrente');
        echo json_encode(['ok' => true, 'status' => $res->status->value]);
        exit(0);
    }

    if ($mode === 'reject') {
        $mod = ConsumptionRequestModification::findOrFail($payload['modification_id']);
        $res = $modService->reject($mod, (string) $payload['user_id'], 'Rechazo concurrente');
        echo json_encode(['ok' => true, 'status' => $res->status->value]);
        exit(0);
    }

    if ($mode === 'dispatch') {
        $user = User::findOrFail($payload['user_id']);
        Auth::login($user);
        $req = ConsumptionRequest::withoutGlobalScopes()->findOrFail($payload['request_id']);
        $updated = $dispatchService->dispatch($req);
        echo json_encode(['ok' => true, 'status' => $updated->status]);
        exit(0);
    }
} catch (Throwable $e) {
    echo json_encode([
        'ok' => false,
        'exception' => get_class($e),
        'message' => $e->getMessage(),
    ]);
    exit(0);
}
