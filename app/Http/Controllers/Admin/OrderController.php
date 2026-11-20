<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\OrderComplementProviderEnum;
use App\Enums\OrderDeliveryModeEnum;
use App\Facades\CompanyFacade;
use App\Facades\OrderFacade;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Order\CancelOrderRequest;
use App\Http\Requests\Admin\Order\StoreOrderPaymentRequest;
use App\Http\Requests\Admin\Order\StoreOrderRequest;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\OrderResource;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $search = $request->input('search') ? trim((string) $request->input('search')) : null;
        $registeredFrom = $request->input('registered_from') ? (string) $request->input('registered_from') : null;
        $registeredTo = $request->input('registered_to') ? (string) $request->input('registered_to') : null;
        $deliveryFrom = $request->input('delivery_from') ? (string) $request->input('delivery_from') : null;
        $deliveryTo = $request->input('delivery_to') ? (string) $request->input('delivery_to') : null;
        $deliveryMode = $request->input('delivery_mode') ? (string) $request->input('delivery_mode') : null;
        $customerId = $request->input('customer_id') ? (string) $request->input('customer_id') : null;
        $status = $request->input('status') ? (string) $request->input('status') : null;

        $query = Order::query()
            ->with([
                'customer' => fn ($q) => $q->select('id', 'full_name', 'phone', 'address')->withCount('orders'),
                'user:id,name',
                'cancelledBy:id,name',
                'items:id,order_id,product_name,quantity,portions_or_size',
            ])
            ->withCount(['items', 'attachments'])
            ->search($search)
            ->filterByRegisteredDate($registeredFrom, $registeredTo)
            ->filterByDeliveryDate($deliveryFrom, $deliveryTo);

        if ($deliveryMode !== null && $deliveryMode !== '') {
            $query->where('delivery_mode', $deliveryMode);
        }

        if ($customerId !== null && $customerId !== '') {
            $query->where('customer_id', $customerId);
        }

        if ($status !== null && $status !== '') {
            $query->filterByStatus($status);
        }

        $orders = $query
            ->orderByDesc('number')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Order $order): array => (new OrderResource($order))->resolve());

        $selectedCustomer = null;
        if ($customerId !== null && $customerId !== '') {
            $customerModel = Customer::query()->withCount('orders')->find($customerId);
            if ($customerModel !== null) {
                $selectedCustomer = (new CustomerResource($customerModel))->resolve();
            }
        }

        $stats = [
            'total_orders' => Order::query()->count(),
            'active_orders' => Order::query()->registered()->count(),
            'cancelled_orders' => Order::query()->cancelled()->count(),
            'today_deliveries' => Order::query()->registered()->whereDate('delivery_date', now()->toDateString())->count(),
        ];

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'filters' => [
                'search' => $search ?? '',
                'registered_from' => $registeredFrom ?? '',
                'registered_to' => $registeredTo ?? '',
                'delivery_from' => $deliveryFrom ?? '',
                'delivery_to' => $deliveryTo ?? '',
                'delivery_mode' => $deliveryMode ?? '',
                'customer_id' => $customerId ?? '',
                'status' => $status ?? '',
                'per_page' => $perPage,
            ],
            'selectedCustomer' => $selectedCustomer,
            'stats' => $stats,
            'deliveryModes' => OrderDeliveryModeEnum::options(),
        ]);
    }

    public function create(): Response
    {
        $nextNumber = ((int) Order::query()->max('number')) + 1;

        $catalogProducts = Product::query()
            ->select('id', 'name', 'code', 'price')
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(250)
            ->get();

        return Inertia::render('Admin/Orders/Create', [
            'nextFormattedNumber' => str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT),
            'todayDate' => now()->toDateString(),
            'deliveryModes' => OrderDeliveryModeEnum::options(),
            'complementProviders' => OrderComplementProviderEnum::options(),
            'paymentMethods' => ['QR', 'Efectivo', 'Transferencia', 'Tarjeta'],
            'catalogProducts' => $catalogProducts,
        ]);
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $order = OrderFacade::createOrder(
            data: $request->validated(),
            files: $request->allFiles(),
            user: $request->user()
        );

        return redirect()
            ->route('admin.orders.index')
            ->with('success', "Reserva #{$order->formatted_number} registrada correctamente.");
    }

    public function show(Order $order): JsonResponse
    {
        $order->load([
            'customer' => fn ($q) => $q->withCount('orders'),
            'user:id,name',
            'items.details',
            'items.attachments',
            'items.complements',
            'complements',
            'attachments',
            'payments.user:id,name',
        ]);

        $previousOrders = Order::query()
            ->select('id', 'number', 'registered_at', 'delivery_date', 'delivery_time', 'total', 'balance_due')
            ->where('customer_id', $order->customer_id)
            ->where('id', '!=', $order->id)
            ->orderByDesc('number')
            ->limit(10)
            ->get()
            ->map(fn (Order $prev): array => [
                'id' => $prev->id,
                'number' => $prev->number,
                'formatted_number' => $prev->formatted_number,
                'registered_at_formatted' => $prev->registered_at?->format('d/m/Y'),
                'delivery_date_formatted' => $prev->delivery_date?->format('d/m/Y'),
                'delivery_time' => $prev->delivery_time,
                'total' => (float) $prev->total,
                'balance_due' => (float) $prev->balance_due,
            ]);

        return response()->json([
            'order' => (new OrderResource($order))->resolve(),
            'previous_orders' => $previousOrders,
        ]);
    }

    public function addPayment(StoreOrderPaymentRequest $request, Order $order): RedirectResponse|JsonResponse
    {
        OrderFacade::addPayment(
            order: $order,
            data: $request->validated(),
            user: $request->user()
        );

        if ($request->wantsJson()) {
            $order->load([
                'customer' => fn ($q) => $q->withCount('orders'),
                'user:id,name',
                'items.details',
                'items.attachments',
                'items.complements',
                'complements',
                'attachments',
                'payments.user:id,name',
            ]);

            return response()->json([
                'message' => 'Abono registrado exitosamente.',
                'order' => (new OrderResource($order))->resolve(),
            ]);
        }

        return redirect()
            ->back()
            ->with('success', "Abono registrado en la Reserva #{$order->formatted_number}.");
    }

    public function cancel(CancelOrderRequest $request, string $id): JsonResponse|RedirectResponse
    {
        $userId = (string) $request->user()?->id;
        $reason = $request->input('reason') ? (string) $request->input('reason') : null;

        try {
            $order = OrderFacade::cancelOrder($id, $reason, $userId);
        } catch (\DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => "Pedido #{$order->formatted_number} anulado exitosamente.",
                'order' => (new OrderResource($order))->resolve(),
            ]);
        }

        return redirect()
            ->back()
            ->with('success', "Pedido #{$order->formatted_number} anulado exitosamente.");
    }

    public function searchCustomers(Request $request): JsonResponse
    {
        $term = trim((string) $request->input('q', ''));

        $customers = Customer::query()
            ->withCount('orders')
            ->search($term)
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        return response()->json([
            'customers' => CustomerResource::collection($customers)->resolve(),
        ]);
    }

    public function receiptPdf(Request $request, Order $order): HttpResponse
    {
        $order->load([
            'customer',
            'user:id,name',
            'items.details',
            'items.attachments',
            'items.complements',
            'complements',
            'payments.user:id,name',
        ]);

        $company = CompanyFacade::getCompany();

        $pdf = Pdf::loadView('exports.orders.order-receipt-pdf', [
            'order' => $order,
            'company' => $company,
            'generatedAt' => now()->locale('es')->translatedFormat('d/m/Y H:i'),
        ])->setPaper('a4', 'portrait');

        $filename = "reserva-{$order->formatted_number}.pdf";

        if ($request->boolean('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }
}

