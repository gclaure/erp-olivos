<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderAttachment;
use App\Models\OrderComplement;
use App\Models\OrderItem;
use App\Models\OrderItemDetail;
use App\Models\OrderPayment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class OrderService
{
    /**
     * Crea un pedido completo (Cliente, Reserva, Productos, Detalles, Fotografías WebP, Complementos y Pagos).
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $files
     */
    public function createOrder(array $data, array $files = [], ?User $user = null): Order
    {
        return DB::transaction(function () use ($data, $files, $user): Order {
            $branchId = $user?->branch_id ? (string) $user->branch_id : null;
            $userId = $user?->id ? (string) $user->id : null;

            $customer = $this->resolveOrUpdateCustomer(
                customerData: (array) ($data['customer'] ?? []),
                deliveryAddress: isset($data['delivery_address']) ? (string) $data['delivery_address'] : null,
                branchId: $branchId
            );

            $lastOrder = Order::query()
                ->select('id', 'number')
                ->orderByDesc('number')
                ->lockForUpdate()
                ->first();
            $nextNumber = ((int) ($lastOrder?->number ?? 0)) + 1;

            $discount = round(max(0.0, (float) ($data['discount'] ?? 0)), 2);
            $subtotal = round(max(0.0, (float) ($data['subtotal'] ?? 0)), 2);
            $discount = round(max(0.0, (float) ($data['discount'] ?? 0)), 2);
            $total = round(max(0.0, (float) ($data['total'] ?? 0)), 2);
            $paidAmount = 0.0;
            $balanceDue = 0.0;

            $order = Order::create([
                'number' => $nextNumber,
                'customer_id' => $customer->id,
                'branch_id' => $branchId,
                'user_id' => $userId,
                'status' => 'registrado',
                'registered_at' => !empty($data['registered_at']) ? (string) $data['registered_at'] : now()->toDateString(),
                'delivery_date' => (string) $data['delivery_date'],
                'delivery_time' => trim((string) $data['delivery_time']),
                'delivery_mode' => (string) ($data['delivery_mode'] ?? 'pickup'),
                'delivery_address' => ($data['delivery_mode'] ?? 'pickup') === 'delivery'
                    ? trim((string) ($data['delivery_address'] ?? ''))
                    : null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'balance_due' => $balanceDue,
                'payment_notes' => isset($data['payment_notes']) && trim((string) $data['payment_notes']) !== ''
                    ? trim((string) $data['payment_notes'])
                    : null,
                'general_notes' => isset($data['general_notes']) && trim((string) $data['general_notes']) !== ''
                    ? trim((string) $data['general_notes'])
                    : null,
            ]);

            $itemsInput = is_array($data['items'] ?? null) ? $data['items'] : [];
            $itemFilesGroup = is_array($files['items'] ?? null) ? $files['items'] : [];

            foreach ($itemsInput as $itemIndex => $itemData) {
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => !empty($itemData['product_id']) ? (string) $itemData['product_id'] : null,
                    'product_name' => trim((string) ($itemData['product_name'] ?? 'Producto personalizado')),
                    'quantity' => max(0.01, (float) ($itemData['quantity'] ?? 1)),
                    'portions_or_size' => isset($itemData['portions_or_size']) && trim((string) $itemData['portions_or_size']) !== ''
                        ? trim((string) $itemData['portions_or_size'])
                        : null,
                    'unit_price' => max(0.0, (float) ($itemData['unit_price'] ?? 0)),
                    'custom_description' => isset($itemData['custom_description']) && trim((string) $itemData['custom_description']) !== ''
                        ? trim((string) $itemData['custom_description'])
                        : null,
                    'reference_notes' => isset($itemData['reference_notes']) && trim((string) $itemData['reference_notes']) !== ''
                        ? trim((string) $itemData['reference_notes'])
                        : null,
                    'observations' => isset($itemData['observations']) && trim((string) $itemData['observations']) !== ''
                        ? trim((string) $itemData['observations'])
                        : null,
                    'sort_order' => (int) $itemIndex,
                ]);

                // Especificaciones personalizadas (OrderItemDetail)
                $detailsInput = is_array($itemData['details'] ?? null) ? $itemData['details'] : [];
                foreach ($detailsInput as $detailIndex => $detailRow) {
                    $label = trim((string) ($detailRow['label'] ?? ''));
                    $value = trim((string) ($detailRow['value'] ?? ''));
                    if ($label === '' || $value === '') {
                        continue;
                    }

                    OrderItemDetail::create([
                        'order_item_id' => $orderItem->id,
                        'label' => $label,
                        'value' => $value,
                        'sort_order' => (int) $detailIndex,
                    ]);
                }

                // Complementos específicos de este producto (OrderComplement)
                $itemComplements = is_array($itemData['complements'] ?? null) ? $itemData['complements'] : [];
                foreach ($itemComplements as $compRow) {
                    $description = trim((string) ($compRow['description'] ?? ''));
                    if ($description === '') {
                        continue;
                    }

                    OrderComplement::create([
                        'order_id' => $order->id,
                        'order_item_id' => $orderItem->id,
                        'description' => $description,
                        'provider_type' => in_array(($compRow['provider_type'] ?? 'client'), ['client', 'business'], true)
                            ? (string) $compRow['provider_type']
                            : 'client',
                    ]);
                }

                // Fotografías de referencia de este producto (OrderAttachment en .webp)
                $uploadedImages = $itemFilesGroup[$itemIndex]['images'] ?? ($itemData['images'] ?? []);
                if (is_array($uploadedImages)) {
                    foreach ($uploadedImages as $uploadedImage) {
                        if (!$uploadedImage instanceof UploadedFile) {
                            continue;
                        }

                        $stored = $this->processAndStoreAttachmentImage($uploadedImage);

                        OrderAttachment::create([
                            'order_id' => $order->id,
                            'order_item_id' => $orderItem->id,
                            'file_path' => $stored['file_path'],
                            'file_url' => $stored['file_url'],
                            'original_name' => $stored['original_name'],
                            'notes' => $orderItem->reference_notes,
                        ]);
                    }
                }
            }

            // Complementos generales a nivel del pedido (si también se enviaran fuera de items)
            $orderComplements = is_array($data['complements'] ?? null) ? $data['complements'] : [];
            foreach ($orderComplements as $compRow) {
                $description = trim((string) ($compRow['description'] ?? ''));
                if ($description === '') {
                    continue;
                }

                OrderComplement::create([
                    'order_id' => $order->id,
                    'order_item_id' => null,
                    'description' => $description,
                    'provider_type' => in_array(($compRow['provider_type'] ?? 'client'), ['client', 'business'], true)
                        ? (string) $compRow['provider_type']
                        : 'client',
                ]);
            }

            return $order->load([
                'customer',
                'user:id,name',
                'items.details',
                'items.attachments',
                'items.complements',
                'complements',
                'attachments',
                'payments.user:id,name',
            ]);
        });
    }

    /**
     * Registra un nuevo abono/pago para un pedido existente y recalcula automáticamente el saldo pendiente.
     *
     * @param array<string, mixed> $data
     */
    public function addPayment(Order $order, array $data, ?User $user = null): OrderPayment
    {
        return DB::transaction(function () use ($order, $data, $user): OrderPayment {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

            $amount = round(max(0.01, (float) ($data['amount'] ?? 0)), 2);

            $payment = OrderPayment::create([
                'order_id' => $lockedOrder->id,
                'user_id' => $user?->id ? (string) $user->id : null,
                'amount' => $amount,
                'payment_method' => trim((string) ($data['payment_method'] ?? 'Efectivo')) ?: 'Efectivo',
                'paid_at' => !empty($data['paid_at']) ? (string) $data['paid_at'] : now()->toDateTimeString(),
                'notes' => isset($data['notes']) && trim((string) $data['notes']) !== ''
                    ? trim((string) $data['notes'])
                    : null,
            ]);

            $paidAmount = round((float) $lockedOrder->payments()->sum('amount'), 2);
            $balanceDue = max(0.0, round((float) $lockedOrder->total - $paidAmount, 2));

            $updatePayload = [
                'paid_amount' => $paidAmount,
                'balance_due' => $balanceDue,
            ];

            if (array_key_exists('payment_notes', $data) && $data['payment_notes'] !== null) {
                $updatePayload['payment_notes'] = trim((string) $data['payment_notes']) ?: null;
            }

            $lockedOrder->update($updatePayload);

            return $payment->load('user:id,name');
        });
    }

    /**
     * Comprime (redimensionando si supera 1600px) y convierte cualquier imagen subida a formato .webp (calidad 80).
     *
     * @return array{file_path: string, file_url: string, original_name: string}
     */
    public function processAndStoreAttachmentImage(UploadedFile $file): array
    {
        try {
            $realPath = $file->getRealPath();
            if ($realPath === false || $realPath === '') {
                throw new RuntimeException('El archivo temporal de la imagen de referencia no es accesible.');
            }

            $imageData = file_get_contents($realPath);
            if ($imageData === false) {
                throw new RuntimeException('No se pudo leer el contenido de la imagen subida.');
            }

            $sourceImage = @imagecreatefromstring($imageData);
            if ($sourceImage === false) {
                throw new RuntimeException('El archivo adjunto no es una imagen válida.');
            }

            $origWidth = imagesx($sourceImage);
            $origHeight = imagesy($sourceImage);
            $maxDimension = 1600;

            if ($origWidth > $maxDimension || $origHeight > $maxDimension) {
                $ratio = min($maxDimension / $origWidth, $maxDimension / $origHeight);
                $targetWidth = max(1, (int) round($origWidth * $ratio));
                $targetHeight = max(1, (int) round($origHeight * $ratio));

                $resized = imagecreatetruecolor($targetWidth, $targetHeight);
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
                imagefilledrectangle($resized, 0, 0, $targetWidth, $targetHeight, $transparent);

                imagecopyresampled(
                    $resized,
                    $sourceImage,
                    0,
                    0,
                    0,
                    0,
                    $targetWidth,
                    $targetHeight,
                    $origWidth,
                    $origHeight
                );
                imagedestroy($sourceImage);
                $image = $resized;
            } else {
                if (!imageistruecolor($sourceImage)) {
                    imagepalettetotruecolor($sourceImage);
                }
                imagealphablending($sourceImage, true);
                imagesavealpha($sourceImage, true);
                $image = $sourceImage;
            }

            $directory = 'orders/attachments';
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
            }

            $filename = 'ord_' . Str::lower(Str::random(12)) . '_' . time() . '.webp';
            $relativePath = $directory . '/' . $filename;

            ob_start();
            $converted = imagewebp($image, null, 80);
            $webpBinary = ob_get_clean();
            imagedestroy($image);

            if ($converted === false || $webpBinary === false || $webpBinary === '') {
                throw new RuntimeException('Error al comprimir y convertir la fotografía a formato .webp.');
            }

            Storage::disk('public')->put($relativePath, $webpBinary);

            $baseOriginal = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $cleanOriginal = ($baseOriginal !== '' ? Str::slug($baseOriginal) : 'referencia') . '.webp';

            return [
                'file_path' => $relativePath,
                'file_url' => Storage::url($relativePath),
                'original_name' => $cleanOriginal,
            ];
        } catch (\Throwable $e) {
            Log::error('Error procesando fotografía de pedido a WebP: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            throw new RuntimeException('No se pudo procesar la fotografía de referencia: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Busca un cliente existente por ID o por número de celular, o crea uno nuevo para conservar su historial.
     *
     * @param array<string, mixed> $customerData
     */
    private function resolveOrUpdateCustomer(array $customerData, ?string $deliveryAddress, ?string $branchId): Customer
    {
        $customerId = isset($customerData['id']) ? trim((string) $customerData['id']) : '';
        $fullName = trim((string) ($customerData['full_name'] ?? ''));
        $phone = trim((string) ($customerData['phone'] ?? ''));

        $customer = null;

        if ($customerId !== '' && Str::isUuid($customerId)) {
            $customer = Customer::find($customerId);
        }

        if ($customer === null && $phone !== '') {
            $customer = Customer::query()->where('phone', $phone)->first();
        }

        if ($customer !== null) {
            $updates = [];
            if ($fullName !== '' && $customer->full_name !== $fullName) {
                $updates['full_name'] = $fullName;
            }
            if ($phone !== '' && $customer->phone !== $phone) {
                $updates['phone'] = $phone;
            }
            if ($deliveryAddress !== null && trim($deliveryAddress) !== '') {
                $updates['address'] = trim($deliveryAddress);
            }
            if (!empty($updates)) {
                $customer->update($updates);
            }

            return $customer;
        }

        return Customer::create([
            'branch_id' => $branchId,
            'full_name' => $fullName,
            'phone' => $phone,
            'address' => ($deliveryAddress !== null && trim($deliveryAddress) !== '') ? trim($deliveryAddress) : null,
        ]);
    }

    /**
     * Anula una reserva u orden de pedido activa.
     */
    public function cancelOrder(string $orderId, ?string $reason, string $userId): Order
    {
        return DB::transaction(function () use ($orderId, $reason, $userId): Order {
            /** @var Order $order */
            $order = Order::query()->lockForUpdate()->findOrFail($orderId);

            if ($order->isCancelled()) {
                throw new \DomainException("El pedido #{$order->formatted_number} ya se encuentra anulado.");
            }

            $order->update([
                'status' => 'anulado',
                'cancelled_at' => now(),
                'cancelled_by_user_id' => $userId,
                'cancellation_reason' => $reason !== null && trim($reason) !== '' ? trim($reason) : null,
            ]);

            return $order->fresh(['customer', 'branch', 'user', 'cancelledBy']);
        });
    }
}


