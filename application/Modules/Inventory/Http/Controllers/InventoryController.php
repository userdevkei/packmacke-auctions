<?php

namespace Modules\Inventory\Http\Controllers;

use App\Exports\InventoryStockExport;
use App\Exports\InventoryTransactionExport;
use App\Exports\ItemMovementsExport;
use App\Exports\LocalPurchaseOrderExport;
use App\Exports\LpoDetailedExport;
use App\Exports\LpoExport;
use App\Exports\UnifiedInventoryTransactionExport;
use App\Models\Client;
use App\Models\Station;
use App\Services\AppClass;
use App\Services\CustomIds;
use App\Services\InventoryService;
use App\Services\Log;
use App\Services\TraceTea;
use Carbon\Carbon;
use DB;
use Excel;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Modules\Inventory\Entities\LocalPurchaseOrder;
use Modules\Inventory\Entities\LpoItem;
use Mpdf\Mpdf;
use Mpdf\Output\Destination as PdfDestination;
use View;
use Modules\Inventory\Entities\Inventory;
use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Entities\ItemCategory;
use Modules\Inventory\Entities\PurchaseOrder;
use Modules\Inventory\Entities\Release;
use Modules\Inventory\Entities\ReleaseItem;
use Modules\Inventory\Entities\Requisition;
use Modules\Inventory\Entities\Supplier;
use Modules\Inventory\Entities\TransferIn;
use Modules\Inventory\Entities\TransferOut;
use Modules\Inventory\Entities\TransferOutItem;
use PhpOffice\PhpSpreadsheet\Calculation\Category;

class InventoryController extends Controller
{

