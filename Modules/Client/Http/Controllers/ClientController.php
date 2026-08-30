<?php

namespace Modules\Client\Http\Controllers;

use App\Models\BlendSheet;
use App\Models\BlendTea;
use App\Models\ClearingAgent;
use App\Models\Client;
use App\Models\DeliveryOrder;
use App\Models\Driver;
use App\Models\ExternalTransfer;
use App\Models\LoadingInstruction;
use App\Models\Shipment;
use App\Models\ShippingInstruction;
use App\Models\Transfers;
use App\Models\Transporter;
use App\Models\Warehouse;
use App\Services\AppClass;
use App\Services\Log;
use App\Services\TraceTea;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Clerk\Entities\TeaSamples;

class ClientController extends Controller
{
    protected $logger, $traceService, $appClass, $clientId;
    public function __construct(Log $logger, TraceTea $traceService, AppClass $appClass)
    {
        $this->logger = $logger;
        $this->traceService = $traceService;
        $this->AppClass = $appClass;
        // Use a middleware closure to set the client ID after auth is available
        $this->middleware(function ($request, $next) {
            $this->clientId = auth()->id(); // or auth()->user()->user_id if needed
            return $next($request);
        });
    }
    public function traceTea($id)
    {
        $traceData = $this->traceService->traceDeliveryOrder($id);

        if (!$traceData) {
            return response()->json(['error' => 'Delivery order not found'], 404);
        }
        return view('client::DOS.traceTea')->with('teaDetails', $traceData);
    }
    public function traceBlendBalance($id)
    {
        $traceData = $this->traceService->traceBlendBalance($id);
        if (!$traceData) {
            return response()->json(['error' => 'Delivery order not found'], 404);
        }
        return view('client::DOS.traceBlendBalance')->with(['teaDetails' => $traceData, 'id' => $id]);
    }
    public function traceTeaByInvoice(Request $request)
    {
        $search = trim($request->input('invoice'));
        $query = str_replace(['%', '_'], ['\%', '\_'], $search);

        $teas = DB::table('currentstock')->leftJoin('delivery_orders', 'delivery_orders.delivery_id', '=', 'currentstock.delivery_id')
            ->where(function ($q) use ($query) {
                $q->where(DB::raw('TRIM(currentstock.lot_number)'), 'LIKE', "%$query%")
                    ->orWhere(DB::raw('TRIM(delivery_orders.invoice_number)'), 'LIKE', "%$query%")
                    ->orWhere(DB::raw('TRIM(currentstock.invoice_number)'), 'LIKE', "%$query%")
                    ->orWhere(DB::raw('TRIM(delivery_orders.order_number)'), 'LIKE', "%$query%")
                    ->orWhere(DB::raw('TRIM(client_name)'), 'LIKE', "%$query%")
                    ->orWhere(DB::raw('TRIM(currentstock.delivery_number)'), 'LIKE', "%$query%");
            })

            ->where(['currentstock.client_id' => $this->clientId])
            ->select('delivery_orders.delivery_id', 'delivery_orders.invoice_number', 'client_name', 'grade_name', 'garden_name', 'delivery_orders.lot_number', 'delivery_orders.order_number', 'delivery_orders.packet', 'delivery_orders.weight', DB::raw("SUM(current_stock) as current_stock"), DB::raw("SUM(current_weight) as current_weight"))
            ->groupBy('delivery_id', 'invoice_number', 'client_name', 'grade_name', 'garden_name', 'lot_number', 'order_number', 'packet', 'weight')
            ->orderBy('delivery_orders.invoice_number')
            ->orderBy('delivery_orders.created_at', 'desc')
            ->get();

        $straightLine = ShippingInstruction::join('clients', 'clients.client_id', '=', 'shipping_instructions.client_id')
            ->join('destinations', 'destinations.destination_id', '=', 'shipping_instructions.destination_id')
            ->join('stations', 'stations.station_id', '=', 'shipping_instructions.station_id')
            ->leftJoin('shipments', 'shipments.shipping_id', '=', 'shipping_instructions.shipping_id')
            ->select('shipping_instructions.shipping_id', 'client_name', 'shipping_instructions.created_at', 'shipping_instructions.status', 'station_name', 'shipping_number', 'container_number', 'vessel_name', 'port_name', DB::raw('SUM(shipments.shipped_packages) as total_packages'), DB::raw('SUM(CAST(REPLACE(REPLACE(shipments.shipped_weight, ",", ""), ".00", "") AS DECIMAL(10,2))) as total_weight'))
            ->where(function($q) use ($query) {
                $q->where('shipping_number', 'LIKE', "%$query%")
                    ->orWhere('client_name', 'LIKE', "%$query%")
                    ->orWhere('container_number', 'LIKE', "%$query%");
            })
            ->where(['shipping_instructions.client_id' => $this->clientId])
            ->groupBy('shipping_instructions.shipping_id', 'client_name', 'shipping_instructions.created_at', 'shipping_instructions.status', 'station_name', 'shipping_number', 'vessel_name', 'port_name', 'container_number')
            ->latest('shipping_instructions.created_at')
            ->get();

        $blends = DB::table('blend_sheets')
            ->join('clients', 'clients.client_id', '=', 'blend_sheets.client_id')
            ->join('destinations', 'destinations.destination_id', '=', 'blend_sheets.destination_id')
            ->join('stations', 'stations.station_id', '=', 'blend_sheets.station_id')
            ->select('blend_sheets.blend_id', 'blend_sheets.created_at', 'stations.station_id', 'station_name', 'client_name', 'blend_sheets.client_id', 'blend_number', 'vessel_name', 'port_name', 'blend_sheets.status', 'output_packages', 'output_weight', 'location_id')
            ->where(function($q) use ($query) {
                $q->where('blend_number', 'LIKE', "%$query%")
                    ->orWhere('client_name', 'LIKE', "%$query%")
                    ->orWhere('contract', 'LIKE', "%$query%")
                    ->orWhere('seal_number', 'LIKE', "%$query%");
            })
            ->where(['blend_sheets.client_id' => $this->clientId])
            ->latest('blend_sheets.created_at')
            ->get();

        $blendBalances = DB::table('blendBalances')
            ->select('blend_id', 'client_name', 'blend_number', 'station_name', 'garden', 'grade', DB::raw("SUM(ex_packages) as blend_packages"), DB::raw('SUM(CAST(REPLACE(REPLACE(net_weight, ",", ""), ".00", "") AS DECIMAL(10,2))) as blend_weight'), DB::raw("SUM(current_packages) as balance_packages"), DB::raw("SUM(current_weight) as balance_weight"))
            ->groupBy('blend_id', 'client_name', 'blend_number', 'station_name', 'garden', 'grade')
            ->where(['blendBalances.client_id' => $this->clientId])
            ->orderBy('blend_number')->get();

        $externalTransfers = ExternalTransfer::join('stock_ins', 'stock_ins.stock_id', '=', 'external_transfers.stock_id')
            ->join('stations', 'stations.station_id', '=', 'stock_ins.station_id')
            ->join('delivery_orders', 'delivery_orders.delivery_id', '=', 'external_transfers.delivery_id')
            ->join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->join('warehouses', 'warehouses.warehouse_id', '=', 'external_transfers.warehouse_id')
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'external_transfers.transporter_id')
            ->select('external_transfers.status', 'client_name', 'warehouses.warehouse_name', 'station_name', 'external_transfers.delivery_number', 'transporter_name')
            ->selectRaw('SUM(transferred_palettes) AS total_palettes')
            ->selectRaw('SUM(transferred_weight) AS total_weight')
            ->orderBy('external_transfers.delivery_number', 'desc')
            ->orderBy('external_transfers.created_at', 'desc')
            ->groupBy('delivery_number', 'status', 'client_name', 'warehouse_name', 'station_name', 'transporter_name')
            ->where(['delivery_orders.client_id' => $this->clientId])
            ->where(function($q) use ($query) {
                $q->where('external_transfers.delivery_number', 'LIKE', "%$query%")
                    ->orWhere('client_name', 'LIKE', "%$query%");
            })
            ->get();

