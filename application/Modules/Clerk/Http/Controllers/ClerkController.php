<?php

namespace Modules\Clerk\Http\Controllers;

use App\Imports\ImportBulkyTeas;
use App\Imports\ImportDOS;
use App\Models\BlendBalance;
use App\Models\BlendMaterial;
use App\Models\BlendSheet;
use App\Models\BlendShipment;
use App\Models\BlendSupervision;
use App\Models\BlendTea;
use App\Models\Broker;
use App\Models\ClearingAgent;
use App\Models\Client;
use App\Models\DeliveryOrder;
use App\Models\Destination;
use App\Models\Driver;
use App\Models\ExternalTransfer;
use App\Models\Garden;
use App\Models\Grade;
use App\Models\LoadingInstruction;
use App\Models\Shipment;
use App\Models\ShipmentContainer;
use App\Models\ShippingInstruction;
use App\Models\Station;
use App\Models\StockIn;
use App\Models\SubWarehouse;
use App\Models\Transfers;
use App\Models\Transporter;
use App\Models\UserInfo;
use App\Models\Warehouse;
use App\Models\WarehouseBay;
use App\Services\AppClass;
use App\Services\CustomIds;
use App\Services\ExportDeliveryOrders;
use App\Services\ExportDirectDeliveryOrders;
use App\Services\ExportPendingTCI;
use App\Services\ExportStock;
use App\Services\ExportTCI;
use App\Services\ExportTeaTransport;
use App\Services\Log;
use App\Services\TraceTea;
use BaconQrCode\Encoder\QrCode;
use Carbon\Carbon;
use DateTime;
use Exception;
use function PHPUnit\Framework\isEmpty;
use function Symfony\Component\Translation\t;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\RequiredIf;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Files\Disk;
use Modules\Admin\Entities\OtherDestination;
use Modules\Admin\Entities\OtherTransporter;
use Modules\Clerk\Entities\Approval;
use Modules\Clerk\Entities\Auction;
use Modules\Clerk\Entities\DeliveryNote;
use Modules\Clerk\Entities\ForeignTea;
use Modules\Clerk\Entities\Rebagging;
use Modules\Clerk\Entities\ReportRequest;
use Modules\Clerk\Entities\TeaSamples;
use Modules\Tasks\Entities\NotificationUser;
use Mpdf\Mpdf;
use Mpdf\Output\Destination as PdfDestination;
use Mpdf\Tag\Br;
use NcJoes\OfficeConverter\OfficeConverter;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ClerkController extends Controller
{
    protected $logger, $traceService, $appClass;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Log $logger, TraceTea $traceService, AppClass $appClass)
    {
        $this->logger = $logger;
        $this->traceService = $traceService;
        $this->AppClass = $appClass;
    }
    public function traceTea($id)
    {
       $traceData = $this->traceService->traceDeliveryOrder($id);

        if (!$traceData) {
            return response()->json(['error' => 'Delivery order not found'], 404);
        }
        return view('clerk::DOS.traceTea')->with('teaDetails', $traceData);
    }
    public function traceBlendBalance($id)
    {
        $traceData = $this->traceService->traceBlendBalance($id);
        if (!$traceData) {
            return response()->json(['error' => 'Delivery order not found'], 404);
        }
        return view('clerk::DOS.traceBlendBalance')->with(['teaDetails' => $traceData, 'id' => $id]);
    }
    public function traceTeaByInvoice(Request $request)
    {
        $query = trim($request->input('invoice'));
        $teas = DeliveryOrder::leftJoin('currentstock', 'currentstock.delivery_id', 'delivery_orders.delivery_id')
            ->where(function ($q) use ($query) {
                $q->where('delivery_orders.invoice_number', 'LIKE', "%$query%")
                    ->orWhere('delivery_orders.lot_number', 'LIKE', "%$query%")
                    ->orWhere('delivery_orders.order_number', 'LIKE', "%$query%")
                    ->orWhere('client_name', 'LIKE', "%$query%")
                    ->orWhere('currentstock.delivery_number', 'LIKE', "%$query%");
            })
            ->select('delivery_orders.delivery_id', 'delivery_orders.invoice_number', 'client_name', 'grade_name', 'garden_name', 'delivery_orders.lot_number', 'delivery_orders.order_number', 'delivery_orders.packet', 'delivery_orders.weight', DB::raw("SUM(current_stock) as current_stock"), DB::raw("SUM(current_weight) as current_weight"))
            ->groupBy('delivery_id', 'invoice_number', 'client_name', 'grade_name', 'garden_name', 'lot_number', 'order_number', 'packet', 'weight', 'height')
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
            ->latest('blend_sheets.created_at')
            ->get();

        $blendBalances = DB::table('blendBalances')
            ->select('blend_id', 'client_name', 'blend_number', 'station_name', 'garden', 'grade', DB::raw("SUM(ex_packages) as blend_packages"), DB::raw('SUM(CAST(REPLACE(REPLACE(net_weight, ",", ""), ".00", "") AS DECIMAL(10,2))) as blend_weight'), DB::raw("SUM(current_packages) as balance_packages"), DB::raw("SUM(current_weight) as balance_weight"))
            ->groupBy('blend_id', 'client_name', 'blend_number', 'station_name', 'garden', 'grade')
            ->where(function($q) use ($query) {
                $q->where('blend_number', 'LIKE', "%$query%")
                    ->orWhere('client_name', 'LIKE', "%$query%");
            })
            ->orderBy('blend_number')->get();

        $internalTransfers = Transfers::join('stations', 'stations.station_id', '=', 'transfers.station_id')
            ->join('delivery_orders', 'delivery_orders.delivery_id', '=', 'transfers.delivery_id')
            ->join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->join('stations as destination_station', 'destination_station.station_id', '=', 'transfers.destination')
            ->join('warehouse_locations', 'warehouse_locations.location_id', '=', 'destination_station.location_id')
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'transfers.transporter_id')
            ->orderBy('transfers.created_at', 'desc')
            ->select('stations.station_name', 'clients.client_name', 'destination_station.station_name as destination_name', 'transfers.status', 'transfers.delivery_number', 'transporter_name')
            ->selectRaw('SUM(requested_palettes) as total_palettes')
            ->selectRaw('SUM(requested_weight) as total_weight')
            ->groupBy('delivery_number', 'station_name', 'client_name', 'destination_name', 'status', 'transporter_name')
            ->where(function($q) use ($query) {
                $q->where('delivery_number', 'LIKE', "%$query%")
                    ->orWhere('client_name', 'LIKE', "%$query%");
            })
            ->get();

        $externalTransfers = ExternalTransfer::join('stock_ins', 'stock_ins.stock_id', '=', 'external_transfers.stock_id')
            ->join('stations', 'stations.station_id', '=', 'stock_ins.station_id')
            ->join('delivery_orders', 'delivery_orders.delivery_id', '=', 'external_transfers.delivery_id')
            ->join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->join('warehouses', 'warehouses.warehouse_id', '=', 'external_transfers.warehouse_id')
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'external_transfers.transporter_id')
            ->select('external_transfers.status', 'client_name', 'warehouses.warehouse_name', 'station_name', 'external_transfers.delivery_number', 'transporter_name', 'lot')
            ->selectRaw('SUM(transferred_palettes) AS total_palettes')
            ->selectRaw('SUM(transferred_weight) AS total_weight')
            ->orderBy('external_transfers.delivery_number', 'desc')
            ->orderBy('external_transfers.created_at', 'desc')
            ->groupBy('delivery_number', 'status', 'client_name', 'warehouse_name', 'station_name', 'transporter_name', 'lot')
            ->where(function($q) use ($query) {
                $q->where('external_transfers.delivery_number', 'LIKE', "%$query%")
                    ->orWhere('client_name', 'LIKE', "%$query%");
            })
            ->get();

        $tcis = LoadingInstruction::join('stations', 'stations.station_id', '=', 'loading_instructions.station_id')
            ->join('delivery_orders', 'delivery_orders.delivery_id', '=', 'loading_instructions.delivery_id')
            ->join('warehouses', 'warehouses.warehouse_id', '=', 'delivery_orders.warehouse_id')
            ->join('sub_warehouses', 'sub_warehouses.sub_warehouse_id', '=', 'delivery_orders.sub_warehouse_id')
            ->join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'loading_instructions.transporter_id')
            ->select('client_name', 'loading_number', 'warehouse_name', 'sub_warehouses.sub_warehouse_name', 'station_name', 'loading_instructions.status', 'transporter_name', DB::raw("SUM(packet) as packages"), DB::raw("SUM(weight) as net_weight"))
            ->groupBy('client_name', 'loading_number', 'warehouse_name', 'sub_warehouses.sub_warehouse_name', 'station_name', 'loading_instructions.status', 'transporter_name')
            ->where(function($q) use ($query) {
                $q->where('loading_instructions.loading_number', 'LIKE', "%$query%")
                    ->orWhere('order_number', 'LIKE', "%$query%")
                    ->orWhere('client_name', 'LIKE', "%$query%");
            })
            ->latest('loading_instructions.created_at')
            ->get();

        return view('clerk::search.index')->with(['teas' => $teas, 'straightLine' => $straightLine, 'blends' => $blends, 'blendBalances' => $blendBalances, 'internalTransfers' => $internalTransfers, 'externalTransfers' => $externalTransfers, 'searchTerm' => $query, 'tcis' => $tcis]);
    }

    public function index()
    {
        $orders = DeliveryOrder::leftJoin('loading_instructions', 'loading_instructions.delivery_id', '=', 'delivery_orders.delivery_id')
            ->select('loading_instructions.status as load_status', 'loading_instructions.deleted_at', 'delivery_orders.created_at as date_received', 'loading_number')
            ->whereNull('delivery_orders.deleted_at');
        $uncollected = clone $orders;
        $late = clone $orders;
        $noTCI = clone $orders;
        $overstayed = clone $orders;
        $uncollected = $uncollected->where('loading_instructions.status', 1)->where('loading_instructions.deleted_at', '=', null)->get();
        $threshold = Carbon::now();
        $late = $late->whereRaw("DATE_ADD(loading_instructions.created_at, INTERVAL 2 DAY) <= '$threshold'")->where('loading_instructions.status', 1)->where('loading_instructions.deleted_at', '=', null)->get();
        $noTCI = $noTCI->where('delivery_orders.delivery_type', 1)
                ->where(function ($noTCI) {
                    $noTCI->where('delivery_orders.status', 0)
                        ->orWhereNull('delivery_orders.status'); // Fixed: checks for null status
                })
                ->where(function ($noTCI) {
                    $noTCI->whereNull('loading_instructions.delivery_id')  // No matching loading instruction
                        ->orWhereNotNull('loading_instructions.deleted_at');  // Exists but only where deleted_at is not null
                })
                ->get();
        $now = \Carbon\Carbon::now();
        $overstayed = $overstayed->whereRaw("DATE_ADD(delivery_orders.prompt_date, INTERVAL 7 DAY) <= '$now'")
            ->where('loading_instructions.status', 1)
            ->where('loading_instructions.deleted_at', '=', null)
            ->get();
        $internal = Transfers::where('transfers.status', '<', 3)
            ->orWhere('transfers.status', null)
            ->whereNull('transfers.deleted_at')
            ->latest('transfers.created_at')
            ->get()
            ->groupBy('delivery_number');
        $external = ExternalTransfer::latest('external_transfers.created_at')
            ->where('external_transfers.status', '<', 3)
            ->orWhere('external_transfers.status', null)
            ->whereNull('external_transfers.deleted_at')
            ->orderBy('delivery_number', 'desc')
            ->get()
            ->groupBy('delivery_number');
        $si = ShippingInstruction::latest('shipping_instructions.created_at')
            ->where('shipping_instructions.status', '<', 4)
            ->orWhere('shipping_instructions.status', null)
            ->whereNull('shipping_instructions.deleted_at')
            ->get();
        $blend = DB::table('blend_sheets')->where(function ($query) {
                $query->where('blend_sheets.status', '<', 4)
                    ->orWhereNull('blend_sheets.status');
            })
            ->whereNull('blend_sheets.deleted_at')
            ->latest('blend_sheets.created_at')
            ->get();

        $stocks = DB::table('currentstock')
            ->select('client_name')
            ->selectRaw('SUM(current_stock) as packages, SUM(current_weight) as net_weight')
            ->groupBy('client_name')
            ->orderBy('net_weight', 'desc')
            ->where('current_stock', '>', 0)
            ->get();
        $tcis = LoadingInstruction::whereIn('status',[null, 0, 1])->whereNull('deleted_at')->select('loading_number')->get()->groupBy('loading_number')->count();
        $clients = Client::all();
        return view('clerk::welcome')->with(['blend' => $blend, 'si' => $si, 'internal' => $internal, 'external' => $external, 'uncollected' => $uncollected, 'late' => $late, 'noTCI' => $noTCI, 'overstayed' => $overstayed, 'tcis' => $tcis, 'clients' => $clients, 'stocks' => $stocks]);
    }
    public function dashboardReport($id)
    {
        $id = base64_decode($id);
        $orders = DeliveryOrder::join('users as u', 'u.user_id', '=', 'delivery_orders.created_by')
            ->join('gardens as g', 'g.garden_id', '=', 'delivery_orders.garden_id')
            ->join('grades as gr', 'gr.grade_id', '=', 'delivery_orders.grade_id')
            ->join('brokers as br', 'br.broker_id', '=', 'delivery_orders.broker_id')
            ->join('warehouses as wh', 'wh.warehouse_id', '=', 'delivery_orders.warehouse_id')
            ->leftJoin('sub_warehouses as sub', 'sub.sub_warehouse_id', '=', 'delivery_orders.sub_warehouse_id')
            ->join('clients as cl', 'cl.client_id', '=', 'delivery_orders.client_id')
            ->leftJoin('loading_instructions as li', function ($join) {
                $join->on('li.delivery_id', '=', 'delivery_orders.delivery_id')
                    ->whereNull('li.deleted_at');
            })
            ->leftJoin('foreign_teas as ft', function ($join) {
                $join->on('ft.delivery_order_id', '=', 'delivery_orders.delivery_id')
                    ->whereNull('ft.deleted_at');
            })
            ->leftJoin('drivers as dr', 'dr.driver_id', '=', 'li.driver_id')
            ->leftJoin('transporters as tr', 'tr.transporter_id', '=', 'li.transporter_id')
            ->leftJoin('stations as st', 'st.station_id', '=', 'li.station_id')
            ->leftJoin('users as lu', 'lu.user_id', '=', 'li.created_by')
            ->select('u.username', 'g.garden_name', 'gr.grade_name', 'br.broker_name', 'wh.warehouse_name', 'wh.warehouse_id', 'cl.client_name', 'delivery_orders.*', 'tr.transporter_id', 'tr.transporter_name', 'dr.driver_id', 'dr.driver_name', 'dr.id_number', 'dr.phone', 'li.loading_id', 'li.loading_number', 'li.status as load_status', 'li.registration', 'li.created_by as load_user_id', 'lu.username as load_user', 'st.station_name', 'st.station_id', 'sub.sub_warehouse_name', 'li.deleted_at', 'delivery_orders.created_at as date_received', 'collection', 'received', 'validated', 'tea_type')
            ->whereNull('delivery_orders.deleted_at')
            ->where('delivery_orders.delivery_type', 1)
            ->orderBy('delivery_orders.created_at', 'desc');
        // Clone the query builder instance for each variable
        $uncollected = clone $orders;
        $late = clone $orders;
        $noTCI = clone $orders;
        $overstayed = clone $orders;
        $uncollected = $uncollected->whereIn('li.collection', ['in_hand', 'under_collection'])->get();
        $threshold = Carbon::now();
        $late = $late->whereRaw("DATE_ADD(li.created_at, INTERVAL 2 DAY) <= '$threshold'")->where('li.status', 1)->get();
        $noTCI = $noTCI->where('delivery_orders.delivery_type', 1)
                ->where(function ($noTCI) {
                    $noTCI->where('delivery_orders.status', 0)
                        ->orWhereNull('delivery_orders.status'); // Fixed: checks for null status
                })
                ->where(function ($noTCI) {
                    $noTCI->whereNull('li.delivery_id')  // No matching loading instruction
                        ->orWhereNotNull('li.deleted_at');  // Exists but only where deleted_at is not null
                })
                ->get();
        // $noTCI->whereNull('li.loading_number')->get();
        $now = \Carbon\Carbon::now();
        $overstayed = $overstayed->whereRaw("DATE_ADD(delivery_orders.prompt_date, INTERVAL 7 DAY) <= '$now'")->where('li.status', 1)->get();
        $internal = Transfers::leftJoin('stations', 'stations.station_id', '=', 'transfers.station_id')
            ->leftJoin('stations as destination_station', 'destination_station.station_id', '=', 'transfers.destination')
            ->leftJoin('delivery_orders', 'delivery_orders.delivery_id', '=', 'transfers.delivery_id')
            ->join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->leftJoin('currentstock', function($join) {
                $join->on('currentstock.delivery_id', '=', 'transfers.delivery_id')
                    ->on('currentstock.stock_id', '=', 'transfers.stock_id');
            })
            ->select('transfers.created_at', 'stations.station_name', 'clients.client_name', 'destination', 'destination_station.station_name as destination_name', 'transfers.status', 'transfers.delivery_number')
            ->selectRaw('SUM(transfers.requested_palettes) as total_palettes, SUM(transfers.requested_weight) as total_weight')
            ->where(function ($query) {
                $query->where('transfers.status', '<', 3)
                    ->orWhereNull('transfers.status');
            })
            ->whereNull('transfers.deleted_at')
            ->latest('transfers.created_at')
            ->groupBy('transfers.created_at', 'stations.station_name', 'clients.client_name', 'transfers.destination', 'destination_station.station_name', 'transfers.status', 'transfers.delivery_number', 'stations.station_name')
            ->get();
        $external = ExternalTransfer::join('currentstock', 'currentstock.stock_id', '=', 'external_transfers.stock_id')
            ->leftJoin('warehouses', 'warehouses.warehouse_id', '=', 'external_transfers.warehouse_id')
            ->select('external_transfers.created_at', 'currentstock.client_name', 'external_transfers.status', 'warehouses.warehouse_name', 'external_transfers.delivery_number', 'currentstock.stocked_at as station_name', 'warehouses.warehouse_name as destination_name')
            ->latest('external_transfers.created_at')
            ->where('external_transfers.status', '<', 3)
            ->orWhere('external_transfers.status', null)
            ->whereNull('external_transfers.deleted_at')
            ->orderBy('delivery_number', 'desc')
            ->selectRaw('SUM(external_transfers.transferred_palettes) as total_palettes')
            ->selectRaw('SUM(external_transfers.transferred_weight) as total_weight')
            ->groupBy('external_transfers.created_at', 'currentstock.client_name', 'external_transfers.status', 'warehouses.warehouse_name', 'external_transfers.delivery_number', 'currentstock.stocked_at', 'warehouses.warehouse_name')
            ->get();
        $si = ShippingInstruction::join('clients', 'clients.client_id', '=', 'shipping_instructions.client_id')
            ->join('destinations', 'destinations.destination_id', '=', 'shipping_instructions.destination_id')
            ->join('stations', 'stations.station_id', '=', 'shipping_instructions.station_id')
            ->leftJoin('clearing_agents', 'clearing_agents.agent_id', '=', 'shipping_instructions.clearing_agent')
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'shipping_instructions.transporter_id')
            ->select('shipping_instructions.shipping_id', 'shipping_instructions.created_at', 'clients.client_name', 'shipping_instructions.clearing_agent', 'shipping_number', 'vessel_name', 'port_name', 'load_type', 'container_size', 'shipping_mark', 'consignee', 'shipping_instructions.status', 'shipping_instructions', 'escort', 'seal_number', 'agent_name', 'ship_date', 'container_number', 'container_tare', 'station_name')
            ->latest('shipping_instructions.created_at')
            ->where('shipping_instructions.status', '<', 4)
            ->orWhere('shipping_instructions.status', null)
            ->whereNull('shipping_instructions.deleted_at')
            ->get();
        $blend = DB::table('blend_sheets')
            ->join('clients', 'clients.client_id', '=', 'blend_sheets.client_id')
            ->join('destinations', 'destinations.destination_id', '=', 'blend_sheets.destination_id')
            ->leftJoin('blend_teas', 'blend_teas.blend_id', '=', 'blend_sheets.blend_id')
            ->leftJoin('drivers', 'drivers.driver_id', '=', 'blend_sheets.driver_id')
            ->leftJoin('clearing_agents', 'clearing_agents.agent_id', '=', 'blend_sheets.agent_id')
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'blend_sheets.transporter_id')
            ->leftJoin('stations', 'stations.station_id', '=', 'blend_sheets.station_id')
            ->select('blend_sheets.created_at', 'blend_sheets.blend_id as shipping_id', 'blend_sheets.client_id', 'client_name', 'clients.phone as cPhone', 'email', 'blend_number as shipping_number', 'vessel_name', 'blend_sheets.destination_id', 'port_name', 'shipping_mark', 'consignee', 'contract', 'grade', 'garden', 'blend_date', 'blend_sheets.status', 'container_size', 'clients.address', 'package_type', 'registration', 'transporter_name', 'transporters.transporter_id', 'driver_name', 'drivers.phone as driver_phone', 'container_tare', 'blend_shipped', 'agent_name', 'seal_number', 'escort', 'output_packages', 'output_weight', 'blend_sheets.packet_tare', 'blend_sheets.agent_id', 'id_number', 'stations.station_id', 'stations.station_name', 'stations.location_id', 'standard_details')
            ->selectRaw('SUM(blend_teas.blended_packages) as input_packages')
            ->selectRaw('SUM(blend_teas.blended_weight) as input_weight')
            ->groupBy('created_at', 'blend_sheets.blend_id', 'blend_sheets.client_id', 'client_name', 'clients.phone', 'email', 'blend_number', 'vessel_name', 'blend_sheets.destination_id', 'port_name', 'shipping_mark', 'consignee', 'contract', 'grade', 'garden', 'blend_date', 'blend_sheets.status', 'container_size', 'clients.address', 'package_type', 'registration', 'transporter_name', 'driver_name', 'driver_phone', 'container_tare', 'blend_shipped', 'agent_name', 'seal_number', 'escort', 'output_packages', 'output_weight', 'packet_tare', 'agent_id', 'transporter_id', 'id_number', 'station_id', 'station_name', 'standard_details', 'location_id')
            ->where(function ($query) {
                $query->where('blend_sheets.status', '<', 4)
                    ->orWhereNull('blend_sheets.status');
            })
            ->whereNull('blend_teas.deleted_at')
            ->whereNull('blend_sheets.deleted_at')
            ->latest('blend_sheets.created_at')
            ->get();
        $id == 1 ? $report = $uncollected : ($id == 2 ? $report = $late : ($id == 3 ? $report = $noTCI : ($id == 4 ? $report = $overstayed : ($id == 5 ? $report = $internal : ($id == 6 ? $report = $external : ($id == 7 ? $report = $si : ($id == 8 ? $report = $blend : null )))))));
        if ($id <= 4){
            return view('clerk::dashboard.collections')->with(['orders' => $report, 'id' => $id]);
        }elseif ($id >= 5 && $id <= 6){
            return view('clerk::dashboard.transfers')->with(['orders' => $report, 'id' => $id]);
        }elseif($id >= 7){
            return view('clerk::dashboard.sis')->with(['orders' => $report, 'id' => $id]);
        }
    }
    public function viewInternalTransfers(Request $request)
    {
        $from = $request->get('from') ?? Carbon::now()->startOfMonth();
        $to = $request->get('to') ?? Carbon::now();

        $transfers = Transfers::leftJoin('blendBalances', function ($join) {
            $join->on('blendBalances.blend_balance_id', '=', 'transfers.stock_id')
                ->on('blendBalances.blend_id', '=', 'transfers.delivery_id');
        })
            ->leftJoin('delivery_orders', function ($join) {
                $join->on('delivery_orders.delivery_id', '=', 'transfers.delivery_id');
            })
            ->leftJoin('stations', 'stations.station_id', '=', 'transfers.station_id')
            ->leftJoin('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->leftJoin('stations as destination_station', 'destination_station.station_id', '=', 'transfers.destination')
            ->leftJoin('warehouse_locations', 'warehouse_locations.location_id', '=', 'destination_station.location_id')
            ->select(
                'stations.station_name',
                'stations.station_id',
                DB::raw('COALESCE(clients.client_name, blendBalances.client_name) as client_name'),
                'destination_station.station_name as destination_name',
                'destination_station.station_id as destination',
                'transfers.status',
                'transfers.delivery_number',
                DB::raw('DATE(transfers.created_at) as created_at'),
                'warehouse_locations.location_id',
                'stations.location_id as origin'
            )
            ->selectRaw('SUM(transfers.requested_palettes) as total_palettes')
            ->selectRaw('SUM(transfers.requested_weight) as total_weight')
            ->groupBy(
                'transfers.delivery_number',
                'stations.station_name',
                DB::raw('COALESCE(clients.client_name, blendBalances.client_name)'),
                'destination_station.station_name',
                'transfers.status',
                DB::raw('DATE(transfers.created_at)'),
                'stations.station_id',
                'destination_station.station_id',
                'warehouse_locations.location_id',
                'stations.location_id'
            )
            // ->whereBetween('transfers.created_at', [$from, $to])
            ->orderBy('transfers.created_at', 'desc')
            ->take(2000)
            ->get();

        return view('clerk::transfers.internalTransfers')->with(['transfers' => $transfers, 'to' => $to, 'from' => $from]);
    }
    public function prepareToReceiveTransfer($id)
    {
        $transfers = Transfers::leftJoin('blendBalances', function ($join) {
               $join->on('blendBalances.blend_balance_id', '=', 'transfers.stock_id')
                   ->on('blendBalances.blend_id', '=', 'transfers.delivery_id');
                })
               ->leftJoin('delivery_orders', function ($join) {
                   $join->on('delivery_orders.delivery_id', '=', 'transfers.delivery_id');
               })
            ->join('stations', 'stations.station_id', '=', 'transfers.station_id')
            ->leftJoin('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
            ->leftJoin('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
            ->leftJoin('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->leftJoin('stations as destination_station', 'destination_station.station_id', '=', 'transfers.destination')
            ->leftJoin('warehouse_locations', 'warehouse_locations.location_id', '=', 'destination_station.location_id')
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'transfers.transporter_id')
            ->leftJoin('drivers', 'drivers.driver_id', '=', 'transfers.driver_id')
            ->orderBy('transfers.created_at', 'desc')
            ->select('stations.station_name', 'stations.station_id', 'clients.client_name', 'destination_station.station_name as destination_name', 'destination_station.station_id as destination', 'transfers.status', 'transfers.delivery_number', 'transfers.created_at', 'warehouse_locations.location_id', 'stations.location_id as origin', 'transfers.requested_palettes', 'transfers.requested_weight', 'garden_name', 'grade_name', 'invoice_number', 'lot_number', 'stock_id', 'registration', 'driver_name', 'id_number', 'drivers.phone', 'transporters.transporter_id', 'transporter_name', 'transfer_id', 'garden', 'grade', 'blend_number', DB::raw("CASE WHEN blendBalances.blend_balance_id IS NOT NULL THEN 2 WHEN delivery_orders.delivery_id IS NOT NULL THEN 1 ELSE NULL END AS transfer_type"))
            ->where(['delivery_number' => base64_decode($id)])
            ->whereIn('transfers.status', [1, 2, 3])
            ->get();

        $transporters = Transporter::all();
        $registrations = Transfers::pluck('registration')->toArray();
        $drivers = Driver::all();
        $stations = WarehouseBay::where('station_id', $transfers[0]->destination)->get();
       return view('clerk::transfers.prepareToReceiveTransfer')->with(['transfers' => $transfers, 'transporters' => $transporters, 'registrations' => $registrations, 'users' => $drivers, 'stations' => $stations]);
    }
    public function viewInternalTransferDetails($id)
    {
       $transfers = Transfers::leftJoin('blendBalances', function ($join) {
                $join->on('blendBalances.blend_balance_id', '=', 'transfers.stock_id')
                    ->on('blendBalances.blend_id', '=', 'transfers.delivery_id');
            })
            ->leftJoin('delivery_orders', function ($join){
                $join->on('delivery_orders.delivery_id', '=', 'transfers.delivery_id');
            })
            ->join('stations', 'stations.station_id', '=', 'transfers.station_id')
            ->leftJoin('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
            ->leftJoin('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
            ->leftJoin('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->join('stations as destination_station', 'destination_station.station_id', '=', 'transfers.destination')
            ->join('warehouse_locations', 'warehouse_locations.location_id', '=', 'destination_station.location_id')
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'transfers.transporter_id')
            ->leftJoin('drivers', 'drivers.driver_id', '=', 'transfers.driver_id')
            ->select('stations.station_name', 'stations.station_id', 'clients.client_name', 'destination_station.station_name as destination_name', 'destination_station.station_id as destination', 'transfers.status', 'transfers.delivery_number', 'transfers.created_at', 'warehouse_locations.location_id', 'stations.location_id as origin', 'transfers.requested_palettes', 'transfers.requested_weight', 'garden_name', 'grade_name', 'invoice_number', 'lot_number', 'stock_id', 'registration', 'driver_name', 'id_number', 'drivers.phone', 'transporters.transporter_id', 'transporter_name', 'transfer_id', 'grade', 'garden', 'blend_number', 'blendBalances.client_name as client')
            ->where(['delivery_number' => base64_decode($id)])
            ->orderBy('garden_name', 'asc')
            ->orderBy('garden', 'asc')
            ->orderBy('invoice_number', 'asc')
            ->orderBy('blend_number', 'asc')
            ->get();
        return view('clerk::transfers.viewInternalTransfer')->with(['transfers' => $transfers]);
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
            ->select('ex_transfer_id', 'external_transfers.status', DB::raw('COALESCE(clients.client_name, blendBalances.client_name) as client_name'), DB::raw('COALESCE(warehouses.warehouse_name, other_destinations.warehouse_name) as warehouse_name'), DB::raw('COALESCE(stations.station_name, blendBalances.station_name) as station_name'), 'external_transfers.delivery_number', DB::raw('DATE(external_transfers.created_at) as created_at'), 'location_id', DB::raw('COALESCE(gardens.garden_name, blendBalances.garden) as garden_name'),DB::raw('COALESCE(grades.grade_name, blendBalances.grade) as grade_name'), DB::raw('COALESCE(delivery_orders.invoice_number, blendBalances.blend_number) as invoice_number'), 'external_transfers.transferred_palettes', 'external_transfers.transferred_weight', 'delivery_orders.lot_number', 'release_date', 'lot')
            ->where(['external_transfers.delivery_number' => base64_decode($id)])
            ->get();

        return view('clerk::transfers.viewExternalTransfer')->with(['transfers' => $transfers]);
    }

public function viewExternalTransfers(Request $request)
{
    $from = $request->get('from') ?? Carbon::now()->startOfMonth();
    $to = $request->get('to') ?? Carbon::now();

    $transfers = DB::table('external_transfers')
    ->leftJoin('blendBalances', function ($join) {
        $join->on('blendBalances.blend_balance_id', '=', 'external_transfers.stock_id')
            ->on('blendBalances.blend_id', '=', 'external_transfers.delivery_id');
    })
    // Force 1 row per delivery_id using MIN(client_id)
    ->leftJoin(DB::raw('(
        SELECT delivery_id, MIN(client_id) as client_id
        FROM delivery_orders
        WHERE deleted_at IS NULL
        GROUP BY delivery_id
    ) as delivery_orders'), 'delivery_orders.delivery_id', '=', 'external_transfers.delivery_id')
    ->leftJoin('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
    // Force 1 row per delivery_id using MIN(client_id)
    ->leftJoin(DB::raw('(
        SELECT delivery_id, MIN(client_id) as client_id
        FROM auctions
        WHERE deleted_at IS NULL
        GROUP BY delivery_id
    ) as auctions'), 'auctions.delivery_id', '=', 'external_transfers.delivery_id')
    ->leftJoin('clients as buyer', 'buyer.client_id', '=', 'auctions.client_id')
    // Force 1 row per stock_id
    ->leftJoin(DB::raw('(
        SELECT stock_id, MIN(station_id) as station_id
        FROM stock_ins
        GROUP BY stock_id
    ) as stock_ins'), 'stock_ins.stock_id', '=', 'external_transfers.stock_id')
    ->leftJoin('stations', 'stations.station_id', '=', 'stock_ins.station_id')
    ->leftJoin('warehouse_locations', 'warehouse_locations.location_id', '=', 'stations.location_id')
    ->leftJoin('warehouses', 'warehouses.warehouse_id', '=', 'external_transfers.warehouse_id')
    ->leftJoin('other_destinations', 'other_destinations.warehouse_id', '=', 'external_transfers.warehouse_id')
    ->leftJoin('transporters', 'transporters.transporter_id', '=', 'external_transfers.transporter_id')
    ->leftJoin('other_transporters', 'other_transporters.transporter_id', '=', 'external_transfers.transporter_id')
    ->leftJoin('drivers', 'drivers.driver_id', '=', 'external_transfers.driver_id')
    ->select([
        'external_transfers.delivery_number',
        'external_transfers.status',
        'external_transfers.warehouse_id',
        'external_transfers.registration',
        'external_transfers.release_date',
        'external_transfers.lot',
        'external_transfers.created_at',
        'warehouse_locations.location_id',

        DB::raw("COALESCE(clients.client_name, blendBalances.client_name, '') as client_name"),
        DB::raw("COALESCE(warehouses.warehouse_name, other_destinations.warehouse_name, '') as warehouse_name"),
        DB::raw("COALESCE(stations.station_name, blendBalances.station_name, '') as station_name"),
        DB::raw("COALESCE(transporters.transporter_id, other_transporters.transporter_id) as transporter_id"),
        DB::raw("COALESCE(transporters.transporter_name, other_transporters.transporter_name, '') as transporter_name"),

        'buyer.client_name as buyer_name',
        'drivers.driver_id',
        'drivers.driver_name',
        'drivers.phone',
        'drivers.id_number',

        DB::raw('SUM(external_transfers.transferred_palettes) as total_palettes'),
        DB::raw('SUM(external_transfers.transferred_weight) as total_weight')
    ])
    ->groupBy([
        'external_transfers.delivery_number',
        'external_transfers.lot',
    ])
    ->orderByDesc('external_transfers.delivery_number')
    ->whereNull('external_transfers.deleted_at')
    ->get();

    // Cache static lookups
    $warehouses = Cache::remember('all_warehouses', 3600, function () {
        return Warehouse::select('warehouse_id', 'warehouse_name')
            ->orderBy('warehouse_name')
            ->get()
            ->concat(
                OtherDestination::select('warehouse_id', 'warehouse_name')
                    ->orderBy('warehouse_name')
                    ->get()
            );
    });

    $transporters = Cache::remember('all_transporters', 3600, function () {
        return Transporter::select('transporter_id', 'transporter_name')
            ->orderBy('transporter_name')
            ->get()
            ->concat(
                OtherTransporter::select('transporter_id', 'transporter_name')
                    ->orderBy('transporter_name')
                    ->get()
            );
    });

    $users = Driver::select('id_number', 'driver_name')
        ->orderBy('driver_name')
        ->get();

    return view('clerk::transfers.externalTransfers')
        ->with([
            'transfers' => $transfers,
            'warehouses' => $warehouses,
            'transporters' => $transporters,
            'users' => $users,
            'from' => $from,
            'to' => $to
        ]);
}

    public function selectClients(Request $request)
    {
        $teas = DB::table('currentstock')->select('client_name', 'client_id')
            ->where('current_stock', '>', 0)
            ->where('current_weight', '>', 0)
            ->where(['station_id' => $request->warehouseId])
            ->select('client_id', 'client_name')
            ->orderBy('client_name')
            ->groupBy(['client_id', 'client_name'])
            ->get();

        $blends = DB::table('blendBalances')->where('current_packages', '>', 0)
            ->where('current_weight', '>', 0)
            ->where('current_packages', '>', 0)
            ->where(['station_id' => $request->warehouseId])
            ->select('client_id','client_name')
            ->get();

        $data = $teas->merge($blends)->unique(function ($item) {
            return $item->client_id . '|' . $item->client_name;
        })->values();

        return response()->json($data);
    }
    public function selectClient(Request $request)
    {
        $data = DB::table('currentstock')
            ->whereNotNull('current_stock')
            ->where('current_stock', '>', 0)
            ->whereNotNull('current_weight')
            ->where('current_weight', '>', 0)
            ->where(['station_id' => $request->warehouseId, 'client_id' => $request->clientId])
            ->where( 'current_stock', '>', 0)
            ->select('client_name', 'garden_name', 'grade_name', 'order_number', 'invoice_number', 'sale_number', 'current_stock', 'current_weight', 'stock_id')
            ->orderBy('client_name')
            ->get();

        return response()->json($data);
    }
    public function prepareInternalTransfer(Request $request)
    {
        $teas = DB::table('currentstock')->where('current_stock', '>', 0)
            ->where('current_weight', '>', 0)
            ->where(['station_id' => $request->station, 'client_id' => $request->client])
            ->select('client_id', 'stock_id', 'order_number', 'garden_name', 'grade_name', 'invoice_number', 'lot_number', 'current_stock', 'current_weight',  DB::raw("1 as type"))
            ->get();

        $balances = DB::table('blendBalances')->where('current_packages', '>', 0)
            ->where('current_weight', '>', 0)
            ->where(['client_id' => $request->client, 'station_id' => $request->station])
            ->select('client_id', 'blend_balance_id as stock_id', 'blend_number as order_number', 'garden as garden_name', 'grade as grade_name', 'blend_number as invoice_number', 'blend_number as lot_number', 'current_packages as current_stock', 'current_weight', DB::raw("2 as type"))
            ->get();

        $transfers = collect([])->merge($teas)->merge($balances)
            ->sortBy([
                ['garden_name', 'asc'],
                ['garden', 'asc'],
                ['invoice_number', 'asc'],
                ['lot_number', 'asc']
            ]);

        $client = Client::find($request->client);
        $destination = Station::find($request->station);
        $station = Station::find($request->location);
        $transporters = Transporter::all();
        $registrations = Transfers::pluck('registration')->toArray();
        $drivers = Driver::all();
        return view('clerk::transfers.prepareInternalTransfer')->with(['transfers' => $transfers, 'client' => $client, 'station' => $station, 'destination' => $destination, 'transporters' => $transporters, 'registrations' => $registrations, 'users' => $drivers]);
    }
    public function prepareExternalTransfer(Request $request)
    {
        $teas = DB::table('currentstock')->where('current_stock', '>', 0)
            ->where('current_weight', '>', 0)
            ->where(['client_id' => $request->client, 'station_id' => $request->location])
            ->select('client_id', 'stock_id', 'order_number', 'garden_name', 'grade_name', 'invoice_number', 'lot_number', 'current_stock', 'current_weight', DB::raw("1 as type"))
            ->orderBy('garden_name', 'asc')
            ->orderBy('invoice_number', 'asc')
            ->get();

        $balances = DB::table('blendBalances')->where('current_packages', '>', 0)
            ->where('current_weight', '>', 0)
            ->where(['client_id' => $request->client, 'station_id' => $request->location])
            ->select('client_id', 'blend_balance_id as stock_id', 'blend_number as order_number', 'garden as garden_name', 'grade as grade_name', 'blend_number as invoice_number', 'blend_number as lot_number', 'current_packages as current_stock', 'current_weight', DB::raw("2 as type"))
            ->get();

        $transfers = collect([])->merge($teas)->merge($balances)
            ->sortBy([
                ['garden_name', 'asc'],
                ['garden', 'asc'],
                ['invoice_number', 'asc'],
                ['lot_number', 'asc']
            ]);

        $client = Client::find($request->client);
        $destinations = Warehouse::all();
        $station = Station::find($request->location);
        $transporters = Transporter::all();
        $registrations = Transfers::pluck('registration')->toArray();
        $drivers = Driver::all();
        return view('clerk::transfers.prepareExternalTransfer')->with(['transfers' => $transfers, 'client' => $client, 'station' => $station, 'destinations' => $destinations, 'transporters' => $transporters, 'registrations' => $registrations, 'users' => $drivers]);
    }
    public function registerInternalRequest(Request $request)
    {
      $requestData = json_decode($request->allDeliveries, true);
      if(empty($requestData)){
          return redirect()->route('clerk.viewInternalTransfers')->with('error', 'Oops! Data not well captured');
      }
        if (isset($requestData['deliveries']) && !empty($requestData['deliveries'])) {
            DB::beginTransaction();
            try {
                $customId = new CustomIds();
                $driver = Driver::where('id_number', $request->idNumber)->first();
                if ($driver || $request->idNumber === null){
                    $delID = Transfers::newDelivery();
                    foreach ($requestData['deliveries'] as $key => $delivery) {
                        $transferId = $customId->generateId();
                         $stock = StockIn::where('stock_id', $delivery['deliveryId'])->first()
                            ?? DB::table('blendBalances')->where('blend_balance_id', $delivery['deliveryId'])
                                ->select('blend_balance_id as stock_id', 'blend_id as delivery_id')
                                ->first();

                        $transfer = [
                            'stock_id' => $stock->stock_id,
                            'delivery_number' => $delID,
                            'transfer_id' => $transferId,
                            'driver_id' => $driver == null ? null : $driver->driver_id,
                            'delivery_id' => $stock->delivery_id,
                            'registration' => $request->registration,
                            'transporter_id' => $request->transporter,
                            'station_id' => $request->station,
                            'requested_palettes' => $delivery['palette'],
                            'requested_weight' => $delivery['weight'],
                            'destination' => $request->location,
                            'created_by' => auth()->user()->user_id,
                            'delivery_type' => 2
                        ];
                        Transfers::create($transfer);
                    }
                }else {
                    $driverId = $customId->generateId();
                    $newDriver = [
                        'driver_id' => $driverId,
                        'id_number' => $request->idNumber,
                        'driver_name' => strtoupper($request->driverName),
                        'phone' => $request->driverPhone
                    ];
                    Driver::create($newDriver);
                    $delID = Transfers::newDelivery();
                    foreach ($requestData['deliveries'] as $key => $delivery) {
                        $transferId = $customId->generateId();
//                        $stock = StockIn::where('stock_id', $delivery['deliveryId'])->first();
                        $stock = StockIn::where('stock_id', $delivery['deliveryId'])->first()
                            ?? DB::table('blendBalances')->where('blend_balance_id', $delivery['deliveryId'])
                                ->select('blend_balance_id as stock_id', 'blend_id as delivery_id')
                                ->first();
                        $transfer = [
                            'transfer_id' => $transferId,
                            'stock_id' => $stock->stock_id,
                            'delivery_number' => $delID,
                            'driver_id' => $driverId,
                            'delivery_id' => $stock->delivery_id,
                            'registration' => $request->registration,
                            'transporter_id' => $request->transporter,
                            'station_id' => $request->station,
                            'requested_palettes' => $delivery['palette'],
                            'requested_weight' => $delivery['weight'],
                            'destination' => $request->location,
                            'created_by' => auth()->user()->user_id,
                            'delivery_type' => 2
                        ];
                        Transfers::create($transfer);
                    }
                }
                $this->logger->create();
                DB::commit();
                return redirect()->route('clerk.viewInternalTransfers')->with('success', 'Success! Transfer created successfully');
            } catch (Exception $e) {
                // Rollback the transaction if an exception occurs
                DB::rollback();
                // Handle or log the exception
                return redirect()->route('clerk.viewInternalTransfers')->with('error', 'Oops! An error occurred please try again '.$e->getMessage());
            }
        }else {
            return redirect()->back()->with('error', "Oops! You need to select at least 1 tea and the number of palettes and weight you are requesting to proceed");
        }
    }
    public function registerExternalRequest(Request $request)
    {
        // Handle custom warehouse
        if ($request->warehouse === 'other') {
            $oWarehouse = OtherDestination::where('warehouse_name', $request->warehouse_other)->first();
            if ($oWarehouse) {
                $warehouseId = $oWarehouse->warehouse_id;
            } else {
                $destination = [
                    'warehouse_id' => (new CustomIds())->generateId(),
                    'warehouse_name' => $request->warehouse_other
                ];
                OtherDestination::create($destination);
                $warehouseId = $destination['warehouse_id'];
            }
        }
        // Handle custom transporter
        if ($request->transporter === 'other') {
            $existingTransporter = OtherTransporter::where('transporter_name', $request->transporter_other)->first();
            if ($existingTransporter) {
                $transporterId = $existingTransporter->transporter_id;
            } else {
                $transporterDetails = [
                    'transporter_id' => (new CustomIds())->generateId(),
                    'transporter_name' => $request->transporter_other
                ];
                OtherTransporter::create($transporterDetails);
                $transporterId = $transporterDetails['transporter_id'];
            }
        }
        $requestData = json_decode($request->allDeliveries, true);
        if (isset($requestData['deliveries']) && !empty($requestData['deliveries'])) {
            DB::beginTransaction();
            try {
                $customId = new CustomIds();
                $driver = Driver::where('id_number', $request->idNumber)->first();
                if ($driver || $request->idNumber === null) {
                    $delID = ExternalTransfer::newDelivery();
                    foreach ($requestData['deliveries'] as $delivery) {
                        $transferId = $customId->generateId();
                        $stock = StockIn::where('stock_id', $delivery['deliveryId'])->first()
                            ?? DB::table('blendBalances')->where('blend_balance_id', $delivery['deliveryId'])
                                ->select('blend_balance_id as stock_id', 'blend_id as delivery_id')
                                ->first();
                       $transfer = [
                            'stock_id' => $stock->stock_id,
                            'ex_transfer_id' => $transferId,
                            'delivery_number' => $delID,
                            'driver_id' => $driver == null ? null : $driver->driver_id,
                            'delivery_id' => $stock->delivery_id,
                            'warehouse_id' => $request->warehouse === 'other' ? $warehouseId : $request->warehouse,
                            'registration' => $request->registration,
                            'loading_number' => $request->loading_number,
                            'transporter_id' => $request->transporter === 'other' ? $transporterId : $request->transporter,
                            'transferred_palettes' => $delivery['palette'],
                            'transferred_weight' => $delivery['weight'],
                            'created_by' => auth()->user()->user_id,
                            'status' => 0,
                            'buyer_id' => $delivery['buyerId']
                        ];
                        ExternalTransfer::create($transfer);
                    }
                } else {
                    $driverId = $customId->generateId();
                    $newDriver = [
                        'driver_id' => $driverId,
                        'id_number' => $request->idNumber,
                        'driver_name' => strtoupper($request->driverName),
                        'phone' => $request->driverPhone
                    ];
                    Driver::create($newDriver);
                    $delID = ExternalTransfer::newDelivery();
                    foreach ($requestData['deliveries'] as $delivery) {
                        $transferId = $customId->generateId();
                        $stock = StockIn::where('stock_id', $delivery['deliveryId'])->first()
                            ?? DB::table('blendBalances')->where('blend_balance_id', $delivery['deliveryId'])
                                ->select('blend_balance_id as stock_id', 'blend_id as delivery_id')
                                ->first();
                       $transfer = [
                            'stock_id' => $stock->stock_id,
                            'ex_transfer_id' => $transferId,
                            'delivery_number' => $delID,
                            'driver_id' => $driverId,
                            'delivery_id' => $stock->delivery_id,
                            'warehouse_id' => $request->warehouse === 'other' ? $warehouseId : $request->warehouse,
                            'registration' => $request->registration,
                            'loading_number' => $request->loading_number,
                            'transporter_id' => $request->transporter === 'other' ? $transporterId : $request->transporter,
                            'transferred_palettes' => $delivery['palette'],
                            'transferred_weight' => $delivery['weight'],
                            'created_by' => auth()->user()->user_id,
                            'status' => 0,
                           'buyer_id' => $delivery['buyerId']
                        ];

                        ExternalTransfer::create($transfer);
                    }
                }
                $this->logger->create();
                DB::commit();
                return redirect()->route('clerk.viewExternalTransfers')->with('success', 'Success! External transfer created successfully');
            } catch (Exception $e) {
//                // Rollback the transaction if an exception occurs
                DB::rollback();
//                // Handle or log the exception
                return redirect()->route('clerk.viewExternalTransfers')->with('error', 'Oops! An error occurred please try again '.$e);
            }
        }
    }
    public function initiateTransfer($id)
    {
        if (auth()->user()->role_id == 3){
            Transfers::where('delivery_number', base64_decode($id))->update(['status' => 0]);
            $this->logger->create();
            return redirect()->back()->with('success', 'Success! Transfer request initiated successfully');
        }elseif(auth()->user()->role_id == 2 || auth()->user()->hasPermission('transfer.internal.approve') || auth()->user()->role_id ==  3 && auth()->user()->hasPermission('transfer.internal.approve')) {
            Transfers::where('delivery_number', base64_decode($id))->update(['status' => 1]);
            Approval::create([
                'approval_id' => (new CustomIds())->generateId(),
                'job_id' => base64_decode($id),
                'user_id' => auth()->user()->user_id,
                'approval_date' => time(),
                'order' => 1
            ]);
            $this->logger->create();
            return redirect()->back()->with('success', 'Success! Transfer request approved successfully');
        }else{
            Transfers::where('delivery_number', base64_decode($id))->update(['status' => 2]);
            $this->logger->create();
            return redirect()->back()->with('success', 'Success! Transfer request release successfully');
        }
    }
    public function initiateExternalTransfer($id)
    {
        $transfer = ExternalTransfer::where('delivery_number', base64_decode($id));
        $transfer->update(['status' => 3]);
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Transfer request initiated successfully');
    }
    public function approveExternalTransfer($id)
    {
        ExternalTransfer::where('delivery_number', base64_decode($id))->update(['status' => 2]);
        Approval::create([
            'approval_id' => (new CustomIds())->generateId(),
            'job_id' => base64_decode($id),
            'user_id' => auth()->user()->user_id,
            'approval_date' => time(),
            'order' => 1
        ]);
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Transfer request approved successfully');
    }

    public function releaseExternalTransfer(Request $request, $id)
    {

        if (!Driver::where('id_number', $request->idNumber)->exists()){
            $driver = Driver::create([
                'driver_id' => (new CustomIds())->generateId(),
                'driver_name' => $request->driverName,
                'id_number' => $request->idNumber,
                'phone' => $request->driverPhone,
            ]);

            $driverId = $driver['driver_id'];
        }else{
            $driverId = Driver::where('id_number', $request->idNumber)->first()->driver_id;
        }

        if($request->transporter === 'other'){
            $existingTransporter = Transporter::where('transporter_name', $request->transporter_other)->first();
            if ($existingTransporter) {
                $transporterId = $existingTransporter->transporter_id;
            } else {
                $transporterDetails = [
                    'transporter_id' => (new CustomIds())->generateId(),
                    'transporter_name' => $request->transporter_other,
                    'transporter_type' => 2,
                    'created_by' => auth()->user()->user_id
                ];
                Transporter::create($transporterDetails);
                $transporterId = $transporterDetails['transporter_id'];
            }
        }else{
            $transporterId = $request->transporter;
        }

        list($deliveryId, $lot) = explode(':', base64_decode($id));

        ExternalTransfer::where(['delivery_number' => $deliveryId, 'lot' => $lot])->update([
            'warehouse_id' => $request->warehouse_id,
            'status' => 4,
            'driver_id' => $driverId,
            'registration' => $request->registration,
            'transporter_id' => $transporterId
        ]);
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Transfer request released successfully');
    }

    public function releaseTransfer(Request $request)
    {
        $transfer = ExternalTransfer::where('ex_transfer_id', $request->releaseId)->first();

        if (!$transfer) {
            return response()->json([
                'success' => false,
                'message' => 'Transfer not found'
            ], 404);
        }

        if ($request->status === 'true') {
            $releaseDate = now();
            $today = $releaseDate->toDateString();

            // Check if there are already releases today
            $existingReleaseToday = ExternalTransfer::whereDate('release_date', $today)
                ->where('delivery_number', $transfer->delivery_number)
                ->whereNotNull('lot')
                ->first();

            $delNumber = $transfer->delivery_number;

            if ($existingReleaseToday) {
                // Use the same lot number as existing releases today
                $lotNumber = $existingReleaseToday->lot;
            } else {
                // This is the first release today, get the next lot number
                $lotNumber = $this->getNextLotNumber($today, $delNumber);
            }

            $transfer->update([
                'release_date' => $releaseDate,
                'lot' => $lotNumber
            ]);

            if (Auction::where(['stock_id' => $transfer->stock_id])->exists()) {
                Auction::where(['stock_id' => $transfer->stock_id])->update(['release_date' => now()->format('Y-m-d')]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Transfer released successfully with Lot ' . $lotNumber,
                'lot_number' => $lotNumber,
                'release_date' => $releaseDate->format('Y-m-d')
            ]);
        } else {
            $transfer->update([
                'release_date' => null,
                'lot' => null
            ]);

            if (Auction::where(['stock_id' => $transfer->stock_id])->exists()) {
                Auction::where(['stock_id' => $transfer->stock_id])->update(['release_date' => null]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Transfer release cancelled successfully',
                'release_date' => ''
            ]);
        }
    }

    private function getNextLotNumber($date, $delNumber)
    {
        // Get all distinct dates that have releases up to the given date
        $existingReleaseDates = ExternalTransfer::whereNotNull('release_date')
            ->whereNotNull('lot')
            ->whereDate('release_date', '<=', $date)
            ->where('delivery_number', $delNumber)
            ->select(DB::raw('DATE(release_date) as release_day'), 'lot')
            ->distinct()
            ->orderBy('release_day')
            ->pluck('lot')
            ->unique()
            ->values()
            ->toArray();

        $lotIndex = count($existingReleaseDates);

        // Convert number to letter (0=A, 1=B, 2=C, etc.)
        if ($lotIndex < 26) {
            return chr(65 + $lotIndex); // A-Z
        } else {
            $first = chr(65 + floor($lotIndex / 26) - 1);
            $second = chr(65 + ($lotIndex % 26));
            return $first . $second;
        }
    }

    public function serviceRequest($id)
    {
        Transfers::where('delivery_number', base64_decode($id))->update([
            'status' => 3
        ]);
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Transfer request serviced and stock updated successfully');
    }
    public function receiveInterTransferRequest(Request $request, $id)
    {
       $request->validate([
            'idNumber' => 'required',
            'driverName' => 'required',
            'driverPhone' => 'required',
        ]);
        $transfers = json_decode($request->allDeliveries, TRUE);
        DB::beginTransaction();
        try {
            foreach ($transfers['deliveries'] as $transferItem) {
                $transfer = Transfers::where('transfer_id', $transferItem['deliveryId'])->first();

                // Get or create driver
                $driver = Driver::where('id_number', $request->idNumber)->first();

                if (!$driver) {
                    $driverId = (new CustomIds())->generateId();
                    $driver = Driver::create([
                        'driver_id' => $driverId,
                        'id_number' => $request->idNumber,
                        'driver_name' => strtoupper($request->driverName),
                        'phone' => $request->driverPhone
                    ]);
                } else {
                    $driverId = $driver->driver_id;
                }

                // Common transfer update data
                $transferUpdateData = [
                    'status' => 4,
                    'driver_id' => $driverId,
                    'registration' => $request->registration,
                    'transporter_id' => $request->transporter,
                    'requested_palettes' => $transferItem['palette'],
                    'requested_weight' => $transferItem['weight'],
                ];

                if ($transferItem['transferType'] == 1) {
                    // Handle stock transfer
                    $stockId = (new CustomIds())->generateId();
                    $stock = [
                        'stock_id' => $stockId,
                        'delivery_id' => $transfer->delivery_id,
                        'station_id' => $transfer->destination,
                        'date_received' => time(),
                        'delivery_number' => $transfer->delivery_number,
                        'delivery_type' => 2,
                        'warehouse_bay' => $request->bayId,
                        'total_weight' => $transferItem['weight'],
                        'total_pallets' => $transferItem['palette'],
                        'pallet_weight' => 0,
                        'package_tare' => 0,
                        'net_weight' => $transferItem['weight'],
                        'user_id' => auth()->user()->user_id,
                        'registration' => $request->registration,
                        'driver_id' => $driverId,
                        'transporter_id' => $request->transporter,
                    ];
                    StockIn::create($stock);
                } else {
                    // Handle blend balance transfer
                    $bb = BlendBalance::where('blend_balance_id', $transfer->stock_id)->first();
                    $station = WarehouseBay::where('bay_id', $request->bayId)->first();

                    $balance = [
                        'blend_balance_id' => (new CustomIds())->generateId(),
                        'blend_id' => $transfer->delivery_id,
                        'ex_packages' => $transferItem['palette'],
                        'unit_weight' => (int) ($transferItem['weight'] / $transferItem['palette']),
                        'net_weight' => $transferItem['weight'],
                        'gross_weight' => $transferItem['weight'],
                        'station_id' => $station->station_id,
                        'type' => $bb->type,
                    ];
                    BlendBalance::create($balance);
                }

                // Update transfer record
                 $transfer->update($transferUpdateData);
            }
            $this->logger->create();
            DB::commit();
            return redirect()->route('clerk.viewInternalTransfers')->with('success', 'Success! Transfer request received successfully');
        } catch (Exception $e) {
            // Rollback the transaction if an exception occurs
            DB::rollback();
            // Handle or log the exception
            return redirect()->back()->with('error', 'Oops! An error occurred please try again');
        }
    }
    public function updateInterTransferRequest(Request $request, $id)
    {
        $request->validate([
            'pallets' => 'required|numeric',
            'weight' => 'required|numeric',
        ]);
        DB::beginTransaction();
        try{
            $driver = Driver::where('id_number', $request->idNumber)->first();
            if ($driver){
                Transfers::where('transfer_id', $id)->update([
                    'requested_palettes' => $request->pallets,
                    'requested_weight' => $request->weight,
                    'warehouse_id' => $request->warehouse,
                    'registration' => $request->registration,
                    'transporter_id' => $request->transporter,
                ]);
            }else{
                $customId = new CustomIds();
                $driverId = $customId->generateId();

                $driver = [
                    'driver_id' => $driverId, 'id_number' => $request->idNumber, 'driver_name' => $request->driverName, 'phone' => $request->driverPhone
                ];
                Driver::create($driver);
                Transfers::where('transfer_id', $id)->update([
                    'requested_palettes' => $request->pallets,
                    'requested_weight' => $request->weight,
                    'warehouse_id' => $request->warehouse,
                    'registration' => $request->registration,
                    'transporter_id' => $request->transporter,
                    'driver_id' => $driverId
                ]);
            }
            $this->logger->create();
            DB::commit();
            return redirect()->back()->with('success', 'Success! Transfer request updated successfully');
        } catch (Exception $e) {
            // Rollback the transaction if an exception occurs
            DB::rollback();
            // Handle or log the exception
            return redirect()->back()->with('error', 'Oops! An error occurred please try again');
        }
    }
    public function updateExternalTransferRequest (Request $request, $id)
    {
        $request->validate([
            "pallets" => 'required',
            "weight" => 'required',
            "warehouse" => 'required',
            "transporter" => 'required',
            "registration" => 'required',
            "idNumber" => 'required',
            "driverName" => 'required',
            "driverPhone" => 'required'
        ]);
        DB::beginTransaction();
        try {
            $driver = Driver::where('id_number', $request->idNumber)->first();
            if ($driver){
                ExternalTransfer::where('ex_transfer_id', $id)->update([ 'warehouse_id' => $request->warehouse, 'registration' => $request->registration, 'transporter_id' => $request->transporter, 'transferred_palettes' => $request->pallets, 'transferred_weight' => $request->weight
                ]);
            }else{
                $customId = new CustomIds();
                $driverId = $customId->generateId();
                $driver = [
                    'driver_id' => $driverId, 'id_number' => $request->idNumber, 'driver_name' => $request->driverName, 'phone' => $request->driverPhone
                ];
                Driver::create($driver);
                ExternalTransfer::where('ex_transfer_id', $id)->update([ 'driver_id' => $driverId, 'warehouse_id' => $request->warehouse, 'registration' => $request->registration, 'transporter_id' => $request->transporter, 'transferred_palettes' => $request->pallets, 'transferred_weight' => $request->weight
                ]);
            }
            $this->logger->create();
            DB::commit();
            return redirect()->back()->with('success', 'Success! Transfer request was successfully updated');
        } catch (Exception $e) {
            // Rollback the transaction if an exception occurs
            DB::rollback();
            // Handle or log the exception
            return redirect()->back()->with('error', 'Oops! An error occurred please try again');
        }
    }
    public function cancelInterTransferRequest($id)
    {
        Transfers::where('transfer_id', $id)->delete();
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Transfer request canceled successfully');
    }
    public function cancelExternalTransferRequest($id)
    {
        ExternalTransfer::where('ex_transfer_id', $id)->delete();
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Transfer request canceled successfully');
    }
    public function viewDeliveryOrders()
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $orders = DeliveryOrder::join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->join('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
            ->join('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
            ->join('brokers', 'brokers.broker_id', '=', 'delivery_orders.broker_id')
            ->join('warehouses', 'warehouses.warehouse_id', '=', 'delivery_orders.warehouse_id')
            ->leftJoin('sub_warehouses', 'sub_warehouses.sub_warehouse_id', '=', 'delivery_orders.sub_warehouse_id')
            ->leftJoin('loading_instructions', function ($join) {
                $join->on('loading_instructions.delivery_id', '=', 'delivery_orders.delivery_id')
                    ->whereNull('loading_instructions.deleted_at');
            })
            ->select('delivery_orders.delivery_id','gardens.garden_name', 'grades.grade_name', 'brokers.broker_name', 'warehouses.warehouse_name', 'clients.client_name', 'delivery_orders.invoice_number', 'loading_instructions.loading_number', 'sub_warehouses.sub_warehouse_name', 'locality', 'lot_number', 'tea_type', 'collection')
            ->where('delivery_orders.delivery_type', 1)
            ->whereNull('delivery_orders.deleted_at')
            ->orderBy('delivery_orders.created_at', 'desc')
            ->orderBy('delivery_orders.status', 'asc')
            ->whereMonth('delivery_orders.created_at', $currentMonth)
            ->whereYear('delivery_orders.created_at', $currentYear)
            ->get();
        $clients = Client::all();
        return view('clerk::DOS.index')->with(['orders' => $orders, 'clients' => $clients]);
    }
    public function addDeliveryOrders()
    {
        $clients = Client::all();
        $gardens = Garden::all();
        $grades = Grade::all();
        $warehouses = Warehouse::all();
        $brokers = Broker::all();
        return view('clerk::DOS.addDO')->with(['clients' => $clients, 'gardens' => $gardens, 'grades' => $grades, 'warehouses' => $warehouses, 'brokers' => $brokers]);
    }
    public function registerDeliveryOrder(Request $request)
    {
        $request->validate([
            'client_id' => 'required',
            'order_number' => ['required', Rule::unique('delivery_orders')->where(function ($query) use ($request) {
                return $query->where('invoice_number', $request->invoice_number);
            })],
            'invoice_number' => ['required', Rule::unique('delivery_orders')->where(function ($query) use ($request) {
                return $query->where('order_number', $request->order_number);
            })],
            'tea_id' => 'required',
            'garden_id' => 'required',
            'grade_id' => 'required',
            'packet' => 'required|string',
            'package' => 'required',
            'weight' => 'required|string',
            'warehouse_id' => 'required',
            'broker_id' => 'required',
            'sale_number' => [
                new RequiredIf($request->tea_id == 1),
            ],
            'lot_number' => 'required|string',
            'sale_date' => 'required|date',
            'prompt_date' => 'required|date|after:sale_date',
            'branch' => 'required',
            'locality' => 'required|numeric',
            'tea_type' => 'required'
        ]);
        $exits = DeliveryOrder::where(['client_id' => $request->client_id, 'invoice_number' => $request->invoice_number, 'garden_id' => $request->garden_id])->exists();
        if ($exits){
            return redirect()->back()->with('error', 'Oops! The invoice number for this client exists already exists');
        }
        $customId = new CustomIds();
        $deliveryId = $customId->generateId();
        $order = [
            'delivery_id' => $deliveryId,
            'order_number' => $request->order_number,
            'client_id' => $request->client_id,
            'tea_id' => $request->tea_id,
            'tea_type' => $request->tea_type,
            'garden_id' => $request->garden_id,
            'grade_id' => $request->grade_id,
            'packet' => $request->packet,
            'package' => $request->package,
            'weight' => $request->weight,
            'warehouse_id' => $request->warehouse_id,
            'broker_id' => $request->broker_id,
            'sale_number' => $request->tea_id == 1 ? $request->sale_number : ($request->tea_id == 2 ? 'P/Sale' : ($request->tea_id == 3 ? 'F/Sale' : 'B/Rem')),
            'invoice_number' => $request->invoice_number,
            'lot_number' => $request->lot_number,
            'sale_date' => $request->sale_date,
            'prompt_date' => $request->prompt_date,
            'sub_warehouse_id' => $request->branch,
            'locality' => $request->locality,
            'created_by' => auth()->user()->user_id,
            'delivery_type' => 1,
        ];
        DeliveryOrder::create($order);
        $this->logger->create();
        return redirect()->route('clerk.viewDeliveryOrders')->with('success', 'Successful! Delivery order created successfully');
    }
    public function getDoToEdit($id)
    {
        $order = DeliveryOrder::leftJoin('sub_warehouses', 'sub_warehouses.sub_warehouse_id', '=', 'delivery_orders.sub_warehouse_id')->select('delivery_orders.*', 'sub_warehouse_name')->findOrFail($id);
        $gardens = Garden::orderBy('garden_name', 'asc')->get();
        $warehouses = Warehouse::orderBy('warehouse_name', 'asc')->get();
        $grades = Grade::orderBy('grade_name', 'asc')->get();
        $brokers = Broker::orderBy('broker_name', 'asc')->get();
        $clients = Client::orderBy('client_name', 'asc')->get();
        $transporters  = Transporter::orderBy('transporter_name')->get();
        $users  = Driver::orderBy('id_number', 'asc')->get();
        $registrations = LoadingInstruction::orderBy('registration', 'asc')->get()->groupBy('registration');
        $stations = Station::where('status', 1)->orderBy('station_name', 'asc')->get();

        return view('clerk::DOS.editDO')->with(['order' => $order, 'gardens' => $gardens, 'warehouses' => $warehouses, 'grades' => $grades, 'brokers' => $brokers, 'clients' => $clients, 'transporters' => $transporters, 'users' => $users, 'registrations' => $registrations, 'allStations' => $stations]);
    }
    public function getDoToDelete($id)
    {
        DeliveryOrder::where('delivery_id', $id)->delete();
        return redirect()->route('clerk.viewDeliveryOrders')->with('success', 'Successful! Delivery order updated successfully');
    }
    public function updateDeliveryOrder(Request $request, $id)
    {
        $request->validate([
            'order_number' => 'required',
            'tea_id' => 'required',
            'client_id' => 'required',
            'garden_id' => 'required',
            'grade_id' => 'required',
            'packet' => 'required|string',
            'package' => 'required',
            'weight' => 'required|string',
            'invoice_number' => 'required|string',
            'tea_type' => 'required'
        ]);
        $order = [
            'order_number' => $request->order_number,
            'tea_id' => $request->tea_id,
            'tea_type' => $request->tea_type,
            'client_id' => $request->client_id,
            'garden_id' => $request->garden_id,
            'grade_id' => $request->grade_id,
            'packet' => $request->packet,
            'package' => $request->package,
            'weight' => $request->weight,
            'warehouse_id' => $request->warehouse_id,
            'broker_id' => $request->broker_id,
            'sale_number' => $request->tea_id == 1 ? $request->sale_number : ($request->tea_id == 4 ? 'B/Rem' : ($request->tea_id == 3 ? 'F/Sale' : 'P/Sale')),
            'invoice_number' => $request->invoice_number,
            'lot_number' => $request->lot_number,
            'sale_date' => $request->sale_date,
            'prompt_date' => $request->prompt_date,
            'sub_warehouse_id' => $request->branch,
            'locality' => $request->locality,
            'production_date' => $request->production_date,
            'expiry_date' => $request->expiry_date,
        ];
        DeliveryOrder::where('delivery_id', $id)->update($order);
        $this->logger->create();
        return redirect()->route('clerk.viewDeliveryOrders')->with('success', 'Successful! Delivery order updated successfully');
    }
    public function registerWarehouse(Request $request)
    {
        $request->validate([
            'warehouse' => 'required|string|unique:warehouses,warehouse_name',
        ]);
        $customId = new CustomIds();
        $warehouseId = $customId->generateId();
        $warehouse = [
            'warehouse_id' => $warehouseId,
            'warehouse_name' => strtoupper($request->warehouse),
            'phone' => $request->phone,
            'address' => strtoupper($request->address),
            'created_by' => auth()->user()->user_id
        ];
        Warehouse::create($warehouse);
        $this->logger->create();
        return redirect()->back()->with('success', 'Successful! New warehouse added successfully');
    }
    public function filterWarehouseBranch(Request $request)
    {
        $data = SubWarehouse::where(['warehouse_id' => $request->warehouseId, 'status' => 1])->orderBy('sub_warehouse_name', 'asc')->get();
        return response()->json($data);
    }
    public function registerTeaGrade(Request $request)
    {
        $request->validate([
            'grade' => 'required|string|unique:grades,grade_name'
        ]);
        $customId = new CustomIds();
        $gradeId = $customId->generateId();
        $grade = [
            'grade_id' => $gradeId,
            'grade_name' => $request->grade,
            'description' => $request->description,
            'created_by' => auth()->user()->user_id,
        ];
        Grade::create($grade);
        $this->logger->create();
        return redirect()->back()->with('success', 'Successful! New tea grade added successfully');
    }
    public function registerGarden(Request $request)
    {
        $request->validate([
            'garden' => 'required|string|unique:gardens,garden_name',
            'garden_type' => 'required'
        ]);
        $customId = new CustomIds();
        $gardenId = $customId->generateId();
        $garden = [
            'garden_id' => $gardenId,
            'garden_name' => strtoupper($request->garden),
            'garden_type' => $request->garden_type,
            'created_by' => auth()->user()->user_id,
            'description' => $request->description,
            'status' => 1,
        ];
        Garden::create($garden);
        $this->logger->create();
        return redirect()->back()->with('success', 'Successful! New garden added successfully');
    }
    public function registerBroker(Request $request)
    {
        $request->validate([
            'broker' => 'required|string|unique:clients,client_name',
            'broker_type' => 'required',
            'phone' => [
                'nullable',
                'max:15', 'min:9',
                Rule::unique('clients', 'phone')->ignore($request->input('phone'), 'phone'),
            ],
            'email' => [
                'nullable',
                Rule::unique('clients', 'email')->ignore($request->input('email'), 'email'),
            ], 'address' => [
                'nullable',
                Rule::unique('clients', 'address')->ignore($request->input('address'), 'address'),
            ],
        ]);
        $customId = new CustomIds();
        $brokerId = $customId->generateId();
        $client = [
            'broker_id' => $brokerId,
            'broker_name' => strtoupper($request->broker),
            'broker_type' => $request->broker_type,
            'email' => strtolower($request->email),
            'phone' => $request->phone,
            'address' => strtoupper($request->address),
            'created_by' => auth()->user()->user_id,
        ];
        Broker::create($client);
        $this->logger->create();
        return redirect()->back()->with('success', 'Successful! New broker added successfully');
    }
    public function registerTransporter(Request $request)
    {
        $request->validate([
            'transporter' => 'required|string|unique:transporters,transporter_name',
            'transporter_type' => 'required',
        ]);
        $customId = new CustomIds();
        $transporterId = $customId->generateId();
        $transporter = [
            'transporter_id' => $transporterId,
            'transporter_name' => strtoupper($request->transporter),
            'transporter_type' => $request->transporter_type,
            'description' => $request->description,
            'created_by' => auth()->user()->user_id,
        ];
        Transporter::create($transporter);
        $this->logger->create();
        return redirect()->back()->with('success', 'Successful! New transporter added successfully');
    }
    public function fetchIdNumber(Request $request)
    {
        $data = Driver::where('id_number', $request->idNumber)->latest()->first();
        $driver = [
            'driver_name' => $data->driver_name,
            'driver_id' => $data->id_number,
            'driver_phone' => $data->phone,
        ];
        return response()->json($driver);
    }
    public function createLLI(Request $request)
    {
        $request->validate([
            'station' => 'required',
            'deliveryIds' => 'required',
        ]);
        DB::beginTransaction();
        try{
            $loadingNumber = LoadingInstruction::newTCI();
            $driver = Driver::where('id_number', $request->idNumber)->first();
            if ($driver || $request->idNumber == null){
                $customId = new CustomIds();
                foreach ($request->deliveryIds as $delivery){
                    $loadingId = $customId->generateId();
                    $load = [
                        'loading_id' => $loadingId,
                        'station_id' =>  $request->station,
                        'loading_number' => $loadingNumber,
                        'transporter_id' => $request->transporter,
                        'delivery_id' => $delivery,
                        'registration' => strtoupper($request->registration),
                        'driver_id' => $driver == null ? null : $driver->driver_id,
                        'created_by' => auth()->user()->user_id,
                        'status' => 1,
                    ];
                    if (LoadingInstruction::create($load)){
                        DeliveryOrder::where('delivery_id', $delivery)->update(['status' => 1]);
                    }
                }
            }else{
                $customId = new CustomIds();
                $driverId = $customId->generateId();
                $newDriver = [
                    'driver_id' => $driverId,
                    'id_number' => $request->idNumber,
                    'driver_name' => strtoupper($request->driverName),
                    'phone' => $request->driverPhone
                ];
                Driver::create($newDriver);
                foreach ($request->deliveryIds as $delivery){
                    $loadingId = $customId->generateId();
                    $load = [
                        'loading_id' => $loadingId,
                        'station_id' =>  $request->station,
                        'loading_number' => $loadingNumber,
                        'transporter_id' => $request->transporter,
                        'delivery_id' => $delivery,
                        'registration' => strtoupper($request->registration),
                        'driver_id' => $driverId,
                        'created_by' => auth()->user()->user_id,
                        'status' => 1,
                    ];
                    if (LoadingInstruction::create($load)){
                        DeliveryOrder::where('delivery_id', $delivery)->update(['status' => 1]);
                    }
                }
            }
            $this->logger->create();
            DB::commit();
            return redirect()->back()->with('success', 'Successful! Local loading instructions added successfully');
        } catch (Exception $e) {
            // Rollback the transaction if an exception occurs
            DB::rollback();
            // Handle or log the exception
            return redirect()->back()->with('error', 'Oops! An error occurred please try again');
        }
    }
    public function downloadLLI($id)
    {
        list($loadNumber, $type) = explode(':', base64_decode($id));
        $orders = DeliveryOrder::join('users', 'users.user_id', '=', 'delivery_orders.created_by')
            ->join('user_infos', 'user_infos.user_id', '=', 'delivery_orders.created_by')
            ->join('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
            ->join('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
            ->join('brokers', 'brokers.broker_id', '=', 'delivery_orders.broker_id')
            ->join('warehouses', 'warehouses.warehouse_id', '=', 'delivery_orders.warehouse_id')
            ->join('sub_warehouses', 'sub_warehouses.sub_warehouse_id', '=', 'delivery_orders.sub_warehouse_id')
            ->join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->leftJoin('loading_instructions', 'loading_instructions.delivery_id', '=', 'delivery_orders.delivery_id')
            ->leftJoin('drivers', 'drivers.driver_id', '=', 'loading_instructions.driver_id')
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'loading_instructions.transporter_id')
            ->leftJoin('stations', 'stations.station_id', '=', 'loading_instructions.station_id')
            ->leftJoin('users as loading_user', 'loading_user.user_id', '=', 'loading_instructions.created_by')
            // ->leftJoin('stock_ins', 'stock_ins.delivery_id', '=', 'delivery_orders.delivery_id')
            ->leftJoin('stock_ins', function ($join) {
                $join->on('stock_ins.delivery_id', '=', 'delivery_orders.delivery_id')
                     ->where('stock_ins.date_received', '=', function ($query) {
                         $query->selectRaw('MIN(date_received)')
                               ->from('stock_ins')
                               ->whereColumn('stock_ins.delivery_id', 'delivery_orders.delivery_id');
                     });
            })
            ->whereNull('loading_instructions.deleted_at')
            ->select('users.username','users.user_id', 'gardens.garden_name', 'grades.grade_name', 'brokers.broker_name', 'warehouses.warehouse_name', 'clients.client_name', 'delivery_orders.*', 'transporters.transporter_id', 'transporters.transporter_name', 'drivers.driver_id', 'drivers.driver_name', 'drivers.id_number', 'drivers.phone', 'loading_instructions.loading_id', 'loading_instructions.loading_number', 'loading_instructions.status as load_status', 'loading_instructions.registration', 'loading_instructions.created_by as load_user_id', 'loading_user.username as load_user', 'stations.station_name', 'loading_instructions.deleted_at', 'stock_ins.total_pallets', 'stock_ins.total_weight', 'first_name', 'surname', 'sub_warehouse_name')
            ->orderBy('gardens.garden_name', 'asc')
            ->orderBy('grades.grade_name', 'asc')
            ->orderBy('delivery_orders.invoice_number', 'asc')
            ->where('loading_number', $loadNumber)
            ->get();

        if ($type == 2){
            return Excel::download(new ExportTCI($orders), $loadNumber.' '.time().'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        }

        // Prepare user info
        $details = $orders->first();
        $prepared = UserInfo::where('user_id', $details->user_id)->first();
        $user = $prepared->first_name . ' ' . $prepared->surname;
        $printed = auth()->user()->user;
        $by = $printed->first_name . ' ' . $printed->surname;

        // Render Blade view
        $html = View::make('clerk::downloads.tci', compact('orders', 'details', 'user', 'by'))->render();

        // Initialize mPDF with settings
        $mpdf = new Mpdf([
            'tempDir' => storage_path('app/mpdf_temp'),
            'mode'        => 'utf-8',
            'format'      => 'A4-L', // Landscape
            'orientation' => 'L',
            'margin_top'    => 2,
            'margin_bottom' => 7,
            'margin_left'   => 5,
            'margin_right'  => 5,
            'setAutoBottomMargin' => 'stretch'
        ]);


        // Set footer for all pages
        $mpdf->SetHTMLFooter('
            <table width="100%">
                <tr>
                    <td align="left">Printed by: <strong>' . $by . '</strong></td>
                    <td align="center">Page {PAGENO} of {nbpg}</td>
                    <td align="right">Prepared by: <strong>' . $user . '</strong></td>
                </tr>
            </table>
        ');

        // Write HTML content
        $mpdf->WriteHTML($html);

        // Generate PDF filename
        $pdfFileName = $details->loading_number.'.pdf';

        // Output PDF as downloadable file
        return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
        ]);
    }
    public function revertLLI(Request $request)
    {
        $request->validate([
            'doNumbers' => 'required'
        ]);
        $lli = LoadingInstruction::whereIn('loading_id', $request->doNumbers);
        DeliveryOrder::whereIn('delivery_id', $lli->pluck('delivery_id'))->update(['status' => 0]);
        $lli->delete();
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Loading instructions canceled');
    }
    public function updateLLI(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $liNumber = base64_decode($id);
            $driver = Driver::where('id_number', $request->idNumber)->first();
            if ($request->delNumbers === null) {
                if ($driver !== null){
                    LoadingInstruction::where('loading_number', $liNumber)->update(['driver_id' => $driver->driver_id, 'transporter_id' => $request->transporter, 'registration' => strtoupper($request->registration)]);
                }else{
                    $customId = new CustomIds();
                    $driverId = $customId->generateId();
                    $newDriver = [
                        'driver_id' => $driverId,
                        'id_number' => $request->idNumber,
                        'driver_name' => strtoupper($request->driverName),
                        'phone' => $request->driverPhone
                    ];
                    Driver::create($newDriver);
                    LoadingInstruction::where('loading_number', $liNumber)->update(['driver_id' => $driverId, 'transporter_id' => $request->transporter, 'registration' => strtoupper($request->registration)]);
                }
            }else{
                if ($driver || $request->idNumber === null ){
                    $customId = new CustomIds();
                    foreach ($request->delNumbers as $delivery){
                        $loadingId = $customId->generateId();
                        $load = [
                            'loading_id' => $loadingId,
                            'station_id' =>  $request->station,
                            'loading_number' => $liNumber,
                            'transporter_id' => $request->transporter,
                            'delivery_id' => $delivery,
                            'registration' => strtoupper($request->registration),
                            'driver_id' => $driver == null ? null : $driver->driver_id,
                            'created_by' => auth()->user()->user_id,
                            'status' => 1,
                        ];
                        if (LoadingInstruction::create($load)){
                            DeliveryOrder::where('delivery_id', $delivery)->update(['status' => 1]);
                        }
                    }
                }else{
                    $customId = new CustomIds();
                    $driverId = $customId->generateId();
                    $newDriver = [
                        'driver_id' => $driverId,
                        'id_number' => $request->idNumber,
                        'driver_name' => strtoupper($request->driverName),
                        'phone' => $request->driverPhone
                    ];
                    Driver::create($newDriver);
                    foreach ($request->deliveryIds as $delivery){
                        $loadingId = $customId->generateId();
                        $load = [
                            'loading_id' => $loadingId,
                            'station_id' =>  $request->station,
                            'loading_number' => $liNumber,
                            'transporter_id' => $request->transporter,
                            'delivery_id' => $delivery,
                            'registration' => strtoupper($request->registration),
                            'driver_id' => $driverId,
                            'created_by' => auth()->user()->user_id,
                            'status' => 1,
                        ];
                        if (LoadingInstruction::create($load)){
                            DeliveryOrder::where('delivery_id', $delivery)->update(['status' => 1]);
                        }
                    }
                }
            }
            $this->logger->create();
            DB::commit();
            return redirect()->back()->with('success', 'Successful! Local loading instructions added successfully');
        } catch (Exception $e) {
            // Rollback the transaction if an exception occurs
            DB::rollback();
            // Handle or log the exception
            return redirect()->back()->with('error', 'Oops! An error occurred please try again');
        }
    }
    public function viewDeliveries (Request $request)
    {
        $from = $request->get('from') ?? Carbon::now()->startOfMonth()->subMonth(2);
        $to = $request->get('to') ?? Carbon::now();

        $teas = DB::table('currentstock')
            ->orderBy('sortOrder', 'desc')
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('current_stock', '>', 0)
                        ->where('current_weight', '>', 0);
                })
                    ->orWhere(function ($q) {
                        $q->where('allocated_weight', '>', 0)
                            ->where('allocated_packages', '>', 0);
                    });
            })
            ->whereNull('deleted_at')
            // ->whereBetween('currentstock.date_received', [strtotime($from), strtotime($to)])
            ->select(
                'delivery_id',
                'garden_name',
                'grade_name',
                'client_name',
                'order_number',
                'lot_number',
                'invoice_number',
                'date_received',
                'stocked_at',
                'bay_name',
                'current_stock',
                'current_weight',
                'sortOrder',
                'sale_number',
                'stock_id',
                'client_id',
                'station_id',
                'production_date',
                'expiry_date',
                'tea_type',
                'allocated_job',
                'allocated_weight',
                'allocated_packages',
                DB::raw('CASE
                    WHEN shipped_packages > 0
                    OR transferred_palettes > 0
                    OR blended_packages > 0
                    OR sample_palletes > 0
                    OR requested_palettes > 0
                    THEN 1
                    ELSE 0
                END AS used'), 'net_weight', 'total_pallets', DB::raw('(package_tare * current_stock) + current_weight AS gross_weight')
            );
        $deliveries = $teas->get();
        $clients = $teas->select('client_id', 'client_name')->groupBy('client_id', 'client_name')->get();
        return view('clerk::stock.index')->with(['stocks' => $deliveries, 'clients' => $clients, 'from' => $from, 'to' => $to]);
    }
    public function getDoNumber (Request $request)
    {
        $do = DeliveryOrder::join('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
            ->join('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
            ->join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->leftJoin('loading_instructions', function ($join) {
                $join->on('loading_instructions.delivery_id', '=', 'delivery_orders.delivery_id')
                    ->whereIn('loading_instructions.status', [1, 2])
                    ->whereNull('loading_instructions.deleted_at');
            })
            ->select('gardens.garden_name','grades.grade_name', 'clients.client_name', 'delivery_orders.*', 'loading_instructions.loading_id', 'loading_instructions.loading_number', 'loading_instructions.status as load_status', 'loading_instructions.deleted_at')
            ->whereIn('delivery_orders.status', [0, 1, 2])
            ->where(function($query) use ($request) {
                $query->where('order_number', $request->doNumber)
                    ->orWhere('invoice_number', $request->doNumber)
                    ->orWhere('loading_number', $request->doNumber);
            })->selectSub(function ($query) { $query->selectRaw("delivery_orders.packet - (SELECT COALESCE(SUM(total_pallets), 0) FROM stock_ins WHERE stock_ins.delivery_id = delivery_orders.delivery_id) - (SELECT COALESCE(SUM(sample_palletes), 0) FROM tea_samples WHERE tea_samples.delivery_id = delivery_orders.delivery_id)");
            }, 'maxPallets')->selectSub(function ($query) { $query->selectRaw("delivery_orders.weight - (SELECT COALESCE(SUM(net_weight), 0) FROM stock_ins WHERE stock_ins.delivery_id = delivery_orders.delivery_id) - (SELECT COALESCE(SUM(sample_weight), 0) FROM tea_samples WHERE tea_samples.delivery_id = delivery_orders.delivery_id)");
            }, 'maxWeight')->havingRaw('maxWeight > 0')->get();

        // $locationId = Station::where('station_id', auth()->user()->station->station_id)->first()->location_id;
        // $stationIds = Station::where('location_id', $locationId)->pluck('station_id')->toArray();
        $stations = Station::orderBy('station_name', 'asc')->get();
        $transporters = Transporter::all();
        $drivers = Driver::all();
        $registrations = LoadingInstruction::pluck('registration')->toArray();

        if (!$do->isEmpty()) {
            return view('clerk::stock.receiveTCI')->with(['orders' => $do, 'stations' => $stations, 'transporters' => $transporters, 'drivers' => $drivers, 'registrations' => $registrations]);
        } else {
            return back()->with('error', 'Oops! The TCI is fully received or doesn\'t exist');
        }
    }
    public function filterWarehouses(Request $request)
    {
        $warehouseId = $request->input('warehouse');
        // Get the stations that match the warehouse_id
        $warehouses = Station::where('station_id', $warehouseId)->get();
        // Return the filtered warehouses as a JSON response
        return response()->json([
            'warehouses' => $warehouses
        ]);
    }
    public function selectStation(Request $request)
    {
        $warehouseId = $request->input('stationId');
        $data = Station::whereNot('station_id', $warehouseId)->where('status', 1)->get();
        return response()->json($data);
    }

    public function receiveDelivery(Request $request)
    {
        $request->validate([
            'delivery_number' => 'required|string',
            'station' => 'required|string',
            'bay' => 'required|string',
            'transporter' => 'required|string',
            'registration' => 'required|string',
            'idNumber' => 'required|string',
            'driverName' => 'required|string',
            'driverPhone' => 'required|string',
            'delivery_note' => 'required|file|mimetypes:image/png,image/jpeg,application/pdf|max:5120',

            // 'delivery_note' => 'required|image|mimes:png,jpg,jpeg|max:5120'
        ]);

        $orders = array_map(function ($order) {
            // Fix the keys by removing any extra quotes
            $cleanedOrder = [];
            foreach ($order as $key => $value) {
                // Remove leading and trailing quotes from key names
                $cleanedKey = str_replace(["'", '"'], '', $key);
                $cleanedOrder[$cleanedKey] = $value;
            }
            return $cleanedOrder;
        }, $request->orders);

       $deliveries = array_filter($orders, function ($delivery) {
            // Check if all required keys exist in the delivery array
            return array_key_exists('numberPackages', $delivery)
                && array_key_exists('grossWeight', $delivery)
                && array_key_exists('packageTare', $delivery)
                && array_key_exists('paletteTare', $delivery)
                && array_key_exists('netWeight', $delivery)
                && array_key_exists('deliveryId', $delivery)
                // Check if any of the values are null
                && $delivery['numberPackages'] !== null
                && $delivery['grossWeight'] !== null
                && $delivery['packageTare'] !== null
                && $delivery['paletteTare'] !== null
                && $delivery['netWeight'] !== null
                && $delivery['deliveryId'] !== null;
        });

        $deliveriesWithDifference = array_filter($deliveries, function ($delivery) {
            return isset($delivery['differenceType']) && $delivery['differenceType'] !== null;
        });
        $deliveriesWithoutDifference = array_filter($deliveries, function ($delivery) {
            return !isset($delivery['differenceType']) || $delivery['differenceType'] === null;
        });
        $deliveriesWithDifference = array_values($deliveriesWithDifference);
        $deliveriesWithoutDifference = array_values($deliveriesWithoutDifference);
        $errors = [];
        DB::beginTransaction();
        try {
            $driver = Driver::where('id_number', $request->idNumber)->first();
            if ($driver) {
                $driverID = $driver->driver_id;
            } else {
                $driverID = (new CustomIds())->generateId();
                $drDetails = [
                    'driver_id' => $driverID,
                    'id_number' => $request->idNumber,
                    'driver_name' => strtoupper($request->driverName),
                    'phone' => $request->driverPhone
                ];
                Driver::create($drDetails);
            }

            foreach ($deliveriesWithoutDifference as $delivery) {
                $data = DeliveryOrder::leftJoin('loading_instructions', function ($join) {
                    $join->on('loading_instructions.delivery_id', '=', 'delivery_orders.delivery_id')
                        ->whereIn('loading_instructions.status', [0, 1, 2])
                        ->whereNull('loading_instructions.deleted_at');
                })
                    ->whereIn('delivery_orders.status', [0, 1, 2])
                    ->selectSub(function ($query) {
                        $query->from('stock_ins')
                            ->select(DB::raw('delivery_orders.packet - COALESCE(SUM(CAST(total_pallets AS SIGNED INTEGER)), 0)'))
                            ->whereColumn('delivery_id', 'delivery_orders.delivery_id');
                    }, 'maxPallets')
                    ->selectSub(function ($query) {
                        $query->from('stock_ins')
                            ->select(DB::raw('delivery_orders.weight - COALESCE(SUM(CAST(net_weight AS SIGNED INTEGER)), 0)'))
                            ->whereColumn('delivery_id', 'delivery_orders.delivery_id');
                    }, 'maxWeight')
                    ->havingRaw('maxWeight > 0')
                    ->where('delivery_orders.delivery_id', $delivery['deliveryId'])
                    ->first();

                if ($data->maxPallets >= $delivery['numberPackages'] && $data->maxWeight >= $delivery['netWeight']) {
                    $customId = new CustomIds();
                    $stockId = $customId->generateId();
                    $stock = [
                        'stock_id' => $stockId,
                        'delivery_id' => $delivery['deliveryId'],
                        'station_id' => $request->station,
                        'date_received' => $request->date_received == null ? time() : Carbon::parse($request->date_received)->timestamp,
                        'delivery_number' => $request->delivery_number,
                        'warehouse_bay' => $request->bay,
                        'total_weight' => $delivery['grossWeight'],
                        'net_weight' => $delivery['netWeight'],
                        'pallet_weight' => $delivery['paletteTare'],
                        'package_tare' => $delivery['packageTare'],
                        'total_pallets' => $delivery['numberPackages'],
                        'transporter_id' => $request->transporter,
                        'driver_id' => $driverID,
                        'registration' => $request->registration,
                        'user_id' => auth()->user()->user_id,
                        'delivery_type' => 1
                    ];
                    StockIn::create($stock);
                    LoadingInstruction::where('delivery_id', $delivery['deliveryId'])->update([
                        'collection' => 'collected',
                        'status' => 2,
                        'driver_id' => $driverID,
                        'registration' => strtoupper($request->registration),
                        'transporter_id' => $request->transporter
                    ]);
                    DeliveryOrder::where('delivery_id', $delivery['deliveryId'])->update([
                        'status' => 2,
                        'production_date' => $delivery['productionDate'],
                        'expiry_date' => $delivery['expiryDate'],
                        'height' => $delivery['height'],
                    ]);
                } else {
                    $errors[] = 'Oops! Pallets cannot exceed ' . $data['maxPallets'] . ' and weight cannot exceed ' . $data['maxWeight'] . ' for INVOICE NUMBER: ' . $delivery['invNumber'];
                }
            }

            foreach ($deliveriesWithDifference as $deliveryD) {
                $data = DeliveryOrder::leftJoin('loading_instructions', function ($join) {
                    $join->on('loading_instructions.delivery_id', '=', 'delivery_orders.delivery_id')
                        ->whereIn('loading_instructions.status', [0, 1, 2])
                        ->whereNull('loading_instructions.deleted_at');
                })
                    ->whereIn('delivery_orders.status', [0, 1, 2])
                    ->selectSub(function ($query) {
                        $query->from('stock_ins')
                            ->select(DB::raw('delivery_orders.packet - COALESCE(SUM(CAST(total_pallets AS SIGNED INTEGER)), 0)'))
                            ->whereColumn('delivery_id', 'delivery_orders.delivery_id');
                    }, 'maxPallets')
                    ->selectSub(function ($query) {
                        $query->from('stock_ins')
                            ->select(DB::raw('delivery_orders.weight - COALESCE(SUM(CAST(net_weight AS SIGNED INTEGER)), 0)'))
                            ->whereColumn('delivery_id', 'delivery_orders.delivery_id');
                    }, 'maxWeight')
                    ->havingRaw('maxWeight > 0')
                    ->where('delivery_orders.delivery_id', $deliveryD['deliveryId'])
                    ->first();

                if ($data->maxPallets >= $deliveryD['numberPackages'] && $data->maxWeight >= $deliveryD['netWeight']) {
                    $netWeight = (float)$deliveryD['netWeight'];
                    $doWeight = (float)$deliveryD['doWeight'];
                    $numberPackages = (int)$deliveryD['numberPackages'];

                    $weightPerBag = round($doWeight / $numberPackages, 2);
                    $theDifference = round(abs($doWeight - $netWeight), 2);

                    $fullLostBags = floor($theDifference / $weightPerBag);
                    $partialLoss = round($theDifference - ($fullLostBags * $weightPerBag), 2);
                    $affectedBags = ceil($theDifference / $weightPerBag);
                    $goodPackages = $numberPackages - $affectedBags;

                    $stocks = [];
                    $teaDifferences = [];

                    $stockID = (new CustomIds())->generateId();
                    $stocks = [
                        'stock_id' => $stockID,
                        'delivery_id' => $deliveryD['deliveryId'],
                        'station_id' => $request->station,
                        'date_received' => $request->date_received == null
                            ? time()
                            : Carbon::parse($request->date_received)->timestamp,
                        'delivery_number' => $request->delivery_number,
                        'warehouse_bay' => $request->bay,
                        'transporter_id' => $request->transporter,
                        'registration' => $request->registration,
                        'user_id' => auth()->user()->user_id,
                        'delivery_type' => 1,
                        'pallet_weight' => $deliveryD['paletteTare'],
                        'package_tare' => $deliveryD['packageTare'],
                        'driver_id' => $driverID,
                        'total_pallets' => $numberPackages,
                        'total_weight' => round(($doWeight + $deliveryD['paletteTare'] + $deliveryD['packageTare']), 2),
                        'net_weight' => round($doWeight, 2),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                    $fullStockId = null;
                    $partialStockId = null;
                    $goodPackages = $numberPackages - ($fullLostBags + ($partialLoss > 0 ? 1 : 0));

                    if ($fullLostBags > 0) {
                        $teaDifferences[] = [
                            'sample_id' => (new CustomIds())->generateId(),
                            'delivery_id' => $deliveryD['deliveryId'],
                            'stock_id' => $stockID, // ✅ Correct routing
                            'sample_weight' => round($weightPerBag * $fullLostBags, 2),
                            'sample_palletes' => $fullLostBags,
                            'package_weight' => number_format($weightPerBag, 2),
                            'status' => 1,
                            'user_id' => auth()->user()->user_id,
                            'type' => $deliveryD['differenceType'],
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ];
                    }

                    if ($partialLoss > 0) {
                        $teaDifferences[] = [
                            'sample_id' => (new CustomIds())->generateId(),
                            'delivery_id' => $deliveryD['deliveryId'],
                            'stock_id' => $stockID,
                            'sample_weight' => round($partialLoss, 2),
                            'sample_palletes' => 1,
                            'package_weight' => number_format($weightPerBag, 2),
                            'status' => 1,
                            'user_id' => auth()->user()->user_id,
                            'type' => $deliveryD['differenceType'],
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ];
                    }
                    DB::table('stock_ins')->insert($stocks);
                    DB::table('tea_samples')->insert($teaDifferences);
                    LoadingInstruction::where('delivery_id', $deliveryD['deliveryId'])->update([
                        'status' => 2,
                        'driver_id' => $driverID,
                        'registration' => strtoupper($request->registration),
                        'transporter_id' => $request->transporter
                    ]);
                    DeliveryOrder::where('delivery_id', $deliveryD['deliveryId'])->update([
                        'status' => 2,
                        'production_date' => $delivery['productionDate'],
                        'expiry_date' => $delivery['expiryDate'],
                        'height' => $delivery['height'],
                    ]);
                } else {
                    $errors[] = 'Oops! Pallets cannot exceed ' . $data['maxPallets'] . ' and weight cannot exceed ' . $data['maxWeight'] . ' for INVOICE NUMBER: ' . $delivery['invNumber'];
                }
            }

            $file = $request->file('delivery_note');
            $ext = $file->getClientOriginalExtension();
            $fileName = (string) Str::uuid() . '.' .$ext;
            $path = $file->storeAs('/', $fileName, 'delivery_notes');

            DeliveryNote::updateOrCreate(['delivery_number' => $request->delivery_number], ['path' => '/'.$path]);

            $this->logger->create();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->route('clerk.viewDeliveries')->with('error', 'Oops! An error occurred please try again '.$e->getMessage());
        }
        if (!empty($errors)) {
            return redirect()->route('clerk.viewDeliveries')->with(['importErrors' => $errors]);
        } else {
            return redirect()->route('clerk.viewDeliveries')->with('success', 'Success! Delivery has been received');
        }
    }
    public function updateStock(Request $request, $id)
    {
        $request->validate([
            'delivery_number' => 'required|string',
            'station' => 'required|string',
            'bay' => 'required|string',
            'numberPackages' => 'required|string',
            'total_weight' => 'required|string',
            'tare' => 'required|string',
            'pallet_weight' => 'required|string',
            'netWeight' => 'required|string',
            'transporter' => 'required|string',
            'registration' => 'required|string',
            'idNumber' => 'required|string',
            'driverName' => 'required|string',
            'driverPhone' => 'required|string'
        ]);
        $data = StockIn::join('delivery_orders', 'delivery_orders.delivery_id', '=', 'stock_ins.delivery_id')
            ->whereNull('delivery_orders.deleted_at')
            ->where(['stock_ins.stock_id' => $id])
            ->first();
        if ($request->numberPackages > $data->packet){
            return redirect()->back()->with('error', 'Oops! The total pallets cannot be more than requested on the DO');
        }
        DB::beginTransaction();
        try {
            $customId = new CustomIds();
            $driver = Driver::where('id_number', $request->idNumber)->first();
            if ($driver === null){
                $driverID = $customId->generateId();
                $drDetails = [
                    'driver_id' => $driverID,
                    'id_number' => $request->idNumber,
                    'driver_name' => strtoupper($request->driverName),
                    'phone' => $request->driverPhone
                ];
                Driver::create($drDetails);
                LoadingInstruction::where('delivery_id', $request->delivery_id)->update([
                    'driver_id' => $driverID,
                    'registration' => strtoupper($request->registration),
                    'transporter_id' => $request->transporter
                ]);

            }else{
                LoadingInstruction::where('delivery_id', $request->delivery_id)->update([
                    'driver_id' => $driver->driver_id,
                    'registration' => strtoupper($request->registration),
                    'transporter_id' => $request->transporter
                ]);
            }
            $data->update([
                'station_id' => $request->station,
                'delivery_number' => $request->delivery_number,
                'warehouse_bay' => $request->bay,
                'total_weight' => $request->total_weight,
                'total_pallets' => $request->numberPackages,
                'net_weight' => $request->netWeight,
                'pallet_weight' => $request->pallet_weight,
                'package_tare' => $request->tare,
            ]);
            $this->logger->create();
            DB::commit();
            return redirect()->back()->with('success', 'Success! Stock entry has been updated');
        } catch (Exception $e) {
            // Rollback the transaction if an exception occurs
            DB::rollback();
            // Handle or log the exception
            return redirect()->back()->with('error', 'Oops! An error occurred please try again');
        }
    }
    public function exportStock(Request $request)
    {
        $client = $request->input('client');
        $garden = $request->input('garden');
        $grade = $request->input('grade');
        $station = $request->input('station');
        $from = $request->input('monthAgo');
        $to = $request->input('todayDate');

        $query = DB::table('currentstock')
            ->whereNotNull('current_stock')
            ->where('current_stock', '>', 0)
            ->whereNotNull('current_weight')
            ->where('current_weight', '>', 0)
            ->orderBy('sortOrder', 'desc')
            ->orderBy('client_name', 'asc')
            ->orderBy('station_name', 'asc');

        if (!is_null($client)) {
            $query->where('client_id', $client);
        }
        if (!is_null($garden)) {
            $query->where('garden_id', $garden);
        }
        if (!is_null($grade)) {
            $query->where('grade_id', $grade);
        }
        if (!is_null($station)) {
            $query->where('station_id', $station);
        }
        if (!is_null($from)) {
            $fromTimestamp = strtotime($from);
            $query->where('date_received', '>=', $fromTimestamp);
        }
        if (!is_null($to)) {
            $toTimestamp = strtotime($to);
            $query->where('date_received', '<=', $toTimestamp);
        }
        $data = $query->get();
        return Excel::download(new ExportStock($data), time().'STOCK.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }
    public function editStock($id)
    {
        $order = DB::table('currentstock')->where('stock_id', $id)->first();
        $stations = Station::orderBy('station_name', 'asc')->get();
        $transporters  = Transporter::orderBy('transporter_name')->get();
        $drivers  = Driver::orderBy('id_number', 'asc')->get();
        $registrations = LoadingInstruction::orderBy('registration', 'asc')->get()->groupBy('registration');
        return view('clerk::stock.editStock')->with(['stations' => $stations, 'transporters' => $transporters, 'drivers' => $drivers, 'registrations' => $registrations, 'order' => $order]);
    }
    public function updatedStock(Request $request, $id)
    {
        $request->validate([
            'delivery_number' => 'required|string',
            'station' => 'required|string',
            'bay' => 'required|string',
            'numberPackages' => 'required|string',
            'total_weight' => 'required|string',
            'tare' => 'required|string',
            'pallet_weight' => 'required|string',
            'netWeight' => 'required|string',
        ]);
        $data = StockIn::join('delivery_orders', 'delivery_orders.delivery_id', '=', 'stock_ins.delivery_id')
            ->whereNull('delivery_orders.deleted_at')
            ->where(['stock_ins.stock_id' => $id])
            ->first();
        if ($request->numberPackages > $data->packet){
            return redirect()->back()->with('error', 'Oops! The total pallets cannot be more than requested on the DO');
        }
        $customId = new CustomIds();
        $driver = Driver::where('id_number', $request->idNumber)->first();
        if ($driver === null && $request->idNumber !== null){
            $driverID = $customId->generateId();
            $drDetails = [
                'driver_id' => $driverID,
                'id_number' => $request->idNumber,
                'driver_name' => strtoupper($request->driverName),
                'phone' => $request->driverPhone
            ];
            Driver::create($drDetails);
            LoadingInstruction::where('delivery_id', $data->delivery_id)->update([
                'driver_id' => $driverID,
                'registration' => strtoupper($request->registration),
                'transporter_id' => $request->transporter
            ]);
        }elseif($driver !== null){
            LoadingInstruction::where('delivery_id', $data->delivery_id)->update([
                'driver_id' => $driver->driver_id,
                'registration' => strtoupper($request->registration),
                'transporter_id' => $request->transporter
            ]);
        }
        $data->update([
            'station_id' => $request->station,
            'delivery_number' => $request->delivery_number,
            'warehouse_bay' => $request->bay,
            'total_weight' => $request->netWeight,
            'total_pallets' => $request->numberPackages,
            'net_weight' => $request->total_weight,
            'pallet_weight' => $request->pallet_weight,
            'package_tare' => $request->tare,
            'date_received' => $request->date_received == null ? time() : strtotime($request->date_received),
            'transporter_id' => $request->transporter == '' ? null : $request->transporter_id,
            'driver_id' => $driver === null && $request->idNumber === null ? '' : ($driver !== null ? $driver->driver_id : $driverID),
            'registration' => $request->registration == null ? '' : $request->registration
        ]);
        $this->logger->create();
        return redirect()->route('clerk.viewDeliveries')->with('success', 'Success! Stock entry has been updated');
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
    public function blendBalanceReport(Request $request)
    {
        $client = $request->input('client');
        $station = $request->input('station');
        $from = $request->input('from');
        $to = $request->input('to');
        $report = $request->report;

        return $this->AppClass->blendBalanceReport($client, $station, $from, $to, $report);
    }
    public function viewLLIs ()
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $instructions  = LoadingInstruction::join('stations', 'stations.station_id', '=', 'loading_instructions.station_id')
            ->join('delivery_orders', 'delivery_orders.delivery_id', '=', 'loading_instructions.delivery_id')
            ->join('warehouses', 'warehouses.warehouse_id', '=', 'delivery_orders.warehouse_id')
            ->join('sub_warehouses', 'sub_warehouses.sub_warehouse_id', '=', 'delivery_orders.sub_warehouse_id')
            ->join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'loading_instructions.transporter_id')
            ->select('loading_number', 'client_name', 'warehouse_name', 'sub_warehouse_name', 'locality', 'transporter_name', DB::raw("SUM(packet) as packages"), DB::raw("SUM(weight) as weight"), 'collection')
            ->groupBy('loading_number', 'client_name', 'warehouse_name', 'sub_warehouse_name', 'locality', 'transporter_name')
            ->whereNull('loading_instructions.deleted_at')
            ->orderBy('loading_number', 'desc')
            ->whereMonth('loading_instructions.created_at', $currentMonth)
            ->whereYear('loading_instructions.created_at', $currentYear)
            ->get();

        return view('clerk::DOS.collection')->with(['instructions' => $instructions]);

    }
    public function addTCI()
    {
        $orders = DeliveryOrder::join('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
                ->join('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
                ->join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
                ->join('sub_warehouses', 'sub_warehouses.sub_warehouse_id', '=', 'delivery_orders.sub_warehouse_id')
                ->join('warehouses', 'warehouses.warehouse_id', '=', 'sub_warehouses.warehouse_id')
                ->leftJoin('loading_instructions', 'loading_instructions.delivery_id', '=', 'delivery_orders.delivery_id')
                ->where('delivery_orders.delivery_type', 1)
                ->where(function ($query) {
                    $query->where('delivery_orders.status', 0)
                        ->orWhereNull('delivery_orders.status'); // Fixed: checks for null status
                })
                ->where(function ($query) {
                    $query->whereNull('loading_instructions.delivery_id')  // No matching loading instruction
                        ->orWhereNotNull('loading_instructions.deleted_at');  // Exists but only where deleted_at is not null
                })
                ->get();
       $stations = Station::where('status', 1)->get();
       $transporters = Transporter::all();
       $registrations = LoadingInstruction::pluck('registration')->toArray();
       $drivers = Driver::all();
        return view('clerk::DOS.addTCI')->with(['orders' => $orders, 'stations' => $stations, 'transporters' => $transporters, 'registrations' => $registrations, 'drivers' => $drivers]);
    }
    public function filterByGarden(Request $request)
    {
        $data = DeliveryOrder::join('warehouses', 'warehouses.warehouse_id', '=', 'delivery_orders.warehouse_id')
            ->join('sub_warehouses', 'sub_warehouses.warehouse_id', '=', 'warehouses.warehouse_id')
            ->leftJoin('loading_instructions', function ($join) {
                $join->on('loading_instructions.delivery_id', '=', 'delivery_orders.delivery_id')
                    ->whereNull('loading_instructions.deleted_at');
            })
            ->where('delivery_orders.warehouse_id', $request->warehouseId)
            ->where(function ($query) {
                $query->where('delivery_orders.status', 0)
                    ->orWhereNull('delivery_orders.status');
            })
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('delivery_orders')
                    ->whereColumn('delivery_orders.sub_warehouse_id', 'sub_warehouses.sub_warehouse_id')
                    ->where(function ($query) {
                        $query->where('delivery_orders.status', 0)
                            ->orWhereNull('delivery_orders.status');
                    });
            })
            ->select(
                'warehouses.warehouse_id',
                'sub_warehouses.sub_warehouse_id',
                'sub_warehouses.sub_warehouse_name',
                DB::raw('COUNT(delivery_orders.delivery_id) as total_deliveries')
            )
            ->groupBy('warehouses.warehouse_id', 'sub_warehouses.sub_warehouse_id', 'sub_warehouses.sub_warehouse_name')
            ->having('total_deliveries', '>', 0) // Ensure that only sub-warehouses with valid delivery orders are returned
            ->orderBy('sub_warehouses.sub_warehouse_name', 'asc')
            ->get();
        return response()->json($data);
    }
    public function filterByClient(Request $request)
    {
        $data = DeliveryOrder::join('warehouses', 'warehouses.warehouse_id', '=', 'delivery_orders.warehouse_id')
            ->join('sub_warehouses', 'sub_warehouses.sub_warehouse_id', '=', 'delivery_orders.sub_warehouse_id')
            ->join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->leftJoin('loading_instructions', function ($join) {
                $join->on('loading_instructions.delivery_id', '=', 'delivery_orders.delivery_id')
                    ->whereNull('loading_instructions.deleted_at'); // Changed to whereNull
            })
            ->where('warehouses.warehouse_id', $request->warehouseId)
            ->where('sub_warehouses.sub_warehouse_id', $request->warehouseBranchId)
            ->where(function ($query) {
                $query->where('delivery_orders.status', 0)
                    ->orWhereNull('delivery_orders.status');
            })
            ->select('clients.client_id', 'clients.client_name')
            ->orderBy('clients.client_name', 'asc') // Changed to asc for alphabetical order
            ->get();
        return response()->json($data);
    }
    public function filterBySaleNumber(Request $request)
    {
        $data = DeliveryOrder::join('users', 'users.user_id', '=', 'delivery_orders.created_by')
            ->join('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
            ->join('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
            ->join('brokers', 'brokers.broker_id', '=', 'delivery_orders.broker_id')
            ->join('warehouses', 'warehouses.warehouse_id', '=', 'delivery_orders.warehouse_id')
            ->join('sub_warehouses', 'sub_warehouses.sub_warehouse_id', '=', 'delivery_orders.sub_warehouse_id')
            ->join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->leftJoin('loading_instructions', function ($join) {
                $join->on('loading_instructions.delivery_id', '=', 'delivery_orders.delivery_id')
                    ->whereNull('loading_instructions.deleted_at');
            })
            ->leftJoin('drivers', 'drivers.driver_id', '=', 'loading_instructions.driver_id')
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'loading_instructions.transporter_id')
            ->leftJoin('stations', 'stations.station_id', '=', 'loading_instructions.station_id')
            ->leftJoin('users as loading_user', 'loading_user.user_id', '=', 'loading_instructions.created_by')
            ->where(['clients.client_id' => $request->clientId, 'warehouses.warehouse_id' => $request->warehouseId, 'sub_warehouses.sub_warehouse_id' => $request->warehouseBranchId])
            ->where(function ($query) {
                $query->where('delivery_orders.status', 0)
                    ->orWhereNull('delivery_orders.status');
            })
            ->select('users.username', 'gardens.garden_name', 'grades.grade_name', 'brokers.broker_name', 'warehouses.warehouse_name', 'warehouses.warehouse_id', 'clients.client_name', 'delivery_orders.*', 'transporters.transporter_id', 'transporters.transporter_name', 'drivers.driver_id', 'drivers.driver_name', 'drivers.id_number', 'drivers.phone', 'loading_instructions.loading_id', 'loading_instructions.loading_number', 'loading_instructions.status as load_status', 'loading_instructions.registration', 'loading_instructions.created_by as load_user_id', 'loading_user.username as load_user', 'stations.station_name', 'stations.station_id', 'sub_warehouses.sub_warehouse_name', 'loading_instructions.deleted_at')
            ->orderBy('clients.client_name', 'asc')
            ->orderBy('gardens.garden_name', 'asc')
            ->get();
        return response()->json($data);
    }
    public function viewTciDetails($id)
    {
        $tciNumber = base64_decode($id);
        $orders = LoadingInstruction::join('stations', 'stations.station_id', '=', 'loading_instructions.station_id')
            ->join('delivery_orders', 'delivery_orders.delivery_id', '=', 'loading_instructions.delivery_id')
            ->join('warehouses', 'warehouses.warehouse_id', '=', 'delivery_orders.warehouse_id')
            ->join('sub_warehouses', 'sub_warehouses.sub_warehouse_id', '=', 'delivery_orders.sub_warehouse_id')
            ->join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->join('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
            ->join('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
            ->select('stations.station_name', 'delivery_orders.delivery_id', 'loading_instructions.status', 'invoice_number', 'lot_number', 'sale_number', 'weight', 'packet', 'warehouses.warehouse_name', 'sub_warehouses.sub_warehouse_name', 'clients.client_name', 'garden_name', 'grade_name', 'prompt_date', 'sale_date', 'loading_number', 'loading_id')
            ->where('loading_number', $tciNumber)
            ->get();
        $tci = $orders->first();
        return view('clerk::DOS.tciDetails')->with(['orders' => $orders, 'tci' => $tci]);
    }
    public function filterWarehouseBay(Request $request)
    {
        $data = WarehouseBay::where('station_id', $request->selectedStation)->orderBy('bay_name', 'asc')->get();
        return response()->json($data);
    }
    public function viewShippingInstructions()
    {
        $shipping = ShippingInstruction::join('clients', 'clients.client_id', '=', 'shipping_instructions.client_id')
            ->join('destinations', 'destinations.destination_id', '=', 'shipping_instructions.destination_id')
            ->join('stations', 'stations.station_id', '=', 'shipping_instructions.station_id')
            ->select('shipping_id', 'client_name', 'shipping_instructions.created_at', 'shipping_instructions.status', 'station_name', 'shipping_number', 'vessel_name', 'port_name', 'load_type', 'location_id', 'shipping_instructions.si_number')
            ->latest('shipping_instructions.created_at')
            ->get();
        $registrations = ShippingInstruction::pluck('registration')->toArray();
        $users = Driver::all();
        $agents = ClearingAgent::all();
        $transporters = Transporter::all();
        return view('clerk::shipping.SIs')->with(['shipping' => $shipping, 'registrations' => $registrations, 'users' => $users, 'agents' => $agents, 'transporters' => $transporters]);
    }
    public function createSI()
    {
        $stations = Station::where('status', 1)->get();
        $ports = Destination::all();
        $clients = DB::table('currentstock')
            ->whereIn('station_id', $stations->pluck('station_id')->toArray())
            ->groupBy('client_id', 'client_name')
            ->select('client_id', 'client_name')
            ->get();
        return view('clerk::shipping.createSI')->with(['ports' => $ports, 'stations' => $stations, 'clients' => $clients]);
    }
    public function editSI($id)
    {
        $si = ShippingInstruction::find($id);
        $siTeas = Shipment::where('shipping_id', $id)->count();
        $stations = Station::where('status', 1)->get();
        $ports = Destination::all();
        $clients = DB::table('currentstock')
            ->whereIn('station_id', $stations->pluck('station_id')->toArray())
            ->groupBy('client_id', 'client_name')
            ->select('client_id', 'client_name')
            ->get();
        return view('clerk::shipping.editSI')->with(['ports' => $ports, 'stations' => $stations, 'clients' => $clients, 'si' => $si, 'siTeas' => $siTeas]);
    }
    public function addShippingInstruction(Request $request)
    {
        $request->validate([
            'client' => 'string|required',
            'station' => 'string|required',
            'vessel' => 'string|required',
            'shipmentNumber' => 'required|string|unique:shipping_instructions,shipping_number',
            'destination' => 'required|string',
            'package' => 'required|string',
            'containerSize' => 'required|string',
            'consignee' => 'required|string',
            'mark' => 'required|string',
            'shippingInstruction' => 'required|string'
        ]);
        $customId = new CustomIds();
        $siId = $customId->generateId();
        $shipment = new ShippingInstruction;
        $shipment->shipping_id = $siId;
        $shipment->client_id = $request->client;
        $shipment->vessel_name = $request->vessel;
        $shipment->shipping_number = $request->shipmentNumber;
        $shipment->destination_id = $request->destination;
        $shipment->load_type = $request->package;
        $shipment->container_size = $request->containerSize;
        $shipment->consignee = $request->consignee;
        $shipment->address = [
            'address' => $request->address,
            'mobile' => $request->mobile,
            'box' => $request->box,
            'state' => $request->state
        ];
        $shipment->shipping_mark = $request->mark;
        $shipment->station_id = $request->station;
        $shipment->booking_number = $request->bookingNumber;
        $shipment->si_number = $request->shippingNumber;
        $shipment->shipping_instructions = $request->shippingInstruction;
        $shipment->user_id = auth()->user()->user_id;
        $shipment->save();
        $this->logger->create();
        return redirect()->route('clerk.addShipmentTeas', $siId)->with('success', 'Success! Shipping instruction created successfully');
    }
    public function updateSI(Request $request, $id)
    {
        $request->validate([
            'client_hidden' => 'string|required',
            'station_hidden' => 'string|required',
            'vessel' => 'string|required',
            'shipmentNumber' => 'required|string|unique:shipping_instructions,shipping_number,'.$id.',shipping_id',
            'destination' => 'required|string',
            'package' => 'required|string',
            'containerSize' => 'required|string',
            'consignee' => 'required|string',
            'mark' => 'required|string',
            'shippingInstruction' => 'required|string'
        ]);

        $si = [
            'client_id' => $request->client_hidden,
            'vessel_name' => $request->vessel,
            'shipping_number' => $request->shipmentNumber,
            'destination_id' => $request->destination,
            'load_type' => $request->package,
            'container_size' => $request->containerSize,
            'consignee' => $request->consignee,
            'shipping_mark' => $request->mark,
            'station_id' => $request->station_hidden,
            'shipping_instructions' => $request->shippingInstruction,
            'address' => [
                'address' => $request->address,
                'mobile' => $request->mobile,
                'box' => $request->box,
                'state' => $request->state
            ],
            'booking_number' => $request->bookingNumber,
            'si_number' => $request->shippingNumber,
        ];

        ShippingInstruction::where('shipping_id', $id)->update($si);
        $this->logger->create();
        return redirect()->route('clerk.addShipmentTeas', $id)->with('success', 'Success! Shipping instruction created successfully');
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
            ->select('shipment_id', 'shipments.shipped_packages', 'shipments.shipped_weight', 'shipments.status', 'garden_name', 'grade_name', 'invoice_number', 'stock_ins.package_tare', 'stock_ins.pallet_weight', 'delivery_orders.height')
            ->where('shipping_id', $id)
            ->orderBy('shipments.created_at', 'desc')
            ->get();
        $clientTeas = DB::table('currentstock')
            ->where('current_stock', '>', 0)
            ->where('current_weight', '>', 0)
            ->where(['client_id' => $si->client_id, 'station_id' =>  $si->station_id])
            ->orderBy('sortOrder', 'desc')
            ->get();
        return view('clerk::shipping.addTeasToSI')->with(['teas' => $teas, 'clientTeas' => $clientTeas, 'si' => $si]);
    }
    public function storeShippingInstruction(Request $request, $id)
    {
        $data = json_decode($request->form_data);
        foreach ($data as $tea){
            $stock = StockIn::where('stock_id', $tea->stock_id)->first();
            $customId =  new CustomIds();
            $shipment = [
                'shipment_id' =>  $customId->generateId(),
                'shipping_id' => $id,
                'stock_id' => $tea->stock_id,
                'delivery_id' => $stock->delivery_id,
                'shipped_packages' => $tea->stock,
                'shipped_weight' => number_format($tea->weight, 2),
                'pallet_weight' => $tea->pallet_weight,
                'pallet_height' => $tea->pallet_height,
                'package_tare' => $tea->package_tare,
                'status' => 0
            ];
            Shipment::create($shipment);
        }
        $this->logger->create();
        return redirect()->back()->with('success', 'Successful! Teas added to shipping instruction');
    }
    public function initateSI($id){
        ShippingInstruction::where('shipping_id', $id)->update(['status' => 1]);
        $this->logger->create();
        return redirect()->route('clerk.viewShippingInstructions')->with('success', 'Success! Shipping instructions updated successfully');
    }
    public function updateShippingInstruction($id)
    {
        if (auth()->user()->role_id == 2 || auth()->user()->hasPermission('straightline.approve')){
            ShippingInstruction::where('shipping_id', $id)->update(['status' => 4]);
            $this->logger->create();
        }else{
            ShippingInstruction::where('shipping_id', $id)->update(['status' => 3]);
            $this->logger->create();
        }
        return redirect()->route('clerk.viewShippingInstructions')->with('success', 'Success! Shipping instructions updated successfully');
    }
    public function markAsShipped($id)
    {
        ShippingInstruction::where('shipping_id', $id)->update(['ship_date' => time(), 'status' => 4]);
        Shipment::where('shipping_id', $id)->update(['status' => 1]);
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Shipment details updated successfully');
    }
    public function updateShippingInstructionDetails(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $driver = Driver::where('id_number', $request->idNumber)->first();
            if ($driver){
                $shipment = [
                    'container_number' => $request->containerNumber,
                    'container_tare' => $request->tare,
                    'clearing_agent' => $request->agent,
                    'seal_number' => $request->seal,
                    'transporter_id' => $request->transporter,
                    'driver_id' => $driver->driver_id,
                    'registration' => $request->registration,
                    'ship_date' => time(),
                    'escort' => $request->escort,
                    'status' => 2
                ];
            }else{
                $customId = new CustomIds();
                $driverId = $customId->generateId();
                $newDriver = [
                    'driver_id' => $driverId,
                    'id_number' => $request->idNumber,
                    'driver_name' => strtoupper($request->driverName),
                    'phone' => $request->driverPhone
                ];
                Driver::create($newDriver);
                $shipment = [
                    'container_number' => $request->containerNumber,
                    'container_tare' => $request->tare,
                    'clearing_agent' => $request->agent,
                    'seal_number' => $request->seal,
                    'transporter_id' => $request->transporter,
                    'driver_id' => $driverId,
                    'registration' => $request->registration,
                    'ship_date' => time(),
                    'escort' => $request->escort,
                    'status' => 2
                ];
            }
            ShippingInstruction::where('shipping_id', $id)->update($shipment);
            $this->logger->create();
            // Commit the transaction
            DB::commit();
        } catch (Exception $e) {
            // Rollback the transaction if an exception occurs
            DB::rollback();
            // Handle or log the exception
            return redirect()->back()->with('error', 'Oops! An error occurred please try again');
        }
        return redirect()->back()->with('success', 'Successful!, Shipping Instructions updated successfully');
    }
    public function downloadSIDocument($id)
    {
        return $this->AppClass->downloadStraightLine($id);
    }

    public function downloadSIPackingList($id)
    {
        return $this->AppClass->downloadSIPackingList($id);
    }
    public function downloadSIContinuedPackingList($id)
    {
        return $this->AppClass->downloadSIContinuedPackingList($id);
    }
    public function downloadDriverClearance($id)
    {
        return $this->AppClass->downloadStraightLineClearance($id);
    }
    public function viewBlendProcessing()
    {
        $sheets = DB::table('blend_sheets')
            ->join('clients', 'clients.client_id', '=', 'blend_sheets.client_id')
            ->join('destinations', 'destinations.destination_id', '=', 'blend_sheets.destination_id')
            ->join('stations', 'stations.station_id', '=', 'blend_sheets.station_id')
            ->select('blend_sheets.blend_id', 'blend_sheets.created_at', 'station_name', 'client_name', 'clients.client_id', 'blend_number', 'vessel_name', 'port_name', 'blend_sheets.status', 'output_packages', 'output_weight', 'location_id', 'stations.station_id', 'package_type', 'si_number')
            ->whereNull('blend_sheets.deleted_at')
            ->latest('blend_sheets.created_at')
            ->get();
        return view('clerk::shipping.blendSheets')->with(['sheets' => $sheets]);
    }
    public function createBlendSheet()
    {
        $stations = Station::where('status', 1)->get();
        $ports = Destination::all();
        $clients = DB::table('currentstock')
            ->whereIn('station_id', $stations->pluck('station_id')->toArray())
            ->groupBy('client_id', 'client_name')
            ->select('client_id', 'client_name')
            ->get();
        return view('clerk::shipping.createBlend')->with(['ports' => $ports, 'stations' => $stations, 'clients' => $clients]);
    }
    public function addBlendSheet(Request $request)
    {
        $request->validate([
            'client' => 'required|string',
            'station' => 'required|string',
            'shipmentNumber' =>'required|string',
            'contract' => 'required|string',
            'destination' => 'required|string',
            'packagingType' =>'required|string',
            'containerSize' => 'required|string',
            'consignee' => 'required|string',
            'mark' => 'required|string',
        ]);

        $customId = new CustomIds();
        $sheet = [
            'blend_id' => $customId->generateId(),
            'client_id' => $request->client,
            'vessel_name' => $request->vessel,
            'blend_number' => $request->shipmentNumber,
            'contract' => $request->contract,
            'destination_id' => $request->destination,
            'garden' => $request->gardenName,
            'grade' => $request->blendGrade,
            'blend_date' => $request->blendDate,
            'package_type' => $request->packagingType,
            'container_size' => $request->containerSize,
            'consignee' => $request->consignee,
            'shipping_mark' => $request->mark,
            'standard_details' => $request->shippingInstruction,
            'station_id' => $request->station,
            'user_id' => auth()->user()->user_id,
            'address' => [
                'address' => $request->address,
                'mobile' => $request->mobile,
                'box' => $request->box,
                'state' => $request->state
            ],
            'booking_number' => $request->bookingNumber,
            'si_number' => $request->shippingNumber,
        ];
        BlendSheet::create($sheet);
        $this->logger->create();
        return redirect()->route('clerk.addBlendTeas', $sheet['blend_id'])->with('success', 'Success! Blend sheet created successfully');
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
        $clientTeas = DB::table('currentstock')
            ->where('current_stock', '>', 0)
            ->where('current_weight', '>', 0)
            ->where(['client_id' => $bs->client_id, 'station_id' => $bs->station_id])
            ->orderBy('sortOrder', 'desc')
            ->get();
        $blendBalances = DB::table('blendBalances')->where(['client_id' => $bs->client_id, 'status' => 0])->where('current_weight', '>', 0)->get();
        return view('clerk::shipping.addTeasToBlend')->with(['teas' => $teas, 'clientTeas' => $clientTeas, 'bs' => $bs, 'blendBalances' => $blendBalances]);
    }
    public function storeBlendTeas(Request $request, $id)
    {
        $data = json_decode($request->form_data);
        foreach ($data as $tea){
            $stock = StockIn::where('stock_id', $tea->stock_id)->first();
            $customId =  new CustomIds();
            $sheet = [
                'blended_id' =>  $customId->generateId(),
                'blend_id' => $id,
                'stock_id' => $tea->stock_id,
                'delivery_id' => $stock->delivery_id,
                'blended_packages' => $tea->stock,
                'blended_weight' => round($tea->weight, 2),
            ];
            BlendTea::create($sheet);
        }
        $this->logger->create();
        return redirect()->back()->with('success', 'Successful! Teas added to blend sheet');
    }
    public function addBlendBalanceTeas(Request $request, $id)
    {
        $filteredBlends = array_filter($request->blends, function ($record) {
            // Check if all required keys exist in the delivery array
            return array_key_exists('packages', $record)
                && array_key_exists('weights', $record)
                // Check if any of the values are null
                && $record['packages'] !== null
                && $record['weights'] !== null;
        });
        DB::beginTransaction();
        try {
            foreach ($filteredBlends as $blendID => $stock){
                $blendB = BlendBalance::find($blendID);
                $customId =  new CustomIds();
                $sheet = [
                    'blended_id' =>  $customId->generateId(),
                    'blend_id' => $id,
                    'stock_id' => $blendB->blend_balance_id,
                    'delivery_id' => $blendB->blend_id,
                    'blended_packages' => $stock['packages'],
                    'blended_weight' => $stock['weights'],
                ];
                BlendTea::create($sheet);
            }
            $this->logger->create();
            DB::commit();
            return redirect()->back()->with('success', 'Successful! Teas added to blend sheet');
        } catch (Exception $e) {
            // Rollback the transaction if an exception occurs
            DB::rollback();
            // Handle or log the exception
            return redirect()->back()->with('error', 'Oops! An error occurred please try again');
        }
    }
    public function updateBlendSheet($id)
    {
        if (auth()->user()->role_id == 2 || auth()->user()->hasPermission('blend.approve')){
            $blend = BlendSheet::where('blend_id', $id)->first();
            $status = $blend->status !== 3 ? ($blend->status + 1) : 4;
            $blend->update(['status' => $status, 'blend_shipped' => time()]);
            $this->logger->create();
        }else{
            BlendSheet::where('blend_id', $id)->update(['status' => 1]);
            $this->logger->create();
        }
        return redirect()->route('clerk.viewBlendProcessing')->with('success', 'Success! Blend sheet updated successfully');
    }
    public function deleteBlendTea($id)
    {
        $bt = BlendTea::find($id);
//        BlendBalance::where('blend_balance_id', $bt->stock_id)->update(['status' => 0]);
        $bt->delete();
        $this->logger->create();
        return redirect()->back()->with('success', 'Successful! Teas removed from blend sheet successfully');
    }
    public function deleteSITea($id)
    {
        $bt = Shipment::find($id);
//        BlendBalance::where('blend_balance_id', $bt->stock_id)->update(['status' => 0]);
        $bt->delete();
        $this->logger->create();
        return redirect()->back()->with('success', 'Successful! Teas removed from SI successfully');
    }
    public function updateOutTurnReport ($id)
    {
       $bs = BlendSheet::join('blend_teas', 'blend_teas.blend_id', '=', 'blend_sheets.blend_id')
            ->join('clients', 'clients.client_id', 'blend_sheets.client_id')
            ->leftJoin('clearing_agents', 'clearing_agents.agent_id', '=', 'blend_sheets.agent_id')
            ->select('blend_sheets.blend_id', 'client_name', 'blend_number', 'b_dust', 'c_dust', 'sweepings', 'fibre', 'packet_tare', 'blend_sheets.agent_id', 'container_tare', 'seal_number', 'escort', 'blend_date', 'transporter_id', 'registration', 'driver_id') // Specify necessary columns
            ->selectRaw('SUM(blend_teas.blended_packages) as input_packages')
            ->selectRaw('SUM(blend_teas.blended_weight) as input_weight')
            ->where('blend_sheets.blend_id', $id) // Assuming $id is for blend_sheets.blend_id
            ->groupBy('blend_id', 'blend_number', 'client_name', 'b_dust', 'c_dust', 'sweepings', 'fibre', 'packet_tare', 'agent_id', 'container_tare', 'seal_number', 'escort', 'blend_date', 'transporter_id', 'registration', 'driver_id') // Group by specific columns
            ->first();
        $agents = ClearingAgent::all();
        $transporters = Transporter::all();
        $registrations = BlendSheet::pluck('registration')->toArray();
        $users = Driver::all();
        $shipment = BlendShipment::where('blend_id', $id)->first();
        $driver = Driver::where('driver_id', $bs->driver_id)->first();
        $supervisors = BlendSupervision::where('blend_id', $id)->get();
        $mOperator = $supervisors->firstWhere('supervisor_type', 1)?->supervisor_name ?? null;
        $bSupervisor = $supervisors->firstWhere('supervisor_type', 2)?->supervisor_name ?? null;
        $tParty = $supervisors->firstWhere('supervisor_type', 3)?->supervisor_name ?? null;
        $materials = BlendMaterial::where('blend_id', $id)->get();
        $newPaperSack = $materials->first(fn($m) => $m->material_type == 1 && $m->condition == 1)?->total;
        $inUsePaperSack = $materials->first(fn($m) => $m->material_type == 1 && $m->condition == 2)?->total;
        $damagedPaperSack = $materials->first(fn($m) => $m->material_type == 1 && $m->condition == 3)?->total;
        $newPoly = $materials->first(fn($m) => $m->material_type == 2 && $m->condition == 1)?->total;
        $inUsePoly = $materials->first(fn($m) => $m->material_type == 2 && $m->condition == 2)?->total;
        $damagedPoly = $materials->first(fn($m) => $m->material_type == 2 && $m->condition == 3)?->total;
        $newPouch = $materials->first(fn($m) => $m->material_type == 3 && $m->condition == 1)?->total;
        $newPallet = $materials->first(fn($m) => $m->material_type == 4 && $m->condition == 1)?->total;
        $inUsePallet = $materials->first(fn($m) => $m->material_type == 3 && $m->condition == 2)?->total;
        $damagedPallet = $materials->first(fn($m) => $m->material_type == 3 && $m->condition == 3)?->total;
        $newGummy = $materials->first(fn($m) => $m->material_type == 5 && $m->condition == 1)?->total;
        $inUseGummy = $materials->first(fn($m) => $m->material_type == 4 && $m->condition == 2)?->total;
        $damagedGummy = $materials->first(fn($m) => $m->material_type == 4 && $m->condition == 3)?->total;
        $containerNumbers = ShipmentContainer::where('blend_id', $id)->get();

        $outTurnReport = [
            'packageTare' => $bs->packet_tare,
            'sweepings' => $bs->sweepings,
            'b_dust' => $bs->b_dust,
            'fibre' => $bs->fibre,
            'c_dust' => $bs->c_dust,
            'variance' => $shipment == null ? 0 : $shipment->weight_variance,
            'container_tare' => $bs->container_tare,
            'agent_id' => $bs->agent_id,
            'transporter_id' => $bs->transporter_id,
            'blend_date' => $bs->blend_date,
            'escort' => $bs->escort,
            'seal_number' => $bs->seal_number,
            'registration' => $bs->registration,
            'id_number' => $driver == null ? null : $driver->id_number,
            'driver_name'=> $driver == null ? null : $driver->driver_name,
            'phone' => $driver == null ? null : $driver->phone,
            'mOperator' => $mOperator,
            'bSupervisor' => $bSupervisor,
            'tParty' => $tParty,
            'newPaper' => $newPaperSack,
            'inUsePaper' => $inUsePaperSack,
            'damagedPaper' => $damagedPaperSack,
            'newPoly' => $newPoly,
            'inUsePoly' => $inUsePoly,
            'damagedPoly' => $damagedPoly,
            'newPallet' => $newPallet,
            'inUsePallet' => $inUsePallet,
            'damagedPallet' => $damagedPallet,
            'newPouch' => $newPouch,
            'newGummy' => $newGummy,
            'inUseGummy' => $inUseGummy,
            'damagedGummy' => $damagedGummy,
        ];
        return view('clerk::shipping.updateOutTurnReport')->with(['bs' => $bs, 'agents' => $agents, 'transporters' => $transporters, 'registrations' => $registrations, 'users' => $users, 'outTurnReport' => $outTurnReport, 'containerNumbers' => $containerNumbers]);
    }
    public function amendOutTurnReport ($id)
    {
        return $blend = BlendSheet::where('blend_id', $id)->first();

    }
    public function updateBlendSheetDetails(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $blendSheet = BlendSheet::find($id);
            $customIds = new CustomIds();
            $driverID = null;
            BlendShipment::where('blend_id', $id)->delete();
            BlendBalance::where('blend_id', $id)->delete();
            ShipmentContainer::where('blend_id', $id)->delete();
            BlendSupervision::where('blend_id', $id)->delete();
            BlendMaterial::where('blend_id', $id)->delete();
            $blendSheet->update([
                'driver_id' => null,
                'agent_id' => null,
                'transporter_id' => null,
                'registration' => null,
                'seal_number' => null,
                'escort' => null,
                'blend_date' => null,
                'container_tare' => null,
                'output_packages' => null,
                'output_weight' => null,
                'packet_tare' => null,
                'b_dust' => null,
                'c_dust' => null,
                'fibre' => null,
                'sweepings' => null,
            ]);

            if ($request->idNumber) {
                $drivers = Driver::where('id_number', $request->idNumber)->first();
                if (!$drivers) {
                    $driverId = $customIds->generateId();
                    $newDriver = [
                        'driver_id' => $driverId,
                        'id_number' => $request->idNumber,
                        'driver_name' => strtoupper($request->driverName),
                        'phone' => $request->driverPhone
                    ];
                    Driver::create($newDriver);
                    $driverID = $newDriver['driver_id'];
                }else{
                    $driverID = $drivers->driver_id;
                }
            }

            $outputPacks = 0;
            $outputWeight = 0;
            if ($request->blend !== null){
                foreach ($request->blend as $shipping){
                    $blendShipment = $customIds->generateId();
                    $shipment = [
                        'blend_shipment_id' => $blendShipment,
                        'blend_id' => $blendSheet->blend_id,
                        'blended_packages' => $shipping['packages'],
                        'unit_weight' => $shipping['weight'],
                        'weight_variance' => $request->tareVariance,
                        'net_weight' => floatval($shipping['packages']) * floatval($shipping['weight']),
                        'package_tare' => $request->packageTare,
                        'gross_weight' => floatval($shipping['packages']) * floatval($shipping['weight']) + (floatval($shipping['packages']) * floatval($request->packageTare)) + floatval($shipping['packages']) * floatval($request->tareVariance)
                    ];
                    $outputPacks += floatval( $shipping['packages']);
                    $outputWeight += floatval( $shipping['weight']) * floatval( $shipping['packages']);
                    BlendShipment::create($shipment);
                }
            }

            $blendSheet->update([
                'driver_id' => $driverID ?? null,
                'agent_id' => $request->agentId ?? null,
                'transporter_id' => $request->transporter ?? null,
                'registration' => $request->registration ?? null,
                'seal_number' => $request->seal ?? null,
                'escort' => $request->escortId ?? null,
                'blend_date' => $request->blendDate,
                'container_tare' => $request->tare,
                'output_packages' => $outputPacks,
                'output_weight' => $outputWeight,
                'packet_tare' => $request->packageTare,
                'b_dust' => $request->bDust,
                'c_dust' => $request->cDust,
                'fibre' => $request->fibre,
                'sweepings' => $request->sweepings,
            ]);

            if($request->balances !== null){
                foreach ($request->balances as $blendBal){
                    $blendBalance = $customIds->generateId();
                    $balance = [
                        'blend_balance_id' => $blendBalance,
                        'blend_id' => $blendSheet->blend_id,
                        'ex_packages' => $blendBal['packages'],
                        'unit_weight' => floatval($blendBal['weight']),
                        'net_weight' => floatval($blendBal['weight']) * floatval($blendBal['packages']),
                        'station_id' => $blendSheet->station_id,
                        'gross_weight' => floatval($blendBal['weight']) * floatval($blendBal['packages']),
                        'type' => 1,
                    ];
                    BlendBalance::create($balance);
                }
            }

            BlendBalance::create([
                'blend_balance_id' => (new CustomIds())->generateId(),
                'blend_id' => $blendSheet->blend_id,
                'ex_packages' => 1,
                'unit_weight' => $request->bDust,
                'net_weight' => $request->bDust,
                'station_id' => $blendSheet->station_id,
                'gross_weight' => $request->bDust,
                'type' => 2
            ]);

            if ($request->containers !== null) {
                // Get all submitted container numbers
                $submittedContainerNumbers = [];

                foreach ($request->containers as $contain) {
                    $containerNumber = $contain['containerNumber'];

                    // Check if this container already exists for this blend
                    $existingContainer = ShipmentContainer::where('blend_id', $blendSheet->blend_id)
                        ->where('container_number', $containerNumber)
                        ->first();

                    if ($existingContainer) {
                        // UPDATE existing container
                        $existingContainer->update([
                            'seal_number' => $contain['sealNumber'],
                            'tare_weight' => $contain['containerTareWeight'] ?? null,
                            'pallet_weight' => $contain['palletWeight'] ?? null,
                        ]);

                        $submittedContainerNumbers[] = $containerNumber;
                    } else {
                        // CREATE new container
                        $container = [
                            'container_id' => $customIds->generateId(),
                            'container_number' => $containerNumber,
                            'seal_number' => $contain['sealNumber'],
                            'tare_weight' => $contain['containerTareWeight'] ?? null,
                            'pallet_weight' => $contain['palletWeight'] ?? null,
                            'blend_id' => $blendSheet->blend_id
                        ];

                        ShipmentContainer::create($container);
                        $submittedContainerNumbers[] = $containerNumber;
                    }
                }

                // DELETE containers that were removed (exist in DB but not in submitted data)
                ShipmentContainer::where('blend_id', $blendSheet->blend_id)
                    ->whereNotIn('container_number', $submittedContainerNumbers)
                    ->delete();
            }

            $customId = new CustomIds();
            foreach ($request->supervisor as $key => $user){
                $supervisor = [
                    'supervision_id' => $customId->generateId(),
                    'blend_id' => $id,
                    'supervisor_type' => $key,
                    'supervisor_name' => $user['name'],
                    'compiled_by' => auth()->user()->user_id
                ];
                BlendSupervision::create($supervisor);
            }
            foreach ($request->new as $key => $new){
                $newMaterial = [
                    'material_id' => $customId->generateId(),
                    'blend_id' => $id,
                    'material_type' => $key,
                    'total' => $new['name'],
                    'condition' => 1
                ];
                BlendMaterial::create($newMaterial);
            }
            foreach ($request->inUse as $key => $used){
                $usedMaterial = [
                    'material_id' => $customId->generateId(),
                    'blend_id' => $id,
                    'material_type' => $key,
                    'total' => $used['name'],
                    'condition' => 2
                ];
                BlendMaterial::create($usedMaterial);
            }
            foreach ($request->damaged as $key => $used){
                $usedMaterial = [
                    'material_id' => $customId->generateId(),
                    'blend_id' => $id,
                    'material_type' => $key,
                    'total' => $used['name'],
                    'condition' => 3
                ];
                BlendMaterial::create($usedMaterial);
            }
            if ($blendSheet->status == 4){
                BlendSheet::where('blend_id', $id)->update(['status' => 4]);
            }elseif($blendSheet->status == 3) {
                BlendSheet::where('blend_id', $id)->update(['status' => 3]);
            }else{
                BlendSheet::where('blend_id', $id)->update(['status' => 2]);
            }
            BlendTea::where('blend_id', $id)->update(['status' => 1]);
            $this->logger->create();
            DB::commit();
            return redirect()->route('clerk.viewBlendProcessing')->with('success', 'Success! Blend sheet updated successfully');
        } catch (Exception $exception) {
            DB::rollback();
            return redirect()->back()->with('error', 'Oops! An error occurred please try again '.$exception->getMessage());
        }
    }
    public function markBlendTeaAsShipped($id)
    {
        DB::beginTransaction();
        try {
            BlendSheet::where('blend_id', $id)->update(['status' => 3]);
            BlendTea::where('blend_id', $id)->update(['status' => 1]);
            $this->logger->create();
            DB::commit();
            return redirect()->back()->with('success', 'Success! Blend sheet details updated successfully');
        } catch (Exception $e) {
            // Rollback the transaction if an exception occurs
            DB::rollback();
            // Handle or log the exception
            return redirect()->back()->with('error', 'Oops! An error occurred please try again');
        }
    }
    public function editBlendSheet($id)
    {
       $sheet = BlendSheet::find($id);
        $blendTeas = BlendTea::where('blend_id', $id)->count();
        $stations = Station::where('status', 1)->get();
        $ports = Destination::all();
        $clients = DB::table('currentstock')
            ->whereIn('station_id', $stations->pluck('station_id')->toArray())
            ->groupBy('client_id', 'client_name')
            ->select('client_id', 'client_name')
            ->get();
        return view('clerk::shipping.editBlend')->with(['ports' => $ports, 'stations' => $stations, 'clients' => $clients, 'sheet' => $sheet, 'blendTeas' => $blendTeas]);
    }
    public function updateBlend(Request $request, $id)
    {
        $request->validate([
            'client' => 'required|string',
            'station' => 'required|string',
            'shipmentNumber' =>'required|string|unique:blend_sheets,blend_number,'.$id.',blend_id',
            'contract' => 'required|string',
            'destination' => 'required|string',
            'packagingType' =>'required|string',
            'containerSize' => 'required|string',
            'consignee' => 'required|string',
            'mark' => 'required|string',
        ]);

        $sheet = [
            'client_id' => $request->client,
            'vessel_name' => $request->vessel,
            'blend_number' => $request->shipmentNumber,
            'contract' => $request->contract,
            'destination_id' => $request->destination,
            'garden' => $request->gardenName,
            'grade' => $request->blendGrade,
            'package_type' => $request->packagingType,
            'container_size' => $request->containerSize,
            'consignee' => $request->consignee,
            'shipping_mark' => $request->mark,
            'standard_details' => $request->shippingInstruction,
            'station_id' => $request->station,
            'address' => [
                'address' => $request->address,
                'mobile' => $request->mobile,
                'box' => $request->box,
                'state' => $request->state
            ],
            'booking_number' => $request->bookingNumber,
            'si_number' => $request->shippingNumber,
        ];
        BlendSheet::where('blend_id', $id)->update($sheet);
        $this->logger->create();
        return redirect()->route('clerk.addBlendTeas', $id)->with('success', 'Success! Blend sheet updated successfully');
    }
    public function viewBlendBalances()
    {
        $balances = DB::table('blendBalances')->where('current_weight', '>', 0)->orderBy('blend_number', 'asc')->get();
        return view('clerk::stock.blendBalances')->with(['balances' => $balances]);
    }
    public function downloadBlendSheet($id)
    {
        return $this->AppClass->downloadBlendJob($id);
    }
    public function downloadBlendPackingList($id)
    {
        return $this->AppClass->downloadBlendPackingList($id);
    }
    public function downloadBlendPackingListCont($id)
    {
        return $this->AppClass->downloadBlendPackingListCont($id);
    }
    public function downloadBlendDriverClearance($id)
    {
        return $this->AppClass->driverClearanceBlends($id);
    }
    /*public function viewDirectDeliveries()
    {
       $orders = DeliveryOrder::join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->join('stock_ins', 'stock_ins.delivery_id', '=', 'delivery_orders.delivery_id')
            ->leftJoin('stations', 'stations.station_id', '=', 'stock_ins.station_id')
            ->leftJoin('warehouses', 'warehouses.warehouse_id', '=', 'delivery_orders.warehouse_id')
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'stock_ins.transporter_id')
            ->leftJoin('drivers', 'drivers.driver_id', '=', 'stock_ins.driver_id')
            ->leftJoin('delivery_notes', function ($join) {
                $join->on('delivery_notes.delivery_number', 'stock_ins.delivery_number');
            })
            ->select(
                'client_name',
                'tea_id',
                'warehouse_name',
                'station_name',
                'delivery_orders.status as order_status',
                'stock_ins.delivery_number',
                'delivery_orders.created_at',
                'drivers.driver_id', 'transporters.transporter_id', 'registration', 'id_number', 'drivers.phone', 'driver_name', 'path'
            )
            ->selectRaw('SUM(total_pallets) AS total_packages')
            ->selectRaw('SUM(net_weight) AS total_net_weight')
            ->where('delivery_orders.delivery_type', 2)
            ->groupBy('delivery_number', 'client_name')
            ->orderBy('delivery_orders.created_at', 'desc')
            ->get();
        $stations = Station::where('status', 1)->get();
        $clients = Client::all();

        $transporters = Transporter::all();
        $users = Driver::all();

        return view('clerk::DOS.directDelivery')->with(['orders' => $orders, 'stations' => $stations, 'clients' => $clients, 'transporters' => $transporters, 'users' => $users]);
    }*/

    public function viewDirectDeliveries(Request $request)
    {
        // ── Build filterable query ──────────────────────────────────────────────
        $query = DB::table('stock_ins as si')
            ->join('delivery_orders as do', 'do.delivery_id', '=', 'si.delivery_id')
            ->join('clients as c', 'c.client_id', '=', 'do.client_id')
            ->leftJoin('stations as st', 'st.station_id', '=', 'si.station_id')
            ->leftJoin('warehouses as w', 'w.warehouse_id', '=', 'do.warehouse_id')
            ->leftJoin('transporters as t', 't.transporter_id', '=', 'si.transporter_id')
            ->leftJoin('drivers as d', 'd.driver_id', '=', 'si.driver_id')
            ->leftJoin('delivery_notes as dn', 'dn.delivery_number', '=', 'si.delivery_number')
            ->where('do.delivery_type', 2)
            ->select([
                'si.delivery_number',
                'c.client_name',
                'w.warehouse_name',
                'st.station_name',
                'do.status as order_status',
                'do.created_at',
                't.transporter_id',
                'd.driver_id',
                'si.registration',
                'd.id_number',
                'd.phone',
                'd.driver_name',
                'dn.path',
                'do.dispatch_date',
                't.transporter_name',
                DB::raw('FROM_UNIXTIME(si.date_received) as arrival_date'),
                DB::raw('SUM(si.total_pallets) as total_packages'),
                DB::raw('SUM(si.net_weight) as total_net_weight'),
            ])
            ->groupBy(
                'si.delivery_number',
                'c.client_name',
                'w.warehouse_name',
                'st.station_name',
                'do.status',
                'do.created_at',
                't.transporter_id',
                'd.driver_id',
                'si.registration',
                'd.id_number',
                'd.phone',
                'd.driver_name',
                'dn.path',
                'do.dispatch_date',
                't.transporter_name',
                DB::raw('FROM_UNIXTIME(si.date_received)')
            )
            ->orderBy('do.created_at', 'desc');

        // ── Filters ─────────────────────────────────────────────────────────────
        if ($request->filled('dispatch_from')) {
            $query->whereDate('do.dispatch_date', '>=', $request->dispatch_from);
        }
        if ($request->filled('dispatch_to')) {
            $query->whereDate('do.dispatch_date', '<=', $request->dispatch_to);
        }
        if ($request->filled('arrival_from')) {
//            $query->whereRaw('DATE(FROM_UNIXTIME(si.date_received)) >= ?', [$request->arrival_from ?? Carbon::startOfMonth()]);
              $query->where('si.date_received', '>=', Carbon::parse($request->arrival_from ?? now()->subMonths(3))->startOfDay()->timestamp
            );
        }
        if ($request->filled('arrival_to')) {
            $query->whereRaw('DATE(FROM_UNIXTIME(si.date_received)) <= ?', [$request->arrival_to]);
        }
        if ($request->filled('client_id')) {
            $query->where('do.client_id', $request->client_id);
        }
        if ($request->filled('delivery_number')) {
            $query->where('si.delivery_number', 'like', '%' . $request->delivery_number . '%');
        }
        if ($request->filled('transporter_id')) {
            $query->where('si.transporter_id', $request->transporter_id);
        }

        // ── Export ───────────────────────────────────────────────────────────────
        if ($request->filled('export')) {
            return (new \App\Exports\DirectDeliveryExport($request->only([
                'dispatch_from', 'dispatch_to',
                'arrival_from',  'arrival_to',
                'client_id', 'delivery_number', 'transporter_id',
            ])))->download();
        }

        $orders = $query->limit(200)->get();

        // ── Sidebar dropdowns (cached — they rarely change) ──────────────────────
        $stations     = cache()->remember('stations_active', 300, fn() => Station::where('status', 1)->get());
        $clients      = cache()->remember('clients_all',     300, fn() => Client::all());
        $transporters = cache()->remember('transporters_all', 300, fn() => Transporter::all());
        $users        = Driver::all(); // not cached — may change more often

        return view('clerk::DOS.directDelivery', compact(
            'orders', 'stations', 'clients', 'transporters', 'users'
        ));
    }

    public function updateTransporterDetails(Request $request, $id)
    {
        $request->validate([
            'transporter' => 'required|string',
            'registration' => 'required|string',
            'idNumber' => 'required|string',
            'driverName' => 'required|string'
        ]);

        if ($request->transporter == 'other'){
            $transporter = Transporter::updateOrCreate(['transporter_name' => $request->transporter_other],
                ['transporter_id' => (new CustomIds())->generateId(), 'transporter_type' => 1, 'created_by' => Auth::id()]
            );
        }else{
            $transporter = Transporter::find($request->transporter);
        }

        $driver = Driver::updateOrCreate(['id_number' => $request->idNumber],
            ['driver_id' => (new CustomIds())->generateId(), 'driver_name' => $request->driverName, 'phone' => $request->driverPhone]
        );

        StockIn::where(['delivery_number' => base64_decode($id), 'delivery_type' => null])->update([
            'transporter_id' => $transporter->transporter_id,
            'driver_id' => $driver['driver_id'],
            'registration' => $request->registration
        ]);

        return back()->with('success', 'Transporter Details updated successfully');
    }

    public function updateCollection(Request $request, $loadingNumber)
    {
        $request->validate([
            'issued' => 'required|in:true,false,1,0',
        ]);

        // Convert to boolean properly
        $issued = filter_var($request->issued, FILTER_VALIDATE_BOOLEAN);

        $collection = $issued ? 'under_collection' : 'in_hand';

        LoadingInstruction::where('loading_number', $loadingNumber)->update([
            'collection' => $collection
        ]);

        return response()->json([
            'message' => 'Orders updated successfully',
        ]);
    }
    public function addDirectDelivery()
    {
        $clients = Client::all();
        $gardens = Garden::all();
        $grades = Grade::all();
        $warehouses = Warehouse::all();
        $locationId = Station::where('station_id', auth()->user()->station_id)->first()->location_id;
        $stations = Station::where('status', 1)->get();
        $pmls = Station::where('status', 1)->get();
        return view('clerk::DOS.addDirectDelivery')->with(['clients' => $clients, 'gardens' => $gardens, 'grades' => $grades, 'warehouses' => $warehouses, 'stations' => $stations, 'pmls' => $pmls]);

    }
    public function viewDirectDeliveryOrder($id)
    {
        $delId = base64_decode($id);
        $orders = DeliveryOrder::join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->join('stock_ins', 'stock_ins.delivery_id', '=', 'delivery_orders.delivery_id')
            ->join('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
            ->join('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
            ->leftJoin('stations', 'stations.station_id', '=', 'stock_ins.station_id')
            ->leftJoin('warehouses', 'warehouses.warehouse_id', '=', 'delivery_orders.warehouse_id')
            ->select('client_name', 'invoice_number', 'tea_id', 'garden_name', 'grade_name',  'delivery_orders.delivery_id', 'order_number', 'client_name', 'tea_id', 'warehouse_name', 'station_name', 'delivery_orders.status as order_status', 'garden_name', 'grade_name', 'packet', 'weight')
            ->where(['delivery_orders.delivery_type' => 2, 'delivery_number' => $delId])
            ->latest('delivery_orders.created_at')
            ->get();
        return view('clerk::DOS.viewDirectDelivery')->with(['orders' => $orders, 'delivery' => $delId]);
    }
    public function importStock(Request $request)
    {
        $data = [
            'station_id' => $request->stationId,
            'bay_id' => $request->bayId,
            'client_id' => $request->clientId
        ];
        $import = new ImportBulkyTeas($data);
        // Perform the import
        Excel::import($import, $request->file('uploadFile'), null, \Maatwebsite\Excel\Excel::XLSX, ['calculateFormulas' => true]);
        // Get specific errors
        $errors = $import->getErrors();
        if (!empty($errors)) {
            return redirect()->back()->with('importErrors', $errors);
        } else {
            // If no errors, continue with your desired action
            return redirect()->back()->with('success', 'Successful! Tea have been imported to the system successfully');
        }
    }

    /**
     * Receive parsed rows from JS, store in session, redirect to preview page.
     */
    public function previewImport(Request $request)
    {
        $request->validate([
            'clientId'  => 'required',
            'stationId' => 'required',
            'bayId'     => 'required',
            'records'   => 'required|array|min:1',
        ]);

        $required = [
            'Tea Type', 'Garden', 'Grade', 'Package', 'Packages', 'Package Tare', 'Gross Weight', 'Total Tare', 'Pallete Weight', 'Pallete Weight', 'Sample Received',
            'Printed Net Weight', 'Actual Net Weight', 'Production Date', 'Expiry Date', 'Warehouse Bay', 'Delivery Number', 'Invoice Number', 'Producer Warehouse', 'RA'
        ];

        $records = [];
        foreach ($request->records as $row) {
            // Skip completely blank rows
            if (empty(array_filter($row, fn($v) => $v !== null && $v !== ''))) {
                continue;
            }

            $missing               = array_values(array_filter($required, fn($f) => empty($row[$f] ?? null)));
            $row['_incomplete']    = !empty($missing);
            $row['_missing']       = $missing;
            $records[]             = $row;
        }

        // Store everything in session — no temp file needed
        session([
            'import_records'  => $records,
            'import_meta'     => [
                'clientId'  => $request->clientId,
                'stationId' => $request->stationId,
                'bayId'     => $request->bayId,
            ],
            'import_headers'  => array_keys((array) ($request->records[0] ?? [])),
        ]);

        return response()->json([
            'success'  => true,
            'redirect' => route('clerk.importPreviewPage'),
        ]);
    }

    /**
     * Show the preview Blade page from session data.
     */
    public function importPreviewPage()
    {
        if (!session()->has('import_records')) {
            return redirect()->route('clerk.viewDirectDeliveries')
                ->withErrors(['error' => 'No import data found. Please upload again.']);
        }

        $records      = session('import_records');
        $meta         = session('import_meta');
        $headerRow    = session('import_headers');

        // Filter out internal keys from display headers
        $displayCols  = array_filter($headerRow, fn($h) => !str_starts_with($h, '_'));

        $transporters = Transporter::all();   // your model
        $warehouses   = Station::all();       // producer warehouses
        $users        = Driver::select('id_number', 'driver_name', 'phone')->get();

        return view('clerk::DOS.preview-import',
            compact('records', 'meta', 'displayCols', 'transporters', 'warehouses', 'users')
        );
    }

    /**
     * Save confirmed records from session to DB.
     */
    public function saveImport(Request $request)
    {
        $request->validate([
            'transporter_id'     => 'required',
            'driver_name'        => 'required|string',
            'driver_phone'       => 'required|string',
            'registration'       => 'required|string',
            'id_number'          => 'required|string',
            'dispatch_date'      => 'required|date',
            'arrival_date'       => 'required',
            'delivery_note'      => 'required|file|mimes:png,jpg,jpeg,pdf|max:2048'
        ]);

        $records = session('import_records');

        if (empty($records)) {
            return back()->withErrors(['error' => 'Session expired. Please upload the file again.']);
        }

        $required = [
            'Tea Type', 'Garden', 'Grade', 'Package', 'Packages', 'Package Tare', 'Gross Weight', 'Total Tare', 'Pallete Weight', 'Pallete Weight', 'Sample Received',
            'Printed Net Weight', 'Actual Net Weight', 'Production Date', 'Expiry Date', 'Warehouse Bay', 'Delivery Number', 'Invoice Number', 'Producer Warehouse', 'RA'
        ];

        $meta = session('import_meta');

        $imported = 0;
        $skipped  = 0;
        $errors   = [];

        DB::beginTransaction();

        try {
            foreach ($records as $index => $record) {
                $deliveryNumber = $record['Delivery Number'];

                // Skip incomplete rows (flagged during preview)
                if (!empty($record['_incomplete'])) {
                    $skipped++;
                    continue;
                }

                try {
                    // ── Resolve related models ──────────────────────────────
                    $garden = Garden::where('garden_name', trim($record['Garden']))->first();
                    if (!$garden) {
                        $errors[] = 'Row ' . ($index + 1) . ': Garden "' . $record['Garden'] . '" not found.';
                        continue;
                    }

                    $grade = Grade::where('grade_name', trim($record['Grade']))->first();
                    if (!$grade) {
                        $errors[] = 'Row ' . ($index + 1) . ': Grade "' . $record['Grade'] . '" not found.';
                        continue;
                    }

                    $client = Client::where('client_id', $meta['clientId'])->first();
                    if (!$client) {
                        $errors[] = 'Row ' . ($index + 1) . ': Client not found.';
                        continue;
                    }

                    // Producer warehouse comes from the form select, not Excel column
                    $warehouse = Warehouse::where('warehouse_name', $record['Producer Warehouse'])->first();
                    if (!$warehouse) {
                        $errors[] = 'Row ' . ($index + 1) . ': Producer warehouse not found.';
                        continue;
                    }

                    // Bay: try to match by name from Excel first, fallback to selected bay
                    $bay = WarehouseBay::where([
                        'station_id' => $meta['stationId'],
                        'bay_name'   => trim($record['Warehouse Bay'] ?? ''),
                    ])->first();

                    // ── Enums ───────────────────────────────────────────────
                    $package = match (strtoupper(trim($record['Package'] ?? ''))) {
                        'PB'    => 1,
                        'PS'    => 2,
                        default => null,
                    };

                    $teaType = match (strtoupper(trim($record['Tea Type'] ?? ''))) {
                        'AUCTION TEAS'  => 1,
                        'AUCTION TEA'   => 1,
                        'PRIVATE TEAS'  => 2,
                        'PRIVATE TEA'   => 2,
                        'FACTORY TEAS'  => 3,
                        'FACTORY TEA'   => 3,
                        default         => 4,
                    };

                    // ── Duplicate check (same as onRow) ─────────────────────
                    $existingOrder = DeliveryOrder::withoutTrashed()->where([
                        'invoice_number' => $record['Invoice Number'],
                        'client_id'      => $meta['clientId'],
                        'garden_id'      => $garden->garden_id,
                    ])->first();

                    if ($existingOrder) {
                        $errors[] = 'Row ' . ($index + 1) . ': Duplicate invoice "' . $record['Invoice Number'] . '" for client ' . $client->client_name . '.';
                        $skipped++;
                        continue;
                    }

                    // ── Dates (SheetJS sends formatted strings, not Excel serials) ──
                    $productionDate = !empty($record['Production Date'])
                        ? \Carbon\Carbon::parse($record['Production Date'])
                        : null;

                    $expiryDate = !empty($record['Expiry Date'])
                        ? \Carbon\Carbon::parse($record['Expiry Date'])
                        : null;

                    // ── Generate IDs ────────────────────────────────────────
                    $deliveryId = (new CustomIds())->generateId();
                    $stockId    = (new CustomIds())->generateId();

                    $driver = Driver::firstOrCreate(['id_number' => $request->id_number],
                        [
                            'driver_id' => (new CustomIds())->generateId(),
                            'phone' => $request->driver_phone,
                            'driver_name' => $request->driver_name,
                        ]

                    );

                    if ($request->transporter_other) {
                        $transporter = Transporter::firstOrCreate(['transporter_name' => $request->transporter_other],
                        [
                            'transporter_id' => (new CustomIds())->generateId(),
                            'transporter_type' => 1,
                            'created_by' => \auth()->user()->user_id
                        ]);
                    }else{
                        $transporter = Transporter::where('transporter_id', $request->transporter_id)->first();
                    }

                    // ── Insert DeliveryOrder ────────────────────────────────
                    DeliveryOrder::create([
                        'delivery_id'     => $deliveryId,
                        'delivery_type'   => 2,
                        'order_number'    => $record['Order Number'] ?? $record['Delivery Number'],
                        'client_id'       => $client->client_id,
                        'tea_id'          => $teaType,
                        'garden_id'       => $garden->garden_id,
                        'grade_id'        => $grade->grade_id,
                        'packet'          => $record['Packages'] ?? null,
                        'package'         => $package,
                        'unit_weight'     => $record['Actual Net Weight'] ?? null,
                        'weight'          => number_format($record['Actual Net Weight']/$record['Packages'], 2, '.', ''),
                        'gross_weight'    => number_format(($record['Actual Net Weight']/$record['Packages']) + $record['Package Tare'], 2, '.', ''),
                        'total_weight'    => number_format($record['Actual Net Weight'] + ($record['Packages']   * $record['Package Tare']) + $record['Pallete Weight'], 2, '.', ''),
                        'warehouse_id'    => $warehouse->warehouse_id,
                        'invoice_number'  => $record['Invoice Number']     ?? null,
                        'production_date' => $productionDate,
                        'expiry_date'     => $expiryDate,
                        'dispatch_date'   => $request->dispatch_date,
                        'printed_weight'  => $record['Printed Net Weight'] ?? null,
                        'height'          => $record['Pallete Height'],
                        'created_by'      => auth()->user()->user_id,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);

                    // ── Insert StockIn ──────────────────────────────────────
                    StockIn::create([
                        'stock_id'        => $stockId,
                        'delivery_id'     => $deliveryId,
                        'station_id'      => $meta['stationId'],
                        'date_received'   => strtotime($request->arrival_date),
                        'delivery_number' => $record['Delivery Number'],
                        'warehouse_bay'   => $bay ? $bay->bay_id : $meta['bayId'],
                        'total_weight'    => number_format($record['Actual Net Weight'] + ($record['Packages']   * $record['Package Tare']) + $record['Pallete Weight'], 2, '.', ''),
                        'total_pallets'   => $record['Packages']           ?? null,
                        'pallet_weight'   => $record['Pallete Weight']     ?? null,
                        'package_tare'    => $record['Package Tare']       ?? 0,
                        'net_weight'      => number_format($record['Actual Net Weight']  ?? null, 2, '.', ''),
                        'transporter_id'  => $transporter->transporter_id,
                        'driver_id'       => $driver->driver_id,
                        'registration'    => $request->registration,
                        'ra'              => $record['RA'] ?? null,
                        'sample_received' => $record['Sample Received'] ?? null,
                        'gain_loss'       => $record['Gain/Loss']       ?? null,
                        'user_id'         => auth()->user()->user_id
                    ]);

                    $imported++;

                } catch (\Exception $e) {
                    DB::rollBack();
                    $errors[] = 'Row ' . ($index + 1) . ' (' . ($record['Invoice Number'] ?? '?') . '): ' . $e->getMessage();
                }
            }

            $file = $request->file('delivery_note');
            $ext = $file->getClientOriginalExtension();
            $fileName = (string) Str::uuid() . '.' .$ext;
            $path = $file->storeAs('/', $fileName, 'delivery_notes');

            DeliveryNote::updateOrCreate(['delivery_number' => $deliveryNumber], ['path' => '/'.$path]);

            DeliveryOrder::join('stock_ins', 'stock_ins.delivery_id', '=', 'delivery_orders.delivery_id')
                ->where(['stock_ins.delivery_number' => $deliveryNumber, 'delivery_orders.delivery_type' => 2])
                ->update(['delivery_orders.status' => 2]);
            $this->logger->create();

            DB::commit();
            session()->forget(['import_records', 'import_meta', 'import_headers']);

            $message = "Import complete! Saved: {$imported}, Skipped: {$skipped}.";
            if (!empty($errors)) {
                return redirect()->route('clerk.viewDirectDeliveries')
                    ->with('success', $message)
                    ->with('importErrors', $errors);
            }

            return redirect()->route('clerk.viewDirectDeliveries')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Import failed: ' . $e->getMessage()]);
        }

        /*DB::beginTransaction();
        try {
            $imported = 0;
            $skipped  = 0;
            $errors   = [];

            foreach ($records as $index => $record) {
                // Skip incomplete rows
                if (!empty($record['_incomplete'])) {
                    $skipped++;
                    continue;
                }

                try {
                    DirectDelivery::create([
                        'delivery_number'    => $request->delivery_number,
                        'client_id'          => $meta['clientId'],
                        'station_id'         => $meta['stationId'],
                        'bay_id'             => $meta['bayId'],
                        'transporter_id'     => $request->transporter_id,
                        'driver_name'        => $request->driver_name,
                        'phone'              => $request->driver_phone,
                        'registration'       => $request->registration,
                        'dispatch_date'      => $request->dispatch_date,
                        'producer_warehouse' => $request->producer_warehouse,
                        // Excel columns
                        'invoice_number'     => $record['Invoice Number']    ?? null,
                        'tea_type'           => $record['Tea Type']           ?? null,
                        'order_number'       => $record['Order Number']       ?? null,
                        'garden'             => $record['Garden']             ?? null,
                        'grade'              => $record['Grade']              ?? null,
                        'packet'             => $record['Package']            ?? null,
                        'total_packages'     => $record['Packages']           ?? null,
                        'package_tare'       => $record['Package Tare']       ?? null,
                        'gross_weight'       => $record['Gross Weight']       ?? null,
                        'printed_net_weight' => $record['Printed Net Weight'] ?? null,
                        'total_tare'         => $record['Total Tare']         ?? null,
                        'pallet_weight'      => $record['Pallete Weight']     ?? null,
                        'total_net_weight'   => $record['Actual Net Weight']  ?? null,
                        'gain_loss'          => $record['Gain/Loss']          ?? null,
                        'pallet_height'      => $record['Pallete Height']     ?? null,
                        'warehouse_bay'      => $record['Warehouse Bay']      ?? null,
                        'production_date'    => $record['Production Date']    ?? null,
                        'expiry_date'        => $record['Expiry Date']        ?? null,
                        'sample_received'    => $record['Sample Received']    ?? null,
                        'ra'                 => $record['RA']                 ?? null,
                        'user_id'            => auth()->id(),
                    ]);
                    $imported++;

                } catch (\Exception $e) {
                    $errors[] = 'Row ' . ($index + 1) . ': ' . $e->getMessage();
                }
            }

            \DB::commit();
            session()->forget(['import_records', 'import_meta', 'import_headers']);

            $message = "Import complete! Saved: {$imported}, Skipped (incomplete): {$skipped}";
            if (!empty($errors)) {
                $message .= ' | Errors: ' . implode('; ', $errors);
            }

            return redirect()->route('clerk.viewDirectDeliveries')->with('success', $message);

        } catch (\Exception $e) {
            \DB::rollBack();
            return back()->withErrors(['error' => 'Import failed: ' . $e->getMessage()]);
        }*/
    }

    public function registerDirectDeliveryOrder(Request $request)
    {
        $customId = new CustomIds();
        $deliveryId = $customId->generateId();
        $stockId = $customId->generateId();
        $exits = DeliveryOrder::where(['client_id' => $request->client_id, 'invoice_number' => $request->invoice_number, 'garden_id' => $request->garden_id])->exists();
        if ($exits){
            return redirect()->back()->with('error', 'Oops! The invoice number for this client exists already exists');
        }
        DB::beginTransaction();
        try {
        $do = [
            'delivery_id' => $deliveryId,
            'order_number' => $request->order_number,
            'tea_id' => $request->tea_id,
            'garden_id' => $request->garden_id,
            'grade_id' => $request->grade_id,
            'packet' => $request->packet,
            'weight' => $request->weight,
            'package' => $request->package,
            'invoice_number' => $request->invoice_number,
            'client_id' => $request->client_id,
            'created_by' => auth()->user()->user_id,
            'delivery_type' => 2,
            'warehouse_id' => $request->warehouse_id,
            'production_date' => $request->productionDate,
            'expiry_date' => $request->expiryDate,
        ];
        DeliveryOrder::create($do);
        $stock = [
            'stock_id' => $stockId,
            'delivery_id' => $deliveryId,
            'station_id' => $request->station_id,
            'date_received' => time(),
            'delivery_number' => $request->order_number,
            'warehouse_bay' => $request->bay,
            'total_weight' => $request->netWeight,
            'total_pallets' => $request->packet,
            'pallet_weight' => $request->pallet_weight,
            'package_tare' => $request->tare,
            'net_weight' => $request->weight,
            'user_id' => auth()->user()->user_id
        ];
            StockIn::create($stock);
            $this->logger->create();
            DB::commit();
            return redirect()->route('clerk.viewDirectDeliveries')->with('success', 'Direct delivery added successfully');
        } catch (Exception $e) {
//            // Rollback the transaction if an exception occurs
            DB::rollback();
//            // Handle or log the exception
            return redirect()->back()->with('error', 'Oops! An error occurred please try again');
        }
    }
    public function storeDirectDeliveryOrder(Request $request)
    {
        $customId = new CustomIds();
        $deliveryId = $customId->generateId();
        $stockId = $customId->generateId();
        $exits = DeliveryOrder::where(['client_id' => $request->client_id, 'invoice_number' => $request->invoice_number, 'garden_id' => $request->garden_id])->exists();
        if ($exits){
            return redirect()->back()->with('error', 'Oops! The invoice number for this client exists already exists');
        }
        DB::beginTransaction();
        try {
        $do = [
            'delivery_id' => $deliveryId,
            'order_number' => $request->orderNumber,
            'tea_id' => $request->teaId,
            'garden_id' => $request->gardenId,
            'grade_id' => $request->gradeId,
            'packet' => $request->totalPackages,
            'package' => $request->packageType,
            'unit_weight' => $request->bagNetWeight,
            'gross_weight' => $request->bagGrossWeight,
            'total_weight' => $request->totalGrossWeight,
            'warehouse_id' => $request->warehouseId,
            'weight' => $request->totalNetWeight,
            'invoice_number' => $request->invoiceNumber,
            'client_id' => $request->clientId,
            'production_date' => $request->productionDate,
            'expiry_date' => $request->expiryDate,
            'created_by' => auth()->user()->user_id,
            'delivery_type' => 2,
        ];
        DeliveryOrder::create($do);

        $stock = [
            'stock_id' => $stockId,
            'delivery_id' => $deliveryId,
            'station_id' => $request->stationId,
            'date_received' => time(),
            'delivery_number' => $request->orderNumber,
            'warehouse_bay' => $request->bayId,
            'total_weight' => $request->totalGrossWeight,
            'total_pallets' => $request->totalPackages,
            'pallet_weight' => $request->palleteWeight,
            'package_tare' => $request->packingTare,
            'net_weight' => $request->totalNetWeight,
            'user_id' => auth()->user()->user_id
        ];
            StockIn::create($stock);
            $this->logger->create();
            DB::commit();
            return redirect()->route('clerk.viewDirectDeliveries')->with('success', 'Direct delivery added successfully');
        } catch (Exception $e) {
//            // Rollback the transaction if an exception occurs
            DB::rollback();
//            // Handle or log the exception
            return redirect()->back()->with('error', 'Oops! An error occurred please try again '.$e->getMessage());
        }
    }
    public function receiveDirectDeliveries(Request $request, $id)
    {
        $request->validate([
            // 'delivery_note' => 'required|image|mimes:png,jpg,jpeg|max:5120'
            'delivery_note' => 'required|file|mimetypes:image/png,image/jpeg,application/pdf|max:5120',
        ]);

        try {
            $file = $request->file('delivery_note');
            $ext = $file->getClientOriginalExtension();
            $fileName = (string) Str::uuid() . '.' .$ext;
            $path = $file->storeAs('/', $fileName, 'delivery_notes');

            DeliveryNote::updateOrCreate(['delivery_number' => base64_decode($id)], ['path' => '/'.$path]);

            DeliveryOrder::join('stock_ins', 'stock_ins.delivery_id', '=', 'delivery_orders.delivery_id')
                ->where(['stock_ins.delivery_number' => base64_decode($id), 'delivery_orders.delivery_type' => 2])
                ->update(['delivery_orders.status' => 2]);
            $this->logger->create();
            return redirect()->back()->with('success', 'Tea received and stock updated successfully');
        }catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Oops! An error occurred please try again');
        }
    }
    public function downloadDirectDeliveries($id)
    {
        list($doNumber, $type) = explode(':', base64_decode($id));
        $orders = DeliveryOrder::join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->join('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
            ->join('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
            ->join('stock_ins', 'stock_ins.delivery_id', '=', 'delivery_orders.delivery_id')
            ->leftJoin('warehouses', 'warehouses.warehouse_id', '=', 'delivery_orders.warehouse_id')
            ->leftJoin('sub_warehouses', 'sub_warehouses.sub_warehouse_id', '=', 'delivery_orders.sub_warehouse_id')
            ->join('stations', 'stations.station_id', '=', 'stock_ins.station_id')
            ->join('warehouse_bays', 'warehouse_bays.bay_id', '=', 'stock_ins.warehouse_bay')
            ->join('user_infos', 'user_infos.user_id', '=', 'delivery_orders.created_by')
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'stock_ins.transporter_id')
            ->leftJoin('drivers', 'drivers.driver_id', '=', 'stock_ins.driver_id')
            ->select('delivery_orders.delivery_type', 'package', 'sub_warehouse_name', 'order_number', 'clients.client_name', 'delivery_orders.tea_id', 'warehouses.warehouse_name', 'stock_ins.total_pallets', 'stock_ins.net_weight', 'delivery_orders.status as order_status', 'gardens.garden_name', 'grades.grade_name', 'delivery_orders.invoice_number', 'stations.station_name', 'warehouse_bays.bay_name', 'delivery_orders.total_weight', 'date_received', 'delivery_orders.created_at', 'delivery_orders.status', 'first_name', 'surname', 'delivery_orders.created_by', 'registration', 'drivers.id_number', 'drivers.phone', 'driver_name', 'transporters.transporter_name')
            ->where(['delivery_orders.delivery_type' => 2, 'delivery_number' => $doNumber])
            ->get();

        if ($type == 2){
            return Excel::download(new ExportDirectDeliveryOrders($orders), 'DIRECT DELIVERY TALLY'.time().'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        }

        $detail = $orders[0];
        $staffName = auth()->user()->user->surname.' '.auth()->user()->user->first_name;
        $printed = auth()->user()->user;
        $by = $printed->first_name.' '.$printed->surname;

        // Render Blade view
        $html = View::make('clerk::downloads.delivery_order_tally', compact('orders', 'detail', 'staffName', 'by', 'printed'))->render();

        // Initialize mPDF with settings
        $mpdf = new Mpdf([
            'tempDir' => storage_path('app/mpdf_temp'),
            'mode'        => 'utf-8',
            'format'      => 'A4-P', // Landscape
            'orientation' => 'P',
            'margin_top'    => 2,
            'margin_bottom' => 7,
            'margin_left'   => 5,
            'margin_right'  => 5,
//            'setAutoTopMargin' => 'stretch',
            'setAutoBottomMargin' => 'stretch',
        ]);

        // Set footer for all pages
        $mpdf->SetHTMLFooter('
            <table width="100%">
                <tr>
                    <td align="left">Printed by: <strong>' . $by . '</strong></td>
                    <td align="center">Page {PAGENO} of {nbpg}</td>
                    <td align="right">Prepared by: <strong>' . $staffName . '</strong></td>
                </tr>
            </table>
        ');

        // Write HTML content
        $mpdf->WriteHTML($html);

        // Generate PDF filename
        $pdfFileName = 'TALLY OF GOODS '.str_replace('/', '', $detail->delivery_number).'.pdf';

        // Output PDF as downloadable file
        return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
        ]);
    }
    public function downloadInterDelNote($id)
    {
        return $this->AppClass->downloadInternalTransfer($id);
    }
    public function downloadExtraDelNote($id)
    {
        return $this->AppClass->downloadExternalTransfers($id);
    }

    public function downloadDelNote($id)
    {
        return $this->AppClass->downloadExternalDelNote($id);
    }
    public function downloadOutturReport($id)
    {
        return $this->AppClass->downloadBlendOutTurn($id);
    }
    public function downloadBlendBalances(Request $request){
        $blendBalances = DB::table('blendBalances')->where('current_weight', '>', 0);
        if (!is_null($request->client)) {
            $blendBalances->where('client_id', $request->client);
        }
        if (!is_null($request->from)) {
            $blendBalances->where('blend_date', '>=', $request->from);
        }
        if (!is_null($request->to)) {
            $blendBalances->where('blend_date', '<=', $request->to);
        }
        $balances = $blendBalances->get();
        $date = date('D, d-m-Y, h:i:s');
        $printed = auth()->user()->user;
        $by = $printed->first_name.' '.$printed->surname;
        $domPdfPath = base_path('vendor/dompdf/dompdf');
        \PhpOffice\PhpWord\Settings::setPdfRendererPath($domPdfPath);
        \PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF');
        $headers = ['size' => 8, 'name' => 'New Times Roman', 'bold' => true, 'space' => ['after' => 50, 'before' => 100]];
        $text = ['size' => 7, 'name' => 'New Times Roman', 'bold' => false, 'space' => ['after' => 40, 'before' => 100]];
        $table = new Table(['unit' => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP, 'width' => 1400 * 1400, 'align' => 'center']);
        $table->addRow();
        $table->addCell(600, ['borderSize' => 1])->addText('#', $headers, ['space' => ['before' => 100]]);
        $table->addCell(1800, ['borderSize' => 1])->addText('BLEND NUMBER', $headers, ['space' => ['before' => 50]]);
        $table->addCell(2600, ['borderSize' => 1])->addText('CLIENT NAME', $headers, ['space' => ['before' => 50]]);
        $table->addCell(2300, ['borderSize' => 1])->addText('GARDEN NAME', $headers, ['space' => ['before' => 50]]);
        $table->addCell(1800, ['borderSize' => 1])->addText('GRADE NAME', $headers, ['space' => ['before' => 50]]);
        $table->addCell(1200, ['borderSize' => 1])->addText('PACKAGES', $headers, ['space' => ['before' => 50]]);
        $table->addCell(1200, ['borderSize' => 1])->addText('WEIGHT', $headers, ['space' => ['before' => 50]]);
        $table->addCell(2000, ['borderSize' => 1])->addText("BLEND DATE", $headers, ['space' => ['before' => 50]]);
        $table->addCell(1800, ['borderSize' => 1])->addText('WAREHOUSE', $headers, ['space' => ['before' => 50]]);
        $totalPackets = 0;
        $totalWeight = 0;
        foreach ($balances as $key => $stock){
            $table->addRow();
            $table->addCell(600, ['borderSize' => 1])->addText(++$key, $text, ['space' => ['before' => 50]]);
            $table->addCell(1800, ['borderSize' => 1])->addText($stock->blend_number, $text, ['space' => ['before' => 50]]);
            $table->addCell(2000, ['borderSize' => 1])->addText($stock->client_name, $text, ['space' => ['before' => 50]]);
            $table->addCell(1800, ['borderSize' => 1])->addText($stock->garden, $text, ['space' => ['before' => 50]]);
            $table->addCell(1800, ['borderSize' => 1])->addText($stock->grade, $text, ['space' => ['before' => 50]]);
            $table->addCell(1200, ['borderSize' => 1])->addText(number_format($stock->current_packages, 2), $text, ['space' => ['before' => 50]]);
            $table->addCell(1200, ['borderSize' => 1])->addText(number_format($stock->current_weight, 2), $text, ['space' => ['before' => 50]]);
            $table->addCell(2000, ['borderSize' => 1])->addText($stock->blend_date, $text, ['space' => ['before' => 50]]);
            $table->addCell(1800, ['borderSize' => 1])->addText($stock->station_name, $text, ['space' => ['before' => 50]]);
            $totalPackets += $stock->current_packages;
            $totalWeight += $stock->current_weight;
        }
        $table->addRow();
        $table->addCell(7700, ['gridSpan' => 5])->addText();
        $table->addCell(900, ['borderSize' => 1])->addText(number_format($totalPackets, 2), $headers, ['space' => ['before' => 50]]);
        $table->addCell(1000, ['borderSize' => 1])->addText(number_format($totalWeight, 2), $headers, ['space' => ['before' => 50]]);
        $table->addCell(5000, ['gridSpan' => 2])->addText();
        $stock = new TemplateProcessor(storage_path('blend_stock_template.docx'));
        $stock->setComplexBlock('{table}', $table);
        $stock->setValue('date', $date);
        $stock->setValue('by', $by);
        $docPath = 'Files/TempFiles/BLEND BALANCES '.time().'.docx';
        $stock->saveAs($docPath);
        $phpWord = IOFactory::load($docPath);
        $contents = \PhpOffice\PhpWord\IOFactory::load($docPath);
        $pdfPath = 'Files/TempFiles/BLEND BALANCES'.time(). ".pdf";
        $converter =  new OfficeConverter($docPath, 'Files/TempFiles/');
        $converter->convertTo('BLEND BALANCES'.time().".pdf");
        unlink($docPath);
        return response()->download($pdfPath)->deleteFileAfterSend(true);
    }
    public function teaSamplesRequest()
    {
        $samples = TeaSamples::join('delivery_orders', 'delivery_orders.delivery_id', '=', 'tea_samples.delivery_id')
                   ->join('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
                   ->join('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
                   ->join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
                   ->select('sample_id', 'invoice_number', 'lot_number', 'grade_name', 'garden_name', 'client_name', 'sample_weight', 'sample_palletes', 'tea_samples.type', 'tea_samples.created_at')
                   ->orderBy('tea_samples.created_at', 'desc')
                   ->get();
        return view('clerk::stock.teaSamples')->with('samples', $samples);
    }
    public function withdrawSample($id)
    {
        $stock = DB::table('currentstock')->where('stock_id', $id)->first();
        return view('clerk::stock.withdrawSample')->with('data', $stock);
    }
    public function restoreSample($id)
    {
        TeaSamples::where('sample_id', $id)->delete();
        return back()->with('success', 'Sample restored successfully');
    }
    public function restorePartialSample($id)
    {
        TeaSamples::where('sample_id', $id)->delete();
        return back()->with('success', 'Sample restored successfully');
    }
    public function storeSampleRequest(Request $request, $id)
    {
        $stock = DB::table('currentstock')->where('stock_id', $id)->first();
        $newWeight = $stock->current_weight/$stock->current_stock;
        if ($newWeight - floatval($request->sample_weight) >= 0){
            DB::beginTransaction();
            try {

            $sample = [
                'sample_id' => (new CustomIds())->generateId(),
                'delivery_id' => $stock->delivery_id,
                'stock_id' => $stock->stock_id,
                'sample_weight' => $request->sample_weight,
                'sample_palletes' => 1,
                'package_weight' => number_format($newWeight, 2),
                'status' => 1,
                'type' => 1,
                'user_id' => auth()->user()->user_id
            ];
            TeaSamples::create($sample);
              $newStock = [
                'stock_id' => (new CustomIds())->generateId(),
                'delivery_id' => $stock->delivery_id,
                'station_id' => $stock->station_id,
                'date_received' => time(),
                'delivery_number' => 'SP'.time(),
                'warehouse_bay' => $stock->warehouse_bay,
                'total_weight' => number_format(floatval($newWeight )- floatval($request->sample_weight), 2),
                'total_pallets' => 1,
                'pallet_weight' => 0,
                'package_tare' => 0,
                'net_weight' => number_format(floatval($newWeight )- floatval($request->sample_weight), 2),
                'user_id' => auth()->user()->user_id
            ];
             StockIn::create($newStock);

                $this->logger->create();
                DB::commit();
                return redirect()->route('clerk.teaSamplesRequest')->with('success', 'Success! Sample request created successfully');
            } catch (Exception $e) {
                // Rollback the transaction if an exception occurs
                DB::rollback();
                // Handle or log the exception
                return redirect()->back()->with('error', 'Oops! An error occurred please try again '.$e->getMessage());
            }
        }else{
            return back()->with('error', 'Oops! Sample weight cannot be more than weight of one bag of tea. Try again');
        }
    }
    public function viewReportRequest ()
    {
        $requests = ReportRequest::join('clients', 'clients.client_id', '=', 'report_requests.client_id')->select('report_requests.*', 'clients.client_name')->orderBy('service_number', 'desc')->get();
        $clients = Client::latest()->get();
        return view('clerk::reports.index')->with(['requests' => $requests, 'clients' => $clients]);
    }
    public function filterReports(Request $request)
    {
        if($request->typeReport == 1){
            $data = DB::table('currentstock')
                ->where('current_stock', '>', 0)
                ->where('current_weight', '>', 0)
                ->where(['client_id' => $request->idClient])
                ->orderBy('delivery_number', 'asc')
                ->get()
                ->groupBy('delivery_number');

        }elseif ($request->typeReport == 2){
            $data = DB::table('blendBalances')
                ->where('current_packages', '>', 0)
                ->where('current_weight', '>', 0)
                ->where(['client_id' => $request->idClient])
                ->orderBy('blend_number', 'asc')
                ->get()
                ->groupBy('blend_number');

        }elseif ($request->typeReport == 3){
            $data = ShippingInstruction::where(['client_id' => $request->idClient])->orderBy('shipping_number', 'asc')->get()->groupBy('shipping_number');

        }elseif ($request->typeReport == 4){
            $data = BlendSheet::where(['client_id' => $request->idClient])->orderBy('blend_number', 'asc')->get()->groupBy('blend_number');

        }elseif ($request->typeReport == 5){
            $data = ExternalTransfer::join('delivery_orders', 'delivery_orders.delivery_id', '=', 'external_transfers.delivery_id')
                ->where(['client_id' => $request->idClient])
                ->orderBy('delivery_number', 'asc')
                ->get()
                ->groupBy('delivery_number');
        }elseif ($request->typeReport == 6){
            $data = DeliveryOrder::where(['client_id' => $request->idClient])->orderBy('invoice_number', 'asc')->get()->groupBy('invoice_number');
        }
        return response()->json($data);
    }
    public function storeReport(Request $request)
    {
        $serviceId = ReportRequest::serviceId();
        $reportRequest = [
            'request_id' => (new CustomIds())->generateId(),
            'service_number' => $serviceId,
            'request_type' => $request->request_type,
            'client_id' => $request->client_id,
            'request_number' => $request->request_number,
            'date_from' => $request->date_from == null ? null : $request->date_from,
            'date_to' => $request->date_to == null ? Carbon::today() : $request->date_to,
            'priority' => $request->priority,
            'user_id' => auth()->user()->user_id
        ];
        ReportRequest::create($reportRequest);
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Report request has been sent');
    }
    public function downloadReportRequest ($id)
    {
        return $this->AppClass->downloadVerifiedReport($id);
    }
    public function approveReportRequest($id)
      {
          ReportRequest::where(['request_id' => $id])->update(['status' => 1, 'approved_by' => auth()->user()->user_id]);
          $this->logger->create();
          return redirect()->back()->with('success', 'Success! Report request has been approved');
      }
    public function exportTransportReport(Request $request)
      {
          $from = $request->from;
          $to = $request->to;
          $query = DB::table('transportreport');

          if (!is_null($from)) {
              $fromTimestamp = strtotime($from);
              $query->where('date_received', '>=', $fromTimestamp);
          }
          if (!is_null($to)) {
              $toTimestamp = strtotime($to);
              $query->where('date_received', '<=', $toTimestamp);
          }
         $orders = $query->get();
         ini_set('memory_limit', '10000M');
         ini_set('max_execution_time', 30000);
          return Excel::download(new ExportTeaTransport($orders), 'TRANSPORTERS'.' '.time().'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
      }
    public function ImportDOS(Request $request)
    {
        $clientId = $request->clientId;
        $import = new ImportDOS($clientId);
        // Perform the import
        Excel::import($import, $request->file('uploadFile'));
        // Get specific errors
        $errors = $import->getErrors();
        if (!empty($errors)) {
            return redirect()->back()->with('importErrors', $errors);
        } else {
            $this->logger->create();
            // If no errors, continue with your desired action
            return redirect()->back()->with('success', 'Successful! DOS have been imported successfully');
        }
    }
    public function rebagging()
    {
       $bags = Rebagging::leftJoin('blend_sheets', 'blend_sheets.blend_id', '=', 'rebaggings.shipping_id')
            ->join('user_infos', 'user_infos.user_id', '=', 'rebaggings.user_id')
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
                DB::raw('SUM(rebaggings.packages) as packages'),
                DB::raw('SUM(rebaggings.weight) as weight')
            )
            ->groupBy('shippingId', 'type', 'siNumber', 'clientName', 'username', 'status')
            ->get();

        return view('clerk::shipping.rebagging')->with(['bags' => $bags]);
    }
    public function prepareRebagJob(Request $request)
    {
        if ($request->request_type == 1){
            $data = ShippingInstruction::where(['shipping_id' => $request->siNumber])->first();
        }else{
            $data = BlendSheet::where(['blend_id' => $request->siNumber])->first();
        }

        $tea = DB::table('currentstock')->where('current_stock', '>', 0)
            ->where('current_weight', '>', 0)
            ->where(['client_id' => $data->client_id])
            ->select('client_id', 'stock_id', 'order_number', 'garden_name', 'grade_name', 'invoice_number', 'lot_number', 'current_stock', 'current_weight',  DB::raw("1 as type"))
            ->get();

        $balances = DB::table('blendBalances')->where('current_packages', '>', 0)
            ->where('current_weight', '>', 0)
            ->where(['client_id' => $data->client_id])
            ->select('client_id', 'blend_balance_id as stock_id', 'blend_number as order_number', 'garden as garden_name', 'grade as grade_name', 'blend_number as invoice_number', 'blend_number as lot_number', 'current_packages as current_stock', 'current_weight', DB::raw("2 as type"))
            ->get();

       $teas = collect([])->merge($tea)->merge($balances)
            ->sortBy([
                ['garden_name', 'asc'],
                ['garden', 'asc'],
                ['invoice_number', 'asc'],
                ['lot_number', 'asc']
            ]);

       return view('clerk::shipping.prepareRebagging')->with(['teas' => $teas, 'data' => $data]);
    }
    public function fetchBySiNumber(Request $request)
    {
        if ($request->reportId == 1){
            $data = ShippingInstruction::join('clients', 'clients.client_id', '=', 'shipping_instructions.client_id')
                ->select('shipping_id', 'client_name', 'shipping_number as siNumber')
                ->orderBy('shipping_number')
                ->get();
        }else{
            $data = BlendSheet::join('clients', 'clients.client_id', '=', 'blend_sheets.client_id')
                ->select('blend_id as shipping_id', 'client_name', 'blend_number as siNumber')
                ->orderBy('blend_number')
                ->get();
        }
        return response()->json($data);
    }
    public function storeRebaggingRequest(Request $request, $id)
    {
        $deliveries = json_decode($request->allDeliveries, true);
        DB::beginTransaction();
        try {
            $expandedDeliveries = [];
            foreach ($deliveries['deliveries'] as $item) {
                $stockId = $item['deliveryId'];
                $totalWeight = (float)$item['weight'];
                $currentWeight = (float)$item['currentWeight'];
                $currentPackages = (float)$item['currentPackages'];

                if ($currentPackages == 0) {
                    continue; // avoid division by zero
                }

                $unitWeight = $currentWeight / $currentPackages;

                $fullPackages = floor($totalWeight / $unitWeight);
                $partialWeight = round($totalWeight - ($fullPackages * $unitWeight), 2);

                if ($fullPackages > 0) {
                    $expandedDeliveries[] = [
                        'rebagging_id' => (new CustomIds())->generateId(),
                        'shipping_id' => $id,
                        'stock_id' => $stockId,
                        'packages' => $fullPackages,
                        'weight' => round($unitWeight * $fullPackages, 2),
                        'user_id' => auth()->user()->user_id,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                }

                if ($partialWeight > 0) {
                    $expandedDeliveries[] = [
                        'rebagging_id' => (new CustomIds())->generateId(),
                        'shipping_id' => $id,
                        'stock_id' => $stockId,
                        'packages' => 1,
                        'weight' => $partialWeight,
                        'user_id' => auth()->user()->user_id,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                }
            }
            DB::table('rebaggings')->insert($expandedDeliveries);
            $this->logger->create();
            DB::commit();
        }catch (Exception $e){
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
        return redirect()->route('clerk.viewRebaggedTeas', $id)->with('success', 'Rebagging request submitted successfully');
    }
    public function viewRebaggedTeas ($id)
    {
        $current = Rebagging::join('currentstock as cs', 'cs.stock_id', '=', 'rebaggings.stock_id')
            ->leftJoin('blend_sheets', 'blend_sheets.blend_id', 'rebaggings.shipping_id')
            ->leftJoin('shipping_instructions', 'shipping_instructions.shipping_id', 'rebaggings.shipping_id')
            ->where('rebaggings.shipping_id', $id)
            ->select(
                'rebagging_id',
                'shipping_number',
                'blend_sheets.blend_number',
                'cs.grade_name',
                'cs.garden_name',
                'cs.invoice_number',
                'rebaggings.packages',
                'rebaggings.weight',
                'cs.client_id',
                'rebaggings.shipping_id'
            );

        $blends = Rebagging::join('blendBalances as bb', 'bb.blend_balance_id', '=', 'rebaggings.stock_id')
            ->leftJoin('blend_sheets', 'blend_sheets.blend_id', 'rebaggings.shipping_id')
            ->leftJoin('shipping_instructions', 'shipping_instructions.shipping_id', 'rebaggings.shipping_id')
            ->where('rebaggings.shipping_id', $id)
            ->select(
                'rebagging_id',
                'shipping_number',
                'blend_sheets.blend_number',
                'bb.grade as grade_name',
                'bb.garden as garden_name',
                'bb.blend_number as invoice_number',
                'rebaggings.packages',
                'rebaggings.weight',
                'bb.client_id',
                'rebaggings.shipping_id'
            );

        $lines = $current->unionAll($blends)
            ->orderBy('invoice_number')
            ->orderBy('garden_name')
            ->orderByDesc('packages')
            ->get();;

        $data = $lines[0];
        $tea = DB::table('currentstock')->where('current_stock', '>', 0)
            ->where('current_weight', '>', 0)
            ->where(['client_id' => $data->client_id])
            ->select('client_id', 'stock_id', 'order_number', 'garden_name', 'grade_name', 'invoice_number', 'lot_number', 'current_stock', 'current_weight',  DB::raw("1 as type"))
            ->get();

        $balances = DB::table('blendBalances')->where('current_packages', '>', 0)
            ->where('current_weight', '>', 0)
            ->where(['client_id' => $data->client_id])
            ->select('client_id', 'blend_balance_id as stock_id', 'blend_number as order_number', 'garden as garden_name', 'grade as grade_name', 'blend_number as invoice_number', 'blend_number as lot_number', 'current_packages as current_stock', 'current_weight', DB::raw("2 as type"))
            ->get();

        $teas = collect([])->merge($tea)->merge($balances)
            ->sortBy([
                ['garden_name', 'asc'],
                ['garden', 'asc'],
                ['invoice_number', 'asc'],
                ['lot_number', 'asc']
            ]);

        return view('clerk::shipping.viewRebagging')->with(['teas' => $teas, 'data' => $data, 'lines' => $lines]);
    }
    public function removeRebaggedTea($id)
    {
        Rebagging::where('rebagging_id', $id)->delete();
        return back()->with('success', 'Line removed successfully');
    }
    public function removeRebaggedTeas($id)
    {
        Rebagging::where('shipping_id', $id)->delete();
        return back()->with('success', 'Lines removed successfully');
    }
    public function downloadTemplate()
    {
        $file = 'imports/bulky_tea_import.xlsx';
        return response()->download($file);
    }
        public function teaAuction(Request $request)
        {
            $query = Auction::join('stock_ins', 'stock_ins.stock_id', '=', 'auctions.stock_id')
                ->join('delivery_orders', 'delivery_orders.delivery_id', '=', 'stock_ins.delivery_id')
                ->join('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
                ->join('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
                ->join('brokers', 'brokers.broker_id', '=', 'auctions.broker_id')
                ->join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
                ->leftJoin('clients as buyer', 'buyer.client_id', '=', 'auctions.client_id')
                ->leftJoin('warehouses', 'warehouses.warehouse_id', '=', 'auctions.warehouse_id')
                ->leftJoin('external_transfers', function ($join) {
                    $join->on('external_transfers.stock_id', '=', 'auctions.stock_id');
                })
                ->whereNull('external_transfers.deleted_at')
                ->whereNull('auctions.deleted_at')
                ->whereNull('stock_ins.deleted_at')
                ->whereNull('delivery_orders.deleted_at')
                ->whereNull('gardens.deleted_at')
                ->whereNull('grades.deleted_at')
                ->whereNull('brokers.deleted_at')
                ->select(
                    'auction_id',
                    'warrant_number',
                    'stock_ins.stock_id',
                    'stock_ins.delivery_id',
                    'garden_name',
                    'grade_name',
                    'invoice_number',
                    'auctions.status',
                    'brokers.broker_name',
                    'brokers.broker_id',
                    'buyer.client_name as buyer_name',
                    'auctions.client_id as buyer_id',
                    'sale',
                    'auctions.sale_date',
                    'auctions.prompt_date',
                    'auctions.warehouse_id',
                    'warehouses.warehouse_name',
                    'external_transfers.release_date',
                    'external_transfers.delivery_number',
                    'stock_ins.total_pallets',
                    'stock_ins.net_weight',
                    'delivery_orders.garden_id',
                    'delivery_orders.grade_id'
                );

            // --- Warrant / Invoice search ---
            if ($request->filled('warrant_invoice')) {
                $search = $request->warrant_invoice;
                $query->where(function ($q) use ($search) {
                    $q->where('warrant_number', 'like', "%{$search}%")
                    ->orWhere('invoice_number', 'like', "%{$search}%");
                });
            }

            // --- Garden filter ---
            if ($request->filled('garden')) {
                $query->where('delivery_orders.garden_id', $request->garden);
            }

            // --- Grade filter ---
            if ($request->filled('grade')) {
                $query->where('delivery_orders.grade_id', $request->grade);
            }

            // --- Broker filter ---
            if ($request->filled('broker')) {
                $query->where('auctions.broker_id', $request->broker);
            }

            // --- Buyer filter ---
            if ($request->filled('buyer')) {
                $query->where('auctions.client_id', $request->buyer);
            }

            // --- Warehouse filter ---
            if ($request->filled('warehouse')) {
                $query->where('auctions.warehouse_id', $request->warehouse);
            }

            // --- Sale Status filter (0 = On Sale, 1 = Sold) ---
            if ($request->filled('sale_status') && in_array($request->sale_status, ['0', '1'])) {
                $query->where('auctions.status', $request->sale_status);
            }

            // --- Release Status filter ---
            if ($request->filled('release_status')) {
                if ($request->release_status === 'released') {
                    $query->whereNotNull('external_transfers.release_date');
                } elseif ($request->release_status === 'pending') {
                    $query->whereNull('external_transfers.release_date');
                }
            }

            if ($request->filled('sale')) {
                $query->where('sale', $request->sale);
            }

            // --- Sale Date range ---
            if ($request->filled('sale_date_from')) {
                $query->whereDate('auctions.sale_date', '>=', $request->sale_date_from);
            }
            if ($request->filled('sale_date_to')) {
                $query->whereDate('auctions.sale_date', '<=', $request->sale_date_to);
            }

            // --- Prompt Date range ---
            if ($request->filled('prompt_date_from')) {
                $query->whereDate('auctions.prompt_date', '>=', $request->prompt_date_from);
            }
            if ($request->filled('prompt_date_to')) {
                $query->whereDate('auctions.prompt_date', '<=', $request->prompt_date_to);
            }

            // --- Release Date range ---
            if ($request->filled('release_date_from')) {
                $query->whereDate('external_transfers.release_date', '>=', $request->release_date_from);
            }
            if ($request->filled('release_date_to')) {
                $query->whereDate('external_transfers.release_date', '<=', $request->release_date_to);
            }

            // --- Handle Export ---
            if ($request->filled('export')) {
                $auctions = $query->orderBy('warrant_number')->get();
                return $this->exportAuctions($auctions, $request->export);
            }

            $auctions = $query->orderBy('warrant_number')->get();

            // Dropdown data - scoped to records that exist in auctions
        $gardens = Garden::join('delivery_orders', 'delivery_orders.garden_id', '=', 'gardens.garden_id')
            ->join('stock_ins', 'stock_ins.delivery_id', '=', 'delivery_orders.delivery_id')
            ->join('auctions', 'auctions.stock_id', '=', 'stock_ins.stock_id')
            ->select('gardens.garden_id', 'gardens.garden_name')
            ->distinct()
            ->orderBy('garden_name')
            ->get();

        $grades = Grade::join('delivery_orders', 'delivery_orders.grade_id', '=', 'grades.grade_id')
            ->join('stock_ins', 'stock_ins.delivery_id', '=', 'delivery_orders.delivery_id')
            ->join('auctions', 'auctions.stock_id', '=', 'stock_ins.stock_id')
            ->select('grades.grade_id', 'grades.grade_name')
            ->distinct()
            ->orderBy('grade_name')
            ->get();

        $brokers = Broker::join('auctions', 'auctions.broker_id', '=', 'brokers.broker_id')
            ->select('brokers.broker_id', 'brokers.broker_name')
            ->distinct()
            ->orderBy('broker_name')
            ->get();

        $buyers = Client::join('auctions', 'auctions.client_id', '=', 'clients.client_id')
            ->select('clients.client_id', 'clients.client_name')
            ->distinct()
            ->orderBy('client_name')
            ->get();

        $warehouses = Warehouse::join('auctions', 'auctions.warehouse_id', '=', 'warehouses.warehouse_id')
            ->select('warehouses.warehouse_id', 'warehouses.warehouse_name')
            ->distinct()
            ->orderBy('warehouse_name')
            ->get();

        $sales = Auction::select('sale')->groupBy('sale')->orderBy('sale', 'desc')->get();

            return view('clerk::auctions.auctions', compact(
                'auctions',
                'gardens',
                'grades',
                'brokers',
                'buyers',
                'warehouses',
                'sales'
            ));
        }

    private function exportAuctions($auctions, string $format)
    {
        $filename = 'tea_auctions_' . now()->format('Ymd_His');

        if ($format === 'csv') {
            $headers = [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => "attachment; filename={$filename}.csv",
            ];


            $callback = function () use ($auctions) {
                $handle = fopen('php://output', 'w');

                // CSV Header row
                fputcsv($handle, [
                    '#', 'Warrant No', 'Invoice No', 'Garden', 'Grade',
                    'Packages', 'Net Weight', 'Broker', 'Buyer',
                    'Warehouse', 'Sale', 'Sale Date', 'Prompt Date',
                    'Delivery No', 'Release Date', 'Sale Status', 'Release Status'
                ]);

                foreach ($auctions as $index => $row) {
                    fputcsv($handle, [
                        $index + 1,
                        $row->warrant_number,
                        $row->invoice_number,
                        $row->garden_name,
                        $row->grade_name,
                        number_format($row->total_pallets, 0),
                        number_format($row->net_weight, 2),
                        $row->broker_name,
                        $row->buyer_name,
                        $row->warehouse_name,
                        "\t" . $row->sale,
                        $row->sale_date,
                        $row->prompt_date,
                        $row->delivery_number,
                        $row->release_date,
                        $row->status == 0 ? 'On Sale' : 'Sold',
                        $row->release_date  ? 'Released' : 'Pending',
                    ]);
                }

                fclose($handle);
            };

            return response()->stream($callback, 200, $headers);
        }

        if ($format === 'excel') {
            // Requires maatwebsite/excel — swap class name to match your project
            return \Excel::download(new \Modules\Admin\Exports\AuctionsExport($auctions), "{$filename}.xlsx");
        }

        if ($format === 'pdf') {
            $pdf = \PDF::loadView('admin::auctions.auctions_pdf', ['auctions' => $auctions]);
            return $pdf->download("{$filename}.pdf");
        }

        abort(400, 'Unsupported export format');
    }
    public function viewSales()
    {
        $auctions = Auction::select('sale')->groupBy('sale')->orderBy('sale', 'desc')->get();
        $clients = Client::all();
        return view('clerk::auctions.index', compact('auctions', 'clients'));
    }
    public function viewSale($id)
    {
        $sale = base64_decode($id);
        $teas = Auction::join('delivery_orders', 'delivery_orders.delivery_id', '=', 'auctions.delivery_id')
                ->join('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
                ->join('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
                ->join('stock_ins', function ($join){
                    $join->on('delivery_orders.delivery_id', '=', 'stock_ins.delivery_id');
                })
                ->join('clients as stock', 'stock.client_id', '=', 'delivery_orders.client_id')
                ->join('brokers', 'brokers.broker_id', '=', 'auctions.broker_id')
                ->leftJoin('clients', 'clients.client_id', '=', 'auctions.client_id')
                ->leftJoin('warehouses', 'warehouses.warehouse_id', '=', 'auctions.warehouse_id')
                ->select('auction_id', 'stock.client_name', 'warrant_number', 'stock_ins.total_pallets as current_stock', 'stock_ins.net_weight as current_weight', 'auctions.status', 'clients.client_name as buyer_name', 'brokers.broker_name', 'invoice_number', 'garden_name', 'grade_name', 'order_number', 'auctions.client_id', 'auctions.broker_id', 'sale', 'auctions.sale_date', 'auctions.prompt_date', 'auctions.warehouse_id', 'warehouses.warehouse_name', 'release_date')
                ->where('auctions.sale', $sale)
                ->orderBy('warrant_number')
                ->get();
        $clients = Client::all();
        $brokers = Broker::all();
        $warehouses = Warehouse::all();
        return view('clerk::auctions.viewSale', compact('teas', 'sale', 'clients', 'brokers', 'warehouses'));
    }
    public function prepareAuctionList(Request $request)
    {
        $teas = DB::table('currentstock')
                ->leftJoin('auctions', function ($join) {
                    $join->on('currentstock.delivery_id', '=', 'auctions.delivery_id')
                        ->on('currentstock.stock_id', '=', 'auctions.stock_id')
                        ->whereNull('auctions.deleted_at');
                })
                ->select('currentstock.stock_id', 'currentstock.delivery_id', 'currentstock.client_name', 'currentstock.client_id', 'invoice_number', 'order_number', 'lot_number', 'current_stock', 'current_weight', 'garden_name', 'grade_name')
                ->where('current_stock', '>', 0)
                ->where('current_weight', '>', 0)
                ->where(['currentstock.client_id' => $request->client])
                ->whereNull('auctions.warrant_number')
                ->orderBy('garden_name', 'asc')
                ->orderBy('invoice_number', 'asc')
                ->get();
        $client = Client::where(['client_id' => $request->client])->first();
        $brokers = Broker::all();
        return view('clerk::auctions.prepareAuctionList', compact('teas', 'client', 'brokers'));
    }
    public function storeAuctionList(Request $request)
    {
        $teas = json_decode($request->selectedItems);
        foreach ($teas as $tea){
            $do = StockIn::where(['stock_id' => $tea->deliveryId])->first();
            $auction = [
                'auction_id' => (new CustomIds())->generateId(),
                'stock_id' => $tea->deliveryId,
                'delivery_id' => $do->delivery_id,
                'broker_id' => $tea->brokerId,
                'sale' => $tea->saleNumber,
                'warrant_number' => Auction::newWarrantNumber(),
                'status' => 0,
                'user_id' => auth()->id(),
            ];
            if(!Auction::where(['stock_id' => $tea->deliveryId])->exists()) {
                Auction::create($auction);
                $this->logger->create();
            }
        }
        return redirect()->route('clerk.teaAuction')->with('success', 'Auction added successfully');
    }
    public function removeLineFromSale($id)
    {
        Auction::find($id)->delete();
        $this->logger->create();
        return back()->with('success', 'Line removed successfully');
    }
    public function updateAuctionList(Request $request, $id){
        Auction::where(['auction_id' => $id])->update([
            'broker_id' => $request->broker,
            'client_id' => $request->buyer,
            'status' => $request->status,
            'sale' => $request->sale,
            'sale_date' => $request->sale_date,
            'prompt_date' => $request->prompt_date,
            'warehouse_id' => $request->warehouse_id
        ]);
        $this->logger->create();
        return back()->with('success', 'Line updated successfully');
    }
    public function downloadAuctionSheet (Request $request, $id)
    {
        list($saleN, $dType) = explode(':', base64_decode($id));
        $sale = $saleN;
        $type = $dType ?? $request->type;
        $query = [

        ];
        return $this->AppClass->downloadAuctionSheet($sale, $type, $query);
    }
    public function downloadAuctionSheetReport ($id)
    {
        return $this->AppClass->downloadAuctionSheetReport($id);
    }

    public function deleteBlendBalance($id){
        BlendBalance::where('blend_balance_id', $id)->delete();
        return back()->with('success', 'Blend balance deleted successfully');
    }

    public function list()
    {
        $items = NotificationUser::with('notification', 'notification.creator')
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'DESC')
            ->get();

        return response()->json($items);
    }

    public function details($id)
    {
        $record = NotificationUser::with([
            'notification.creator',
            'notification.users.user'
        ])
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        // Mark as read for ONLY the current user
        if ($record && $record->is_read == 0) {
            $record->update([
                'is_read' => 1,
                'read_at' => now(),
            ]);
        }

        $isAdmin = DB::table('task_module_user_roles')->where(['user_id' => Auth::id(), 'role_id' => 1])->first();

        return response()->json([
            'details' => $record,
            'readers' => $isAdmin ? [
                'read_by' => $record->notification->users->where('is_read', 1)->pluck('user'),
                'pending' => $record->notification->users->where('is_read', 0)->pluck('user'),
            ] : null
        ]);
    }
    public function foreignTeas(Request $request)
    {
        $currentMonth = Carbon::parse($request->start_date) ?? null;
        $currentYear = Carbon::parse($request->end_date) ?? null;

        $query = DeliveryOrder::join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->join('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
            ->join('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
            ->leftJoin('brokers', 'brokers.broker_id', '=', 'delivery_orders.broker_id')
            ->leftJoin('warehouses', 'warehouses.warehouse_id', '=', 'delivery_orders.warehouse_id')
            ->leftJoin('sub_warehouses', 'sub_warehouses.sub_warehouse_id', '=', 'delivery_orders.sub_warehouse_id')
            ->leftJoin('loading_instructions', function ($join) {
                $join->on('loading_instructions.delivery_id', '=', 'delivery_orders.delivery_id')
                    ->whereNull('loading_instructions.deleted_at');
            })
            ->leftJoin('foreign_teas', function ($join) {
                $join->on('foreign_teas.delivery_order_id', '=', 'delivery_orders.delivery_id')
                    ->whereNull('foreign_teas.deleted_at');
            })
            ->select('delivery_orders.delivery_id','gardens.garden_name', 'grades.grade_name', 'brokers.broker_name', 'warehouses.warehouse_name', 'clients.client_name', 'delivery_orders.invoice_number', 'loading_instructions.loading_number', 'sub_warehouses.sub_warehouse_name', 'locality', 'lot_number', 'tea_type', 'collection', 'received', 'validated')
            ->where('delivery_orders.tea_type', 'Foreign')
            ->whereNull('delivery_orders.deleted_at');

////        // Filter by client_id if provided
//        $query->when($request->client_id, function ($q) use ($request) {
//            $q->where('delivery_orders.client_id', $request->client_id);
//        });
//
//        // Filter by date range if provided
//        $query->when($request->start_date && $request->end_date, function ($q) use ($request) {
//            $q->whereBetween('delivery_orders.created_at', [
//                Carbon::parse($request->start_date)->startOfDay(),
//                Carbon::parse($request->end_date)->endOfDay()
//            ]);
//        });
//
//        $query->when(!$request->start_date && !$request->end_date, function ($q) use ($currentMonth, $currentYear) {
//            $q->whereMonth('delivery_orders.created_at', $currentMonth)
//                ->whereYear('delivery_orders.created_at', $currentYear);
//        });

        $orders = $query->orderBy('delivery_orders.created_at', 'desc')
            ->orderBy('delivery_orders.status', 'asc')
            ->get();

        return view('clerk::DOS.foreign_teas')->with(['orders' => $orders]);
    }

    public function updateEntriesReceived(Request $request, $deliveryId)
    {
        $request->validate([
            'received' => 'required|in:true,false,1,0',
        ]);

        // Convert to boolean properly
        $received = filter_var($request->received, FILTER_VALIDATE_BOOLEAN);

        $status = $received ? 'received' : 'not_received';

        ForeignTea::updateOrCreate(['delivery_order_id' => $deliveryId], [
            'foreign_teas_id' => (new CustomIds())->generateId(),
            'received' => $status
        ]);

        return response()->json([
            'message' => 'Tea updated successfully',
        ]);
    }

    public function updateEntriesValidated(Request $request, $deliveryId)
    {
        $request->validate([
            'validated' => 'required|in:true,false,1,0',
        ]);

        // Convert to boolean properly
        $validated = filter_var($request->validated, FILTER_VALIDATE_BOOLEAN);

        $status = $validated ? 'validated' : 'not_validated';

        ForeignTea::where(['delivery_order_id' => $deliveryId])->update([
            'validated' => $status
        ]);

        return response()->json([
            'message' => 'Tea updated successfully',
        ]);
    }

    public function downloadDeliveryNote($id)
    {
        $note = DeliveryNote::where('delivery_number', base64_decode($id))->first();
        $path = $note->path; // e.g. /file/sig/abc123.png
        return response()->download(
            Storage::disk('delivery_notes')->path(ltrim($path, '/'))
        );
    }

    public function viewPendingTCIs()
    {
        $tcis = LoadingInstruction::join('delivery_orders', 'delivery_orders.delivery_id', 'loading_instructions.delivery_id')
            ->join('stations', 'stations.station_id', 'loading_instructions.station_id')
            ->join('warehouse_locations', 'warehouse_locations.location_id', '=', 'stations.location_id')
            ->select('location_name', 'warehouse_locations.location_id')
            ->selectRaw('SUM(packet) as total_packages')
            ->selectRaw('SUM(weight) as total_weight')
            ->withoutTrashed()
            ->where('loading_instructions.status','!=', 2)
            ->groupBy('location_name', 'warehouse_locations.location_id')
            ->get();

        return view('clerk::reports.TCI.index')->with(['tcis' => $tcis]);

    }
    public function viewLocationPendingTCIs($id)
    {
        $teas = LoadingInstruction::join('delivery_orders', 'delivery_orders.delivery_id', 'loading_instructions.delivery_id')
            ->join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->join('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
            ->join('grades', 'grades.grade_id', 'delivery_orders.grade_id')
            ->join('stations', 'stations.station_id', 'loading_instructions.station_id')
            ->join('warehouse_locations', 'warehouse_locations.location_id', '=', 'stations.location_id')
            ->select('client_name', 'loading_number', 'invoice_number', 'lot_number', 'garden_name', 'grade_name', 'packet', 'weight', 'station_name', 'location_name', 'stations.location_id')
            ->withoutTrashed()
            ->where('loading_instructions.status','!=', 2)
            ->where('stations.location_id', $id)
            ->orderBy('loading_number', 'desc')
            ->orderBy('grade_name', 'asc')
            ->get();
        return view('clerk::reports.TCI.locationWise')->with(['teas' => $teas]);
    }
    public function downloadLocationPendingTCIs(Request $request, $id)
    {
        list($locationId, $type) = explode(':', base64_decode($id));
        $query = LoadingInstruction::join('delivery_orders', 'delivery_orders.delivery_id', 'loading_instructions.delivery_id')
            ->join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->join('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
            ->join('grades', 'grades.grade_id', 'delivery_orders.grade_id')
            ->join('stations', 'stations.station_id', 'loading_instructions.station_id')
            ->join('warehouse_locations', 'warehouse_locations.location_id', '=', 'stations.location_id')
            ->select('client_name', 'loading_number', 'invoice_number', 'lot_number', 'garden_name', 'grade_name', 'packet', 'weight', 'station_name', 'location_name')
            ->withoutTrashed()
            ->where('loading_instructions.status','!=', 2)
            ->where('stations.location_id', $locationId)
            ->orderBy('loading_number', 'desc')
            ->orderBy('grade_name', 'asc');

        if ($request->loadingNumber !== null){
            $query->whereIn('loading_number', $request->loadingNumber);
        }

        $teas = $query->get();
        $date = Carbon::today()->format('D, d/m/y H:i');

        if ($type == 2 || $request->reportType == 2){
            return Excel::download(new ExportPendingTCI($teas), $teas[0]['location_name'].' TCI'.' '.time().'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        }elseif($type == 1 || $request->reportType == 1) {
            $location = $teas[0]['location_name'];
            // Render Blade view
            $html = View::make('admin::downloads.collection', compact('teas', 'location', 'date'))->render();

            // Initialize mPDF with settings
            $mpdf = new Mpdf([
                'tempDir' => storage_path('app/mpdf_temp'),
                'mode'        => 'utf-8',
                'format'      => 'A4-L', // Landscape
                'orientation' => 'L',
                'margin_top'    => 2,
                'margin_bottom' => 7,
                'margin_left'   => 5,
                'margin_right'  => 5,
                'setAutoBottomMargin' => 'stretch'
            ]);

            // Set footer for all pages
            $mpdf->SetHTMLFooter('
            <table width="100%">
                <tr>
                    <td align="center">Page {PAGENO} of {nbpg}</td>
                </tr>
            </table>
        ');

            // Write HTML content
            $mpdf->WriteHTML($html);

            // Generate PDF filename
            $pdfFileName = 'TEAS PENDING DELIVERY TO '.$location.' LOCATION.pdf';

            // Output PDF as downloadable file
            return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
            ]);
        }
    }

    public function removeTeaFromTCI($id)
    {
        $tci = LoadingInstruction::where('loading_id', $id)->first();
        DeliveryOrder::where('delivery_id', $tci->delivery_id)->update(['status' => 0]);
        $tci->delete();
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Tea removed from TCI');
    }
        public function getReleaseForm($delivery) {
        list($delivery, $lot) = explode(':', base64_decode($delivery));
        $transfer = ExternalTransfer::where(['delivery_number' => $delivery, 'external_transfers.lot' => $lot])
            ->leftJoin('drivers', 'drivers.driver_id', '=', 'external_transfers.driver_id')
            ->first();
        $warehouses = Warehouse::all();
        $transporters = Transporter::all();
        $users = Driver::all();

        return view('clerk::transfers.release-form', compact('transfer', 'warehouses', 'transporters', 'users'));
    }
}