    protected $logger;
    protected $inventoryService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Log $logger, TraceTea $traceService, AppClass $appClass, InventoryService $inventoryService)
    {
        $this->logger = $logger;
        $this->traceService = $traceService;
        $this->AppClass = $appClass;
        $this->inventoryService = $inventoryService;

    }

    public function itemCategory()
    {
        $categories = ItemCategory::orderBy('category_name')->get();
        $types = ItemCategory::getTypeOptions();
        $status = ItemCategory::getStatusOptions();
        return view('inventory::items.itemCategory', compact('categories', 'types', 'status'));
    }

    public function itemCategoryStore(Request $request){
        $request->validate([
            'category_name' => 'required|string|unique:item_categories',
        ]);

        ItemCategory::create([
            'id' => (new CustomIds())->generateId(),
            'category_name' => $request->category_name,
            'status' => $request->status,
            'type' => $request->type,
            'user_id' => auth()->id(),
        ]);
        $this->logger->create();
        return redirect()->back()->with('message', 'Item Category Created Successfully');
    }

    public function deleteItemCategory($id)
    {
        ItemCategory::destroy($id);
        $this->logger->create();
        return redirect()->back()->with('message', 'Item Category Deleted Successfully');
    }

    public function itemCategoryUpdate(Request $request, $id)
    {
        $request->validate([
            'category_name' => 'required|string|unique:item_categories,category_name,'.$id,
        ]);

        ItemCategory::where('id', $id)->update([
            'category_name' => $request->category_name,
            'status' => $request->status,
            'type' => $request->type,
        ]);
        $this->logger->create();
        return redirect()->back()->with('message', 'Item Category Updated Successfully');
    }

    public function items()
    {
        $items = InventoryItem::orderBy('item_name', 'asc')->get();
        $categories = ItemCategory::where('status', 'active')->get();
        $status = InventoryItem::getStatusOptions();
        $unit = InventoryItem::getUnitOptions();
        return view('inventory::items.items', compact('items', 'categories', 'status', 'unit'));
    }

    public function inventoryItemStore(Request $request)
    {
        $request->validate([
            'item_name' => 'required|string',
            'unit' => 'required',
            'category_id' => 'required',
            'status' => 'required',
        ]);

        if (InventoryItem::where(['item_name' => $request->item_name, 'category_id' => $request->category_id])->exists()) {
            return redirect()->back()->with('error', 'Item already exists');
        }

        InventoryItem::create([
            'id' => (new CustomIds())->generateId(),
            'item_name' => $request->item_name,
            'category_id' => $request->category_id,
            'status' => $request->status,
            'user_id' => auth()->id(),
            'unit' => $request->unit,
        ]);
        $this->logger->create();
        return redirect()->back()->with('message', 'Item Added Successfully');
    }

    public function inventoryItemUpdate(Request $request, $id)
    {
        $item = InventoryItem::findOrFail($id);

        $validated = $request->validate([
            'item_name' => 'required|string|max:255|unique:inventory_items,item_name,'.$id,
            'category_id' => 'required|string|exists:item_categories,id',
            'unit' => 'required|string|in:' . implode(',', array_keys(InventoryItem::getUnitOptions())),
            'status' => 'nullable|string|in:' . implode(',', array_keys(InventoryItem::getStatusOptions())),
        ]);

        // Check if item name already exists in the same category (excluding current item)
        if (InventoryItem::where('item_name', $validated['item_name'])
            ->where('category_id', $validated['category_id'])
            ->where('id', '!=', $id)
            ->exists()) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Item already exists in this category');
        }

        try {
            $item->update([
                'item_name' => $validated['item_name'],
                'category_id' => $validated['category_id'],
                'unit' => $validated['unit'],
                'status' => $validated['status'] ?? $item->status,
            ]);

            $this->logger->create();

            return redirect()->back()->with('success', 'Item updated successfully');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update item. Please try again.');
        }
    }

    public function inventoryItemDelete($id)
    {
        try {
            $item = InventoryItem::findOrFail($id);
            $itemName = $item->item_name;
            $item->delete();
            $this->logger->create();
            return redirect()->back()->with('success', 'Item deleted successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete item. Please try again.');
        }
    }

    public function purchases(Request $request)
    {
        $client = $request->client;
        $lpo = $request->lpo;
        $dateFrom = $request->dateFrom;
        $dateTo = $request->dateTo;

        $clients = PurchaseOrder::join('clients', 'clients.client_id', '=', 'purchase_orders.client_id')->select('clients.client_id', 'clients.client_name')->groupBy('client_id')->get();
        $query = PurchaseOrder::join('clients', 'clients.client_id', '=', 'purchase_orders.client_id')
            ->join('suppliers', 'suppliers.id', '=', 'purchase_orders.supplier_id')
            ->select('purchase_orders.*', 'clients.client_name', 'suppliers.supplier_name')
            ->orderBy('created_at', 'asc');

        if ($client) {
            $query->where('purchase_orders.client_id', $client);
        }

        if ($lpo) {
            $query->where('purchase_orders.lpo_number', $lpo)
                ->orWhere('purchase_orders.purchase_order_number', $lpo);
        }

        if ($request->dateFrom && $request->dateTo) {
            $query->whereBetween('date', [$request->dateFrom, $request->dateTo]);
        } elseif ($request->dateFrom) {
            $query->where('date', '>=', $request->dateFrom);
        } elseif ($request->dateTo) {
            $query->where('date', '<=', $request->dateTo);
        }
        $lpos = $query->get();

        if ($request->has('export')) {
            return Excel::download(
                new LocalPurchaseOrderExport($lpos),
                'purchase orders_' . date('Y-m-d_His') . '.xlsx'
            );
        }
        return view('inventory::inventory.ins.lpos', compact('lpos', 'clients', 'dateFrom', 'dateTo', 'client', 'lpo'));
    }

    public function createLpo()
    {
        $items = InventoryItem::orderBy('item_name', 'asc')->get();
        $clients = Client::orderBy('client_name', 'asc')->get();
        $suppliers = Supplier::orderBy('supplier_name', 'asc')->get();
        return view('inventory::inventory.ins.addLpo', compact('suppliers', 'clients', 'items'));
    }

    public function receiveLpo(Request $request)
    {
        $validated = $request->validate([
            'lpo_id'        => 'nullable|string',
            'lpo_number'    => 'required|string|max:20',
            'date'          => 'required|date',
            'client'        => 'required|string|max:255',
            'supplier'      => 'nullable|string',
            'supplier_name' => 'nullable|string|max:255',
            'items'         => 'required|array|min:1',
            'items.*.itemId'   => 'required|string',
            'items.*.itemName' => 'required|string',
            'items.*.unit'     => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'notes'         => 'nullable|string',
        ]);

        if (empty($validated['supplier']) && empty($validated['supplier_name'])) {
            return response()->json([
                'success' => false,
                'message' => 'A supplier is required.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            // --- resolve supplier ID ---
            if ($validated['supplier_name']) {
                $supplier   = Supplier::firstOrCreate(
                    ['supplier_name' => $validated['supplier_name']],
                    ['id' => (new CustomIds())->generateId()]
                );
                $supplierId = $supplier->id;
            } else {
                $supplierId = $validated['supplier'];
            }

            // --- create purchase_order ---
            $purchaseOrder = PurchaseOrder::create([
                'id'                    => (new CustomIds())->generateId(),
                'purchase_order_number' => PurchaseOrder::newPurchaseOrderNumber(),
                'lpo_number'            => $validated['lpo_number'],
                'client_id'             => $validated['client'],
                'supplier_id'           => $supplierId,
                'date'                  => $validated['date'],
                'notes'                 => $validated['notes'] ?? null,
                'status'                => 'pending',
                'user_id'               => auth()->id(),
            ]);

            // --- create purchase_order items ---
            foreach ($validated['items'] as $index => $item) {
                Inventory::create([
                    'id'                => (new CustomIds())->generateId(),
                    'purchase_order_id' => $purchaseOrder->id,
                    'item_id'           => $item['itemId'],
                    'quantity'          => $item['quantity'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'LPO received successfully.',
                'po_id'   => $purchaseOrder->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('LPO receive failed: ' . $e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to receive LPO. Please try again.',
            ], 500);
        }
    }

    public function lpoUpdateStatus(Request $request, $id)
    {
        PurchaseOrder::where('id', $id)->update([
            'status' => $request->status,
        ]);
        return response()->json([
            'success' => true,
            'message' => 'LPO status updated successfully'
        ]);
    }

    public function lpoDelete($id){
        Inventory::where('purchase_order_id', $id)->delete();
        PurchaseOrder::destroy($id);
        $this->logger->create();
        return redirect()->back()->with('success', 'LPO deleted successfully');
    }

    public function lpoEdit($id){
            $lpo = PurchaseOrder::with('items')->findOrFail($id);
            $clients = Client::orderBy('client_name')->get();
            $suppliers = Supplier::orderBy('supplier_name')->get();
            $items =InventoryItem::orderBy('item_name')->get();
            return view('inventory::inventory.ins.editLpo', compact('lpo', 'clients', 'suppliers', 'items'));
    }

    public function lpoUpdate(Request $request, $id)
    {
        $validated = $request->validate([
            'lpo_number'         => 'required|string',
            'po_id'         => 'required|string',
            'date'          => 'required|date',
            'client'        => 'required|string|max:255',
            'supplier'      => 'nullable|string',
            'supplier_name' => 'nullable|string|max:255',
            'items'         => 'required|array|min:1',
            'items.*.id'       => 'nullable|string',
            'items.*.itemId'   => 'required|string',
            'items.*.itemName' => 'required|string',
            'items.*.unit'     => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.existing' => 'nullable|boolean',
            'items_to_delete'    => 'nullable|array',
            'items_to_delete.*'  => 'nullable|string',
            'notes'         => 'nullable|string',
        ]);

        if (empty($validated['supplier']) && empty($validated['supplier_name'])) {
            return response()->json([
                'success' => false,
                'message' => 'A supplier is required.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $po = PurchaseOrder::findOrFail($id);

            // --- resolve supplier ---
            if ($validated['supplier_name']) {
                $supplier   = Supplier::firstOrCreate(
                    ['supplier_name' => $validated['supplier_name']],
                    ['id' => (new CustomIds())->generateId()]
                );
                $supplierId = $supplier->id;
            } else {
                $supplierId = $validated['supplier'];
            }

            // --- update PO header (lpo_number is locked, not touched) ---
            $po->update([
                'lpo_number'  => $validated['lpo_number'],
                'date'        => $validated['date'],
                'client_id'   => $validated['client'],
                'supplier_id' => $supplierId,
                'notes'       => $validated['notes'] ?? null,
            ]);

            // --- delete removed items ---
            if (!empty($validated['items_to_delete'])) {
                Inventory::whereIn('id', $validated['items_to_delete'])
                    ->where('purchase_order_id', $id)
                    ->delete();
            }

            // --- process each item ---
            foreach ($validated['items'] as $itemData) {
                if ($itemData['existing'] && $itemData['id']) {
                    // existing item — update quantity only
                    Inventory::where('id', $itemData['id'])
                        ->where('purchase_order_id', $id)
                        ->update(['quantity' => $itemData['quantity']]);
                } else {
                    // new item — check if same item_id already exists on this PO first
                    $exists = Inventory::where('purchase_order_id', $id)
                        ->where('item_id', $itemData['itemId'])
                        ->first();

                    if ($exists) {
                        $exists->update(['quantity' => $itemData['quantity']]);
                    } else {
                        Inventory::create([
                            'id'                => (new CustomIds())->generateId(),
                            'purchase_order_id' => $id,
                            'item_id'           => $itemData['itemId'],
                            'quantity'          => $itemData['quantity'],
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase order updated successfully!'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            \Log::error('PO update validation failed: ' . json_encode($e->errors()));

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('PO update failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update purchase order. Please try again.'
            ], 500);
        }
    }

    public function inStock(Request $request)
    {
        // Get distinct clients and items for dropdowns
        $clients = DB::table('inventory_stock_balances')
            ->select('client_id', 'client_name')
            ->distinct()
            ->get();

        $items = DB::table('inventory_stock_balances')
            ->select('item_id', 'item_name')
            ->distinct()
            ->get();

        $client = $request->client;
        $item = $request->item;

        // Start the main query
        $query = DB::table('inventory_stock_balances');

        // Apply filters
        if ($request->client) {
            $query->where('client_id', $request->client);
        }

        if ($request->item) {
            $query->where('item_id', $request->item); // Fixed: was $request->items
        }

        $inventories = $query->get();

        if ($request->has('export')) {
            return Excel::download(
                new InventoryStockExport($inventories),
                'inventory_stock_' . date('Y-m-d_His') . '.xlsx'
            );
        }

        return view('inventory::inventory.inventory', compact('inventories', 'clients', 'items', 'client', 'item'));
    }

    public function transferOutIndex(Request $request)
    {
        $clients = TransferOut::with(['client'])
            ->join('clients', 'transfer_outs.client_id', '=', 'clients.client_id')
            ->select('transfer_outs.client_id', 'clients.client_name')
            ->groupBy('transfer_outs.client_id', 'clients.client_name')
            ->get();
        $transactionNumber = $request->transaction_number;
        $dateFrom = $request->dateFrom;
        $dateTo = $request->dateTo;
        $client = $request->client;

        $query = TransferOut::with(['client', 'recipient', 'items', 'user'])
            ->orderBy('created_at', 'desc');

        if ($request->client) {
            $query->where('client_id', $request->client);
        }

        if ($transactionNumber) {
            $query->where('transfer_outs.transfer_out_number', $transactionNumber);
        }

        if ($request->dateFrom && $request->dateTo) {
            $query->whereBetween('transfer_outs.transfer_date', [$request->dateFrom, $request->dateTo]);
        } elseif ($request->dateFrom) {
            $query->where('transfer_outs.transfer_date', '>=', $request->dateFrom);
        } elseif ($request->dateTo) {
            $query->where('transfer_outs.transfer_date', '<=', $request->dateTo);
        }

        $transactions = $query->get();
        $type = 'transfer_out';
        if ($request->has('export')) {
            return Excel::download(
                new InventoryTransactionExport($transactions, $type),
                $type. '_' . date('Y-m-d_His') . '.xlsx'
            );
        }
        return view('inventory::inventory.transactions.index', compact('transactions', 'type', 'dateFrom', 'dateTo', 'clients', 'client', 'transactionNumber'));
    }

    public function createTransferOut()
    {
        $clients = Client::orderBy('client_name')->get();
        $transferOuts = DB::table('inventory_stock_balances')->select('client_id', 'client_name')->groupBy('client_id')->get();
        return view('inventory::inventory.transfer_outs.create', compact('clients', 'transferOuts'));
    }

    public function storeTransferOut(Request $request)
    {
        $validated = $request->validate([
            'transfer_date' => 'required|date',
            'client_id' => 'required|string',
            'recipient_id' => 'nullable|string',

            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $result = $this->inventoryService->createTransferOut($validated);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Transfer Out created successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message']
        ], 500);
    }

    public function showTransferOut($id)
    {
        $transaction = TransferOut::with(['client', 'recipient', 'items.item', 'user'])
            ->findOrFail($id);
        $type = 'transfer_out';
        return view('inventory::inventory.transactions.show', compact('transaction', 'type'));
    }

    public function editTransferOut($id)
    {
        $transferOut = TransferOut::with('items.item')->findOrFail($id);
        if ($transferOut->status !== 'pending') {
            return redirect()->route('inventory.transfer_outs.index')
                ->with('error', 'Only pending transfers can be edited');
        }
        $clients = DB::table('clients')->get();
        $transferOuts = DB::table('inventory_stock_balances')->select('client_id', 'client_name')->groupBy('client_id')->get();
        return view('inventory::inventory.transfer_outs.create', compact('transferOut', 'clients', 'transferOuts'));
    }

    public function updateTransferOut(Request $request, $id)
    {
        $validated = $request->validate([
            'transfer_date' => 'required|date',
            'client_id' => 'required|string',
            'recipient_id' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable',
            'items.*.item_id' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items_to_delete' => 'nullable|array',
        ]);

        DB::beginTransaction();

        try {
            $transferOut = TransferOut::findOrFail($id);

            if ($transferOut->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending transfers can be updated'
                ], 400);
            }

            $transferOut->update([
                'transfer_date' => $validated['transfer_date'],
                'client_id' => $validated['client_id'],
                'recipient_id' => $validated['recipient_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Delete items marked for deletion
            if (!empty($validated['items_to_delete'])) {
                TransferOutItem::whereIn('id', $validated['items_to_delete'])
                    ->where('transfer_out_id', $id)
                    ->delete();
            }

            // Process items: update existing or create new
            foreach ($validated['items'] as $itemData) {
                if (!empty($itemData['id'])) {
                    // Update existing item - convert ID to string if needed
                    $itemId = (string) $itemData['id'];
                    TransferOutItem::where('id', $itemId)
                        ->where('transfer_out_id', $id)
                        ->update([
                            'item_id' => $itemData['item_id'],
                            'quantity' => $itemData['quantity']
                        ]);
                } else {
                    // Create new item
                    TransferOutItem::create([
                        'id' => (new CustomIds())->generateId(),
                        'transfer_out_id' => $id,
                        'item_id' => $itemData['item_id'],
                        'quantity' => $itemData['quantity'],
                    ]);
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Transfer Out updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Transfer Out Update Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function approveTransferOut($id)
    {
        $result = $this->inventoryService->approve('transfer_out', $id);
        return response()->json($result);
    }

    public function cancelTransferOut($id)
    {
        $result = $this->inventoryService->cancel('transfer_out', $id);
        return response()->json($result);
    }

    // ==================== RELEASE METHODS ====================

    public function releaseIndex(Request $request)
    {
        $clients = Release::join('clients', 'releases.client_id', '=', 'clients.client_id')
            ->select('releases.client_id', 'clients.client_name')
            ->groupBy('releases.client_id', 'clients.client_name')
            ->get();

        $transactionNumber = $request->transaction_number;
        $dateFrom = $request->dateFrom;
        $dateTo = $request->dateTo;
        $client = $request->client;

        $query = Release::with(['client', 'items', 'user'])
            ->orderBy('created_at', 'desc');

        if ($request->client) {
            $query->where('client_id', $request->client);
        }

        if ($transactionNumber) {
            $query->where('releases.release_number', $transactionNumber);
        }

        if ($request->dateFrom && $request->dateTo) {
            $query->whereBetween('releases.release_date', [$request->dateFrom, $request->dateTo]);
        } elseif ($request->dateFrom) {
            $query->where('releases.release_date', '>=', $request->dateFrom);
        } elseif ($request->dateTo) {
            $query->where('releases.release_date', '<=', $request->dateTo);
        }

        $transactions = $query->get();

        $type = 'release';
        if ($request->has('export')) {
            return Excel::download(
                new InventoryTransactionExport($transactions, $type),
                $type. '_' . date('Y-m-d_His') . '.xlsx'
            );
        }
        return view('inventory::inventory.transactions.index', compact('transactions', 'type', 'clients', 'dateFrom', 'dateTo', 'transactionNumber', 'client'));
    }

    public function createRelease()
    {
        $clients = DB::table('inventory_stock_balances')->select('client_id', 'client_name')->groupBy('client_id')->get();
        return view('inventory::inventory.releases.create', compact('clients'));
    }

    public function storeRelease(Request $request)
    {
        $validated = $request->validate([
            'release_date' => 'required|date',
            'client_id' => 'required|string',
            'released_to' => 'required|string',
            'notes' => 'nullable|string',
            'driver_name' => 'nullable|string',
            'phone_number' => 'nullable|string',
            'registration_number' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $result = $this->inventoryService->createRelease($validated);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Release created successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message']
        ], 500);
    }

    public function showRelease($id)
    {
        $transaction = Release::with(['client', 'items.item', 'user'])
            ->findOrFail($id);

        $type = 'release';

        return view('inventory::inventory.transactions.show', compact('transaction', 'type'));
    }

    public function editRelease($id)
    {
        $release = Release::with('items.item')->findOrFail($id);

        if ($release->status !== 'pending') {
            return redirect()->route('inventory.releases.index')
                ->with('error', 'Only pending releases can be edited');
        }

        $clients = DB::table('clients')->get();

        return view('inventory::inventory.releases.create', compact('release', 'clients'));
    }

    public function updateRelease(Request $request, $id)
    {
        $validated = $request->validate([
            'release_date' => 'required|date',
            'client_id' => 'required|string',
            'released_to' => 'required|string',
            'notes' => 'nullable|string',
            'driver_name' => 'nullable|string',
            'phone_number' => 'nullable|string',
            'registration_number' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable',
            'items.*.item_id' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items_to_delete' => 'nullable|array',
        ]);

        DB::beginTransaction();

        try {
            $release = Release::findOrFail($id);

            if ($release->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending releases can be updated'
                ], 400);
            }

            $release->update([
                'release_date' => $validated['release_date'],
                'client_id' => $validated['client_id'],
                'released_to' => $validated['released_to'],
                'notes' => $validated['notes'] ?? null,
                'driver_name' => $validated['driver_name'] ?? null,
                'phone_number' => $validated['phone_number'] ?? null,
                'registration_number' => $validated['registration_number'] ?? null,
            ]);

            // Log what we're about to delete
            \Log::info('Items to delete:', ['items_to_delete' => $validated['items_to_delete'] ?? []]);

            // Delete items marked for deletion
            if (!empty($validated['items_to_delete'])) {
                $deletedCount = ReleaseItem::whereIn('id', $validated['items_to_delete'])
                    ->where('release_id', $id)
                    ->delete();

                \Log::info('Deleted items count:', ['count' => $deletedCount]);
            }

            // Log what items we're processing
            \Log::info('Items to process:', ['items' => $validated['items']]);

            // Process items: update existing or create new
            foreach ($validated['items'] as $itemData) {
                if (!empty($itemData['id'])) {
                    // Update existing item
                    $itemId = (string) $itemData['id'];
                    \Log::info('Updating item:', ['id' => $itemId, 'item_id' => $itemData['item_id'], 'quantity' => $itemData['quantity']]);

                    $updated = ReleaseItem::where('id', $itemId)
                        ->where('release_id', $id)
                        ->update([
                            'item_id' => $itemData['item_id'],
                            'quantity' => $itemData['quantity']
                        ]);

                    \Log::info('Update result:', ['updated' => $updated]);
                } else {
                    // Create new item
                    \Log::info('Creating new item:', ['item_id' => $itemData['item_id'], 'quantity' => $itemData['quantity']]);

                    ReleaseItem::create([
                        'id' => (new CustomIds())->generateId(),
                        'release_id' => $id,
                        'item_id' => $itemData['item_id'],
                        'quantity' => $itemData['quantity'],
                    ]);
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Release updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Release Update Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function approveRelease($id)
    {
        $result = $this->inventoryService->approve('release', $id);
        return response()->json($result);
    }

    public function cancelRelease($id)
    {
        $result = $this->inventoryService->cancel('release', $id);
        return response()->json($result);
    }

    // ==================== REQUISITION METHODS ====================

    public function requisitionIndex(Request $request)
    {
        $clients = Requisition::join('clients', 'requisitions.client_id', '=', 'clients.client_id')
            ->select('requisitions.client_id', 'clients.client_name')
            ->groupBy('requisitions.client_id', 'clients.client_name')
            ->get();
        $transactionNumber = $request->transaction_number;
        $dateFrom = $request->dateFrom;
        $dateTo = $request->dateTo;
        $client = $request->client;

        $query = Requisition::with(['client', 'items', 'user', 'warehouse'])
            ->orderBy('created_at', 'desc');

            if ($request->client) {
                $query->where('client_id', $request->client);
            }

            if ($transactionNumber) {
                $query->where('requisitions.requisition_number', $transactionNumber);
            }

            if ($request->dateFrom && $request->dateTo) {
                $query->whereBetween('requisitions.requisition_date', [$request->dateFrom, $request->dateTo]);
            } elseif ($request->dateFrom) {
                $query->where('requisitions.requisition_date', '>=', $request->dateFrom);
            } elseif ($request->dateTo) {
                $query->where('requisitions.requisition_date', '<=', $request->dateTo);
            }

        $transactions = $query->get();
        $type = 'requisition';
        if ($request->has('export')) {
            return Excel::download(
                new InventoryTransactionExport($transactions, $type),
                $type. '_' . date('Y-m-d_His') . '.xlsx'
            );
        }
        return view('inventory::inventory.transactions.index', compact('transactions', 'type', 'clients', 'dateFrom', 'dateTo', 'client', 'transactionNumber'));
    }

    public function createRequisition()
    {
        $clients = DB::table('inventory_stock_balances')->select('client_id', 'client_name')->groupBy('client_id')->get();
        $warehouses = Station::where('status', 1)->get();
        return view('inventory::inventory.requisitions.create', compact('clients', 'warehouses'));
    }

    public function storeRequisition(Request $request)
    {
        $validated = $request->validate([
            'requisition_date' => 'required|date',
            'client_id' => 'required|string',
            'si_number' => 'nullable|string',
            'purpose' => 'nullable|string',
            'driver_name' => 'nullable|string',
            'phone_number' => 'nullable|string',
            'registration_number' => 'nullable|string',
            'warehouse_id' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $result = $this->inventoryService->createRequisition($validated);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Requisition created successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message']
        ], 500);
    }

    public function showRequisition($id)
    {
        $transaction = Requisition::with(['client', 'items.item', 'user'])
            ->findOrFail($id);
        $type = 'requisition';
        return view('inventory::inventory.transactions.show', compact('transaction', 'type'));
    }

    public function editRequisition($id)
    {
        $requisition = Requisition::with('items.item')->findOrFail($id);
        if ($requisition->status !== 'pending') {
            return redirect()->route('inventory.requisitions.index')
                ->with('error', 'Only pending requisitions can be edited');
        }
        $clients = DB::table('clients')->get();
        $warehouses = Station::where('status', 1)->get();
        return view('inventory::inventory.requisitions.create', compact('requisition', 'clients', 'warehouses'));
    }

    public function updateRequisition(Request $request, $id)
    {
        $validated = $request->validate([
            'requisition_date' => 'required|date',
            'client_id' => 'required|string',
            'si_number' => 'required|string',
            'purpose' => 'nullable|string',
            'notes' => 'nullable|string',
            'driver_name' => 'nullable|string',
            'phone_number' => 'nullable|string',
            'registration_number' => 'nullable|string',
            'warehouse_id' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|string',
            'items.*.item_id' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items_to_delete' => 'nullable|array',
        ]);

        DB::beginTransaction();

        try {
            $requisition = Requisition::findOrFail($id);

            if ($requisition->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending requisitions can be updated'
                ], 400);
            }

            $requisition->update([
                'requisition_date' => $validated['requisition_date'],
                'client_id' => $validated['client_id'],
                'requested_by' => $validated['si_number'],
                'purpose' => $validated['purpose'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'driver_name' => $validated['driver_name'] ?? null,
                'phone_number' => $validated['phone_number'] ?? null,
                'registration_number' => $validated['registration_number'] ?? null,
                'warehouse_id' => $validated['warehouse_id'] ?? null,
            ]);

            // Delete items marked for deletion
            if (!empty($validated['items_to_delete'])) {
                $requisition->items()->whereIn('id', $validated['items_to_delete'])->delete();
            }

            // Process items
            foreach ($validated['items'] as $itemData) {
                // If item has an id, update it
                if (!empty($itemData['id'])) {
                    $existingItem = $requisition->items()->where('id', $itemData['id'])->first();
                    if ($existingItem) {
                        $existingItem->update([
                            'quantity' => $itemData['quantity']
                        ]);
                    }
                } else {
                    // Check if item already exists by item_id
                    $existingItem = $requisition->items()->where('item_id', $itemData['item_id'])->first();

                    if ($existingItem) {
                        // Update existing
                        $existingItem->update([
                            'quantity' => $itemData['quantity']
                        ]);
                    } else {
                        // Create new item
                        $requisition->items()->create([
                            'id' => (new CustomIds())->generateId(),
                            'item_id' => $itemData['item_id'],
                            'quantity' => $itemData['quantity'],
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Requisition updated successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            \Log::error('Requisition update validation failed: ' . json_encode($e->errors()));
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', array_map(fn($err) => implode(', ', $err), $e->errors()))
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Requisition update failed: ' . $e->getMessage() . ' | Line: ' . $e->getLine() . ' | File: ' . $e->getFile());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update requisition: ' . $e->getMessage()
            ], 500);
        }
    }

    public function approveRequisition($id)
    {
        $result = $this->inventoryService->approve('requisition', $id);
        return response()->json($result);
    }

    public function cancelRequisition($id)
    {
        $result = $this->inventoryService->cancel('requisition', $id);
        return response()->json($result);
    }

    // ==================== HELPER METHODS ====================

    public function getClientAvailableItems($clientId)
    {
        $items = $this->inventoryService->getAvailableItems($clientId);

        return response()->json([
            'success' => true,
            'items' => $items
        ]);
    }

    public function clientInventorySummary($clientId)
    {
        $client = Client::findOrFail($clientId);
        $stockBalances = $this->inventoryService->getClientStockBalances($clientId);
        $recentMovements = $this->inventoryService->getClientMovements($clientId);
        $recentMovements = collect($recentMovements)->take(20);
        return view('inventory::inventory.clientInventory', compact('client', 'stockBalances', 'recentMovements'));
    }

    public function lpoDownload($id)
    {
        $lpo = PurchaseOrder::with('items')->findOrFail($id);

        $html = View::make('inventory::downloads.purchase_order', compact('lpo'))->render();

        // Initialize mPDF with settings
        $mpdf = new Mpdf([
            'tempDir' => storage_path('app/mpdf_temp'),
            'mode'        => 'utf-8',
            'format'      => 'A4-P', // Landscape
            'orientation' => 'P',
            'margin_top'    => 8,
            'margin_bottom' => 7,
            'margin_left'   => 10,
            'margin_right'  => 10,
//            'setAutoTopMargin' => 'stretch',
            'setAutoBottomMargin' => 'stretch',
        ]);

        // Write HTML content
        $mpdf->WriteHTML($html);

        // Generate PDF filename
        $pdfFileName = 'PURCHASE ORDER '.str_replace('/', '', $lpo->purchase_order_number).'.pdf';

        // Output PDF as downloadable file
        return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
        ]);
    }

//    public function fetchLpo(Request $request)
//    {
//        $lpo = LocalPurchaseOrder::with('items')->where('lpo_number', $request->lpo_number)->first();
//
//        if (!$lpo) {
//            return response()->json(['found' => false]);
//        }
//
//        return response()->json([
//            'found' => true,
//            'lpo'   => [
//                'id'          => $lpo->id,
//                'date'        => $lpo->date?->format('Y-m-d'),
//                'client_id'   => $lpo->client_id ?? null,
//                'supplier_id' => $lpo->supplier_id,
//                'status'      => $lpo->status,
//                'notes'       => $lpo->notes,
//                'items'       => $lpo->items->map(fn($i) => [
//                    'item_id'   => $i->item_id,
//                    'item_name' => $i->item_name,
//                    'unit'      => $i->unit,
//                    'quantity'  => $i->quantity,
//                ]),
//            ]
//        ]);
//    }

    public function fetchLpo(Request $request)
    {
        $lpo = LocalPurchaseOrder::with('items')->where('lpo_number', $request->lpo_number)->first();

        if (!$lpo) {
            return response()->json(['found' => false]);
        }

        // Get all inventory records for this LPO number
        $inventoryReceived = DB::table('inventories')
            ->join('purchase_orders', 'inventories.purchase_order_id', '=', 'purchase_orders.id')
            ->where('purchase_orders.lpo_number', $lpo->lpo_number)
            ->select('inventories.item_id', DB::raw('SUM(inventories.quantity) as total_received'))
            ->groupBy('inventories.item_id')
            ->get()
            ->keyBy('item_id');

        return response()->json([
            'found' => true,
            'lpo'   => [
                'id'          => $lpo->id,
                'date'        => $lpo->date?->format('Y-m-d'),
                'client_id'   => $lpo->client_id ?? null,
                'supplier_id' => $lpo->supplier_id,
                'status'      => $lpo->status,
                'notes'       => $lpo->notes,
                'items'       => $lpo->items->map(function($item) use ($inventoryReceived) {
                    $orderedQty = (float) $item->quantity;
                    $receivedQty = isset($inventoryReceived[$item->item_id])
                        ? (float) $inventoryReceived[$item->item_id]->total_received
                        : 0;

                    return [
                        'item_id'   => $item->item_id,
                        'item_name' => $item->item_name,
                        'unit'      => $item->unit,
                        'quantity'  => max(0, $orderedQty - $receivedQty), // Remaining quantity
                    ];
                }),
            ]
        ]);
    }
    public function downloadTransaction($id)
    {
        $transaction = Requisition::with('items', 'user')->find($id);
        $type = 'Requisition';

        if ($transaction == null) {
            $transaction = TransferOut::with('items', 'user')->find($id);
            $type = 'Transfer';
        }

        if ($transaction == null) {
            $transaction = Release::with('items', 'user')->find($id);
            $type = 'Release';
        }

        if ($transaction == null) {
            abort(404, 'Transaction not found');
        }

        $html = View::make('inventory::downloads.transaction', compact('transaction', 'type'))->render();

        // Initialize mPDF with settings
        $mpdf = new Mpdf([
            'tempDir' => storage_path('app/mpdf_temp'),
            'mode'        => 'utf-8',
            'format'      => 'A4-P', // Landscape
            'orientation' => 'P',
            'margin_top'    => 8,
            'margin_bottom' => 7,
            'margin_left'   => 10,
            'margin_right'  => 10,
            'setAutoBottomMargin' => 'stretch',
        ]);

        // Write HTML content
        $mpdf->WriteHTML($html);

        // Generate PDF filename
        $pdfFileName = $type.' #'. $transaction->release_number ?? $transaction->transfer_out_number ?? $transaction->requisition_number .'.pdf';

        // Output PDF as downloadable file
        return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
        ]);
    }
    public function lpoIndex(Request $request)
    {
        // Get filter parameters
        $dateFrom = $request->input('dateFrom');
        $dateTo = $request->input('dateTo');
        $supplier = $request->input('supplier');
        $status = $request->input('status');
        $lpoNumber = $request->input('lpo_number');
        $export = $request->input('export');
        // Build query
        $query = LocalPurchaseOrder::with(['supplier', 'items']);
        // Apply filters
        if ($dateFrom) {
            $query->where('date', '>=', Carbon::parse($dateFrom));
        }
        if ($dateTo) {
            $query->where('date', '<=', Carbon::parse($dateTo));
        }
        if ($supplier) {
            $query->where('supplier_id', $supplier);
        }
        if ($status) {
            $query->where('status', $status);
        }
        if ($lpoNumber) {
            $query->where('lpo_number', 'LIKE', '%' . $lpoNumber . '%');
        }

        // Check if export is requested
        if ($export) {
            return Excel::download(
                new LpoDetailedExport($query->get()),
                'local_purchase_order' . date('Y-m-d_His') . '.xlsx'
            );
        }
        // Get paginated results
        $lpos = $query->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50)
            ->appends($request->except('page'));
        // Get clients and suppliers for filter dropdowns
        $clients = Client::orderBy('client_name')->get();
        $suppliers = Supplier::orderBy('supplier_name')->get();
        return view('inventory::inventory.lpo.index', compact(
            'lpos',
            'clients',
            'suppliers',
            'dateFrom',
            'dateTo',
            'supplier',
            'status',
            'lpoNumber'
        ));
    }
    public function lpoCreate()
    {
        $suppliers = Supplier::orderBy('supplier_name')->get();
        $items =InventoryItem::orderBy('item_name')->get();

        return view('inventory::inventory.lpo.create', compact('suppliers', 'items'));
    }
    public function lpoStore(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'supplier' => 'required',
            'items' => 'required|array|min:1',
            'items.*.value' => 'required',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Determine supplier ID and name
            $supplierData = $this->parseSupplierData($request);

            // Create LPO
            $lpo = LocalPurchaseOrder::create([
                'id' => (new CustomIds())->generateId(),
                'lpo_number' => LocalPurchaseOrder::generateLPONumber(),
                'date' => $request->date,
                'supplier_id' => $supplierData['id'],
                'notes' => $request->notes,
                'status' => 'draft',
                'created_by' => auth()->id(),
                'subtotal' => $request->subtotal,
                'vat_amount' => $request->vat_amount,
                'total_amount' => $request->total_amount,
            ]);

            // Create LPO items
            foreach ($request->items as $index => $itemData) {
                $item = InventoryItem::find($itemData['value']);

                if (!$item) {
                    throw new \Exception("Item not found: {$itemData['value']}");
                }

                LpoItem::create([
                    'id' => (new CustomIds())->generateId(),
                    'lpo_id' => $lpo->id,
                    'item_id' => $item->id,
                    'item_name' => $itemData['name'],
                    'unit' => $itemData['unit'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'is_vatable' => $itemData['is_vatable'] ?? false,
                    'vat_rate' => 16.00,
                    'line_number' => $index + 1,
                ]);
            }

            // Recalculate totals
            $lpo->calculateTotals()->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Local Purchase Order created successfully',
                'lpo_id' => $lpo->id,
                'lpo_number' => $lpo->lpo_number,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create LPO: ' . $e->getMessage()
            ], 500);
        }
    }
    public function lpoShow($id)
    {
        $lpo = LocalPurchaseOrder::with([
            'supplier',
            'items.item',
            'statusHistory.changedBy',
            'createdBy',
            'approvedBy'
        ])->findOrFail($id);

        return view('inventory::inventory.lpo.showLpo', compact('lpo'));
    }
    public function editLpo($id)
    {
        $lpo = LocalPurchaseOrder::with('items')->findOrFail($id);
        if (!$lpo->canEdit()) {
            return redirect()->route('lpos.view')
                ->with('error', 'This LPO cannot be edited in its current status');
        }
        $suppliers = Supplier::orderBy('supplier_name')->get();
        $items = InventoryItem::orderBy('item_name')->get();
        return view('inventory::inventory.lpo.create', compact('lpo', 'suppliers', 'items'));
    }
    public function updateLpo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lpo_id' => 'required|exists:local_purchase_orders,id',
            'date' => 'required|date',
            'supplier' => 'required',
            'items' => 'required|array|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $lpo = LocalPurchaseOrder::findOrFail($request->lpo_id);

            if (!$lpo->canEdit()) {
                throw new \Exception('This LPO cannot be edited in its current status');
            }

            // Determine supplier ID and name
            $supplierData = $this->parseSupplierData($request);

            // Update LPO
            $lpo->update([
                'date' => $request->date,
                'supplier_id' => $supplierData['id'],
                'notes' => $request->notes,
                'updated_by' => auth()->id(),
                'vat_amount' => $request->vat_amount,
                'subtotal' => $request->subtotal,
                'total_amount' => $request->total_amount,
            ]);

            // Delete existing items
            $lpo->items()->delete();

            // Create new items
            foreach ($request->items as $index => $itemData) {
                $item = InventoryItem::find($itemData['value']);

                if (!$item) {
                    throw new \Exception("Item not found: {$itemData['value']}");
                }
                LpoItem::create([
                    'id' => (new CustomIds())->generateId(),
                    'lpo_id' => $lpo->id,
                    'item_id' => $item->id,
                    'item_name' => $itemData['name'],
                    'unit' => $itemData['unit'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'is_vatable' => $itemData['is_vatable'] ?? false,
                    'vat_rate' => 16.00,
                    'line_number' => $index + 1,
                ]);
            }

            // Recalculate totals
            $lpo->calculateTotals()->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Local Purchase Order updated successfully',
                'lpo_id' => $lpo->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update LPO: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Remove the specified LPO
     */
    public function destroyLpo($id)
    {
        try {
            $lpo = LocalPurchaseOrder::findOrFail($id);

            if (!$lpo->canDelete()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This LPO cannot be deleted in its current status'
                ], 403);
            }

            $lpo->delete();

            return response()->json([
                'success' => true,
                'message' => 'Local Purchase Order deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete LPO: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Update LPO status
     */
    public function updateLpoStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:draft,pending,approved,rejected,completed,cancelled',
            'remarks' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $lpo = LocalPurchaseOrder::findOrFail($id);

            $lpo->updateStatus(
                $request->status,
                auth()->id(),
                $request->remarks
            );

            return response()->json([
                'success' => true,
                'message' => "LPO status updated to {$request->status}",
                'status' => $lpo->status
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Parse supplier data from request
     */
    private function parseSupplierData(Request $request)
    {
        $supplierId = null;
        $supplierName = null;

        if ($request->supplier === 'other') {
            $supplierName = trim($request->supplier_name);
            $supplier = Supplier::firstOrCreate(
                ['supplier_name' => $supplierName],
                ['id' => (new CustomIds())->generateId()]
            );
            $supplierId = $supplier->id;

        } else {

            $supplierId = $request->supplier;
            $supplier = Supplier::find($supplierId);

            if (!$supplier) {
                throw new \Exception('Invalid supplier selected.');
            }

            $supplierName = $supplier->supplier_name;
        }

        return [
            'id'   => $supplierId,
            'name' => $supplierName,
        ];

    }
    /**
     * Export LPO to PDF
     */
    public function exportLpoPdf($id)
    {
        $lpo = LocalPurchaseOrder::with([
            'supplier',
            'items.uom',
            'createdBy',
            'approvedBy'
        ])->findOrFail($id);

        $html = View::make('inventory::downloads.lpo', compact('lpo'))->render();

        // Initialize mPDF with settings
        $mpdf = new Mpdf([
            'tempDir' => storage_path('app/mpdf_temp'),
            'mode'        => 'utf-8',
            'format'      => 'A4-P', // Landscape
            'orientation' => 'P',
            'margin_top'    => 5,
            'margin_bottom' => 5,
            'margin_left'   => 10,
            'margin_right'  => 10,
//            'setAutoTopMargin' => 'stretch',
            'setAutoBottomMargin' => 'stretch',
        ]);

        // Write HTML content
        $mpdf->WriteHTML($html);

        // Generate PDF filename
        $pdfFileName = 'PURCHASE ORDER '.str_replace('/', '', $lpo->purchase_order_number).'.pdf';

        // Output PDF as downloadable file
        return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
        ]);
    }
    /**
     * Get LPO summary/statistics
     */
    public function statistics(Request $request)
    {
        $startDate = $request->start_date ?? now()->startOfMonth();
        $endDate = $request->end_date ?? now()->endOfMonth();

        $stats = [
            'total_lpos' => LocalPurchaseOrder::dateRange($startDate, $endDate)->count(),
            'total_amount' => LocalPurchaseOrder::dateRange($startDate, $endDate)->sum('total_amount'),
            'by_status' => LocalPurchaseOrder::dateRange($startDate, $endDate)
                ->select('status', DB::raw('count(*) as count'), DB::raw('sum(total_amount) as amount'))
                ->groupBy('status')
                ->get(),
            'top_suppliers' => LocalPurchaseOrder::dateRange($startDate, $endDate)
                ->select('supplier_id', DB::raw('count(*) as count'), DB::raw('sum(total_amount) as amount'))
                ->whereNotNull('supplier_id')
                ->groupBy('supplier_id')
                ->orderBy('amount', 'desc')
                ->limit(10)
                ->with('supplier')
                ->get(),
        ];

        return response()->json($stats);
    }
    public function unifiedIndex(Request $request)
    {
        // Get all unique clients from both tables
        $requisitionClients = Requisition::join('clients', 'requisitions.client_id', '=', 'clients.client_id')
            ->select('clients.client_id', 'clients.client_name')
            ->groupBy('clients.client_id', 'clients.client_name');

        $releaseClients = Release::join('clients', 'releases.client_id', '=', 'clients.client_id')
            ->select('clients.client_id', 'clients.client_name')
            ->groupBy('clients.client_id', 'clients.client_name');

        $clients = $requisitionClients->union($releaseClients)->get();

        $transactionNumber = $request->transaction_number;
        $dateFrom = $request->dateFrom;
        $dateTo = $request->dateTo;
        $client = $request->client;
        $transactionType = $request->transaction_type; // 'all', 'requisition', or 'release'

        // Build Requisition Query
        $requisitionQuery = Requisition::with(['client', 'items', 'user', 'warehouse'])
            ->select(
                'id',
                'requisition_number as transaction_number',
                'requisition_date as transaction_date',
                'client_id',
                'si_number',
                'status',
                'created_at',
                'updated_at',
                \DB::raw("'requisition' as transaction_type"),
                'requisitions.user_id',
                'requisitions.warehouse_id'
            );

        if ($client) {
            $requisitionQuery->where('client_id', $client);
        }

        if ($transactionNumber) {
            $requisitionQuery->where('requisition_number', 'LIKE', "%{$transactionNumber}%");
        }

        if ($dateFrom && $dateTo) {
            $requisitionQuery->whereBetween('requisition_date', [$dateFrom, $dateTo]);
        } elseif ($dateFrom) {
            $requisitionQuery->where('requisition_date', '>=', $dateFrom);
        } elseif ($dateTo) {
            $requisitionQuery->where('requisition_date', '<=', $dateTo);
        }

        // Build Release Query
        $releaseQuery = Release::with(['client', 'items', 'user'])
            ->select(
                'id',
                'release_number as transaction_number',
                'release_date as transaction_date',
                'client_id',
                'released_to',
                'status',
                'created_at',
                'updated_at',
                \DB::raw("'release' as transaction_type")
            );

        if ($client) {
            $releaseQuery->where('client_id', $client);
        }

        if ($transactionNumber) {
            $releaseQuery->where('release_number', 'LIKE', "%{$transactionNumber}%");
        }

        if ($dateFrom && $dateTo) {
            $releaseQuery->whereBetween('release_date', [$dateFrom, $dateTo]);
        } elseif ($dateFrom) {
            $releaseQuery->where('release_date', '>=', $dateFrom);
        } elseif ($dateTo) {
            $releaseQuery->where('release_date', '<=', $dateTo);
        }

        // Combine based on filter
        if ($transactionType === 'requisition') {
            $transactions = $requisitionQuery->get();
        } elseif ($transactionType === 'release') {
            $transactions = $releaseQuery->get();
        } else {
            // Merge both and sort by date
            $requisitions = $requisitionQuery->get();
            $releases = $releaseQuery->get();
            $transactions = $requisitions->merge($releases)->sortByDesc('transaction_date')->values();
        }

        $type = 'unified';

        if ($request->has('export')) {
            return Excel::download(
                new UnifiedInventoryTransactionExport($transactions),
                'transactions_' . date('Y-m-d_His') . '.xlsx'
            );
        }

        return view('inventory::inventory.utilization', compact(
            'transactions',
            'type',
            'clients',
            'dateFrom',
            'dateTo',
            'client',
            'transactionNumber',
            'transactionType'
        ));
    }
    public function itemMovements(Request $request, $clientId, $itemId)
    {
        $client = Client::findOrFail($clientId);
        $item = InventoryItem::findOrFail($itemId);

        // Get filters
        $dateFrom = $request->dateFrom;
        $dateTo = $request->dateTo;
        $transactionType = $request->transaction_type;

        // Build the movements query
        $query = DB::table('inventory_movements')
            ->where('client_id', $clientId)
            ->where('item_id', $itemId)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($dateFrom && $dateTo) {
            $query->whereBetween('transaction_date', [$dateFrom, $dateTo]);
        } elseif ($dateFrom) {
            $query->where('transaction_date', '>=', $dateFrom);
        } elseif ($dateTo) {
            $query->where('transaction_date', '<=', $dateTo);
        }

        if ($transactionType) {
            $query->where('transaction_type', $transactionType);
        }

        $movements = $query->get();

        // Calculate running balance
        $runningBalance = 0;
        $movementsWithBalance = $movements->reverse()->map(function($movement) use (&$runningBalance) {
            $runningBalance += $movement->quantity_in - $movement->quantity_out;
            $movement->running_balance = $runningBalance;
            return $movement;
        })->reverse();

        // Get summary statistics
        $summary = [
            'total_received' => $movements->sum('quantity_in'),
            'total_issued' => $movements->sum('quantity_out'),
            'current_balance' => $runningBalance,
            'transactions_count' => $movements->count()
        ];

        // Get current stock balance from inventory table
        $currentStock = DB::table('inventory_stock_balances')
            ->where('client_id', $clientId)
            ->where('item_id', $itemId)
            ->first();

        if ($request->has('export')) {
            return Excel::download(
                new ItemMovementsExport($movementsWithBalance, $client, $item, $summary),
                'item_movements_' . $item->item_name . '_' . date('Y-m-d_His') . '.xlsx'
            );
        }

        return view('inventory::inventory.item_movements', compact(
            'client',
            'item',
            'movements',
            'movementsWithBalance',
            'summary',
            'currentStock',
            'dateFrom',
            'dateTo',
            'transactionType'
        ));
    }
    public function showPurchaseOrder($id)
    {
        $lpo = PurchaseOrder::with(['items.uom', 'client', 'supplier', 'user'])
            ->findOrFail($id);
        return view('inventory::inventory.ins.showLpo', compact('lpo'));
    }

    public function suppliers()
    {
        $suppliers = Supplier::orderBy('created_at', 'desc')->get();
        return view('inventory::inventory.suppliers', compact('suppliers'));
    }

    public function storeSupplier(Request $request)
    {
        $validated = $request->validate([
            'supplier_name' => 'required|string|max:255',
            'po_box' => 'nullable|string|max:100',
            'street' => 'nullable|string|max:255',
            'town' => 'nullable|string|max:100',
            'phone_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
        ]);

        try {
            $validated['id'] = (new CustomIds())->generateId();
            Supplier::create($validated);

            return redirect()->route('suppliers.view')
                ->with('success', 'Supplier created successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create supplier: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function updateSupplier(Request $request, $id)
    {
        $supplier = Supplier::findOrFail($id);

        $validated = $request->validate([
            'supplier_name' => 'required|string|max:255',
            'po_box' => 'nullable|string|max:100',
            'street' => 'nullable|string|max:255',
            'town' => 'nullable|string|max:100',
            'phone_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
        ]);

        try {
            $supplier->update($validated);

            return redirect()->route('suppliers.view')
                ->with('success', 'Supplier updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update supplier: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroySupplier($id)
    {
        try {
            $supplier = Supplier::findOrFail($id);

            // Check if supplier has related purchase orders
            if ($supplier->purchaseOrders()->count() > 0 || $supplier->localPurchaseOrders()->count() > 0) {
                return redirect()->back()
                    ->with('error', 'Cannot delete supplier with existing purchase orders.');
            }

            $supplier->delete();

            return redirect()->route('suppliers.view')
                ->with('success', 'Supplier deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete supplier: ' . $e->getMessage());
        }
    }
}