        return view('client::search.index')->with(['teas' => $teas, 'straightLine' => $straightLine, 'blends' => $blends, 'blendBalances' => $blendBalances, 'externalTransfers' => $externalTransfers, 'searchTerm' => $query]);
    }
    public function index()
    {
        return view('client::welcome');
    }
    public function viewDeliveryOrders()
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $orders = DeliveryOrder::join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->join('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
            ->join('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
            ->leftJoin('brokers', 'brokers.broker_id', '=', 'delivery_orders.broker_id')
            ->leftJoin('warehouses', 'warehouses.warehouse_id', '=', 'delivery_orders.warehouse_id')
            ->leftJoin('sub_warehouses', 'sub_warehouses.sub_warehouse_id', '=', 'delivery_orders.sub_warehouse_id')
            ->leftJoin('loading_instructions', function ($join) {
                $join->on('loading_instructions.delivery_id', '=', 'delivery_orders.delivery_id')
                    ->whereNull('loading_instructions.deleted_at');
            })
            ->where(['delivery_orders.client_id' => $this->clientId])
            ->select('delivery_orders.delivery_id','gardens.garden_name', 'grades.grade_name', 'brokers.broker_name', 'warehouses.warehouse_name', 'clients.client_name', 'delivery_orders.invoice_number', 'loading_instructions.loading_number', 'sub_warehouses.sub_warehouse_name', 'locality', 'lot_number', 'packet', 'weight')
//            ->where('delivery_orders.delivery_type', 1)
            ->whereNull('delivery_orders.deleted_at')
            ->orderBy('delivery_orders.created_at', 'desc')
            ->orderBy('delivery_orders.status', 'asc')
            ->whereMonth('delivery_orders.created_at', $currentMonth)
            ->whereYear('delivery_orders.created_at', $currentYear)
            ->get();
        return view('client::DOS.index')->with(['orders' => $orders]);
    }
    public function viewDeliveries ()
    {
        $deliveries = DB::table('currentstock')
            ->orderBy('sortOrder', 'desc')->where('current_stock', '>', 0)->where('current_weight', '>', 0)
            ->select('delivery_id', 'garden_name', 'grade_name', 'client_name', 'order_number', 'lot_number', 'invoice_number', 'date_received', 'stocked_at', 'bay_name', 'current_stock', 'current_weight', 'sortOrder', 'sale_number', 'stock_id', 'client_id', 'station_id', 'stock_id')
            ->whereNull('deleted_at')
            ->where(['client_id' => $this->clientId])
            ->get();
        return view('client::stock.index')->with(['stocks' => $deliveries]);
    }
    public function StockReport(Request $request)
    {
        $client = $request->input('client');
        $station = $request->input('station');
        $from = $request->input('from');
        $to = $request->input('to');
        $report = $request->report;

        return $this->AppClass->downloadCurrentStock($client, $station, $from, $to, $report);
    }
    public function viewBlendBalances()
    {
        $balances = DB::table('blendBalances')->where('current_weight', '>', 0)->orderBy('blend_number', 'asc')->where(['client_id' => $this->clientId])->get();
        return view('client::stock.blendBalances')->with(['balances' => $balances]);
    }
    public function teaSamplesRequest()
    {
        $samples = TeaSamples::join('delivery_orders', 'delivery_orders.delivery_id', '=', 'tea_samples.delivery_id')
            ->join('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
            ->join('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
            ->join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->where(['clients.client_id' => $this->clientId])
            ->select('sample_id', 'invoice_number', 'lot_number', 'grade_name', 'garden_name', 'client_name', 'sample_weight', 'sample_palletes', 'tea_samples.type', 'tea_samples.created_at')
            ->orderBy('tea_samples.created_at', 'desc')
            ->get();
        return view('client::stock.teaSamples')->with('samples', $samples);
    }
    public function viewExternalTransfers()
    {
        $transfers = ExternalTransfer::leftJoin('blendBalances', function ($join) {
            $join->on('blendBalances.blend_balance_id', '=', 'external_transfers.stock_id')
                ->on('blendBalances.blend_id', '=', 'external_transfers.delivery_id');
        })
            ->leftJoin('delivery_orders', function ($join) {
                $join->on('delivery_orders.delivery_id', '=', 'external_transfers.delivery_id');
            })
            ->leftJoin('stock_ins', 'stock_ins.stock_id', '=', 'external_transfers.stock_id')
            ->leftJoin('stations', 'stations.station_id', '=', 'stock_ins.station_id')
            ->leftJoin('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->leftjoin('warehouses', 'warehouses.warehouse_id', '=', 'external_transfers.warehouse_id')
            ->leftjoin('other_destinations', 'other_destinations.warehouse_id', '=', 'external_transfers.warehouse_id')
            ->select('external_transfers.status',
                DB::raw('COALESCE(clients.client_name, blendBalances.client_name) as client_name'),
                DB::raw('COALESCE(warehouses.warehouse_name, other_destinations.warehouse_name) as warehouse_name'),
                DB::raw('COALESCE(stations.station_name, blendBalances.station_name) as station_name'),
                'external_transfers.delivery_number',
                DB::raw('DATE(external_transfers.created_at) as created_at'))
            ->selectRaw('SUM(external_transfers.transferred_palettes) AS total_palettes')
            ->selectRaw('SUM(external_transfers.transferred_weight) AS total_weight')
            ->orderBy('delivery_number', 'desc')
            ->orderBy('external_transfers.created_at', 'desc')
            ->where(['delivery_orders.client_id' => $this->clientId])
            ->groupBy(
                'external_transfers.status',
                DB::raw('COALESCE(clients.client_name, blendBalances.client_name)'),
                DB::raw('COALESCE(warehouses.warehouse_name, other_destinations.warehouse_name)'),
                DB::raw('COALESCE(stations.station_name, blendBalances.station_name)'),
                'external_transfers.delivery_number',
                DB::raw('DATE(external_transfers.created_at)'),
            )
            ->get();

        return view('client::transfers.externalTransfers')->with(['transfers' => $transfers]);
    }
    public function downloadExtraDelNote($id)
    {
        return $this->AppClass->downloadExternalTransfers($id);
    }
    public function viewExternalTransferDetails($id)
    {
        $transfers = ExternalTransfer::leftJoin('blendBalances', function ($join) {
            $join->on('blendBalances.blend_balance_id', '=', 'external_transfers.stock_id')
                ->on('blendBalances.blend_id', '=', 'external_transfers.delivery_id');
        })
            ->leftJoin('delivery_orders', function ($join) {
                $join->on('delivery_orders.delivery_id', '=', 'external_transfers.delivery_id');
            })
            ->leftJoin('stock_ins', 'stock_ins.stock_id', '=', 'external_transfers.stock_id')
            ->leftJoin('stations', 'stations.station_id', '=', 'stock_ins.station_id')
            ->leftJoin('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->leftjoin('warehouses', 'warehouses.warehouse_id', '=', 'external_transfers.warehouse_id')
            ->leftjoin('other_destinations', 'other_destinations.warehouse_id', '=', 'external_transfers.warehouse_id')
            ->leftJoin('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
            ->leftJoin('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
            ->select('ex_transfer_id', 'external_transfers.status', DB::raw('COALESCE(clients.client_name, blendBalances.client_name) as client_name'), DB::raw('COALESCE(warehouses.warehouse_name, other_destinations.warehouse_name) as warehouse_name'), DB::raw('COALESCE(stations.station_name, blendBalances.station_name) as station_name'), 'external_transfers.delivery_number', DB::raw('DATE(external_transfers.created_at) as created_at'), 'location_id', DB::raw('COALESCE(gardens.garden_name, blendBalances.garden) as garden_name'),DB::raw('COALESCE(grades.grade_name, blendBalances.grade) as grade_name'), DB::raw('COALESCE(delivery_orders.invoice_number, blendBalances.blend_number) as invoice_number'), 'external_transfers.transferred_palettes', 'external_transfers.transferred_weight', 'delivery_orders.lot_number')
            ->where(['external_transfers.delivery_number' => base64_decode($id)])
            ->get();

        return view('client::transfers.viewExternalTransfer')->with(['transfers' => $transfers]);
    }
    public function viewShippingInstructions()
    {
        $shipping = ShippingInstruction::join('clients', 'clients.client_id', '=', 'shipping_instructions.client_id')
            ->join('destinations', 'destinations.destination_id', '=', 'shipping_instructions.destination_id')
            ->join('stations', 'stations.station_id', '=', 'shipping_instructions.station_id')
            ->leftJoin('shipments', 'shipments.shipping_id', '=', 'shipping_instructions.shipping_id')
            ->select('shipping_instructions.shipping_id', 'client_name', 'shipping_instructions.created_at', 'shipping_instructions.status', 'station_name', 'shipping_number', 'vessel_name', 'port_name', 'load_type', 'location_id', DB::raw("SUM(REPLACE(shipments.shipped_packages, ',', '')) as shipped_packages"), DB::raw("SUM(REPLACE(shipments.shipped_weight, ',', '')) as shipped_weight"))
            ->groupBy('shipping_id', 'client_name', 'shipping_instructions.created_at', 'shipping_instructions.status', 'station_name', 'shipping_number', 'vessel_name', 'port_name', 'load_type', 'location_id')
            ->where(['clients.client_id' => $this->clientId])
            ->latest('shipping_instructions.created_at')
            ->get();
        return view('client::shipping.SIs')->with(['shipping' => $shipping]);
    }
    public function addShipmentTeas($id)
    {
        $si = ShippingInstruction::join('stations', 'stations.station_id', '=', 'shipping_instructions.station_id')
            ->join('clients', 'clients.client_id', '=', 'shipping_instructions.client_id')
            ->join('destinations', 'destinations.destination_id', '=', 'shipping_instructions.destination_id')
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'shipping_instructions.transporter_id')
            ->leftJoin('drivers', 'drivers.driver_id', 'shipping_instructions.driver_id')
            ->leftJoin('clearing_agents', 'clearing_agents.agent_id', 'shipping_instructions.clearing_agent')
            ->select('shipping_instructions.*', 'location_id', 'client_name', 'clients.phone as client_phone', 'clients.email', 'clients.address', 'port_name', 'transporter_name', 'driver_name', 'drivers.phone', 'agent_name')
            ->find($id);
        $teas = Shipment::join('stock_ins', 'stock_ins.stock_id', '=', 'shipments.stock_id')
            ->join('delivery_orders', 'delivery_orders.delivery_id', '=', 'stock_ins.delivery_id')
            ->join('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
            ->join('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
            ->select('shipment_id', 'shipments.shipped_packages', 'shipments.shipped_weight', 'shipments.status', 'garden_name', 'grade_name', 'invoice_number')
            ->where('shipping_id', $id)
            ->orderBy('shipments.created_at', 'desc')
            ->get();

        return view('client::shipping.addTeasToSI')->with(['teas' => $teas, 'si' => $si]);
    }
    public function downloadSIDocument($id)
    {
        return $this->AppClass->downloadStraightLine($id);
    }
    public function viewBlendProcessing()
    {
        $sheets = DB::table('blend_sheets')
            ->join('clients', 'clients.client_id', '=', 'blend_sheets.client_id')
            ->join('destinations', 'destinations.destination_id', '=', 'blend_sheets.destination_id')
            ->join('stations', 'stations.station_id', '=', 'blend_sheets.station_id')
            ->leftJoin('blend_shipments', 'blend_shipments.blend_id', '=', 'blend_sheets.blend_id')
            ->select('blend_sheets.blend_id', 'blend_sheets.created_at', 'station_name', 'client_name', 'blend_number', 'vessel_name', 'port_name', 'blend_sheets.status', 'output_packages', 'output_weight', 'location_id', DB::raw("SUM(REPLACE(blend_shipments.blended_packages, ',', '')) as blended_packages"), DB::raw("SUM(REPLACE(blend_shipments.net_weight, ',', '')) as blended_weight"))
            ->groupBy('blend_sheets.blend_id', 'blend_sheets.created_at', 'station_name', 'client_name', 'blend_number', 'vessel_name', 'port_name', 'blend_sheets.status', 'output_packages', 'output_weight', 'location_id')
            ->where(['clients.client_id' => $this->clientId])
            ->whereNull('blend_sheets.deleted_at')
            ->latest('blend_sheets.created_at')
            ->get();
        return view('client::shipping.blendSheets')->with(['sheets' => $sheets]);
    }
    public function addBlendTeas($id)
    {
        $bs = BlendSheet::join('clients', 'clients.client_id', '=', 'blend_sheets.client_id')
            ->join('destinations', 'destinations.destination_id', '=', 'blend_sheets.destination_id')
            ->join('stations', 'stations.station_id', '=', 'blend_sheets.station_id')
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'blend_sheets.transporter_id')
            ->leftJoin('drivers', 'drivers.driver_id', '=', 'blend_sheets.driver_id')
            ->leftJoin('clearing_agents', 'clearing_agents.agent_id', '=', 'blend_sheets.agent_id')
            ->leftJoin('blend_shipments', 'blend_shipments.blend_id', '=', 'blend_sheets.blend_id')
            ->select(
                'clients.client_id',
                'blend_sheets.blend_id',
                'blend_sheets.blend_number',
                'blend_sheets.status',
                'blend_sheets.consignee',
                'blend_sheets.vessel_name',
                'blend_sheets.shipping_mark',
                'blend_sheets.standard_details',
                'blend_sheets.registration',
                'blend_sheets.escort',
                'blend_sheets.container_tare',
                'blend_sheets.seal_number',
                'clients.client_name',
                'clients.phone as client_phone',
                'clients.email',
                'clients.address',
                'destinations.port_name',
                'transporters.transporter_name',
                'location_id',
                'blend_sheets.station_id',
                'drivers.driver_name',
                'drivers.phone',
                'clearing_agents.agent_name'
            )
            ->selectRaw('SUM(blend_shipments.blended_packages) as outputPackages')
            ->selectRaw('SUM(blend_shipments.net_weight) as outputWeight')
            ->where('blend_sheets.blend_id', $id) // Use where instead of find
            ->groupBy(
                'client_id',
                'blend_sheets.blend_id',
                'blend_sheets.blend_number',
                'blend_sheets.status',
                'blend_sheets.consignee',
                'blend_sheets.vessel_name',
                'blend_sheets.shipping_mark',
                'blend_sheets.standard_details',
                'blend_sheets.registration',
                'blend_sheets.escort',
                'blend_sheets.container_tare',
                'blend_sheets.seal_number',
                'clients.client_name',
                'clients.phone',
                'clients.email',
                'clients.address',
                'destinations.port_name',
                'transporters.transporter_name',
                'location_id',
                'drivers.driver_name',
                'drivers.phone',
                'clearing_agents.agent_name',
                'blend_sheets.station_id'
            )
            ->first(); // Fetch the first record that matches the conditions
        $teas = BlendTea::leftJoin('delivery_orders', 'delivery_orders.delivery_id', '=', 'blend_teas.delivery_id')
            ->leftJoin('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
            ->leftJoin('grades','grades.grade_id','=','delivery_orders.grade_id')
            ->leftJoin('loading_instructions', function($join) {
                $join->on('loading_instructions.delivery_id','=','delivery_orders.delivery_id')
                    ->whereNull('loading_instructions.deleted_at');
            })
            ->leftJoin('blendBalances', function ($join){
                $join->on('blendBalances.blend_balance_id', '=', 'blend_teas.stock_id')
                    ->on('blendBalances.blend_id', '=', 'blend_teas.delivery_id');
            })
            ->select('blended_id', 'blend_teas.blended_packages', 'blend_teas.blended_weight', 'blend_teas.status', 'garden_name', 'grade_name', 'grade', 'garden', 'loading_number', 'sale_number', 'prompt_date', 'invoice_number', 'blend_number', 'blend_date')
            ->where('blend_teas.blend_id', $id)
            ->orderBy('blend_teas.created_at', 'desc')
            ->get();
        return view('client::shipping.addTeasToBlend')->with(['teas' => $teas, 'bs' => $bs]);
    }
    public function downloadBlendSheet($id)
    {
        return $this->AppClass->downloadBlendJob($id);
    }
    public function downloadOutturReport($id)
    {
        return $this->AppClass->downloadBlendOutTurn($id);
    }
}
