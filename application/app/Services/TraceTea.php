<?php

namespace App\Services;

use App\Models\BlendTea;
use App\Models\DeliveryOrder;
use App\Models\ExternalTransfer;
use App\Models\Shipment;
use App\Models\Transfers;
use Illuminate\Support\Facades\DB;
use Modules\Clerk\Entities\Auction;
use Modules\Clerk\Entities\Rebagging;

class TraceTea
{
    public function traceDeliveryOrder($deliveryOrderId)
    {
        $deliveryOrder = DeliveryOrder::join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->join('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
            ->join('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
            ->leftJoin('warehouses', 'warehouses.warehouse_id', '=', 'delivery_orders.warehouse_id')
            ->leftJoin('sub_warehouses', 'sub_warehouses.warehouse_id', '=', 'warehouses.warehouse_id')
            ->leftJoin('brokers', 'brokers.broker_id', '=', 'delivery_orders.broker_id')
            ->leftJoin('user_infos', 'user_infos.user_id', '=', 'delivery_orders.created_by')
            ->leftJoin('loading_instructions', function ($join) {
                $join->on('loading_instructions.delivery_id', '=', 'delivery_orders.delivery_id')
                    ->whereNull('loading_instructions.deleted_at');
            })
            ->leftJoin('foreign_teas', function ($join) {
                $join->on('foreign_teas.delivery_order_id', '=', 'delivery_orders.delivery_id')
                    ->whereNull('foreign_teas.deleted_at');
            })
            ->select('delivery_orders.delivery_id', 'client_name', DB::raw("CASE WHEN delivery_orders.delivery_type = 1 THEN 'DO Entry' ELSE 'Direct Delivery' END AS delivery_type"), DB::raw("CASE WHEN delivery_orders.tea_id = 1 THEN 'Auction Tea' WHEN delivery_orders.tea_id = 2 THEN 'Blend Tea' WHEN delivery_orders.tea_id = 3 THEN 'Factory Tea' ELSE 'Private Tea' END AS tea_type"), 'order_number', 'garden_name', 'grade_name', 'packet', 'weight', DB::raw("CASE WHEN delivery_orders.package = 1 THEN 'Poly Bag' ELSE 'Paper Sack' END AS package"), 'warehouse_name', 'sub_warehouse_name', DB::raw("CASE WHEN delivery_orders.locality = 1 THEN 'Island' WHEN delivery_orders.locality = 2 THEN 'Changamwe' WHEN delivery_orders.locality = 3 THEN 'Jomvu' WHEN delivery_orders.locality = 4 THEN 'Bonje' WHEN delivery_orders.locality = 5 THEN 'Miritini' ELSE '' END AS locality"), 'broker_name', 'sale_number', 'invoice_number', 'lot_number', 'sale_date', 'prompt_date', DB::raw("CASE WHEN delivery_orders.status = 2 THEN 'Collected' ELSE 'Under Collection' END AS status"), 'delivery_orders.status as do_status', DB::raw("CONCAT(surname, ' ', first_name) AS created_by"), 'delivery_orders.created_at', 'production_date', 'expiry_date', 'height', 'collection', 'tea_type', 'received', 'validated')
            ->where('delivery_orders.delivery_id', $deliveryOrderId)
            ->whereNull('delivery_orders.deleted_at')
            ->first();

        $tciDetails = DeliveryOrder::join('loading_instructions', 'loading_instructions.delivery_id', '=', 'delivery_orders.delivery_id')
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'loading_instructions.transporter_id')
            ->leftJoin('drivers', 'drivers.driver_id', '=', 'loading_instructions.driver_id')
            ->leftJoin('user_infos', 'user_infos.user_id', '=', 'loading_instructions.created_by')
            ->leftJoin('stock_ins', function ($join) {
                $join->on('loading_instructions.delivery_id', '=', 'stock_ins.delivery_id');
            })
            ->leftJoin('delivery_notes', function ($join) {
                $join->on('delivery_notes.delivery_number', '=', 'stock_ins.delivery_number');
            })
            ->select('loading_number', 'transporter_name', 'driver_name', 'phone', 'drivers.id_number', DB::raw("CASE WHEN loading_instructions.status = 2 THEN 'Collected' ELSE 'Under Collection' END AS status"), DB::raw("CONCAT(surname, ' ', first_name) AS created_by"), 'loading_instructions.created_at', 'collection', 'stock_ins.delivery_number', 'delivery_notes.path')
            ->where('delivery_orders.delivery_id', $deliveryOrderId)
            ->whereNull('delivery_orders.deleted_at')
            ->whereNull('loading_instructions.deleted_at')
            ->first();

        $directDelivery = DeliveryOrder::leftJoin('stock_ins', function ($join) {
                $join->on('delivery_orders.delivery_id', '=', 'stock_ins.delivery_id');
            })
            ->leftJoin('delivery_notes', function ($join) {
                $join->on('delivery_notes.delivery_number', '=', 'stock_ins.delivery_number');
            })
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'stock_ins.transporter_id')
            ->leftJoin('drivers', 'drivers.driver_id', '=', 'stock_ins.driver_id')
            ->leftJoin('user_infos', 'user_infos.user_id', '=', 'stock_ins.user_id')
            ->select('transporter_name', 'driver_name', 'phone', 'drivers.id_number', DB::raw("CASE WHEN delivery_orders.status = 2 THEN 'Collected' ELSE 'Under Collection' END AS status"), DB::raw("CONCAT(surname, ' ', first_name) AS created_by"), 'delivery_orders.created_at', 'stock_ins.delivery_number', 'delivery_notes.path')
            ->where('delivery_orders.delivery_id', $deliveryOrderId)
            ->where(['delivery_orders.delivery_type' => 2])
            ->whereNull('delivery_orders.deleted_at')
            ->first();

        $blendUsage = DeliveryOrder::join('blend_teas', 'blend_teas.delivery_id', '=', 'delivery_orders.delivery_id')
            ->join('blend_sheets', 'blend_sheets.blend_id', '=', 'blend_teas.blend_id')
            ->leftJoin('user_infos', 'user_infos.user_id', '=', 'blend_sheets.user_id')
            ->select('blend_number', 'blended_packages', 'blended_weight', 'blend_sheets.created_at', 'blend_date', 'blend_shipped', DB::raw("CASE WHEN blend_sheets.status = 0 THEN 'Blend Sheet Created' WHEN blend_sheets.status = 1 THEN 'Teas Updated' WHEN blend_sheets.status = 2 THEN 'Outturn Report Updated' WHEN blend_sheets.status = 3 THEN 'Pending Approval' ELSE 'Blend Shipped' END AS status"), DB::raw("CONCAT(surname, ' ', first_name) AS created_by"))
            ->where('delivery_orders.delivery_id', $deliveryOrderId)
            ->whereNull('blend_teas.deleted_at')
            ->whereNull('blend_sheets.deleted_at')
            ->get();

        $straightLine = DeliveryOrder::join('shipments', 'shipments.delivery_id', '=', 'delivery_orders.delivery_id')
            ->join('shipping_instructions', 'shipping_instructions.shipping_id', '=', 'shipments.shipping_id')
            ->leftJoin('user_infos', 'user_infos.user_id', '=', 'shipping_instructions.user_id')
            ->select('shipping_number', DB::raw("CAST(shipped_packages AS DOUBLE) as shipped_packages"), 'shipped_weight', 'shipping_instructions.created_at', 'ship_date', DB::raw("CASE WHEN shipping_instructions.status = 0 THEN 'SI Created' WHEN shipping_instructions.status = 1 THEN 'Teas Updated' WHEN shipping_instructions.status = 2 THEN 'SI Updated' WHEN shipping_instructions.status = 3 THEN 'Pending Approval' ELSE 'SI Shipped' END AS status"), DB::raw("CONCAT(surname, ' ', first_name) AS created_by"))
            ->where('delivery_orders.delivery_id', $deliveryOrderId)
            ->whereNull('shipping_instructions.deleted_at')
            ->whereNull('shipments.deleted_at')
            ->get();

        $internalTransfer = DeliveryOrder::join('transfers', 'transfers.delivery_id', '=', 'delivery_orders.delivery_id')
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'transfers.transporter_id')
            ->leftJoin('drivers', 'drivers.driver_id', '=', 'transfers.driver_id')
            ->join('stations', 'stations.station_id', '=', 'transfers.station_id')
            ->join('stations as destination', 'destination.station_id', '=', 'transfers.destination')
            ->leftJoin('user_infos', 'user_infos.user_id', '=', 'transfers.created_by')
            ->select('delivery_number', 'requested_palettes', 'requested_weight', DB::raw("CASE WHEN transfers.status = null THEN 'Transfer Created' WHEN transfers.status = 0 THEN 'Transfer Initiated' WHEN transfers.status = 1 THEN 'Transfer Approved' WHEN transfers.status = 2 THEN 'Released' ELSE 'Transfer Received' END AS status"), 'stations.station_name', 'destination.station_name as destination_name', DB::raw("CONCAT(surname, ' ', first_name) AS created_by"), 'transfers.created_at', 'transporter_name', 'drivers.driver_name', 'drivers.id_number', 'phone')
            ->where('delivery_orders.delivery_id', $deliveryOrderId)
            ->whereNull('transfers.deleted_at')
            ->get();

