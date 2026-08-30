<?php


namespace App\Services;

use Modules\Inventory\Entities\Release;
use Modules\Inventory\Entities\ReleaseItem;
use Modules\Inventory\Entities\Requisition;
use Modules\Inventory\Entities\RequisitionItem;
use Modules\Inventory\Entities\TransferIn;
use Modules\Inventory\Entities\TransferInItem;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Entities\TransferOut;
use Modules\Inventory\Entities\TransferOutItem;

class InventoryService
{
    /**
     * Get current stock balance for client and item
     */
    public function getStockBalance($clientId, $itemId)
    {
        $balance = DB::table('inventory_stock_balances')
            ->where('client_id', $clientId)
            ->where('item_id', $itemId)
            ->first();

        return $balance ? (int)$balance->current_balance : 0;
    }

    /**
     * Get all stock for a client
     */
    public function getClientStockBalances($clientId)
    {
        return DB::table('inventory_stock_balances')
            ->where('client_id', $clientId)
            ->orderBy('item_name')
            ->get();
    }

    /**
     * Get items with available stock for a client
     */
    public function getAvailableItems($clientId)
    {
        return DB::table('inventory_stock_balances')
            ->where('client_id', $clientId)
            ->where('current_balance', '>', 0)
            ->orderBy('item_name')
            ->get();
    }