        $externalTransfer = DeliveryOrder::join('external_transfers', 'external_transfers.delivery_id', '=', 'delivery_orders.delivery_id')
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'external_transfers.transporter_id')
            ->leftJoin('drivers', 'drivers.driver_id', '=', 'external_transfers.driver_id')
            ->join('stock_ins', 'stock_ins.stock_id', '=', 'external_transfers.stock_id')
            ->join('stations', 'stations.station_id', '=', 'stock_ins.station_id')
            ->join('warehouses', 'warehouses.warehouse_id', '=', 'external_transfers.warehouse_id')
            ->leftJoin('user_infos', 'user_infos.user_id', '=', 'external_transfers.created_by')
            ->select('external_transfers.delivery_number', 'transferred_palettes', 'transferred_weight', DB::raw("CASE WHEN external_transfers.status = null THEN 'Transfer Created' WHEN external_transfers.status = 0 THEN 'Transfer Initiated' WHEN external_transfers.status = 1 THEN 'Transfer Approved' WHEN external_transfers.status = 2 THEN 'Released' ELSE 'Transfer Received' END AS status"), 'stations.station_name', 'warehouses.warehouse_name', DB::raw("CONCAT(surname, ' ', first_name) AS created_by"), 'external_transfers.created_at', 'transporter_name', 'drivers.driver_name', 'drivers.id_number', 'drivers.phone')
            ->where('delivery_orders.delivery_id', $deliveryOrderId)
            ->whereNull('external_transfers.deleted_at')
            ->get();

        $stockDetails = DB::table('currentstock')
            ->leftJoin('user_infos', 'user_infos.user_id', '=', 'currentstock.received_by')
            ->where('delivery_id', $deliveryOrderId)
            ->select('delivery_number', 'stocked_at', 'bay_name', 'current_stock', 'current_weight', DB::raw("CONCAT(surname, ' ', first_name) AS created_by"), 'date_received')
            ->orderBy('date_received', 'DESC')
            ->get();

        $samples = DeliveryOrder::join('tea_samples', 'tea_samples.delivery_id', '=', 'delivery_orders.delivery_id')
            ->leftJoin('user_infos', 'user_infos.user_id', '=', 'tea_samples.user_id')
            ->select(
                'sample_palletes',
                'sample_weight',
                'tea_samples.created_at',
                DB::raw("CONCAT(surname, ' ', first_name) as user_name"),
                DB::raw("CASE
                    WHEN tea_samples.type = 1 THEN 'Withdrawn Sample'
                    WHEN tea_samples.type = 2 THEN 'Damaged Bags'
                    ELSE 'Weight Loss'
                END as type")
            )
            ->where('delivery_orders.delivery_id', $deliveryOrderId)
            ->whereNull('tea_samples.deleted_at')
            ->orderBy('tea_samples.created_at', 'DESC')
            ->get();

        $rebaggings = Rebagging::leftJoin('blend_sheets', 'blend_sheets.blend_id', '=', 'rebaggings.shipping_id')
            ->join('stock_ins', 'stock_ins.stock_id', '=', 'rebaggings.stock_id')
            ->join('delivery_orders', 'delivery_orders.delivery_id', '=', 'stock_ins.delivery_id')
            ->leftJoin('user_infos', 'user_infos.user_id', '=', 'rebaggings.user_id')
            ->leftJoin('shipping_instructions', 'shipping_instructions.shipping_id', '=', 'rebaggings.shipping_id')
            ->leftJoin('clients', 'clients.client_id', '=', 'blend_sheets.client_id')
            ->leftJoin('clients as client', 'client.client_id', '=', 'shipping_instructions.client_id')
            ->select(
                DB::raw('COALESCE(shipping_instructions.shipping_id, blend_sheets.blend_id) as shippingId'),
                DB::raw("CASE WHEN blend_sheets.blend_id IS NULL THEN 'Straight Line' ELSE 'Blend Job' END AS type"),
                DB::raw('COALESCE(shipping_instructions.shipping_number, blend_sheets.blend_number) as siNumber'),
                DB::raw('COALESCE(client.client_name, clients.client_name) as clientName'),
                DB::raw("CONCAT(user_infos.surname,' ',user_infos.first_name) AS username"),
                DB::raw('COALESCE(shipping_instructions.status, blend_sheets.status) as status'),
                'rebaggings.packages', 'rebaggings.weight', 'rebaggings.created_at',
            )
            ->where('delivery_orders.delivery_id', $deliveryOrderId)
            ->orderBy('siNumber', 'DESC')
            ->orderBy('packages', 'DESC')
            ->get();

        $buyers = Auction::leftJoin('external_transfers', function ($join) {
                $join->on('external_transfers.delivery_id', '=', 'auctions.delivery_id');
            })
            ->leftJoin('delivery_orders', 'delivery_orders.delivery_id', '=', 'auctions.delivery_id')
            ->leftJoin('clients as buyer', 'buyer.client_id', '=', 'auctions.client_id')
            ->leftJoin('clients', 'clients.client_id', '=', 'external_transfers.buyer_id')
            ->select(
                DB::raw("CONCAT(COALESCE(clients.client_name, ''), COALESCE(buyer.client_name, '')) as client_name"), 
                'external_transfers.delivery_number', 
                'external_transfers.created_at', 
                'delivery_orders.packet', 
                'delivery_orders.unit_weight', 
                'delivery_orders.weight', 
                'auctions.sale', 'auctions.sale_date', 'auctions.prompt_date', 'auctions.release_date'
            )
            ->where('auctions.delivery_id', $deliveryOrderId)
            ->orderBy('external_transfers.created_at', 'desc')
            ->get();

        $teaDetails = collect([
            'internalTransfer' => $internalTransfer,
            'externalTransfer' => $externalTransfer,
            'stockDetails' => $stockDetails,
            'straightLine' => $straightLine,
            'blendUsage' => $blendUsage,
            'deliveryOrder' => $deliveryOrder,
            'tciDetails' => $tciDetails,
            'samples' => $samples,
            'rebaggings' => $rebaggings,
            'buyers' => $buyers,
            'directDelivery' => $directDelivery,
        ]);

        if (!$teaDetails) {
            return null; // Or handle the error as needed
        }
        return $teaDetails;

    }
    public function traceBlendBalance($deliveryOrderId)
    {

        $blendUsage = BlendTea::join('blend_sheets', 'blend_sheets.blend_id', '=', 'blend_teas.blend_id')
            ->leftJoin('user_infos', 'user_infos.user_id', '=', 'blend_sheets.user_id')
            ->select('blend_number', 'blended_packages', 'blended_weight', 'blend_sheets.created_at', 'blend_date', 'blend_shipped', DB::raw("CASE WHEN blend_sheets.status = 0 THEN 'Blend Sheet Created' WHEN blend_sheets.status = 1 THEN 'Teas Updated' WHEN blend_sheets.status = 2 THEN 'Outturn Report Updated' WHEN blend_sheets.status = 3 THEN 'Pending Approval' ELSE 'Blend Shipped' END AS status"), DB::raw("CONCAT(surname, ' ', first_name) AS created_by"))
            ->where('blend_teas.delivery_id', $deliveryOrderId)
            ->whereNull('blend_teas.deleted_at')
            ->whereNull('blend_sheets.deleted_at')
            ->get();

        $straightLine = Shipment::join('shipping_instructions', 'shipping_instructions.shipping_id', '=', 'shipments.shipping_id')
            ->leftJoin('user_infos', 'user_infos.user_id', '=', 'shipping_instructions.user_id')
            ->select('shipping_number', 'shipped_packages', 'shipped_weight', 'shipping_instructions.created_at', 'ship_date', DB::raw("CASE WHEN shipping_instructions.status = 0 THEN 'SI Created' WHEN shipping_instructions.status = 1 THEN 'Teas Updated' WHEN shipping_instructions.status = 2 THEN 'SI Updated' WHEN shipping_instructions.status = 3 THEN 'Pending Approval' ELSE 'SI Shipped' END AS status"), DB::raw("CONCAT(surname, ' ', first_name) AS created_by"))
            ->where('shipments.delivery_id', $deliveryOrderId)
            ->whereNull('shipments.deleted_at')
            ->whereNull('shipments.deleted_at')
            ->get();

        $internalTransfer = Transfers::leftJoin('transporters', 'transporters.transporter_id', '=', 'transfers.transporter_id')
            ->leftJoin('drivers', 'drivers.driver_id', '=', 'transfers.driver_id')
            ->join('stations', 'stations.station_id', '=', 'transfers.station_id')
            ->join('stations as destination', 'destination.station_id', '=', 'transfers.destination')
            ->leftJoin('user_infos', 'user_infos.user_id', '=', 'transfers.created_by')
            ->select('delivery_number', 'requested_palettes', 'requested_weight', DB::raw("CASE WHEN transfers.status = null THEN 'Transfer Created' WHEN transfers.status = 0 THEN 'Transfer Initiated' WHEN transfers.status = 1 THEN 'Transfer Approved' WHEN transfers.status = 2 THEN 'Released' ELSE 'Transfer Received' END AS status"), 'stations.station_name', 'destination.station_name as destination_name', DB::raw("CONCAT(surname, ' ', first_name) AS created_by"), 'transfers.created_at', 'transporter_name', 'drivers.driver_name', 'drivers.id_number', 'phone')
            ->where('transfers.delivery_id', $deliveryOrderId)
            ->whereNull('transfers.deleted_at')
            ->get();

        $externalTransfer = ExternalTransfer::leftJoin('transporters', 'transporters.transporter_id', '=', 'external_transfers.transporter_id')
            ->leftJoin('drivers', 'drivers.driver_id', '=', 'external_transfers.driver_id')
            ->join('stock_ins', 'stock_ins.stock_id', '=', 'external_transfers.stock_id')
            ->join('stations', 'stations.station_id', '=', 'stock_ins.station_id')
            ->join('warehouses', 'warehouses.warehouse_id', '=', 'external_transfers.warehouse_id')
            ->leftJoin('user_infos', 'user_infos.user_id', '=', 'external_transfers.created_by')
            ->select('external_transfers.delivery_number', 'transferred_palettes', 'transferred_weight', DB::raw("CASE WHEN external_transfers.status = null THEN 'Transfer Created' WHEN external_transfers.status = 0 THEN 'Transfer Initiated' WHEN external_transfers.status = 1 THEN 'Transfer Approved' WHEN external_transfers.status = 2 THEN 'Released' ELSE 'Transfer Received' END AS status"), 'stations.station_name', 'warehouses.warehouse_name', DB::raw("CONCAT(surname, ' ', first_name) AS created_by"), 'external_transfers.created_at', 'transporter_name', 'drivers.driver_name', 'drivers.id_number', 'drivers.phone')
            ->where('external_transfers.delivery_id', $deliveryOrderId)
            ->whereNull('external_transfers.deleted_at')
            ->get();

        $stockDetails = DB::table('blendBalances')
            ->where('blend_id', $deliveryOrderId)
            ->select('blend_id', 'station_name', 'current_packages', 'current_weight', 'blend_date', 'type')
            ->orderBy('blend_date', 'DESC')
            ->orderBy('type', 'ASC')
            ->whereNull('blendBalances.deleted_at')
            ->get();

        $rebaggings = Rebagging::leftJoin('blend_sheets', 'blend_sheets.blend_id', '=', 'rebaggings.shipping_id')
            ->join('blend_balances', 'blend_balances.blend_balance_id', '=', 'rebaggings.stock_id')
            ->leftJoin('user_infos', 'user_infos.user_id', '=', 'rebaggings.user_id')
            ->leftJoin('shipping_instructions', 'shipping_instructions.shipping_id', '=', 'rebaggings.shipping_id')
            ->leftJoin('clients', 'clients.client_id', '=', 'blend_sheets.client_id')
            ->leftJoin('clients as client', 'client.client_id', '=', 'shipping_instructions.client_id')
            ->select(
                DB::raw('COALESCE(shipping_instructions.shipping_id, blend_sheets.blend_id) as shippingId'),
                DB::raw("CASE WHEN blend_sheets.blend_id IS NULL THEN 'Straight Line' ELSE 'Blend Job' END AS type"),
                DB::raw('COALESCE(shipping_instructions.shipping_number, blend_sheets.blend_number) as siNumber'),
                DB::raw('COALESCE(client.client_name, clients.client_name) as clientName'),
                DB::raw("CONCAT(user_infos.surname,' ',user_infos.first_name) AS username"),
                DB::raw('COALESCE(shipping_instructions.status, blend_sheets.status) as status'),
                'rebaggings.packages', 'rebaggings.weight', 'rebaggings.created_at',
            )
            ->where('blend_balances.blend_id', $deliveryOrderId)
            ->orderBy('siNumber', 'DESC')
            ->orderBy('packages', 'DESC')
            ->get();

        $buyers = ExternalTransfer::join('clients', 'clients.client_id', '=', 'external_transfers.buyer_id')
            ->select('external_transfers.*', 'clients.client_name')
            ->where('external_transfers.delivery_id', $deliveryOrderId)
            ->get();

        $teaDetails = collect([
            'internalTransfer' => $internalTransfer,
            'externalTransfer' => $externalTransfer,
            'stockDetails' => $stockDetails,
            'straightLine' => $straightLine,
            'blendUsage' => $blendUsage,
            'rebaggings' => $rebaggings,
            'buyers' => $buyers,
        ]);

        if (!$teaDetails) {
            return null; // Or handle the error as needed
        }
        return $teaDetails;

    }
}