    /**
     * Get all inventory movements for a client
     */
    public function getClientMovements($clientId, $startDate = null, $endDate = null)
    {
        $query = DB::table('inventory_movements')
            ->where('client_id', $clientId)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($startDate) {
            $query->where('transaction_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('transaction_date', '<=', $endDate);
        }

        return $query->get();
    }

    /**
     * Validate if sufficient stock exists
     */
    public function validateStock($clientId, $items)
    {
        $errors = [];

        foreach ($items as $item) {
            $balance = $this->getStockBalance($clientId, $item['item_id']);

            if ($balance < $item['quantity']) {
                $itemInfo = DB::table('inventory_items')->where('id', $item['item_id'])->first();
                $itemName = $itemInfo ? $itemInfo->item_name : 'Unknown';

                $errors[] = "Insufficient stock for {$itemName}. Available: {$balance}, Requested: {$item['quantity']}";
            }
        }

        if (!empty($errors)) {
            throw new \Exception(implode('; ', $errors));
        }

        return true;
    }

    /**
     * Create Transfer In
     */
    public function createTransferIn($data)
    {
        DB::beginTransaction();

        try {
            $transferIn = TransferIn::create([
                'id' => (new CustomIds())->generateId(),
                'transfer_in_number' => TransferIn::generateNumber(),
                'transfer_date' => $data['transfer_date'],
                'client_id' => $data['client_id'],
                'recipient_id' => $data['recipient_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
                'user_id' => auth()->user()->user_id,
            ]);

            foreach ($data['items'] as $item) {
                TransferInItem::create([
                    'id' => (new CustomIds())->generateId(),
                    'transfer_in_id' => $transferIn->id,
                    'item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                ]);
            }

            DB::commit();
            return ['success' => true, 'record' => $transferIn];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Transfer In creation failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Create Transfer Out
     */
    public function createTransferOut($data)
    {
        DB::beginTransaction();

        try {
            // Validate stock before creating
            $this->validateStock($data['client_id'], $data['items']);

            $transferOut = TransferOut::create([
                'id' => (new CustomIds())->generateId(),
                'transfer_out_number' => TransferOut::generateNumber(),
                'transfer_date' => $data['transfer_date'],
                'client_id' => $data['client_id'],
                'recipient_id' => $data['recipient_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
                'user_id' => auth()->user()->user_id,
            ]);

            foreach ($data['items'] as $item) {
                TransferOutItem::create([
                    'id' => (new CustomIds())->generateId(),
                    'transfer_out_id' => $transferOut['id'],
                    'item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                ]);
            }

            DB::commit();
            return ['success' => true, 'record' => $transferOut];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Transfer Out creation failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Create Release
     */
    public function createRelease($data)
    {
        DB::beginTransaction();

        try {
            // Validate stock
            $this->validateStock($data['client_id'], $data['items']);

            $release = Release::create([
                'id' => (new CustomIds())->generateId(),
                'release_number' => Release::generateNumber(),
                'release_date' => $data['release_date'],
                'client_id' => $data['client_id'],
                'released_to' => $data['released_to'],
                'driver_name' => $data['driver_name'],
                'phone_number' => $data['phone_number'],
                'registration_number' => $data['registration_number'],
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
                'user_id' => auth()->user()->user_id,
            ]);

            foreach ($data['items'] as $item) {
                ReleaseItem::create([
                    'id' => (new CustomIds())->generateId(),
                    'release_id' => $release->id,
                    'item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                ]);
            }

            DB::commit();
            return ['success' => true, 'record' => $release];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Release creation failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Create Requisition
     */
    public function createRequisition($data)
    {
        DB::beginTransaction();

        try {
            $requisition = Requisition::create([
                'id' => (new CustomIds())->generateId(),
                'requisition_number' => Requisition::generateNumber(),
                'requisition_date' => $data['requisition_date'],
                'client_id' => $data['client_id'],
                'si_number' => $data['si_number'],
                'purpose' => $data['purpose'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
                'user_id' => auth()->user()->user_id,
                'driver_name' => $data['driver_name'],
                'phone_number' => $data['phone_number'],
                'registration_number' => $data['registration_number'],
                'warehouse_id' => $data['warehouse_id'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                RequisitionItem::create([
                    'id' => (new CustomIds())->generateId(),
                    'requisition_id' => $requisition->id,
                    'item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                ]);
            }

            DB::commit();
            return ['success' => true, 'record' => $requisition];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Requisition creation failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Approve/Complete transaction
     */
    public function approve($type, $id)
    {
        $modelMap = [
            'transfer_out' => TransferOut::class,
            'release' => Release::class,
            'requisition' => Requisition::class,
        ];

        $model = $modelMap[$type] ?? null;
        if (!$model) {
            return ['success' => false, 'message' => 'Invalid transaction type'];
        }

        try {
            $record = $model::with('items')->findOrFail($id);

            // For requisition, validate stock before fulfilling
            if ($type === 'requisition') {
                $items = $record->items->map(function ($item) {
                    return [
                        'item_id' => $item->item_id,
                        'quantity' => $item->quantity
                    ];
                })->toArray();

                $this->validateStock($record->client_id, $items);

                $record->update([
                    'status' => 'fulfilled',
                    'approved_by' => auth()->user()->user_id ?? null
                ]);
            } else {
                // For transfer_out and release, validate stock
                if (in_array($type, ['transfer_out', 'release'])) {
                    $items = $record->items->map(function ($item) {
                        return [
                            'item_id' => $item->item_id,
                            'quantity' => $item->quantity
                        ];
                    })->toArray();

                    $this->validateStock($record->client_id, $items);
                }

                $record->update([
                    'status' => 'completed',
                    'approved_by' => auth()->user()->user_id ?? null
                ]);
            }

            return ['success' => true, 'message' => 'Transaction approved successfully'];
        } catch (\Exception $e) {
            \Log::error("Approval failed for {$type}: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Cancel transaction
     */
    public function cancel($type, $id)
    {
        $modelMap = [
            'transfer_out' => TransferOut::class,
            'release' => Release::class,
            'requisition' => Requisition::class,
        ];

        $model = $modelMap[$type] ?? null;
        if (!$model) {
            return ['success' => false, 'message' => 'Invalid transaction type'];
        }

        try {
            $record = $model::findOrFail($id);

            if ($record->status !== 'pending') {
                return ['success' => false, 'message' => 'Only pending transactions can be cancelled'];
            }

            $record->update(['status' => 'cancelled']);

            return ['success' => true, 'message' => 'Transaction cancelled successfully'];
        } catch (\Exception $e) {
            \Log::error("Cancellation failed for {$type}: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
