<?php

namespace Modules\Admin\Http\Controllers;

use App\Services\AppClass;
use App\Services\ExportAgingStock;
use App\Services\ExportClientAgingStock;
use App\Services\ExportPendingTCI;
use DateTime;
use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use App\Models\Grade;
use App\Services\Log;
use App\Models\Broker;
use App\Models\Client;
use App\Models\Driver;
use App\Models\Garden;
use App\Models\Station;
use App\Models\StockIn;
use App\Models\BlendTea;
use App\Models\Shipment;
use App\Models\UserInfo;
use App\Models\Transfers;
use App\Models\Warehouse;
use App\Models\BlendSheet;
use App\Services\TraceTea;
use AllowDynamicProperties;
use App\Imports\ImportBulkyTeas;
use App\Models\Destination;
use App\Models\Transporter;
use App\Services\CustomIds;
use App\Services\ExportTCI;
use App\Models\BlendBalance;
use App\Models\SubWarehouse;
use App\Models\WarehouseBay;
use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use App\Models\BlendMaterial;
use App\Models\BlendShipment;
use App\Models\ClearingAgent;
use App\Models\DeliveryOrder;
use App\Services\ExportStock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Models\BlendSupervision;
use App\Models\ExternalTransfer;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Laravel\Facades\Image;
use Modules\Admin\Entities\Department;
use Modules\Admin\Entities\OtherDestination;
use Modules\Admin\Entities\OtherTransporter;
use Modules\Admin\Entities\Permission;
use Modules\Admin\Entities\Signatory;
use Modules\Admin\Entities\UserPermission;
use Modules\Clerk\Entities\Approval;
use Modules\Clerk\Entities\Auction;
use Modules\Clerk\Entities\DeliveryNote;
use Modules\Clerk\Entities\ForeignTea;
use Modules\Clerk\Entities\Rebagging;
use Modules\Tasks\Entities\NotificationUser;
use Mpdf\Mpdf;
use Mpdf\Output\Destination as PdfDestination;
use PhpOffice\PhpWord\IOFactory;
use App\Models\ShipmentContainer;
use App\Services\ExportSTLReport;
use App\Models\LoadingInstruction;
use App\Services\ExportBlendSheet;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\ShippingInstruction;
use App\Services\ExportBlendReport;
use App\Services\ExportTeaTransport;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\SimpleType\Jc;
use App\Services\ExportDeliveryOrders;
use Modules\Clerk\Entities\TeaSamples;
use Symfony\Component\Routing\Annotation\Route;
use function Laravel\Prompts\password;
use function PHPUnit\Framework\isEmpty;
use App\Services\ExportExternalTransfer;
use PhpOffice\PhpWord\TemplateProcessor;
use App\Services\ExportInternalTransfers;
use Modules\Clerk\Entities\ReportRequest;
use Illuminate\Validation\Rules\RequiredIf;
use NcJoes\OfficeConverter\OfficeConverter;
use App\Services\ExportDirectDeliveryOrders;
use App\Services\ExportShippingInstructions;
use Illuminate\Contracts\Support\Renderable;
use Modules\Clerk\Entities\WarehouseLocation;

class AdminController extends Controller
{
    protected $logger;

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
    public function traceTeaByInvoice(Request $request)
    {
        $query = $request->input('invoice');
        $teas = DeliveryOrder::leftJoin('currentstock', 'currentstock.delivery_id', 'delivery_orders.delivery_id')
            ->join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->join('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
            ->join('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
            ->where(function ($q) use ($query) {
                $q->where('delivery_orders.invoice_number', 'LIKE', "%$query%")
                    ->orWhere('delivery_orders.lot_number', 'LIKE', "%$query%")
                    ->orWhere('delivery_orders.order_number', 'LIKE', "%$query%")
                    ->orWhere('clients.client_name', 'LIKE', "%$query%")
                    ->orWhere('currentstock.delivery_number', 'LIKE', "%$query%");
            })
            ->select('delivery_orders.delivery_id', 'delivery_orders.invoice_number', 'clients.client_name', 'grades.grade_name', 'gardens.garden_name', 'delivery_orders.lot_number', 'delivery_orders.order_number', 'delivery_orders.packet', 'delivery_orders.weight', DB::raw("SUM(current_stock) as current_stock"), DB::raw("SUM(current_weight) as current_weight"))
            ->groupBy('delivery_id', 'invoice_number', 'clients.client_name', 'grades.grade_name', 'gardens.garden_name', 'lot_number', 'order_number', 'packet', 'weight')
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
            ->select('external_transfers.status', 'client_name', 'warehouses.warehouse_name', 'station_name', 'external_transfers.delivery_number', 'transporter_name')
            ->selectRaw('SUM(transferred_palettes) AS total_palettes')
            ->selectRaw('SUM(transferred_weight) AS total_weight')
            ->orderBy('external_transfers.delivery_number', 'desc')
            ->orderBy('external_transfers.created_at', 'desc')
            ->groupBy('delivery_number', 'status', 'client_name', 'warehouse_name', 'station_name', 'transporter_name')
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

        return view('admin::search.index')->with(['teas' => $teas, 'straightLine' => $straightLine, 'blends' => $blends, 'blendBalances' => $blendBalances, 'internalTransfers' => $internalTransfers, 'externalTransfers' => $externalTransfers, 'searchTerm' => $query, 'tcis' => $tcis]);
    }
    public function traceTea($id)
    {
        $traceData = $this->traceService->traceDeliveryOrder($id);
        if (!$traceData) {
            return response()->json(['error' => 'Delivery order not found'], 404);
        }
        return view('admin::DOS.traceTea')->with('teaDetails', $traceData);
    }
    public function traceBlendBalance($id)
    {
        $traceData = $this->traceService->traceBlendBalance($id);
        if (!$traceData) {
            return response()->json(['error' => 'Delivery order not found'], 404);
        }
        return view('admin::DOS.traceBlendBalance')->with(['teaDetails' => $traceData, 'id' => $id]);
    }
    public function index()
    {
        $orders = DeliveryOrder::leftJoin('loading_instructions', 'loading_instructions.delivery_id', '=', 'delivery_orders.delivery_id')
            ->select('loading_instructions.status as load_status', 'loading_instructions.deleted_at', 'delivery_orders.created_at as date_received', 'loading_number')
            ->whereNull('delivery_orders.deleted_at');

//// Clone the query builder instance for each variable
        $uncollected = clone $orders;
        $late = clone $orders;
        $noTCI = clone $orders;
        $overstayed = clone $orders;
//
        $uncollected = $uncollected->where('loading_instructions.status', 1)->where('loading_instructions.deleted_at', '=', null)->get();
        $threshold = Carbon::now();
        $late = $late->whereRaw("DATE_ADD(loading_instructions.created_at, INTERVAL 2 DAY) <= '$threshold'")->where('loading_instructions.deleted_at', '=', null)->where('loading_instructions.status', 1)->get();
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
//
        $internal = Transfers::where('transfers.status', '<', 3)
            ->orWhere('transfers.status', null)
            ->whereNull('transfers.deleted_at')
            ->latest('transfers.created_at')
            ->get()
            ->groupBy('delivery_number');
//
        $external = ExternalTransfer::latest('external_transfers.created_at')
            ->where('external_transfers.status', '<', 3)
            ->orWhere('external_transfers.status', null)
            ->whereNull('external_transfers.deleted_at')
            ->orderBy('delivery_number', 'desc')
            ->get()
            ->groupBy('delivery_number');
//
        $si = ShippingInstruction::latest('shipping_instructions.created_at')
            ->where('shipping_instructions.status', '<', 4)
            ->orWhere('shipping_instructions.status', null)
            ->whereNull('shipping_instructions.deleted_at')
            ->get();

        $blend = DB::table('blend_sheets')
            ->where(function ($query) {
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
//
        return view('admin::welcome')->with(['blend' => $blend, 'si' => $si, 'internal' => $internal, 'external' => $external, 'uncollected' => $uncollected, 'late' => $late, 'noTCI' => $noTCI, 'overstayed' => $overstayed, 'tcis' => $tcis, 'clients' => $clients, 'stocks' => $stocks]);
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
//        $uncollected = $uncollected->where('li.status', 1)->get();
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
        $now = \Carbon\Carbon::now();
        $overstayed = $overstayed->whereRaw("DATE_ADD(delivery_orders.prompt_date, INTERVAL 7 DAY) <= '$now'")->where('li.status', 1)->get();

        $internal = Transfers::join('stations', 'stations.station_id', '=', 'transfers.station_id')
            ->join('stations as destination_station', 'destination_station.station_id', '=', 'transfers.destination')
            ->join('delivery_orders', 'delivery_orders.delivery_id', '=', 'transfers.delivery_id')
            ->join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->join('currentstock', function($join) {
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
            return view('admin::dashboard.collections')->with(['orders' => $report, 'id' => $id]);
        }elseif ($id >= 5 && $id <= 6){
            return view('admin::dashboard.transfers')->with(['orders' => $report, 'id' => $id]);
        }elseif($id >= 7){
            return view('admin::dashboard.sis')->with(['orders' => $report, 'id' => $id]);
        }
    }
    public function users()
    {
        $users = User::join('user_infos', 'user_infos.user_id', '=', 'users.user_id')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->join('stations', 'stations.station_id', '=', 'users.station_id')
            ->join('warehouse_locations', 'warehouse_locations.location_id', '=', 'stations.location_id')
            ->select('users.username', 'users.username as staff_number', 'user_infos.first_name', 'user_infos.middle_name', 'user_infos.surname', DB::raw('CONCAT(user_infos.surname," ",user_infos.first_name," ",user_infos.middle_name) as staff_name'), 'user_infos.gender', 'user_infos.phone_number', 'user_infos.email_address', 'user_infos.id_number', 'roles.role_name', 'roles.type', 'stations.station_name', 'users.role_id', 'users.station_id', 'users.user_id', 'users.created_by', 'users.updated_by', 'users.created_at', 'users.status', 'stations.location_id', 'location_name')
            ->orderBy('roles.id', 'asc')
            ->orderBy('users.username', 'asc')
            ->orderBy('users.role_id', 'asc')
            ->get();
        $roles = Role::latest()->get();
        $stations = Station::latest()->get();
        return view('admin::users.users')->with(['users' => $users, 'roles' => $roles, 'stations' => $stations]);
    }
    public function registerUser(Request $request)
    {
        $request->validate([
            'first_name' => 'required|alpha',
            'surname' => 'required|alpha',
//            'other' => 'required_if:other_field_name,1|alpha',
            'id_number' => 'required|max:8',
            'gender' => 'required',
            'station' => 'required',
            'role' => 'required',
            'phone' => ['required', 'max:13', 'min:9', 'regex:/^(07|\+2547|01|\+2541|7|1)\d{8}$/'],
            'email' => 'string|required'
        ]);

        $initial = $request->role == 1 ? 'AD' :($request->role == 2 ? 'OP' :( $request->role == 7 ? 'GA' : ( $request->role == 8 ? 'AR' : ( $request->role == 9 ? 'AP' : ($request->role == 11 ? 'US' : 'CL')))));

        $users = User::whereRaw("LEFT(username, 2) = '" . date('y') . "'")->count();
        $staffNumber = date('y') . $initial. str_pad($users + 1, 3, '0', STR_PAD_LEFT);

        $customId = new CustomIds();
        $userID = $customId->generateId();

        $userLogin = [
            'user_id' => $userID,
            'username' => $staffNumber,
            'password' => Hash::make($request->id_number),
            'role_id' => $request->role,
            'station_id' => $request->station,
            'created_by' => auth()->user()->user_id
        ];

        $userInfo = [
            'user_id' => $userID,
            'first_name' => strtoupper($request->first_name),
            'middle_name' => strtoupper($request->other),
            'surname' => strtoupper($request->surname),
            'gender' => $request->gender,
            'phone_number' => $request->phone,
            'email_address' => $request->email,
            'id_number' => $request->id_number
        ];

        User::create($userLogin);
        UserInfo::create($userInfo);
        $this->logger->create();
        return redirect()->back()->with('success', 'User account registered successfully');
    }
    public function updateUser(Request $request, $id)
    {
        $request->validate([
            'first_name' => 'required|alpha',
            'surname' => 'required|alpha',
            'id_number' => 'required|max:8',
            'gender' => 'required',
            'station' => 'required',
            'role' => 'required',
            'phone' => ['required', 'max:13', 'min:9', 'regex:/^(07|\+2547|01|\+2541|7|1)\d{8}$/'],
            'email' => 'string|required'
        ]);

        $userLogin = [
            'role_id' => $request->role,
            'station_id' => $request->station,
            'updated_by' => auth()->user()->user_id
        ];

        $userInfo = [
            'first_name' => strtoupper($request->first_name),
            'middle_name' => strtoupper($request->other),
            'surname' => strtoupper($request->surname),
            'gender' => $request->gender,
            'phone_number' => $request->phone,
            'email_address' => $request->email,
            'id_number' => $request->id_number,
        ];

        User::where('user_id', $id)->update($userLogin);
        UserInfo::where('user_id', $id)->update($userInfo);
        $this->logger->create();
        return redirect()->back()->with('success', 'User account details updated successfully');
    }
    public function disableStaff($id)
    {
        User::find($id)->update(['status' => 2]);
        $this->logger->create();
        return redirect()->back()->with('success', 'User account details updated successfully');
    }
    public function registerRole(Request $request)
    {
        $request->validate([
            'role_name' => 'required|unique:roles',
        ]);
        Role::create(['role_name' => strtoupper($request->role_name), 'created_by' => auth()->user()->user_id]);
        $this->logger->create();
        return redirect()->back()->with('success', 'Role registered successfully');
    }
    public function registerStation(Request $request)
    {
        $request->validate([
            'station_name' => 'required|unique:stations,station_name|string',
        ]);

        $customId = new CustomIds();
        $stationId = $customId->generateId();

        $station = [
            'station_name' => strtoupper($request->station_name),
            'capacity' => strtoupper($request->capacity),
            'address' => strtoupper($request->address),
            'status' => $request->status,
            'station_id' => $stationId,
            'location_id' => $request->location,
            'created_by' => auth()->user()->user_id
        ];
        Station::create($station);
        $this->logger->create();
        return redirect()->back()->with('success', 'Station registered successfully');
    }
    public function viewStations()
    {
        $stations = Station::leftJoin('users', 'users.user_id', '=', 'stations.created_by')
            ->leftJoin('warehouse_locations', 'warehouse_locations.location_id', '=', 'stations.location_id')
            ->orderBy('stations.created_at', 'desc')
            ->select('stations.station_id', 'stations.station_name', 'stations.capacity', 'stations.address', 'stations.status', 'users.username', 'stations.location_id', 'location_name')
            ->get();
        $locations = WarehouseLocation::where('status', 1)->get();
        return view('admin::warehouses.stations')->with(['stations' => $stations, 'locations' => $locations]);
    }
    public function updateStation(Request $request, $id)
    {
        $request->validate([
            'station_name' => 'required|string|unique:stations,station_name,'.$id.',station_id',
        ]);
        $station = [
            'station_name' => strtoupper($request->station_name),
            'capacity' => strtoupper($request->capacity),
            'address' => strtoupper($request->address),
            'location_id' => $request->location,
            'status' => $request->status,
            'updated_by' => auth()->user()->user_id
        ];
        Station::where('station_id', $id)->update($station);
        $this->logger->create();
        return redirect()->back()->with('success', 'Station details updated successfully');
    }
    public function updateWarehouseBays(Request $request, $id)
    {
        $request->validate([
            'warehouseBay.*' => 'required|string'
        ]);
        $customId  = new CustomIds();

        foreach ($request->warehouseBay as $bay){
            $sub = [
                'bay_id' => $customId->generateId(),
                'station_id' => $id,
                'bay_name' => strtoupper($bay),
                'created_by' => auth()->user()->user_id
            ];
            WarehouseBay::create($sub);
            $this->logger->create();
        }
        return redirect()->back()->with('success', 'Success! Warehouse bay(s) registered successfully');
    }
    public function updateSubwarehouseName(Request $request, $id)
    {
        $request->validate(['newBay' => 'required']);
        WarehouseBay::where('bay_id', $id)->update(['bay_name' => $request->newBay]);
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Warehouse bay name updated successfully');
    }
    public function viewRoles()
    {
        $roles = Role::leftJoin('users', 'users.user_id', '=', 'roles.created_by')
            ->orderBy('roles.created_at', 'asc')
            ->select('roles.id', 'roles.role_name', 'users.username')
            ->get();
        return view('admin::users.roles')->with(['roles' => $roles]);
    }
    public function updateRoles(Request $request, $id)
    {
        $request->validate([
            'role_name' => 'required|unique:roles',
        ]);

        Role::where('id', $id)->update(['role_name' => strtoupper($request->role_name), 'updated_by' => auth()->user()->user_id]);
        $this->logger->create();
        return redirect()->back()->with('success', 'Role updated successfully');
    }
    public function viewClients()
    {
        $clients = Client::join('users', 'users.user_id', '=', 'clients.created_by')
            ->orderBy('clients.created_at', 'desc')
            ->select('clients.client_id', 'clients.client_name', 'clients.client_type', 'clients.phone', 'clients.email', 'clients.address', 'users.username')
            ->get();

        return view('admin::users.clients')->with(['clients' => $clients]);
    }
    public function registerClient(Request $request)
    {
        $request->validate([
            'client' => 'required|string|unique:clients,client_name',
            'client_type' => 'required',
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
        $clientId = $customId->generateId();

        $client = [
            'client_id' => $clientId,
            'client_name' => strtoupper($request->client),
            'client_type' => $request->client_type,
            'email' => strtolower($request->email),
            'phone' => $request->phone,
            'address' => strtoupper($request->address),
            'created_by' => auth()->user()->user_id,
        ];

        Client::create($client);
        $this->logger->create();
        return redirect()->back()->with('success', 'Successful! New client added successfully');
    }
    public function updateClient(Request $request, $id)
    {
        $request->validate([
            'client' => 'required|string',
            'client_type' => 'required',
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

        Client::where('client_id', $id)->update(
            [
                'client_name' => strtoupper($request->client),
                'client_type' => $request->client_type,
                'email' => strtolower($request->email),
                'phone' => $request->phone,
                'address' => strtoupper($request->address),
                'updated_by' => auth()->user()->user_id,
            ]
        );
        $this->logger->create();
        return redirect()->back()->with('success', 'Successful! Client updated successfully');
    }
    public function manageClient($id)
    {
        $user = User::find($id);
        $client = Client::where('client_id', $id)->first();
        return view('admin::users.manageClient')->with(['user' => $user, 'client' => $client]);
    }
    public function createLogins(Request $request, $id)
    {
        $request->validate([
            'username' => [
                'required',
                Rule::unique('users', 'username')->ignore($id, 'user_id')
            ],
            'password' => 'required|min:6|confirmed',
        ]);

        $client = [
            'client_name' => $request->clientName,
            'email' => $request->clientEmail,
            'phone' => $request->clientPhone,
        ];
        Client::where(['client_id' => $id])->update($client);
        if (User::where('user_id', $id)->exists()) {
            User::where(['user_id' => $id])->update(['username' => $request->username, 'password' => Hash::make($request->password),  'created_by' => auth()->user()->user_id]);
        }else{
            $user = [
                'user_id' => $id,
                'username' => $request->username,
                'password' => Hash::make($request->password),
                'role_id' => 10,
                'status' => 1,
                'created_by' => auth()->user()->user_id,
            ];
            User::create($user);
        }
        $this->logger->create();
        return redirect()->route('admin.viewClients')->with('success', 'Success! Client created successfully');
    }
    public function viewTeaGrade()
    {
        $grades = Grade::join('users', 'users.user_id', '=', 'grades.created_by')
            ->orderBy('grades.created_at', 'desc')
            ->select('grades.grade_id', 'grades.grade_name', 'grades.description', 'users.username')
            ->get();
        return view('admin::teas.grades')->with(['grades' => $grades]);

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
    public function updateTeaGrade(Request $request, $id)
    {
        $request->validate([
            'grade' => 'required|string'
        ]);
        Grade::where('grade_id', $id)->update(
            [
                'grade_name' => $request->grade,
                'description' => $request->description,
                'updated_by' => auth()->user()->user_id,
            ]
        );
        $this->logger->create();
        return redirect()->back()->with('success', 'Successful! Client updated successfully');
    }
    public function viewGardens()
    {
        $gardens = Garden::join('users', 'users.user_id', '=', 'gardens.created_by')
            ->orderBy('gardens.created_at', 'desc')
            ->select('gardens.garden_id', 'gardens.garden_name', 'gardens.garden_type', 'gardens.description', 'users.username')
            ->get();
        return view('admin::teas.gardens')->with(['gardens' => $gardens]);
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
    public function updateGarden(Request $request, $id)
    {
        $request->validate([
            'garden' => 'required|string',
            'garden_type' => 'required'
        ]);
        Garden::where('garden_id', $id)->update([
            'garden_name' => strtoupper($request->garden),
            'garden_type' => $request->garden_type,
            'updated_by' => auth()->user()->user_id,
            'description' => $request->description,
            'status' => 1,
        ]);
        $this->logger->create();
        return redirect()->back()->with('success', 'Successful! Garden updated successfully');
    }
    public function viewTransporters()
    {
        $transporters = Transporter::join('users', 'users.user_id', '=', 'transporters.created_by')
            ->orderBy('transporters.created_at', 'desc')
            ->select('transporters.transporter_id', 'transporters.transporter_name', 'transporters.description', 'transporters.transporter_type', 'users.username')
            ->get();
        return view('admin::logistics.transporters')->with('transporters', $transporters);
    }
    public function registerTransporter(Request $request)
    {
        $request->validate([
            'transporter' => 'required|string|unique:transporters,transporter_name',
            'transporter_type' => 'required',
        ]);

        if ($request->transporter_type == 3){

            $customId = new CustomIds();
            $transporterId = $customId->generateId();
            $transporter = [
                'transporter_id' => $transporterId,
                'transporter_name' => strtoupper($request->transporter).' (INBOUND)',
                'transporter_type' => 1,
                'description' => $request->description,
                'created_by' => auth()->user()->user_id,
            ];
            Transporter::create($transporter);
            $customId = new CustomIds();
            $transporterId = $customId->generateId();
            $transporter = [
                'transporter_id' => $transporterId,
                'transporter_name' => strtoupper($request->transporter).' (OUTBOUND)',
                'transporter_type' => 2,
                'description' => $request->description,
                'created_by' => auth()->user()->user_id,
            ];

            Transporter::create($transporter);
        }else{
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
        }
        $this->logger->create();
        return redirect()->back()->with('success', 'Successful! New transporter added successfully');
    }
    public function updateTransporter(Request $request, $id)
    {
        $request->validate([
            'transporter' => 'required|string',
            'transporter_type' => 'required',
        ]);
        Transporter::where('transporter_id', $id)->update(
            [
                'transporter_name' => strtoupper($request->transporter),
                'transporter_type' => $request->transporter_type,
                'description' => $request->description,
                'updated_by' => auth()->user()->user_id,
            ]
        );

        $this->logger->create();
        return redirect()->back()->with('success', 'Successful! Transporter updated successfully');
    }
    public function viewWarehouses()
    {
        $warehouses = Warehouse::join('users', 'users.user_id', '=', 'warehouses.created_by')
            ->orderBy('warehouses.created_at', 'desc')
            ->select('users.username', 'warehouses.warehouse_id', 'warehouses.warehouse_name', 'warehouses.phone', 'warehouses.address', 'warehouses.updated_by')
            ->get();
        return view('admin::warehouses.warehouses')->with('warehouses', $warehouses);
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
    public function updateWarehouse(Request $request, $id)
    {
        $request->validate([
            'warehouse' => 'required|string',
        ]);
        Warehouse::where('warehouse_id', $id)->update([
            'warehouse_name' => strtoupper($request->warehouse),
            'phone' => $request->phone,
            'address' => strtoupper($request->address),
            'updated_by' => auth()->user()->user_id
        ]);
        $this->logger->create();
        return redirect()->back()->with('success', 'Successful! New warehouse added successfully');
    }
    public function updateWarehouseLocations(Request $request, $id)
    {
        $request->validate([
            'subWarehouse.*' => 'required|string|unique:sub_warehouses,sub_warehouse_name'
        ]);
        $customId  = new CustomIds();
        foreach ($request->subWarehouse as $warehouse){
            $sub = [
                'sub_warehouse_id' => $customId->generateId(),
                'warehouse_id' => $id,
                'sub_warehouse_name' => strtoupper($warehouse),
                'created_by' => auth()->user()->user_id
            ];
            SubWarehouse::create($sub);
            $this->logger->create();
        }
        return redirect()->back()->with('success', 'Success! Warehouse sub stations created successfully');
    }
    public function viewBrokers()
    {
        $brokers = Broker::join('users', 'users.user_id', '=', 'brokers.created_by')
            ->orderBy('brokers.created_at', 'desc')
            ->select('users.username', 'brokers.broker_id', 'brokers.broker_name', 'brokers.phone', 'brokers.email', 'brokers.address', 'brokers.broker_type')
            ->get();
        return view('admin::users.brokers')->with('brokers', $brokers);
    }
    public function registerBroker(Request $request)
    {
        $request->validate([
            'broker' => 'required|string|unique:brokers,broker_name',
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
    public function updateBroker(Request $request, $id)
    {
        $request->validate([
            'broker' => 'required|string',
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
        Broker::where('broker_id', $id)->update(
            [
                'broker_name' => strtoupper($request->broker),
                'broker_type' => $request->broker_type,
                'email' => strtolower($request->email),
                'phone' => $request->phone,
                'address' => strtoupper($request->address),
                'updated_by' => auth()->user()->user_id,
            ]
        );
        $this->logger->create();
        return redirect()->back()->with('success', 'Successful! Broker updated successfully');
    }
    public function viewShippingVessels ()
    {
        $vessels = Vessel::latest()->get();
        return view('admin::admin.vessels.viewShippingVessels')->with(['vessels' => $vessels]);
    }
    public function addShippingVessel(Request $request)
    {
        $request->validate([
            'company_name' => 'required',
            'vessel_name' => 'required',
        ]);
        $customId = new CustomIds();
        $vessel = [
            'vessel_id' => $customId->generateId(),
            'company_name' => $request->company_name,
            'vessel_name' => $request->vessel_name,
            'status' => 1,
            'created_by' => auth()->user()->user_id
        ];
        $exVessel = Vessel::where(['company_name' => $request->company_name, 'vessel_name' => $request->vessel_name])->first();
        if ($exVessel){
            return redirect()->back()->with('info', 'Oops! Vessel already created');
        }else{
            Vessel::create($vessel);
            $this->logger->create();
        }
        return redirect()->back()->with('success', 'Success! Vessel created successfully');
    }
    public function updateShippingVessel(Request $request, $id)
    {
        $request->validate([
            'company_name' => 'required',
            'vessel_name' => 'required',
        ]);
        $vessel = [
            'company_name' => $request->company_name,
            'vessel_name' => $request->vessel_name,
        ];
        Vessel::where('vessel_id', $id)->update($vessel);
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Vessel updated successfully');
    }
    public function viewShippingDestinations ()
    {
        $destinations = Destination::latest()->get();
        return view('admin::logistics.destinations')->with(['destinations' => $destinations]);
    }
    public function addShippingDestination(Request $request)
    {
        $request->validate([
            'country_name' => 'required',
            'port_name' => 'required',
        ]);
        $customId = new CustomIds();
        $destination = [
            'destination_id' => $customId->generateId(),
            'country_name' => $request->country_name,
            'port_name' => $request->port_name,
            'status' => 1,
            'created_by' => auth()->user()->user_id
        ];
        $exDestination = Destination::where(['country_name' => $request->country_name, 'port_name' => $request->port_name])->first();
        if ($exDestination){
            return redirect()->back()->with('info', 'Oops! Destination already created');
        }else{
            Destination::create($destination);
            $this->logger->create();
        }
        return redirect()->back()->with('success', 'Success! Destination created successfully');
    }
    public function updateShippingDestination(Request $request, $id)
    {
        $request->validate([
            'country_name' => 'required',
            'port_name' => 'required',
        ]);
        $destination = [
            'country_name' => $request->country_name,
            'port_name' => $request->port_name,
        ];
        Destination::where('destination_id', $id)->update($destination);
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Destination updated successfully');
    }
    public function viewClearingAgents()
    {
        $agents = ClearingAgent::latest()->get();
        return view('admin::users.agents')->with(['agents' => $agents]);
    }
    public function addClearingAgent(Request $request)
    {
        $request->validate([
            'agent_name' => 'required',
            'agent_type' => 'required',
        ]);
        $customId = new CustomIds();
        $agent = [
            'agent_id' => $customId->generateId(),
            'agent_name' => $request->agent_name,
            'agent_type' => $request->agent_type,
            'status' => 1,
            'created_by' => auth()->user()->user_id
        ];
        $exAgent= ClearingAgent::where(['agent_name' => $request->agent_name, 'agent_type' => $request->agent_type])->first();
        if ($exAgent){
            return redirect()->back()->with('info', 'Oops! Clerical agent already created');
        }else{
            ClearingAgent::create($agent);
            $this->logger->create();
        }
        return redirect()->back()->with('success', 'Success! Clerical agent created successfully');
    }
    public function updateClearingAgent(Request $request, $id)
    {
        $request->validate([
            'agent_name' => 'required',
            'agent_type' => 'required',
        ]);
        $agent = [
            'agent_name' => $request->agent_name,
            'agent_type' => $request->agent_type,
        ];
        ClearingAgent::where('agent_id', $id)->update($agent);
        $this->logger->create();
        return redirect()->back()->with('success', 'Success!  Clerical agent updated successfully');
    }
    public function viewLLIs (Request $request)
    {
        $from = $request->get('from') ?? Carbon::now()->startOfMonth();
        $to = $request->get('to') ?? Carbon::now();
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
            ->whereBetween('loading_instructions.created_at', [$from, $to])
            ->get();
        return view('admin::DOS.collection')->with(['instructions' => $instructions, 'from' => $from, 'to' => $to]);
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
        $instructions =  LoadingInstruction::withTrashed()->get()->groupBy('loading_number')->count();
        $loadingNumber = 'TCI'.str_pad($instructions + 1, 4, '0', STR_PAD_LEFT);
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
        return redirect()->back()->with('success', 'Successful! Local loading instructions added successfully');
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
    public function removeTeaFromTCI($id)
    {
        $tci = LoadingInstruction::where('loading_id', $id)->first();
        DeliveryOrder::where('delivery_id', $tci->delivery_id)->update(['status' => 0]);
        $tci->delete();
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Tea removed from TCI');
    }
    public function revertTCI($id)
    {
        $tciNumber = base64_decode($id);
        $tciCollection = LoadingInstruction::where('loading_number', $tciNumber)->get();
        DeliveryOrder::whereIn('delivery_id', $tciCollection->pluck('delivery_id')->toArray())->update(['status' => 0]);
        LoadingInstruction::whereIn('loading_id', $tciCollection->pluck('loading_id')->toArray())->delete();
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Loading instructions canceled');
    }
    public function updateLLI(Request $request, $id)
    {
        $liNumber = base64_decode($id);
        $tci = LoadingInstruction::where('loading_number', $liNumber)->first();
        foreach ($request->delNumbers as $delivery){
            $load = [
                'loading_id' => (new CustomIds())->generateId(),
                'station_id' => $tci->station_id,
                'loading_number' => $liNumber,
                'transporter_id' => $tci->transporter_id,
                'delivery_id' => $delivery,
                'registration' => $tci->registration,
                'driver_id' => $tci->driver_id,
                'created_by' => auth()->user()->user_id,
                'status' => 1,
            ];
            if (LoadingInstruction::create($load)){
                DeliveryOrder::where('delivery_id', $delivery)->update(['status' => 1]);
            }
        }
        $this->logger->create();
        return redirect()->back()->with('success', 'Successful! Local loading instructions added successfully');
    }
    public function filterByGarden(Request $request)
    {
        $data = DeliveryOrder::join('users', 'users.user_id', '=', 'delivery_orders.created_by')
            ->join('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
            ->join('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
            ->join('brokers', 'brokers.broker_id', '=', 'delivery_orders.broker_id')
            ->join('warehouses', 'warehouses.warehouse_id', '=', 'delivery_orders.warehouse_id')
            ->join('sub_warehouses', 'sub_warehouses.warehouse_id', '=', 'warehouses.warehouse_id')
            ->join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->leftJoin('loading_instructions', function ($join) {
                $join->on('loading_instructions.delivery_id', '=', 'delivery_orders.delivery_id')
                    ->whereNull('loading_instructions.deleted_at');
            })
            ->leftJoin('drivers', 'drivers.driver_id', '=', 'loading_instructions.driver_id')
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'loading_instructions.transporter_id')
            ->leftJoin('stations', 'stations.station_id', '=', 'loading_instructions.station_id')
            ->leftJoin('users as loading_user', 'loading_user.user_id', '=', 'loading_instructions.created_by')
            ->where('delivery_orders.warehouse_id', $request->warehouseId)
            ->where(function ($query) {
                $query->where('delivery_orders.status', 0)
                    ->orWhereNull('delivery_orders.status');
            })
            ->select('warehouses.warehouse_id', 'sub_warehouses.sub_warehouse_id', 'sub_warehouses.sub_warehouse_name')
            ->orderBy('sub_warehouses.sub_warehouse_name', 'asc')
            ->get()
            ->groupBy('sub_warehouse_name');
        return response()->json($data);
    }
    public function filterByClient(Request $request)
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
            ->where(['warehouses.warehouse_id' => $request->warehouseId, 'sub_warehouses.sub_warehouse_id' => $request->warehouseBranchId])
            ->where(function ($query) {
                $query->where('delivery_orders.status', 0)
                    ->orWhereNull('delivery_orders.status');
            })
            ->select('clients.client_id', 'clients.client_name')
            ->orderBy( 'clients.client_name', 'desc')
            ->get()
            ->groupBy('client_name');
        return response()->json($data);
    }
    public function filterBySaleNumber(Request $request)
    {
//        return $request->all();
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
    public function viewDeliveryOrders(Request $request)
    {
        $from = $request->get('from') ?? Carbon::now()->startOfMonth();
        $to = $request->get('to') ?? Carbon::now();
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
            ->select('delivery_orders.client_id', 'delivery_orders.warehouse_id', 'delivery_orders.delivery_id','gardens.garden_name', 'grades.grade_name', 'brokers.broker_name', 'warehouses.warehouse_name', 'clients.client_name', 'delivery_orders.invoice_number', 'sub_warehouses.sub_warehouse_name', 'locality', 'lot_number', 'delivery_orders.status', 'delivery_orders.created_at', 'loading_number', 'collection')
            ->where('delivery_orders.delivery_type', 1)
            ->whereNull('delivery_orders.deleted_at')
            ->orderBy('delivery_orders.created_at', 'desc')
            ->orderBy('delivery_orders.status', 'asc')
            ->whereBetween('delivery_orders.created_at', [$from, $to])
            ->get();
        return view('admin::DOS.index')->with(['orders' => $orders, 'from' => $from, 'to' => $to]);
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
            ->select('stations.station_name', 'delivery_orders.delivery_id', 'loading_instructions.status', 'invoice_number', 'lot_number', 'sale_number', 'weight', 'packet', 'warehouses.warehouse_name', 'sub_warehouses.sub_warehouse_name', 'clients.client_name', 'garden_name', 'grade_name', 'prompt_date', 'sale_date', 'loading_number')
            ->where('loading_number', $tciNumber)
            ->get();
        $tci = $orders->first();
        return view('admin::DOS.tciDetails')->with(['orders' => $orders, 'tci' => $tci]);
    }
    public function amendTciDetails($id)
    {
        $tciNumber = base64_decode($id);
        $orders = LoadingInstruction::join('stations', 'stations.station_id', '=', 'loading_instructions.station_id')
            ->join('delivery_orders', 'delivery_orders.delivery_id', '=', 'loading_instructions.delivery_id')
            ->join('warehouses', 'warehouses.warehouse_id', '=', 'delivery_orders.warehouse_id')
            ->join('sub_warehouses', 'sub_warehouses.sub_warehouse_id', '=', 'delivery_orders.sub_warehouse_id')
            ->join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->join('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
            ->join('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
            ->select('stations.station_name', 'delivery_orders.delivery_id', 'loading_instructions.status', 'invoice_number', 'lot_number', 'sale_number', 'weight', 'packet', 'warehouses.warehouse_name', 'sub_warehouses.sub_warehouse_name', 'clients.client_name', 'garden_name', 'grade_name', 'prompt_date', 'sale_date', 'loading_number', 'loading_id', 'delivery_orders.client_id', 'delivery_orders.warehouse_id', 'delivery_orders.sub_warehouse_id')
            ->where('loading_number', $tciNumber)
            ->get();
        $tci = $orders->first();
        $teas = DeliveryOrder::join('users', 'users.user_id', '=', 'delivery_orders.created_by')
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
                ->where(['delivery_orders.client_id' => $tci->client_id, 'delivery_orders.warehouse_id' => $tci->warehouse_id, 'delivery_orders.sub_warehouse_id' => $tci->sub_warehouse_id])
                ->where(function ($query) {
                    $query->where('delivery_orders.status', 0)
                        ->orWhereNull('delivery_orders.status')
                        ->orWhere('delivery_orders.status', null)
                    ;
                })
                ->select('users.username', 'gardens.garden_name', 'grades.grade_name', 'brokers.broker_name', 'warehouses.warehouse_name', 'warehouses.warehouse_id', 'clients.client_name', 'delivery_orders.*', 'sub_warehouses.sub_warehouse_name')
                ->orderBy('clients.client_name', 'asc')
                ->orderBy('gardens.garden_name', 'asc')
                ->get();
        if ($tci == null){
            return redirect()->route('admin.viewLLIs')->with('info', 'Oops! TCI is empty');
        }
        return view('admin::DOS.editTciDetails')->with(['orders' => $orders, 'tci' => $tci, 'teas' => $teas]);
    }
    public function addDeliveryOrders()
    {
        $clients = Client::all();
        $gardens = Garden::all();
        $grades = Grade::all();
        $warehouses = Warehouse::all();
        $brokers = Broker::all();
        return view('admin::DOS.addDO')->with(['clients' => $clients, 'gardens' => $gardens, 'grades' => $grades, 'warehouses' => $warehouses, 'brokers' => $brokers]);
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
        return redirect()->route('admin.viewDeliveryOrders')->with('success', 'Successful! Delivery order created successfully');
    }
    public function editDO($id)
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

        return view('admin::DOS.editDO')->with(['order' => $order, 'gardens' => $gardens, 'warehouses' => $warehouses, 'grades' => $grades, 'brokers' => $brokers, 'clients' => $clients, 'transporters' => $transporters, 'users' => $users, 'registrations' => $registrations, 'allStations' => $stations]);
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
            'expiry_date' => $request->expiry_date
        ];
        DeliveryOrder::where('delivery_id', $id)->update($order);
        $this->logger->create();
        return redirect()->route('admin.viewDeliveryOrders')->with('success', 'Successful! Delivery order updated successfully');
    }
    public function deleteDeliveryOrder($id)
    {
        DeliveryOrder::where('delivery_id', $id)->delete();
        LoadingInstruction::where('delivery_id', $id)->delete();
        StockIn::where('delivery_id', $id)->delete();
        $this->logger->create();
        return redirect()->back()->with('success', 'Delivery order deleted successfully');
    }
    public function collectionReport(Request $request)
    {
        $client = $request->input('client');
        $from = $request->input('from');
        $to = $request->input('to');
        $collection = $request->input('collection');
        $delivery = $request->input('delivery');
        $warehouse = $request->input('warehouse');
        $query = DeliveryOrder::join('users', 'users.user_id', '=', 'delivery_orders.created_by')
            ->join('user_infos', 'user_infos.user_id', '=', 'delivery_orders.created_by')
            ->join('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
            ->join('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
            ->leftJoin('brokers', 'brokers.broker_id', '=', 'delivery_orders.broker_id')
            ->leftJoin('warehouses', 'warehouses.warehouse_id', '=', 'delivery_orders.warehouse_id')
            ->join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->leftJoin('loading_instructions', function ($join) {
                $join->on('loading_instructions.delivery_id', '=', 'delivery_orders.delivery_id')
                    ->whereNull('loading_instructions.deleted_at');
            })
            ->leftJoin('stock_ins', 'stock_ins.delivery_id', '=', 'delivery_orders.delivery_id')
           ->whereNull('delivery_orders.deleted_at')
            ->select('gardens.garden_name', 'grades.grade_name', 'warehouse_name', 'loading_instructions.status as load_status', 'date_received', 'stock_ins.status as stock_status', 'brokers.broker_name', 'warehouses.warehouse_name', 'clients.client_name', 'invoice_number', 'lot_number', 'sale_number', 'user_infos.first_name', 'user_infos.surname', 'delivery_orders.delivery_type', 'order_number', 'packet', 'weight')
            ->orderBy('delivery_orders.created_at', 'desc');
        if (!is_null($client)) {
            $query->where('delivery_orders.client_id', $client);
        }
        if (!is_null($collection)) {
            if ($collection == 1){
                $query->where('loading_instructions.status', 1);
            }elseif ($collection == 2){
                $query->where('loading_instructions.status', 2)->orWhere('delivery_orders.status', 2);
            }else{
                $query->where(['loading_instructions.status' => null, 'delivery_orders.delivery_type' => 1]);
            }
        }

        if (!is_null($delivery)) {
            if ($delivery == 1){
                $query->where('delivery_orders.delivery_type', 1);
            }elseif ($delivery == 2){
                $query->where('delivery_orders.delivery_type', 2);
            }else{
                $query->whereIn('delivery_orders.delivery_type', [1, 2]);
            }
        }

        if (!is_null($warehouse)) {
            $query->where('delivery_orders.warehouse_id', $warehouse);
        }

        if (!is_null($from)) {
            $query->where('delivery_orders.created_at', '>=', $from);
        }

        if (!is_null($to)) {
            $query->where('delivery_orders.created_at', '<=', $to);
        }

        $results = $query->get();
        $date = Carbon::today()->format('Y-m-d');
        $user = auth()->user()->user;
        $by = $user->last_name.' '.$user->first_name;

        if ($request->report == 2){
            return Excel::download(new ExportDeliveryOrders($results), 'TEA COLLECTION '.time().'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        }

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L', // Landscape
            'orientation' => 'L',
            'margin_top' => 2,
            'margin_bottom' => 7,
            'margin_left' => 5,
            'margin_right' => 5,
//            'setAutoTopMargin' => 'stretch',
            'setAutoBottomMargin' => 'stretch',
            'tempDir' => storage_path('app/mpdf_temp'),
            'pcre.backtrack_limit' => '10000000' // Increase backtrack limit
        ]);

        // Set footer
        $mpdf->SetHTMLFooter('
            <table width="100%">
                <tr>
                    <td align="left">Printed On : <strong>' . $date . '</strong></td>
                    <td align="center">Page {PAGENO} of {nbpg}</td>
                    <td align="right">Prepared by : <strong>' . $by . '</strong></td>
                </tr>
            </table>
        ');
        // Add company header (using absolute path for image)
        $logoPath = 'assets/img/favicons/icon.png';
        $companyHeader = '
            <style>
                .company-info { text-align: center; margin-bottom: 2px !important; font-size: 12px !important; line-height: 0.9 !important; }
                .logo { height: 50px; width: 50px; padding: 2px !important; }
                .heading { color: green; font-size: 13px; font-weight: bold; margin: 0 0; }
                .header { text-align: center; font-weight: bold; font-size: 12px; margin: 1px 0; }
                hr { border: 1px solid #000; margin: 3px 0; }
            </style>
            <div class="company-info">
                <span><img class="logo" src="' . $logoPath . '"></span>
                <h1 class="heading">PACKMAC HOLDINGS LIMITED</h1>
                <p>Chai Street Shimanzi High Level, Mombasa P.O BOX 41328-80100, Mombasa, Kenya (TMSA 186)</p>
            </div>
            <div class="header">STOCK POSITION<hr></div>';

        $mpdf->WriteHTML($companyHeader);

        $chunks = array_chunk(collect($results)->map(fn($item) => (object) $item)->toArray(), 1000);

        foreach ($chunks as $chunk) {
            $groupedChunk = collect($chunk)->groupBy('client_name')->map(function ($orders) {
                return $orders->map(fn($order) => (object) $order);
            });

            foreach ($groupedChunk as $clientName => $clientOrders) {
                $html = View::make('admin::downloads.tea_collections', [
                    'clientName' => $clientName,
                    'orders' => $clientOrders,
                    'by' => $by,
                ])->render();

                $mpdf->WriteHTML($html);
            }
        }

        // Output PDF
        $pdfFileName = 'STOCK POSITION.pdf';
        return Response::make($mpdf->Output($pdfFileName, PdfDestination::INLINE), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfFileName . '"',
        ]);

        $domPdfPath = base_path('vendor/dompdf/dompdf');
        \PhpOffice\PhpWord\Settings::setPdfRendererPath($domPdfPath);
        \PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF');

        $headers = ['size' => 8, 'name' => 'New Times Roman', 'bold' => true,/* 'space' => ['before' => 50]*/];
        $text = ['size' => 7, 'name' => 'New Times Roman', 'bold' => false, /*'space' => ['before' => 50]*/];

        $table = new Table(['unit' => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP, 'width' => 1450 * 1450, 'align' => 'center']);
        $table->addRow();
        $table->addCell(600, ['borderSize' => 1])->addText('#', $headers, ['space' => ['before' => 70]]);
        $table->addCell(2000, ['borderSize' => 1])->addText('Client Name', $headers, ['space' => ['before' => 70]]);
        $table->addCell(1700, ['borderSize' => 1])->addText('Garden Name', $headers, ['space' => ['before' => 70]]);
        $table->addCell(900, ['borderSize' => 1])->addText('Grade', $headers, ['space' => ['before' => 70]]);
        $table->addCell(1600, ['borderSize' => 1])->addText('Invoice #', $headers, ['space' => ['before' => 70]]);
        $table->addCell(900, ['borderSize' => 1])->addText('Pkgs', $headers, ['space' => ['before' => 70]]);
        $table->addCell(1800, ['borderSize' => 1])->addText('Weight', $headers, ['space' => ['before' => 70]]);
        $table->addCell(1300, ['borderSize' => 1])->addText("Pro't Date", $headers, ['space' => ['before' => 70]]);
        $table->addCell(2200, ['borderSize' => 1])->addText('Producer Warehouse', $headers, ['space' => ['before' => 70]]);
        $table->addCell(1400, ['borderSize' => 1])->addText('Status', $headers, ['space' => ['before' => 70]]);
        $table->addCell(1100, ['borderSize' => 1])->addText('Aging Date', $headers, ['space' => ['before' => 70]]);

        $totalPackets = 0;
        $totalWeight = 0;
        foreach ($results as $key => $stock){
            $table->addRow();
            $table->addCell(600, ['borderSize' => 1])->addText(++$key, $text, ['space' =>['before' => 70]]);
            $table->addCell(2000, ['borderSize' => 1])->addText($stock['client_name'], $text, ['space' =>['before' => 70]]);
            $table->addCell(1700, ['borderSize' => 1])->addText($stock['garden_name'], $text, ['space' =>['before' => 70]]);
            $table->addCell(900, ['borderSize' => 1])->addText($stock['grade_name'], $text, ['space' =>['before' => 70]]);
            $table->addCell(1600, ['borderSize' => 1])->addText($stock['invoice_number'], $text, ['space' =>['before' => 70]]);
            $table->addCell(900, ['borderSize' => 1])->addText(number_format($stock['packet'], 2), $text, ['space' =>['before' => 70]]);
            $table->addCell(1800, ['borderSize' => 1])->addText(number_format($stock['weight'], 2), $text, ['space' =>['before' => 70]]);
            $table->addCell(1300, ['borderSize' => 1])->addText($stock['prompt_date'], $text, ['space' =>['before' => 70]]);
            $table->addCell(2200, ['borderSize' => 1])->addText($stock['warehouse_name'], $text, ['space' =>['before' => 70]]);
            $table->addCell(1400, ['borderSize' => 1])->addText($stock['load_status'] == null ? "No TCI" : ($stock['load_status'] == 2 ? "Received" : "Under Collection"), $text, ['space' =>['before' => 70]]);

            $today = Carbon::now()->format('Y/m/d');
            if ($stock['stock_status'] == null || $stock['stock_status'] == 0){
                $date1 = new DateTime($today);
                $date2 = new DateTime($stock['created_at']);
                $interval = $date2->diff($date1);
                $dates = $interval->format('%R%a days');
            }elseif ($stock['stock_status'] == 1 && $stock['load_status'] == 1){
                $date1 = new DateTime($today);
                $date3 = new DateTime($stock['created_at']);
                $interval = $date3->diff($date1);
                $dates = $interval->format('%R%a days');
            }else{
                $date1 = new DateTime($today);
                $date4 = new DateTime(Carbon::createFromTimestamp($stock['date_received']));
                $interval = $date4->diff($date1);
                $dates = $interval->format('%R%a days');
            }
            $table->addCell(1100, ['borderSize' => 1])->addText($dates, $text, ['space' =>['before' => 70]]);

            $totalPackets += $stock['packet'];
            $totalWeight += $stock['weight'];
        }

        $table->addRow();
        $table->addCell(6800, ['gridSpan' => 5])->addText('TOTALS', $headers, ['align' => 'left' ,'space' =>['before' => 70]]);
        $table->addCell(900, ['gridSpan' => 1, 'borderSize' => 1])->addText(number_format($totalPackets, 2), $headers, ['space' =>['before' => 70]]);
        $table->addCell(1800, ['gridSpan' => 1, 'borderSize' => 1])->addText(number_format($totalWeight, 2), $headers, ['space' =>['before' => 70]]);
        $table->addCell(5800, ['gridSpan' => 4])->addText('', $headers, ['align' => 'center' ,'space' =>['before' => 70]]);

        $table->addRow();
        $table->addCell(12800, ['gridSpan' => 11])->addText('SUMMARY PER CLIENT', $headers, ['space' => ['before' => 200, 'after' => 100]]);

        $table->addRow();
        $table->addCell(600, ['gridSpan' => 1, 'borderSize' => 1])->addText('#', $headers, ['space' =>['before' => 70]]);
        $table->addCell(3700, ['gridSpan' => 2, 'borderSize' => 1])->addText('CLIENT NAME', $headers, ['space' =>['before' => 70]]);
        $table->addCell(3400, ['gridSpan' => 3, 'borderSize' => 1])->addText('TOTAL PACKAGES', $headers, ['space' =>['before' => 70]]);
        $table->addCell(2400, ['gridSpan' => 2, 'borderSize' => 1])->addText('TOTAL WEIGHT', $headers, ['space' =>['before' => 70]]);
        $table->addCell(3600, ['gridSpan' => 2, 'borderSize' => 1])->addText('PERCENTAGE (%)', $headers, ['space' =>['before' => 70]]);
        $table->addCell(1100, ['gridSpan' => 1])->addText();
        $i = 0;
        foreach ($results->groupBy('client_name') as $client => $stocks){
            $table->addRow();
            $table->addCell(600, ['gridSpan' => 1, 'borderSize' => 1])->addText(++$i, $headers, ['space' =>['before' => 70]]);
            $table->addCell(3700, ['gridSpan' => 2, 'borderSize' => 1])->addText($client, $headers, ['space' =>['before' => 70]]);
            $clientPackets = 0;
            $clientWeight = 0;
            foreach ($stocks as $stock){
                $clientWeight += $stock['weight'];
                $clientPackets += $stock['packet'];
            }
            $table->addCell(2900, ['gridSpan' => 3, 'borderSize' => 1])->addText(number_format($clientPackets, 2), $text, ['space' =>['before' => 70]]);
            $table->addCell(2000, ['gridSpan' => 2, 'borderSize' => 1])->addText(number_format($clientWeight, 2), $text, ['space' =>['before' => 70]]);
            $table->addCell(2800, ['gridSpan' => 2, 'borderSize' => 1])->addText(number_format($clientWeight/$totalWeight*100, 2).'%', $headers, ['space' =>['before' => 70]]);
            $table->addCell(1100, ['gridSpan' => 1])->addText();
        }

        $table->addRow();
        $table->addCell(12800, ['gridSpan' => 11])->addText('SUMMARY PER WAREHOUSE', $headers, ['space' => ['before' => 200, 'after' => 100]]);

        $table->addRow();
        $table->addCell(600, ['gridSpan' => 1, 'borderSize' => 1])->addText('#', $headers, ['space' =>['before' => 70]]);
        $table->addCell(3700, ['gridSpan' => 2, 'borderSize' => 1])->addText('WAREHOUSE NAME', $headers, ['space' =>['before' => 70]]);
        $table->addCell(2900, ['gridSpan' => 3, 'borderSize' => 1])->addText('TOTAL PACKAGES', $headers, ['space' =>['before' => 70]]);
        $table->addCell(2000, ['gridSpan' => 2, 'borderSize' => 1])->addText('TOTAL WEIGHT', $headers, ['space' =>['before' => 70]]);
        $table->addCell(2800, ['gridSpan' => 2, 'borderSize' => 1])->addText('PERCENTAGE (%)', $headers, ['space' =>['before' => 70]]);
        $table->addCell(1100, ['gridSpan' => 1])->addText();

        $i = 0;
        $groupedResults = $results->groupBy('warehouse_name');
        $sortedResults = $groupedResults->map(function ($group) {
            return $group->sortBy('warehouse_name', SORT_NATURAL | SORT_FLAG_CASE);
        });
        foreach ($sortedResults as $station => $stocks){
            $table->addRow();
            $table->addCell(600, ['gridSpan' => 1, 'borderSize' => 1])->addText(++$i, $headers, ['space' =>['before' => 70]]);
            $table->addCell(3700, ['gridSpan' => 2, 'borderSize' => 1])->addText($station == null ? 'UNCLASSIFIED' : $station, $headers, ['space' =>['before' => 70]]);

            $stationPackets = 0;
            $stationWeight = 0;
            foreach ($stocks as $stock){
                $stationWeight += $stock['weight'];
                $stationPackets += $stock['packet'];
            }

            $table->addCell(2900, ['gridSpan' => 3, 'borderSize' => 1])->addText(number_format($stationPackets, 2), $text, ['space' =>['before' => 70]]);
            $table->addCell(2000, ['gridSpan' => 2, 'borderSize' => 1])->addText(number_format($stationWeight, 2), $text, ['space' =>['before' => 70]]);
            $table->addCell(2800, ['gridSpan' => 2, 'borderSize' => 1])->addText(number_format($stationWeight/$totalWeight*100, 2).'%', $headers, ['space' =>['before' => 70]]);
            $table->addCell(1100, ['gridSpan' => 1])->addText();
        }


        $stock = new TemplateProcessor(storage_path('delivery_template.docx'));
        $stock->setComplexBlock('{table}', $table);
        $stock->setValue('date', $date);
        $stock->setValue('by', $by);
        $docPath = 'Files/TempFiles/COLLECTION '.time().'.docx';
        $stock->saveAs($docPath);

        // return response()->download($docPath)->deleteFileAfterSend(true);

        $phpWord = IOFactory::load($docPath);
        $contents = \PhpOffice\PhpWord\IOFactory::load($docPath);
        $pdfPath = 'Files/TempFiles/COLLECTION'.time(). ".pdf";
        $converter =  new OfficeConverter($docPath, 'Files/TempFiles/');
        $converter->convertTo('COLLECTION'.time().".pdf");
        unlink($docPath);
        return response()->download($pdfPath)->deleteFileAfterSend(true);
    }
    public function filterWarehouseBay(Request $request)
    {
        $data = WarehouseBay::where('station_id', $request->selectedStation)->orderBy('bay_name', 'asc')->get();
        return response()->json($data);
    }
    public function filterWarehouseBranch(Request $request)
    {
        $data = SubWarehouse::where(['warehouse_id' => $request->warehouseId, 'status' => 1])->orderBy('sub_warehouse_name', 'asc')->get();
        return response()->json($data);
    }

    public function viewDeliveries (Request $request)
    {
        $from = $request->get('from') ?? Carbon::now()->startOfMonth();
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
            ->whereBetween('currentstock.date_received', [strtotime($from), strtotime($to)])
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
                END AS used'), 'net_weight', 'total_pallets'
                    );

        $deliveries = $teas->get();
        $clients = $teas->select('client_id', 'client_name')->groupBy('client_id', 'client_name')->get();
        $transporters = Transporter::select('transporter_id', 'transporter_name')->groupBy('transporter_id', 'transporter_name')->get();
        return view('admin::stock.index')->with(['stocks' => $deliveries, 'clients' => $clients, 'from' => $from, 'to' => $to, 'transporters' => $transporters]);
    }
    public function editStock($id)
    {
        $order = DB::table('currentstock')->where('stock_id', $id)->first();
        $stations = Station::orderBy('station_name', 'asc')->get();
        $transporters  = Transporter::orderBy('transporter_name')->get();
        $drivers  = Driver::orderBy('id_number', 'asc')->get();
        $registrations = LoadingInstruction::orderBy('registration', 'asc')->get()->groupBy('registration');
        return view('admin::stock.editStock')->with(['stations' => $stations, 'transporters' => $transporters, 'drivers' => $drivers, 'registrations' => $registrations, 'order' => $order]);
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
        return redirect()->route('admin.viewDeliveries')->with('success', 'Success! Stock entry has been updated');
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
    public function getDoNumber (Request $request)
    {
        $data = DeliveryOrder::join('users', 'users.user_id', '=', 'delivery_orders.created_by')
            ->join('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
            ->join('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
            ->join('brokers', 'brokers.broker_id', '=', 'delivery_orders.broker_id')
            ->join('warehouses', 'warehouses.warehouse_id', '=', 'delivery_orders.warehouse_id')
            ->join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->leftJoin('loading_instructions', function ($join) {
                $join->on('loading_instructions.delivery_id', '=', 'delivery_orders.delivery_id')
                    ->whereIn('loading_instructions.status', [1, 2])
                    ->whereNull('loading_instructions.deleted_at');
            })
            ->leftJoin('drivers', 'drivers.driver_id', '=', 'loading_instructions.driver_id')
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'loading_instructions.transporter_id')
            ->leftJoin('stations', 'stations.station_id', '=', 'loading_instructions.station_id')
            ->leftJoin('users as loading_user', 'loading_user.user_id', '=', 'loading_instructions.created_by')
            ->select('users.username', 'gardens.garden_name','grades.grade_name', 'brokers.broker_name', 'warehouses.warehouse_name', 'clients.client_name', 'delivery_orders.*', 'transporters.transporter_id', 'transporters.transporter_name', 'drivers.driver_id', 'drivers.driver_name', 'drivers.id_number', 'drivers.phone', 'loading_instructions.loading_id', 'loading_instructions.loading_number', 'loading_instructions.status as load_status', 'loading_instructions.registration', 'loading_instructions.created_by as load_user_id', 'loading_user.username as load_user', 'stations.station_name', 'loading_instructions.deleted_at')
            ->whereIn('delivery_orders.status', [1, 2])
            ->where(function($query) use ($request) {
                $query->where('order_number', $request->doNumber)
                    ->orWhere('loading_number', $request->doNumber);
            })
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
            ->get();
        $stations = Station::orderBy('station_name', 'asc')->get();
        $transporters = Transporter::all();
        $drivers = Driver::all();
        $registrations = LoadingInstruction::pluck('registration')->toArray();

        if (!$data->isEmpty()) {
            return view('admin::stock.receiveTCI')->with(['orders' => $data, 'stations' => $stations, 'transporters' => $transporters, 'drivers' => $drivers, 'registrations' => $registrations]);
        } else {
            return back()->with('error', 'Oops! The TCI is fully received or doesn\'t exist');
        }
//        return response()->json($data);
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
            // 'delivery_note' => 'required|image|mimes:png,jpg,jpeg|max:5120'
            'delivery_note' => 'required|file|mimetypes:image/png,image/jpeg,application/pdf|max:5120',

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
            return redirect()->route('admin.viewDeliveries')->with('error', 'Oops! An error occurred please try again '.$e->getMessage());
        }
        if (!empty($errors)) {
            return redirect()->route('admin.viewDeliveries')->with(['importErrors' => $errors]);
        } else {
            return redirect()->route('admin.viewDeliveries')->with('success', 'Success! Delivery has been received');
        }
    }
    public function viewInternalTransfers(Request $request)
    {
        $from = $request->get('from') ?? Carbon::now()->startOfMonth();
        $to = $request->get('to') ?? Carbon::now();
        $transfers = Transfers::join('stations', 'stations.station_id', '=', 'transfers.station_id')
            ->join('delivery_orders', 'delivery_orders.delivery_id', '=', 'transfers.delivery_id')
            ->join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->join('stations as destination_station', 'destination_station.station_id', '=', 'transfers.destination')
            ->join('warehouse_locations', 'warehouse_locations.location_id', '=', 'destination_station.location_id')
            ->orderBy('transfers.created_at', 'desc')
            ->select('stations.station_name', 'stations.station_id', 'clients.client_name', 'destination_station.station_name as destination_name', 'destination_station.station_id as destination', 'transfers.status', 'transfers.delivery_number', DB::raw('DATE(transfers.created_at) as created_at'), 'warehouse_locations.location_id', 'stations.location_id as origin')
            ->selectRaw('SUM(requested_palettes) as total_palettes')
            ->selectRaw('SUM(requested_weight) as total_weight')
            ->groupBy('delivery_number', 'station_name', 'client_name', 'destination_name', 'status', 'created_at', 'station_id', 'destination_station.station_id', 'location_id', 'stations.location_id')
            ->whereBetween('transfers.created_at', [$from, $to])
            ->get();
        return view('admin::transfers.internalTransfers')->with(['transfers' => $transfers, 'from' => $from, 'to' => $to]);
    }
    public function prepareInternalTransfer(Request $request)
    {
        $transfers = DB::table('currentstock')->where('current_stock', '>', 0)
            ->where('current_weight', '>', 0)
            ->where(['client_id' => $request->client, 'station_id' => $request->station])
            ->select('client_id', 'stock_id', 'order_number', 'garden_name', 'grade_name', 'invoice_number', 'lot_number', 'current_stock', 'current_weight')
            ->orderBy('garden_name', 'asc')
            ->orderBy('invoice_number', 'asc')
            ->get();
        $client = Client::find($request->client);
        $destination = Station::find($request->station);
        $station = Station::find($request->location);
        $transporters = Transporter::all();
        $registrations = Transfers::pluck('registration')->toArray();
        $drivers = Driver::all();
        return view('admin::transfers.prepareInternalTransfer')->with(['transfers' => $transfers, 'client' => $client, 'station' => $station, 'destination' => $destination, 'transporters' => $transporters, 'registrations' => $registrations, 'users' => $drivers]);
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
            ->whereIn('transfers.status', [2, 3])
            ->get();

        $transporters = Transporter::all();
        $registrations = Transfers::pluck('registration')->toArray();
        $drivers = Driver::all();
        $stations = WarehouseBay::where('station_id', $transfers[0]->destination)->get();
        return view('admin::transfers.prepareToReceiveTransfer')->with(['transfers' => $transfers, 'transporters' => $transporters, 'registrations' => $registrations, 'users' => $drivers, 'stations' => $stations]);
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
            ->select('ex_transfer_id', 'external_transfers.status', DB::raw('COALESCE(clients.client_name, blendBalances.client_name) as client_name'), DB::raw('COALESCE(warehouses.warehouse_name, other_destinations.warehouse_name) as warehouse_name'), DB::raw('COALESCE(stations.station_name, blendBalances.station_name) as station_name'), 'external_transfers.delivery_number', DB::raw('DATE(external_transfers.created_at) as created_at'), 'location_id', DB::raw('COALESCE(gardens.garden_name, blendBalances.garden) as garden_name'),DB::raw('COALESCE(grades.grade_name, blendBalances.grade) as grade_name'), DB::raw('COALESCE(delivery_orders.invoice_number, blendBalances.blend_number) as invoice_number'), 'external_transfers.transferred_palettes', 'external_transfers.transferred_weight', 'delivery_orders.lot_number', 'external_transfers.release_date', 'lot')
            ->where(['external_transfers.delivery_number' => base64_decode($id)])
            ->get();
        return view('admin::transfers.viewExternalTransfer')->with(['transfers' => $transfers]);
    }
    public function viewExternalTransfers(Request $request)
    {
        $from = $request->get('from') ?? Carbon::now()->startOfMonth();
        $to = $request->get('to') ?? Carbon::now();

        $transfers = ExternalTransfer::leftJoin('blendBalances', function ($join) {
            $join->on('blendBalances.blend_balance_id', '=', 'external_transfers.stock_id')
                ->on('blendBalances.blend_id', '=', 'external_transfers.delivery_id');
            })
            ->leftJoin('delivery_orders', function ($join) {
                $join->on('delivery_orders.delivery_id', '=', 'external_transfers.delivery_id')
                    ->whereNull('delivery_orders.deleted_at');
            })
            ->leftJoin('auctions', function ($join) {
                $join->on('auctions.delivery_id', '=', 'external_transfers.delivery_id')
                    ->whereNull('auctions.deleted_at');
            })
            ->leftJoin('clients as buyer', 'buyer.client_id', '=', 'auctions.client_id')
            ->leftJoin('stock_ins', 'stock_ins.stock_id', '=', 'external_transfers.stock_id')
            ->leftJoin('stations', 'stations.station_id', '=', 'stock_ins.station_id')
            ->leftJoin('warehouse_locations', 'warehouse_locations.location_id', '=', 'stations.location_id')
            ->leftJoin('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->leftjoin('warehouses', 'warehouses.warehouse_id', '=', 'external_transfers.warehouse_id')
            ->leftjoin('other_destinations', 'other_destinations.warehouse_id', '=', 'external_transfers.warehouse_id')
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'external_transfers.transporter_id')
            ->leftJoin('other_transporters', 'other_transporters.transporter_id', '=', 'external_transfers.transporter_id')
            ->leftJoin('drivers', 'drivers.driver_id', '=', 'external_transfers.driver_id')
            ->select('external_transfers.status',
                DB::raw("CONCAT(COALESCE(clients.client_name, ''), COALESCE(blendBalances.client_name, '')) as client_name"),
                DB::raw('COALESCE(warehouses.warehouse_name, other_destinations.warehouse_name) as warehouse_name'),
                DB::raw('COALESCE(warehouses.warehouse_id, other_destinations.warehouse_id) as warehouse_id'),
                DB::raw('COALESCE(stations.station_name, blendBalances.station_name) as station_name'),
                'external_transfers.delivery_number',
                DB::raw('DATE(external_transfers.created_at) as created_at'),
                'buyer.client_name as buyer_name', 'warehouse_locations.location_id',
                DB::raw("CONCAT(COALESCE(transporters.transporter_id, ''), COALESCE(other_transporters.transporter_id, '')) as transporter_id"),
                DB::raw("CONCAT(COALESCE(transporters.transporter_name, ''), COALESCE(other_transporters.transporter_name, '')) as transporter_name"),
                'external_transfers.driver_id', 'driver_name', 'drivers.phone', 'id_number', 'external_transfers.registration', 'external_transfers.release_date', 'lot'
            )
            ->selectRaw('SUM(external_transfers.transferred_palettes) AS total_palettes')
            ->selectRaw('SUM(external_transfers.transferred_weight) AS total_weight')
            ->orderBy('delivery_number', 'desc')
            ->orderBy('external_transfers.created_at', 'desc')
            ->groupBy(
                'external_transfers.status',
                DB::raw("CONCAT(COALESCE(clients.client_name, ''), COALESCE(blendBalances.client_name, ''))"),

                DB::raw('COALESCE(warehouses.warehouse_name, other_destinations.warehouse_name)'),
                DB::raw('COALESCE(warehouses.warehouse_id, other_destinations.warehouse_id)'),
                DB::raw('COALESCE(stations.station_name, blendBalances.station_name)'),
                'external_transfers.delivery_number',
                DB::raw('DATE(external_transfers.created_at)'),
                'buyer_name', 'warehouse_locations.location_id',
                DB::raw("CONCAT(COALESCE(transporters.transporter_id, ''), COALESCE(other_transporters.transporter_id, ''))"),
                DB::raw("CONCAT(COALESCE(transporters.transporter_name, ''), COALESCE(other_transporters.transporter_name, ''))"),
                'driver_id', 'driver_name', 'phone', 'id_number', 'registration', 'external_transfers.release_date', 'lot'
            )
            ->whereBetween('external_transfers.created_at', [$from, $to])
            ->get();
        $warehouses = Warehouse::all();
        $transporters = Transporter::all();
        $clients = Client::all();
        $users = Driver::all();
        return view('admin::transfers.externalTransfers')->with(['transfers' => $transfers, 'warehouses' => $warehouses, 'from' => $from, 'to' => $to, 'clients' => $clients, 'transporters' => $transporters, 'users' => $users]);
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
        return view('admin::transfers.prepareExternalTransfer')->with(['transfers' => $transfers, 'client' => $client, 'station' => $station, 'destinations' => $destinations, 'transporters' => $transporters, 'registrations' => $registrations, 'users' => $drivers]);
    }
    public function selectClients(Request $request)
    {
        $data = DB::table('currentstock')
            ->whereNotNull('current_stock')
            ->where('current_stock', '>', 0)
            ->whereNotNull('current_weight')
            ->where('current_weight', '>', 0)
            ->where(['station_id' => $request->warehouseId])
            ->where( 'current_stock', '>', 0)
            ->orderBy('client_name')
            ->select('client_id', 'client_name')
            ->get()
            ->groupBy('client_name');
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
            ->orderBy('client_name')
            ->get();
        return response()->json($data);
    }
    public function registerInternalRequest(Request $request)
    {
        $requestData = json_decode($request->allDeliveries, true);
        if (isset($requestData['deliveries']) && !empty($requestData['deliveries'])) {
            DB::beginTransaction();
            try {
//                 Loop through each delivery item
                $customId = new CustomIds();
                $driver = Driver::where('id_number', $request->idNumber)->first();
                if ($driver || $request->idNumber === null){
                    $delID = Transfers::newDelivery();
                    foreach ($requestData['deliveries'] as $key => $delivery) {
                        $transferId = $customId->generateId();
                        $stock = StockIn::where('stock_id', $delivery['deliveryId'])->first();
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
                        $stock = StockIn::where('stock_id', $delivery['deliveryId'])->first();
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
                        ];
                        Transfers::create($transfer);
                    }
                }
                $this->logger->create();
                DB::commit();
                return redirect()->route('admin.viewInternalTransfers')->with('success', 'Success! Transfer created successfully');
            } catch (\Exception $e) {
                // Rollback the transaction if an exception occurs
                DB::rollback();
                // Handle or log the exception
                return redirect()->back()->with('error', 'Oops! An error occurred please try again');
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
                return redirect()->route('admin.viewExternalTransfers')->with('success', 'Success! External transfer created successfully');
            } catch (Exception $e) {
//                // Rollback the transaction if an exception occurs
                DB::rollback();
//                // Handle or log the exception
                return redirect()->route('admin.viewExternalTransfers')->with('error', 'Oops! An error occurred please try again '.$e);
            }
        }else{
            return redirect()->back()->with('error', 'No teas selected');
        }
    }
    public function initiateTransfer($id)
    {
        Transfers::where('delivery_number', base64_decode($id))->update(['status' => 0]);
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Transfer request initiated successfully');
    }
    public function initiateExternalTransfer($id)
    {
        ExternalTransfer::where('delivery_number',  base64_decode($id))->update(['status' => 1]);
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Transfer request initiated successfully');

    }
    public function approveExternalTransfer($id)
    {
        $transfers = ExternalTransfer::where('delivery_number',  base64_decode($id))->get();
        foreach ($transfers as $transfer) {
            $newStatus = match ($transfer->status) {
                1 => 2,
                2 => 3,
                default => $transfer->status,
            };
            $transfer->update([
                'status' => $newStatus,
            ]);
        }
        $transfer = ExternalTransfer::where('delivery_number', base64_decode($id))->first();
        if (in_array($transfer->status, [2, 3])) {
            Approval::create([
                'approval_id'   => (new CustomIds())->generateId(),
                'job_id'        => base64_decode($id),
                'user_id'       => auth()->user()->user_id,
                'approval_date' => time(),
                'order'         => $transfer->status == 2 ? 1 : 2,
            ]);
        }
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

        ExternalTransfer::where('delivery_number', base64_decode($id))->update([
            'warehouse_id' => $request->warehouse_id,
            'status' => 4,
            'driver_id' => $driverId,
            'registration' => $request->registration,
            'transporter_id' => $request->transporter_id
        ]);

        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Transfer request initiated successfully');
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
        $transfers = Transfers::where('delivery_number', base64_decode($id))->get();
        foreach ($transfers as $transfer) {
            $newStatus = match ($transfer->status) {
                0 => 1,
                1 => 2,
                2 => 3,
                default => $transfer->status,
            };
            $transfer->update([
                'status' => $newStatus,
            ]);
        }
        $transfer = Transfers::where('delivery_number', base64_decode($id))->first();
        if (in_array($transfer->status, [1, 2])) {
            Approval::create([
                'approval_id'   => (new CustomIds())->generateId(),
                'job_id'        => base64_decode($id),
                'user_id'       => auth()->user()->user_id,
                'approval_date' => time(),
                'order'         => $transfer->status == 1 ? 1 : 2,
            ]);
        }
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Transfer request serviced and stock updated successfully');
    }
    public function receiveInterTransferRequests(Request $request, $id)
    {
        $request->validate([
            'idNumber' => 'required',
            'driverName' => 'required',
            'driverPhone' => 'required',
        ]);
        return $transfers = json_decode($request->allDeliveries, TRUE);
        DB::beginTransaction();
        try {
            foreach ($transfers['deliveries'] as $transferItem){
                $transfer = Transfers::where('transfer_id', $transferItem['deliveryId'])->first();
                $driver = Driver::where('id_number', $request->idNumber)->first();
                $customId = new CustomIds();
                $stockId = $customId->generateId();
                if ($driver){
                    $stock = [
                        'stock_id' => $stockId,
                        'delivery_id' => $transfer->delivery_id,
                        'station_id' => $transfer->destination,
                        'date_received' => time(),
                        'delivery_number' => $transfer->delivery_number,
                        'warehouse_bay' => $request->bayId,
                        'total_weight' => $transferItem['weight'],
                        'total_pallets' => $transferItem['palette'],
                        'pallet_weight' => 0,
                        'package_tare' => 0,
                        'net_weight' => $transferItem['weight'],
                        'user_id' => auth()->user()->user_id,
                        'registration' => $request->registration,
                        'driver_id' => $driver->driver_id,
                        'transporter_id' => $request->transporter,
                    ];
                    StockIn::create($stock);
                    $transfer->update([
                        'status' => 4,
                        'driver_id' => $driver->driver_id,
                        'registration' => $request->registration,
                        'transporter_id' => $request->transporter,
                        'requested_palettes' => $transferItem['palette'],
                        'requested_weight' => $transferItem['weight'],
                    ]);
                }else{
                    $driverId = $customId->generateId();
                    $newDriver = [
                        'driver_id' => $driverId,
                        'id_number' => $request->idNumber,
                        'driver_name' => strtoupper($request->driverName),
                        'phone' => $request->driverPhone
                    ];
                    Driver::create($newDriver);
                    $stock = [
                        'stock_id' => $stockId,
                        'delivery_id' => $transfer->delivery_id,
                        'station_id' => $transfer->destination,
                        'date_received' => time(),
                        'delivery_number' => $transfer->delivery_number,
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
                    $transfer->update([
                        'status' => 3,
                        'driver_id' => $driverId,
                        'registration' => $request->registration,
                        'transporter_id' => $request->transporter,
                        'requested_palettes' => $transferItem['palette'],
                        'requested_weight' => $transferItem['weight'],
                    ]);
                }
            }
            $this->logger->create();
            DB::commit();
            return redirect()->route('admin.viewInternalTransfers')->with('success', 'Success! Transfer request received successfully');

        } catch (\Exception $e) {
            // Rollback the transaction if an exception occurs
            DB::rollback();
            // Handle or log the exception
            return redirect()->back()->with('error', 'Oops! An error occurred please try again');
        }
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
             return redirect()->route('admin.viewInternalTransfers')->with('success', 'Success! Transfer request received successfully');
            // return redirect()->route('clerk.viewInternalTransfers')->with('success', 'Success! Transfer request received successfully');
        } catch (Exception $e) {
            // Rollback the transaction if an exception occurs
            DB::rollback();
            // Handle or log the exception
            return redirect()->back()->with('error', 'Oops! An error occurred please try again');
        }
    }

    public function viewInternalTransferDetails($id)
    {
        $transfers = Transfers::join('stations', 'stations.station_id', '=', 'transfers.station_id')
            ->join('delivery_orders', 'delivery_orders.delivery_id', '=', 'transfers.delivery_id')
            ->join('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
            ->join('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
            ->join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->join('stations as destination_station', 'destination_station.station_id', '=', 'transfers.destination')
            ->join('warehouse_locations', 'warehouse_locations.location_id', '=', 'destination_station.location_id')
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'transfers.transporter_id')
            ->leftJoin('drivers', 'drivers.driver_id', '=', 'transfers.driver_id')
            ->orderBy('transfers.created_at', 'desc')
            ->select('stations.station_name', 'stations.station_id', 'clients.client_name', 'destination_station.station_name as destination_name', 'destination_station.station_id as destination', 'transfers.status', 'transfers.delivery_number', 'transfers.created_at', 'warehouse_locations.location_id', 'stations.location_id as origin', 'requested_palettes', 'requested_weight', 'garden_name', 'grade_name', 'invoice_number', 'lot_number', 'stock_id', 'registration', 'driver_name', 'id_number', 'drivers.phone', 'transporters.transporter_id', 'transporter_name', 'transfer_id')
            ->where(['delivery_number' => base64_decode($id)])
            ->get();
        return view('admin::transfers.viewInternalTransfer')->with(['transfers' => $transfers]);
    }
    public function updateInterTransferRequest(Request $request, $id)
    {
        $request->validate([
            'update' => 'required',
            "station" => 'required',
            "transporter" => 'required',
            "registration" => 'required',
            "idNumber" => 'required',
            "driverName" => 'required',
            "driverPhone" => 'required'
        ]);
        $driver = Driver::where('id_number', $request->idNumber)->first();
        if ($driver){
            foreach ($request->update as $transfer){
                $update = [
                    'requested_palettes' => $transfer['pallets'],
                    'requested_weight' => $transfer['weight'],
                    'driver_id' => $driver->driver_id,
                    'destination' => $request->station,
                    'registration' => $request->registration,
                    'transporter_id' => $request->transporter,
                ];
                Transfers::where('transfer_id', $transfer['transferId'])->update($update);
            }

        }else{
            $customId = new CustomIds();
            $driverId = $customId->generateId();
            $driver = [
                'driver_id' => $driverId, 'id_number' => $request->idNumber, 'driver_name' => $request->driverName, 'phone' => $request->driverPhone
            ];
            Driver::create($driver);
            foreach ($request->update as $transfer){
                Transfers::where('transfer_id', $transfer['transfer_id'])->update([
                    'requested_palettes' => $transfer['pallets'],
                    'requested_weight' => $transfer['weight'],
                    'destination' => $request->station,
                    'registration' => $request->registration,
                    'transporter_id' => $request->transporter,
                    'driver_id' => $driverId
                ]);
            }
        }
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Transfer request updated successfully');
    }
    public function updateExternalTransferRequest (Request $request, $id)
    {
        $request->validate([
            "warehouse" => 'required',
            "transporter" => 'required',
            "registration" => 'required',
            "idNumber" => 'required',
            "driverName" => 'required',
            "driverPhone" => 'required'
        ]);
        $driver = Driver::where('id_number', $request->idNumber)->first();
        if ($driver){
            foreach ($request->update as $transfer){
                $update = [
                    'warehouse_id' => $request->warehouse,
                    'registration' => $request->registration,
                    'transporter_id' => $request->transporter,
                    'transferred_palettes' => $transfer['packagesRequested'],
                    'transferred_weight' => $transfer['weight'],
                    'driver_id' => $driver->driver_id
                ];
                ExternalTransfer::where('ex_transfer_id', $transfer['transferId'])->update($update);
            }
        }else{
            $customId = new CustomIds();
            $driverId = $customId->generateId();
            $driver = [
                'driver_id' => $driverId, 'id_number' => $request->idNumber, 'driver_name' => $request->driverName, 'phone' => $request->driverPhone
            ];
            Driver::create($driver);
            foreach ($request->update as $transfer){
                $update = [
                    'warehouse_id' => $request->warehouse,
                    'registration' => $request->registration,
                    'transporter_id' => $request->transporter,
                    'transferred_palettes' => $transfer['packagesRequested'],
                    'transferred_weight' => $transfer['weight'],
                    'driver_id' => $driverId
                ];
                ExternalTransfer::where('ex_transfer_id', $transfer['transferId'])->update($update);
            }
        }
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Transfer request was successfully updated');
    }
    public function removeInterTransferRequestTea($id)
    {
        Transfers::where('transfer_id', $id)->delete();
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Transfer request canceled successfully');
    }
    public function cancelInterTransferRequest($id)
    {
        Transfers::where('delivery_number', base64_decode($id))->delete();
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Transfer request canceled successfully');
    }
    public function cancelExternalTransferRequest($id)
    {
        ExternalTransfer::where('delivery_number', base64_decode($id))->delete();
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Transfer request canceled successfully');
    }
    public function removeExTransferRequestTea($id)
    {
        ExternalTransfer::where('ex_transfer_id', $id)->delete();
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Remove tea from transfer request');
    }
    public function viewShippingInstructions()
    {
       $data = ShippingInstruction::join('clients', 'clients.client_id', '=', 'shipping_instructions.client_id')
           ->join('destinations', 'destinations.destination_id', '=', 'shipping_instructions.destination_id')
           ->join('stations', 'stations.station_id', '=', 'shipping_instructions.station_id')
           ->select('shipping_id', 'client_name', 'shipping_instructions.created_at', 'shipping_instructions.status', 'station_name', 'shipping_number', 'vessel_name', 'port_name', 'load_type', 'location_id', 'shipping_instructions.si_number')
           ->latest('shipping_instructions.created_at');

        $shipping = $data->get();
        $clients = $data->get()->groupBy('client_name');
        $stations = $data->get()->groupBy('station_name');
        $agents = ClearingAgent::all();
        $transporters = Transporter::all();
        $registrations = ShippingInstruction::pluck('registration')->toArray();
        $drivers = Driver::all();
        return view('admin::shipping.SIs')->with(['users' => $drivers, 'registrations' =>$registrations, 'shipping' => $shipping, 'clients' => $clients, 'stations' => $stations, 'agents' => $agents, 'transporters' => $transporters]);
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
        return view('admin::shipping.createSI')->with(['ports' => $ports, 'stations' => $stations, 'clients' => $clients]);
    }
    public function addShippingInstruction(Request $request)
    {
        $request->validate([
            'client' => 'string|required',
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
        return redirect()->route('admin.addShipmentTeas', $siId)->with('success', 'Success! Shipping instruction created successfully');
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
            ->orderBy('garden_name', 'asc')
            ->get();
        return view('admin::shipping.addTeasToSI')->with(['teas' => $teas, 'clientTeas' => $clientTeas, 'si' => $si]);

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
        return redirect()->back()->with('success', 'Success! Shipping instructions updated successfully');
    }
    public function updateShippingInstruction($id)
    {
        ShippingInstruction::where('shipping_id', $id)->update(['status' => 2]);
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Shipping instructions updated successfully');
    }
    public function deleteShippingInstruction($id)
    {
        Shipment::where('shipping_id', $id)->delete();
        ShippingInstruction::find($id)->delete();
        $this->logger->create();
        return redirect()->back()->with('success', 'Shipping instruction deleted successfully');
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

        } catch (\Exception $e) {
            // Rollback the transaction if an exception occurs
            DB::rollback();
            // Handle or log the exception
            return redirect()->back()->with('error', 'Oops! An error occurred please try again');
        }
        return redirect()->back()->with('success', 'Successful!, Shipping Instructions updated successfully');
    }
    public function deleteShippingInstructionTea($id)
    {
        Shipment::find($id)->delete();
        $this->logger->create();
        return redirect()->back()->with('success', 'Tea deleted from shipping instruction successfully');

    }
    public function downloadSIDocument($id)
    {
        return $this->AppClass->downloadStraightLine($id);
    }
    public function downloadDriverClearance($id)
    {
        return $this->AppClass->downloadStraightLineClearance($id);
    }
    public function viewBlendProcessing()
    {
        $data = DB::table('blend_sheets')
            ->join('clients', 'clients.client_id', '=', 'blend_sheets.client_id')
            ->join('destinations', 'destinations.destination_id', '=', 'blend_sheets.destination_id')
            ->join('stations', 'stations.station_id', '=', 'blend_sheets.station_id')
            ->select('blend_sheets.blend_id', 'blend_sheets.created_at', 'station_name', 'client_name', 'clients.client_id', 'blend_number', 'vessel_name', 'port_name', 'blend_sheets.status', 'output_packages', 'output_weight', 'location_id', 'stations.station_id', 'package_type', 'si_number')
            ->whereNull('blend_sheets.deleted_at')
            ->latest('blend_sheets.created_at');

        $sheets = $data->whereNull('blend_sheets.deleted_at')->get();
        $clients = $data->get()->groupBy('client_name');
        $stations = $data->get()->groupBy('station_name');
        return view('admin::shipping.blendSheets')->with(['sheets' => $sheets, 'clients' => $clients, 'stations' => $stations]);
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
        return view('admin::shipping.createBlend')->with(['ports' => $ports, 'stations' => $stations, 'clients' => $clients]);
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
        return redirect()->route('admin.addBlendTeas', $sheet['blend_id'])->with('success', 'Success! Blend sheet created successfully');
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
                'drivers.driver_name',
                'drivers.phone',
                'clearing_agents.agent_name',
                'blend_sheets.station_id',
                'blend_sheets.client_id'
            )
            ->selectRaw('SUM(blend_shipments.blended_packages) as outputPackages')
            ->selectRaw('SUM(blend_shipments.net_weight) as outputWeight')
            ->where('blend_sheets.blend_id', $id) // Use where instead of find
            ->groupBy(
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
                'blend_sheets.station_id',
                'blend_sheets.client_id'
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
        return view('admin::shipping.addTeasToBlend')->with(['teas' => $teas, 'clientTeas' => $clientTeas, 'bs' => $bs, 'blendBalances' => $blendBalances]);
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
        } catch (\Exception $e) {
            // Rollback the transaction if an exception occurs
            DB::rollback();
            // Handle or log the exception
            return redirect()->back()->with('error', 'Oops! An error occurred please try again');
        }
    }
    public function deleteBlendTea($id)
    {
        $bt = BlendTea::find($id);
        BlendBalance::where('blend_balance_id', $bt->stock_id)->update(['status' => 0]);
        $bt->delete();
        $this->logger->create();
        return redirect()->back()->with('success', 'Successful! Teas removed from blend sheet successfully');
    }
    public function updateOutTurnReport ($id)
    {
        // return $id;
       $bs = BlendSheet::withTrashed()->leftJoin('blend_teas', 'blend_teas.blend_id', '=', 'blend_sheets.blend_id')
            ->join('clients', 'clients.client_id', 'blend_sheets.client_id')
           ->select('blend_sheets.blend_id', 'client_name', 'blend_number', 'b_dust', 'c_dust', 'sweepings', 'fibre', 'packet_tare', 'blend_sheets.agent_id', 'container_tare', 'seal_number', 'escort', 'blend_date', 'transporter_id', 'registration', 'driver_id') // Specify necessary columns
            ->selectRaw('SUM(blend_teas.blended_packages) as input_packages')
            ->selectRaw('SUM(blend_teas.blended_weight) as input_weight')
            ->where('blend_sheets.blend_id', $id) // Assuming $id is for blend_sheets.blend_id
            ->whereNull('blend_teas.deleted_at')
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
        $containerNumbers = ShipmentContainer::where('blend_id', $id)->pluck('container_number')->toArray();

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

        return view('admin::shipping.updateOutTurnReport')->with(['bs' => $bs, 'agents' => $agents, 'transporters' => $transporters, 'registrations' => $registrations, 'users' => $users, 'outTurnReport' => $outTurnReport, 'containerNumbers' => $containerNumbers]);
    }
    public function updateBlendSheet($id)
    {
        BlendSheet::where('blend_id', $id)->update(['status' => 2]);
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Blend sheet updated successfully');
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
            return redirect()->route('admin.viewBlendProcessing')->with('success', 'Success! Blend sheet updated successfully');
        } catch (Exception $exception) {
            DB::rollback();
            return redirect()->back()->with('error', 'Oops! An error occurred please try again '.$exception->getMessage());
        }
    }
    public function markBlendTeaAsShipped($id)
    {
        BlendSheet::where('blend_id', $id)->update(['blend_shipped' => time(), 'status' => 4]);
        BlendTea::where('blend_id', $id)->update(['status' => 1]);
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Blend sheet details updated successfully');
    }
    public function deleteBlendSheet($id)
    {
        DB::beginTransaction();
        try {
            BlendBalance::where('blend_id', $id)->delete();
            BlendTea::where('blend_id', $id)->delete();
            BlendShipment::where('blend_id', $id)->delete();
            ShipmentContainer::where('blend_id', $id)->delete();
            BlendSupervision::where('blend_id', $id)->delete();
            BlendMaterial::where('blend_id', $id)->delete();
            BlendSheet::where('blend_id', $id)->delete();
            $this->logger->create();
            // Commit the transaction
            DB::commit();
            return redirect()->back()->with('success', 'Success! Blend sheet updated successfully');

        } catch (\Exception $e) {
                // Rollback the transaction if an exception occurs
            DB::rollback();
            // Handle or log the exception
            return redirect()->back()->with('error', 'Oops! An error occurred please try again');
        }
    }
    public function viewBlendBalances()
    {
        $balances = DB::table('blendBalances')->where('current_weight', '>', 0)->whereNull('deleted_at')->orderBy('blend_number', 'asc')->get();
        return view('admin::stock.blendBalances')->with(['balances' => $balances]);
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
    public function viewDirectDeliveries(Request $request)
    {
        $from = $request->get('from') ?? Carbon::now()->startOfMonth();
        $to = $request->get('to') ?? Carbon::now();
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
            ->groupBy('delivery_number', 'client_name',
//                'tea_id',
//                'warehouse_name',
//                'station_name',
//                'order_status',
//                'delivery_orders.created_at'
            )
            ->orderBy('delivery_orders.created_at', 'desc')
            ->whereBetween('delivery_orders.created_at', [$from, $to])
            ->get();
        $stations = Station::where('status', 1)->get();
        $clients = Client::all();
        $transporters = Transporter::all();
        $users = Driver::all();
        return view('admin::DOS.directDelivery')->with(['orders' => $orders, 'stations' => $stations, 'clients' => $clients, 'from' => $from, 'to' => $to, 'transporters' => $transporters, 'users' => $users]);
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
            'driver_id' => $driver->driver_id,
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

    public function downloadTemplate()
    {
        $file = 'imports/bulky_tea_import.xlsx';
        return response()->download($file);
    }
//    public function filterWarehouseBay(Request $request)
//    {
//        $data = WarehouseBay::where('station_id', $request->selectedStation)->orderBy('bay_name', 'asc')->get();
//        return response()->json($data);
//    }
    public function viewDirectDeliveryOrder($id)
    {
        $delId = base64_decode($id);
        $orders = DeliveryOrder::join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->join('stock_ins', 'stock_ins.delivery_id', '=', 'delivery_orders.delivery_id')
            ->join('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
            ->join('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
            ->leftJoin('stations', 'stations.station_id', '=', 'stock_ins.station_id')
            ->leftJoin('warehouses', 'warehouses.warehouse_id', '=', 'delivery_orders.warehouse_id')
            ->select('client_name', 'invoice_number', 'tea_id', 'garden_name', 'grade_name',  'delivery_orders.delivery_id', 'order_number', 'client_name', 'tea_id', 'warehouse_name', 'station_name', 'delivery_orders.status as status', 'garden_name', 'grade_name', 'packet', 'weight')
            ->where(['delivery_orders.delivery_type' => 2, 'order_number' => $delId])
            ->latest('delivery_orders.created_at')
            ->get();
        return view('admin::DOS.viewDirectDelivery')->with(['orders' => $orders, 'delivery' => $delId]);
    }
    public function addDirectDelivery()
    {
        $clients = Client::all();
        $gardens = Garden::all();
        $grades = Grade::all();
        $warehouses = Warehouse::all();
        $locationId = Station::where('station_id', auth()->user()->station_id)->first()->location_id;
        $stations = Station::where('location_id', $locationId)->get();
        return view('admin::DOS.addDirectDelivery')->with(['clients' => $clients, 'gardens' => $gardens, 'grades' => $grades, 'warehouses' => $warehouses, 'stations' => $stations]);

    }
    public function registerDirectDeliveryOrder(Request $request)
    {
        $customId = new CustomIds();
        $deliveryId = $customId->generateId();
        $stockId = $customId->generateId();
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
            'warehouse_id' => $request->warehouse_id
        ];
        DeliveryOrder::create($do);
        $stock = [
            'stock_id' => $stockId,
            'delivery_id' => $deliveryId,
            'station_id' => auth()->user()->station_id,
            'date_received' => time(),
            'delivery_number' => $request->order_number,
            'warehouse_bay' => $request->bay,
            'total_weight' => $request->weight,
            'total_pallets' => $request->packet,
            'pallet_weight' => $request->pallet_weight,
            'package_tare' => $request->tare,
            'net_weight' => $request->netWeight,
            'user_id' => auth()->user()->user_id
        ];
        StockIn::create($stock);
        $this->logger->create();
        return redirect()->back()->with('success', 'Tea added successfully');
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
//    public function importStock(Request $request)
//    {
//        $deliveryNumber = $request->delivery_number;
//        $import = new ImportBulkyTeas($deliveryNumber);
//        // Perform the import
//        Excel::import($import, $request->file('uploadFile'));
//        // Get specific errors
//        $errors = $import->getErrors();
//        if (!empty($errors)) {
//            return redirect()->back()->with('importErrors', $errors);
//        } else {
//            // If no errors, continue with your desired action
//            return redirect()->back()->with('success', 'Successful! Tea have been imported to the system successfully');
//        }
//    }
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
            ->where(['delivery_orders.delivery_type' => 2, 'stock_ins.delivery_number' => $doNumber])
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

    /*public function downloadDirectDeliveries($id)
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
            ->select('delivery_orders.delivery_type', 'package', 'sub_warehouse_name', 'order_number', 'clients.client_name', 'delivery_orders.tea_id', 'warehouses.warehouse_name', 'stock_ins.total_pallets', 'stock_ins.net_weight', 'delivery_orders.status as order_status', 'gardens.garden_name', 'grades.grade_name', 'delivery_orders.invoice_number', 'stations.station_name', 'warehouse_bays.bay_name', 'total_weight', 'date_received', 'delivery_orders.created_at', 'delivery_orders.status', 'first_name', 'surname', 'delivery_orders.created_by')
            ->where(['delivery_orders.delivery_type' => 2, 'delivery_orders.order_number' => $doNumber])
            ->get()
            ->map(function ($item) {
                // Sanitize all fields in each row
                return collect($item)->map(function ($value) {
                    return is_string($value) ? htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8') : $value;
                });
            });


        if ($type == 2){
            return Excel::download(new ExportDirectDeliveryOrders($orders), 'DIRECT DELIVERY TALLY '.time().'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        }

        $detail = $orders[0];
        $staffName = auth()->user()->user->surname.' '.auth()->user()->user->first_name;
        $date = Carbon::now()->format('D, d-m-Y H:i:s');
        $user = UserInfo::where('user_id', $detail['created_by'])->first();
        $by = $user->surname.' '.$user->first_name;

        $domPdfPath = base_path('vendor/dompdf/dompdf');
        \PhpOffice\PhpWord\Settings::setPdfRendererPath($domPdfPath);
        \PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF');

        $table = new Table(['unit' => \PhpOffice\PhpWord\SimpleType\TblWidth::PERCENT, 'width' => 100 * 50, 'align' => JC::CENTER]);

        $header = ['size' => 8, 'name' => 'Cambria', 'space' => ['before' => 100, 'after' => 100], 'bold' => true];
        $text = ['size' => 8, 'name' => 'Cambria', 'space' => ['before' => 100, 'after' => 100], 'bold' => false];

        $table->addRow();
        $table->addCell(600, ['borderSize' => 1])->addText('#', $header, ['space' => ['before' => 100, 'after' => 100]]);
        $table->addCell(1500, ['borderSize' => 1])->addText('Garden', $header, ['space' => ['before' => 100, 'after' => 100]]);
        $table->addCell(1200, ['borderSize' => 1])->addText('Grade', $header, ['space' => ['before' => 100, 'after' => 100]]);
        $table->addCell(1500, ['borderSize' => 1])->addText('INV Number', $header, ['space' => ['before' => 100, 'after' => 100]]);
        $table->addCell(1200, ['borderSize' => 1])->addText('Gross Weight', $header, ['space' => ['before' => 100, 'after' => 100]]);
        $table->addCell(1000, ['borderSize' => 1])->addText('Packages', $header, ['space' => ['before' => 100, 'after' => 100]]);
        $table->addCell(1200, ['borderSize' => 1])->addText('Net Weight', $header, ['space' => ['before' => 100, 'after' => 100]]);
        $table->addCell(1300, ['borderSize' => 1])->addText('Date Rec\'d', $header, ['space' => ['before' => 100, 'after' => 100]]);
        $table->addCell(2000, ['borderSize' => 1])->addText('Producer Whs', $header, ['space' => ['before' => 100, 'after' => 100]]);
        $sn = 0;
        $totalGrossWeight = $totalNetWeight = $totalPackages = 0;
        foreach ($orders as $order){
            $table->addRow();
            $table->addCell(600, ['borderSize' => 1])->addText(++$sn, $text, ['space' => ['before' => 50, 'after' => 50]]);
            $table->addCell(1500, ['borderSize' => 1])->addText($order['garden_name'], $text, ['space' => ['before' => 50, 'after' => 50]]);
            $table->addCell(1200, ['borderSize' => 1])->addText($order['grade_name'], $text, ['space' => ['before' => 50, 'after' => 50]]);
            $table->addCell(1500, ['borderSize' => 1])->addText($order['invoice_number'], $text, ['space' => ['before' => 50, 'after' => 50]]);
            $table->addCell(1200, ['borderSize' => 1])->addText($order['total_weight'], $text, ['space' => ['before' => 50, 'after' => 50]]);
            $table->addCell(1000, ['borderSize' => 1])->addText($order['total_pallets'], $text, ['space' => ['before' => 50, 'after' => 50]]);
            $table->addCell(1200, ['borderSize' => 1])->addText($order['net_weight'], $text, ['space' => ['before' => 50, 'after' => 50]]);
            $table->addCell(1300, ['borderSize' => 1])->addText(Carbon::createFromTimestamp($order['date_received'])->format('d-m-Y h:i'), $text, ['space' => ['before' => 50, 'after' => 50]]);
            $table->addCell(2000, ['borderSize' => 1])->addText($order['warehouse_name'], $text, ['space' => ['before' => 50, 'after' => 50]]);

            $totalGrossWeight += $order['total_weight'];
            $totalNetWeight += $order['net_weight'];
            $totalPackages += $order['total_pallets'];
        }

        $table->addRow();
        $table->addCell(5100, ['gridSpan' => 4])->addText();
        $table->addCell(1500,['borderSize' => 1])->addText(number_format($totalGrossWeight, 2), $header, ['space' => ['before' => 50, 'after' => 50]] );
        $table->addCell(1500,['borderSize' => 1])->addText(number_format($totalPackages, 2), $header, ['space' => ['before' => 50, 'after' => 50]] );
        $table->addCell(1500,['borderSize' => 1])->addText(number_format($totalNetWeight, 2), $header, ['space' => ['before' => 50, 'after' => 50]] );
        $table->addCell(4000, ['gridSpan' => 2])->addText();

        $stock = new TemplateProcessor(storage_path('direct_delivery_report.docx'));
        $stock->setComplexBlock('table', $table);
        $stock->setValue('client', $detail['client_name']);
        $stock->setValue('station', $detail['station_name']);
        $stock->setValue('bay', $detail['bay_name']);
        $stock->setValue('orderNumber', $detail['order_number']);
        $stock->setValue('by', $staffName);
        $stock->setValue('prepared', $by);
        $stock->setValue('date', $date);
        $docPath = 'Files/TempFiles/DELIVERIES TALLY '.time().'.docx';
        $stock->saveAs($docPath);

//         return response()->download($docPath)->deleteFileAfterSend(true);

        $phpWord = IOFactory::load($docPath);
        $contents = \PhpOffice\PhpWord\IOFactory::load($docPath);
        $pdfPath = 'Files/TempFiles/DELIVERIES TALLY '.time(). ".pdf";
        $converter =  new OfficeConverter($docPath, 'Files/TempFiles/');
        $converter->convertTo('DELIVERIES TALLY '.time().".pdf");
        unlink($docPath);
        return response()->download($pdfPath)->deleteFileAfterSend(true);

    }*/
    public function removeDirectDeliveryTea($id)
    {
        DeliveryOrder::where(['delivery_id' => $id])->delete();
        StockIn::where(['delivery_id' => $id])->delete();
        $this->logger->create();
        return redirect()->back()->with('success', 'Selected tea removed from the delivery');
    }
    public function removeDirectDeliveryTeas($id)
    {
        $teasToDelete = DeliveryOrder::where(['order_number' => base64_decode($id), 'status' => null])->pluck('delivery_id');
        DeliveryOrder::whereIn('delivery_id', $teasToDelete)->delete();
        StockIn::whereIn('delivery_id', $teasToDelete)->delete();
        $this->logger->create();
        return redirect()->back()->with('success', 'Selected teas removed from the delivery');

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
    public function exportBlendsReport(Request $request)
    {
       $sheets = DB::table('blend_sheets')
            ->leftJoin('blend_shipments', 'blend_shipments.blend_id', '=', 'blend_sheets.blend_id')
            ->join('clients', 'clients.client_id', '=', 'blend_sheets.client_id')
            ->join('destinations', 'destinations.destination_id', '=', 'blend_sheets.destination_id')
            ->join('stations', 'stations.station_id', '=', 'blend_sheets.station_id')
            ->leftJoin('clearing_agents', 'clearing_agents.agent_id', '=', 'blend_sheets.agent_id')
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'blend_sheets.transporter_id')
            ->leftJoin('user_infos', 'user_infos.user_id', '=', 'blend_sheets.user_id')
            ->select('client_name', 'blend_sheets.created_at', 'station_name', 'blend_number', 'vessel_name', 'port_name', 'consignee', 'contract', 'blend_sheets.status', 'container_size', 'package_type', 'transporter_name', 'agent_name', 'output_packages', 'output_weight', 'stations.station_name', 'surname', 'first_name', 'blend_sheets.deleted_at')
            ->selectRaw('SUM(blend_shipments.blended_packages) as shipped_packages, SUM(blend_shipments.gross_weight) as shipped_weight')
            ->groupBy('client_name', 'blend_sheets.created_at', 'stations.station_id', 'station_name', 'blend_number', 'vessel_name', 'port_name', 'consignee', 'contract', 'blend_sheets.status', 'container_size', 'package_type', 'transporter_name', 'agent_name', 'output_packages', 'output_weight', 'stations.station_name', 'surname', 'first_name', 'deleted_at')
            ->latest('blend_sheets.created_at')
            ->orderBy('clients.client_name', 'asc');
        $client = $request->client;
        $station = $request->station;
        $from = $request->from;
        $to = $request->to;
        $status = $request->report;
        if (!is_null($from)) {
            $fromTimestamp = strtotime($from);
            $sheets->where('blend_sheets.created_at', '>=', $from);
        }
        if (!is_null($to)) {
            $toTimestamp = strtotime($to);
            $sheets->where('blend_sheets.created_at', '<=', $to);
        }
        if (!is_null($station)) {
            $sheets->where('station_id', $station);
        }
        if (!is_null($client)) {
            $sheets->where('blend_sheets.client_id', $client);
        }
        if (!is_null($status)) {
            if ($status == 1) {
                $sheets->where('blend_sheets.status', '<', 4) ;
            }else{
                $sheets->where('blend_sheets.status', 4);
            }
        }
       $blends = $sheets->get()->groupBy('blend_number');
        return Excel::download(new ExportBlendReport($blends), 'BLEND STATUS'.' '.time().'.xlsx', \Maatwebsite\Excel\Excel::XLSX);

    }
    public function exportSTLReport(Request $request)
    {
        $shippings = ShippingInstruction::join('clients', 'clients.client_id', '=', 'shipping_instructions.client_id')
            ->join('stations', 'stations.station_id', '=', 'shipping_instructions.station_id')
            ->leftjoin('user_infos', 'user_infos.user_id', '=', 'shipping_instructions.user_id')
            ->leftJoin('shipments', 'shipments.shipping_id', '=', 'shipping_instructions.shipping_id')
            ->join('destinations', 'destinations.destination_id', '=', 'shipping_instructions.destination_id')
            ->leftJoin('clearing_agents', 'clearing_agents.agent_id', '=', 'shipping_instructions.clearing_agent')
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'shipping_instructions.transporter_id')
            ->leftJoin('drivers', 'drivers.driver_id', '=', 'shipping_instructions.driver_id')
            ->select('shipping_instructions.created_at', 'shipping_instructions.clearing_agent', 'client_name', 'shipping_number', 'port_name', 'load_type', 'container_size','consignee', 'shipping_instructions.status', 'seal_number', 'agent_name', 'transporter_name', 'surname', 'first_name', 'station_name', 'shipping_instructions.deleted_at')
            ->selectRaw('SUM(shipments.shipped_packages) as stl_packages, SUM(shipments.shipped_weight) as stl_weight')
            ->groupBy('shipping_instructions.created_at', 'shipping_instructions.clearing_agent', 'client_name', 'shipping_number', 'port_name', 'load_type', 'container_size','consignee', 'shipping_instructions.status', 'seal_number', 'agent_name', 'transporter_name', 'surname', 'first_name', 'station_name', 'deleted_at')
            ->latest('shipping_instructions.created_at');
        $client = $request->client;
        $station = $request->station;
        $from = $request->from;
        $to = $request->to;
        $status = $request->report;
        if (!is_null($from)) {
            $fromTimestamp = strtotime($from);
            $shippings->where('shipping_instructions.created_at', '>=', $from);
        }
        if (!is_null($to)) {
            $toTimestamp = strtotime($to);
            $shippings->where('shipping_instructions.created_at', '<=', $to);
        }
        if (!is_null($station)) {
            $shippings->where('shipping_instructions.station_id', $station);
        }
        if (!is_null($client)) {
            $shippings->where('shipping_instructions.client_id', $client);
        }
        if (!is_null($status)) {
            if ($status == 1) {
                $shippings->where('shipping_instructions.status', '<', 4) ;
            }else{
                $shippings->where('shipping_instructions.status', 4);
            }
        }
       $shipments = $shippings->get();
        return Excel::download(new ExportSTLReport($shipments), 'STL STATUS'.' '.time().'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }
    public function downloadOutturReport($id)
    {
        return $this->AppClass->downloadBlendOutTurn($id);
    }
    public function exportTransportReport(Request $request)
    {
        $startOfMonth = $request->from ? strtotime($request->from) : now()->startOfMonth()->timestamp;
        $endOfMonth = $request->to ? strtotime($request->to) : now()->endOfMonth()->timestamp;
        $report = $request->report == 1 ? 'COLLECTION' :($request->report == 2 ? 'TRANSFER' : null);
        $transporter = $request->transporter;
        $orders = $this->AppClass->transportSummary($startOfMonth, $endOfMonth, $report, $transporter);
        return Excel::download(new ExportTeaTransport($orders), 'TRANSPORTERS'.' '.time().'.xlsx', \Maatwebsite\Excel\Excel::XLSX);

    }
    public function exportInterTransferReport(Request $request)
    {
        $client = $request->input('name');
        $from = $request->input('monthAgo');
        $to = $request->input('todayDate');
        $query = Transfers::join('stations', 'stations.station_id', '=', 'transfers.station_id')
            ->join('stations as destination_station', 'destination_station.station_id', '=', 'transfers.destination')
            ->join('delivery_orders', 'delivery_orders.delivery_id', '=', 'transfers.delivery_id')
            ->join('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
            ->join('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
            ->join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->join('stock_ins', function($join) {
                $join->on('stock_ins.delivery_id', '=', 'transfers.delivery_id')
                    ->on('stock_ins.stock_id', '=', 'transfers.stock_id');
            })
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'transfers.transporter_id')
            ->leftJoin('drivers', 'drivers.driver_id', '=', 'transfers.driver_id')
            ->select('stations.station_name', 'clients.client_name', 'gardens.garden_name', 'grades.grade_name', 'requested_palettes', 'requested_weight', 'destination', 'destination_station.station_name as destination_name', 'transfers.status', 'stock_ins.total_pallets', 'stock_ins.net_weight', 'transporters.transporter_id', 'transporters.transporter_name', 'drivers.id_number', 'drivers.driver_name', 'drivers.phone as driver_phone', 'transfers.registration', 'delivery_orders.invoice_number', 'transfers.delivery_number', 'stock_ins.date_received', 'transfers.updated_at as received', 'transfers.created_at as created')
            ->latest('transfers.created_at');
        if (!is_null($client)) {
            $query->where('delivery_orders.client_id', $client);
        }
        if (!is_null($from)) {
            $query->where('stock_ins.date_received', '>=', strtotime($from));
        }
        if (!is_null($to)) {
            $query->where('stock_ins.date_received', '<=', strtotime($to));
        }
        $results = $query->get();
        return Excel::download(new ExportInternalTransfers($results), 'INTERNAL TRANSFERS'.' '.time().'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }
    public function exportExterTransferReport(Request $request)
    {
        $client = $request->input('name');
        $from = $request->input('monthAgo');
        $to = $request->input('todayDate');

        $query = ExternalTransfer::join('currentstock', 'currentstock.stock_id', '=', 'external_transfers.stock_id')
            ->leftJoin('drivers', 'drivers.driver_id', '=', 'external_transfers.driver_id')
            ->leftJoin('warehouses', 'warehouses.warehouse_id', '=', 'external_transfers.warehouse_id')
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'external_transfers.transporter_id')
            ->select('currentstock.client_name', 'currentstock.garden_name', 'currentstock.grade_name', 'currentstock.stocked_at', 'external_transfers.registration', 'external_transfers.status as extStatus', 'external_transfers.transferred_palettes', 'external_transfers.transferred_weight', 'transporters.transporter_name', 'transporters.transporter_id', 'drivers.driver_name', 'drivers.id_number', 'drivers.phone', 'warehouses.warehouse_name', 'external_transfers.created_at as sortOrder', 'currentstock.invoice_number', 'external_transfers.delivery_number', 'currentstock.lot_number', 'currentstock.sale_number', 'currentstock.created_at as received')
            ->orderBy('extStatus', 'asc')
            ->orderBy('sortOrder', 'desc');

        if (!is_null($client)) {
            $query->where('currentstock.client_id', $client);
        }

        if (!is_null($from)) {
            $query->where('currentstock.date_received', '>=', strtotime($from));
        }

        if (!is_null($to)) {
            $query->where('currentstock.date_received', '<=', strtotime($to));
        }

        $results = $query->get();

        return Excel::download(new ExportExternalTransfer($results), 'EXTERNAL TRANSFERS'.' '.time().'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }
    public function deleteInStock($id){
        StockIn::where('stock_id', $id)->delete();
        $this->logger->create();
        return back()->with('success', 'Tea removed from stock successfully');
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

        $balances = $blendBalances->get()->map(function ($item) {
            // Sanitize all fields in each row
            return collect($item)->map(function ($value) {
                return is_string($value) ? htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8') : $value;
            });
        });

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
            $table->addCell(1800, ['borderSize' => 1])->addText($stock['blend_number'], $text, ['space' => ['before' => 50]]);
            $table->addCell(2000, ['borderSize' => 1])->addText($stock['client_name'], $text, ['space' => ['before' => 50]]);
            $table->addCell(1800, ['borderSize' => 1])->addText($stock['garden'], $text, ['space' => ['before' => 50]]);
            $table->addCell(1800, ['borderSize' => 1])->addText($stock['grade'], $text, ['space' => ['before' => 50]]);
            $table->addCell(1200, ['borderSize' => 1])->addText(number_format($stock['current_packages'], 2), $text, ['space' => ['before' => 50]]);
            $table->addCell(1200, ['borderSize' => 1])->addText(number_format($stock['current_weight'], 2), $text, ['space' => ['before' => 50]]);
            $table->addCell(2000, ['borderSize' => 1])->addText($stock['blend_date'], $text, ['space' => ['before' => 50]]);
            $table->addCell(1800, ['borderSize' => 1])->addText($stock['station_name'], $text, ['space' => ['before' => 50]]);
            $totalPackets += $stock['current_packages'];
            $totalWeight += $stock['current_weight'];
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
        //  //return response()->download($docPath)->deleteFileAfterSend();
        $phpWord = IOFactory::load($docPath);
        $contents = \PhpOffice\PhpWord\IOFactory::load($docPath);
        $pdfPath = 'Files/TempFiles/BLEND BALANCES'.time(). ".pdf";
        $converter =  new OfficeConverter($docPath, 'Files/TempFiles/');
        $converter->convertTo('BLEND BALANCES'.time().".pdf");
        unlink($docPath);
        return response()->download($pdfPath)->deleteFileAfterSend(true);
    }
    public function collectionStatus($id)
    {
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
            ->leftJoin('drivers as dr', 'dr.driver_id', '=', 'li.driver_id')
            ->leftJoin('transporters as tr', 'tr.transporter_id', '=', 'li.transporter_id')
            ->leftJoin('stations as st', 'st.station_id', '=', 'li.station_id')
            ->leftJoin('users as lu', 'lu.user_id', '=', 'li.created_by')
            ->select('u.username', 'g.garden_name', 'gr.grade_name', 'br.broker_name', 'wh.warehouse_name', 'wh.warehouse_id', 'cl.client_name', 'delivery_orders.*', 'tr.transporter_id', 'tr.transporter_name', 'dr.driver_id', 'dr.driver_name', 'dr.id_number', 'dr.phone', 'li.loading_id', 'li.loading_number', 'li.status as load_status', 'li.registration', 'li.created_by as load_user_id', 'lu.username as load_user', 'st.station_name', 'st.station_id', 'sub.sub_warehouse_name', 'li.deleted_at', 'delivery_orders.created_at as date_received')
            ->whereNull('delivery_orders.deleted_at')
            ->orderBy('delivery_orders.created_at', 'desc');

        // Clone the query builder instance for each variable
        $uncollected = clone $orders;
        $late = clone $orders;
        $noTCI = clone $orders;
        $overstayed = clone $orders;
        $uncollected = $uncollected->where('li.status', 1)->get();
        $threshold = Carbon::now();
        $late = $late->whereRaw("DATE_ADD(li.created_at, INTERVAL 2 DAY) <= '$threshold'")->where('li.status', 1)->get();
        $noTCI = $noTCI->whereNull('li.loading_number')->get();
        $now = \Carbon\Carbon::now();
        $overstayed = $overstayed->whereRaw("DATE_ADD(delivery_orders.prompt_date, INTERVAL 7 DAY) <= '$now'")->where('li.status', 1)->get();
        $data = $id == 1 ? $uncollected : ($id == 2 ? $late : ($id == 3 ? $noTCI : $overstayed ));
        $file = $id == 1 ? 'UNDER-COLLECTION' : ($id == 2 ? 'LATE-COLLECTION' : ($id == 3 ? 'NO-TCI' : 'OVERSTAYED' ));
        return Excel::download(new ExportDeliveryOrders($data), $file.time().'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }
    public function transferReport($id)
    {
        $internal = Transfers::join('stations', 'stations.station_id', '=', 'transfers.station_id')
            ->join('stations as destination_station', 'destination_station.station_id', '=', 'transfers.destination')
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'transfers.transporter_id')
            ->leftJoin('drivers', 'drivers.driver_id', '=', 'transfers.driver_id')
            ->join('delivery_orders', 'delivery_orders.delivery_id', '=', 'transfers.delivery_id')
            ->join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->join('currentstock', function($join) {
                $join->on('currentstock.delivery_id', '=', 'transfers.delivery_id')
                    ->on('currentstock.stock_id', '=', 'transfers.stock_id');
            })
            ->select('transfers.created_at', 'stations.station_name', 'clients.client_name', 'transfers.requested_palettes', 'transfers.requested_weight', 'destination', 'destination_station.station_name as destination_name', 'transfers.status', 'currentstock.current_stock', 'currentstock.current_weight', 'transfers.delivery_number', 'currentstock.lot_number', 'currentstock.sale_number', 'currentstock.invoice_number', 'garden_name', 'grade_name', 'transporters.transporter_name', 'transfers.registration', 'drivers.id_number', 'drivers.driver_name')
            ->where('transfers.status', '<', 3)
            ->orWhere('transfers.status', null)
            ->whereNull('transfers.deleted_at')
            ->latest('transfers.created_at')
            ->get();
//            ->groupBy('delivery_number');

        $external = ExternalTransfer::join('currentstock', 'currentstock.stock_id', '=', 'external_transfers.stock_id')
            ->leftJoin('warehouses', 'warehouses.warehouse_id', '=', 'external_transfers.warehouse_id')
            ->select('external_transfers.created_at', 'currentstock.client_name', 'external_transfers.status as extStatus', 'external_transfers.transferred_palettes', 'external_transfers.transferred_weight', 'warehouses.warehouse_name', 'external_transfers.delivery_number', 'currentstock.stocked_at', 'garden_name', 'grade_name', 'invoice_number', 'external_transfers.registration', 'driver_name', 'transporter_name', 'id_number')
            ->latest('external_transfers.created_at')
            ->where('external_transfers.status', '<', 3)
            ->orWhere('external_transfers.status', null)
            ->whereNull('external_transfers.deleted_at')
            ->orderBy('delivery_number', 'desc')
            ->get();
//            ->groupBy('delivery_number');

        $si = ShippingInstruction::join('clients', 'clients.client_id', '=', 'shipping_instructions.client_id')
            ->join('destinations', 'destinations.destination_id', '=', 'shipping_instructions.destination_id')
            ->leftJoin('clearing_agents', 'clearing_agents.agent_id', '=', 'shipping_instructions.clearing_agent')
            ->leftJoin('transporters', 'transporters.transporter_id', '=', 'shipping_instructions.transporter_id')
            ->select('shipping_instructions.created_at', 'clients.client_name', 'shipping_instructions.clearing_agent', 'shipping_number', 'vessel_name', 'port_name', 'load_type', 'container_size', 'shipping_mark', 'consignee', 'shipping_instructions.status', 'shipping_instructions', 'escort', 'seal_number', 'agent_name', 'ship_date', 'container_number', 'container_tare')
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
            ->select('blend_sheets.created_at', 'blend_sheets.blend_id', 'blend_sheets.client_id', 'client_name', 'clients.phone as cPhone', 'email', 'blend_number', 'vessel_name', 'blend_sheets.destination_id', 'port_name', 'shipping_mark', 'consignee', 'contract', 'grade', 'garden', 'blend_date', 'blend_sheets.status', 'container_size', 'clients.address', 'package_type', 'registration', 'transporter_name', 'transporters.transporter_id', 'driver_name', 'drivers.phone as driver_phone', 'container_tare', 'blend_shipped', 'agent_name', 'seal_number', 'escort', 'output_packages', 'output_weight', 'blend_sheets.packet_tare', 'blend_sheets.agent_id', 'id_number', 'stations.station_id', 'stations.station_name', 'standard_details')
            ->selectRaw('SUM(blend_teas.blended_packages) as input_packages')
            ->selectRaw('SUM(blend_teas.blended_weight) as input_weight')
            ->groupBy('created_at', 'blend_sheets.blend_id', 'blend_sheets.client_id', 'client_name', 'clients.phone', 'email', 'blend_number', 'vessel_name', 'blend_sheets.destination_id', 'port_name', 'shipping_mark', 'consignee', 'contract', 'grade', 'garden', 'blend_date', 'blend_sheets.status', 'container_size', 'clients.address', 'package_type', 'registration', 'transporter_name', 'driver_name', 'driver_phone', 'container_tare', 'blend_shipped', 'agent_name', 'seal_number', 'escort', 'output_packages', 'output_weight', 'packet_tare', 'agent_id', 'transporter_id', 'id_number', 'station_id', 'station_name', 'standard_details')
            ->where('blend_sheets.status', '<', 4)
            ->orWhere('blend_sheets.status', null)
            ->whereNull('blend_teas.deleted_at')
            ->whereNull('blend_sheets.deleted_at')
            ->latest('blend_sheets.created_at')
            ->get();

        if ($id == 5){
            return Excel::download(new ExportInternalTransfers($internal), 'PENDING INTERNAL TRANSFERS'.' '.time().'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        }elseif ($id == 6){
            return Excel::download(new ExportExternalTransfer($external), 'EXTERNAL TRANSFERS'.' '.time().'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        }elseif ($id == 7){
            return Excel::download(new ExportShippingInstructions($si), 'SHIPPING INSTRUCTION'.' '.time().'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        }elseif($id == 8){
            return Excel::download(new ExportBlendSheet($blend), 'BLEND SHEETS'.' '.time().'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        }
    }
    public function viewOurLocations()
    {
        $locations = WarehouseLocation::latest()->get();
        return view('admin::warehouses.locations')->with('locations', $locations);
    }
    public function registerLocation(Request $request)
    {
        $request->validate([
            'location' => 'string|required'
        ]);
        $location = [
            'location_id' =>  (new CustomIds())->generateId(),
            'location_name' => $request->location,
            'location_address' => $request->address,
            'status' => 1
        ];
        WarehouseLocation::create($location);
        $this->logger->create();
        return back()->with('success', 'Location added successfully');
    }
    public function updateLocation (Request $request, $id)
    {
        $request->validate([
            'location' => [
                'required',
                'string',
                Rule::unique('warehouse_locations', 'location_name')->ignore($id, 'location_id'),
            ],
        ]);
        $location = [
            'location_name' => $request->location,
            'location_address' => $request->address,
            'status' => 1
        ];
        WarehouseLocation::where('location_id', $id)->update($location);
        $this->logger->create();
        return back()->with('success', 'Location updated successfully');
    }
    /*public function manageStock (){
        // Assuming 'tea_id' is the column to check against in all tables

        $stockInTeas = StockIn::join('delivery_orders', 'delivery_orders.delivery_id', '=', 'stock_ins.delivery_id')
            ->join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
            ->join('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
            ->join('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
            ->join('user_infos', 'user_infos.user_id', '=', 'stock_ins.user_id');

        $transferredTeas = Transfers::select('stock_id')->pluck('stock_id')->toArray();
        $externalTransferredTeas = ExternalTransfer::select('stock_id')->pluck('stock_id')->toArray();
        $blendedTeas = BlendTea::select('stock_id')->pluck('stock_id')->toArray();
        $shippedTeas = Shipment::select('stock_id')->pluck('stock_id')->toArray();

        $excludedTeas = array_merge($transferredTeas, $externalTransferredTeas, $blendedTeas, $shippedTeas);

        $teas = $stockInTeas->whereNull('delivery_orders.deleted_at')
            ->whereNotIn('stock_id', $excludedTeas)
            ->select('delivery_type', 'stock_id', 'client_name', 'garden_name', 'grade_name', 'invoice_number', 'total_pallets', 'net_weight', 'surname', 'first_name', 'order_number', 'lot_number')
            ->orderBy('client_name', 'asc')
            ->orderBy('stock_ins.created_at', 'desc')
            ->get();

        return view('admin::stock.manageStock')->with(['teas' => $teas]);

    }*/

    /*public function deleteMultipleTeas(Request $request){
        $toDelete = StockIn::whereIn('stock_id', $request->stockId);
        DeliveryOrder::whereIn('delivery_id', $toDelete->pluck('delivery_id')->toArray())->delete();
        $toDelete->delete();
        return back()->with('success','Success! Selected teas deleted successfully');
    }*/
    public function deleteTea($id){
        StockIn::where('stock_id', $id)->delete();
        $this->logger->create();
        return back()->with('success','Success! Selected tea deleted successfully');
    }
    public function withdrawSample($id)
    {
        $stock = DB::table('currentstock')->where('stock_id', $id)->first();
        return view('admin::stock.withdrawSample')->with('data', $stock);
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
                return redirect()->route('admin.teaSamplesRequest')->with('success', 'Success! Sample request created successfully');
            } catch (\Exception $e) {
                // Rollback the transaction if an exception occurs
                DB::rollback();
                // Handle or log the exception
                return redirect()->back()->with('error', 'Oops! An error occurred please try again');
            }
        }else{
            return back()->with('error', 'Oops! Sample weight cannot be more than weight of one bag of tea. Try again');
        }
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
        return view('admin::stock.teaSamples')->with('samples', $samples);
    }
    public function allArchivedTeas()
    {
            $stocks = StockIn::onlyTrashed()
                    ->join('delivery_orders', 'delivery_orders.delivery_id', '=', 'stock_ins.delivery_id')
                    ->join('clients', 'clients.client_id', '=', 'delivery_orders.client_id')
                    ->join('gardens', 'gardens.garden_id', '=', 'delivery_orders.garden_id')
                    ->join('grades', 'grades.grade_id', '=', 'delivery_orders.grade_id')
                    ->orWhere(function($query) {
                        $query->whereNotNull('stock_ins.deleted_at')
                            ->orWhereNotNull('delivery_orders.deleted_at');
                    })
                    ->where('delivery_orders.status', 2)
                    ->select(
                        'stock_id',
                        'client_name',
                        'order_number',
                        'invoice_number',
                        'lot_number',
                        'garden_name',
                        'grade_name',
                        'total_pallets',
                        'stock_ins.total_weight',
                        'delivery_orders.delivery_type'
                    )
                    ->get();

       return view('admin::stock.archivedTeas')->with(['stocks' => $stocks]);
    }
    public function restoreArchivedTea($id)
    {
        StockIn::withTrashed()->where('stock_id', $id)->restore();
        DeliveryOrder::withTrashed()->where('delivery_id', StockIn::where('stock_id', $id)->first()->delivery_id)->restore();
        if(LoadingInstruction::withTrashed()->where('delivery_id', StockIn::where('stock_id', $id)->first()->delivery_id)->first()){
        LoadingInstruction::withTrashed()->where('delivery_id', StockIn::where('stock_id', $id)->first()->delivery_id)->first()->restore();
        }
        $this->logger->create();
        return redirect()->route('admin.editStock', $id)->with('success', 'Success! Tea restored Proceed to update');
    }
    public function viewReportRequest ()
    {
        $requests = ReportRequest::leftJoin('clients', 'clients.client_id', '=', 'report_requests.client_id')->select('report_requests.*', 'clients.client_name')->orderBy('service_number', 'desc')->get();
        $clients = Client::latest()->get();
        return view('admin::reports.index')->with(['requests' => $requests, 'clients' => $clients]);
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
            $collection = DeliveryOrder::where(['client_id' => $request->idClient])->orderBy('invoice_number', 'asc')->get()->groupBy('invoice_number');
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
    public function approveReportRequest($id)
    {
        ReportRequest::where(['request_id' => $id])->update(['status' => 1, 'approved_by' => auth()->user()->user_id]);
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Report request has been approved');
    }
    public function downloadReportRequest ($id)
    {
        return $this->AppClass->downloadVerifiedReport($id);
    }
    public function deleteReportRequest($id)
    {
        ReportRequest::find($id)->delete();
        $this->logger->create();
        return redirect()->back()->with('success', 'Success! Report Request has been deleted.');
    }
    public function selectStation(Request $request)
    {
        $warehouseId = $request->input('stationId');
        $data = Station::whereNot('station_id', $warehouseId)->where('status', 1)->get();
        return response()->json($data);
    }
    public function stockAgingReport()
    {
        $agingAnalysis = DB::table('currentstock')
            ->select(
                'client_id',
                'client_name',
                DB::raw('SUM(CASE WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(date_received)) <= 30 THEN current_stock ELSE 0 END) AS stock_0_30'),
                DB::raw('SUM(CASE WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(date_received)) <= 30 THEN current_weight ELSE 0 END) AS weight_0_30'),

                DB::raw('SUM(CASE WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(date_received)) BETWEEN 31 AND 90 THEN current_stock ELSE 0 END) AS stock_31_90'),
                DB::raw('SUM(CASE WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(date_received)) BETWEEN 31 AND 90 THEN current_weight ELSE 0 END) AS weight_31_90'),

                DB::raw('SUM(CASE WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(date_received)) BETWEEN 91 AND 180 THEN current_stock ELSE 0 END) AS stock_91_180'),
                DB::raw('SUM(CASE WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(date_received)) BETWEEN 91 AND 180 THEN current_weight ELSE 0 END) AS weight_91_180'),

                DB::raw('SUM(CASE WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(date_received)) BETWEEN 181 AND 365 THEN current_stock ELSE 0 END) AS stock_181_365'),
                DB::raw('SUM(CASE WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(date_received)) BETWEEN 181 AND 365 THEN current_weight ELSE 0 END) AS weight_181_365'),

                DB::raw('SUM(CASE WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(date_received)) > 365 THEN current_stock ELSE 0 END) AS stock_more_than_1yr'),
                DB::raw('SUM(CASE WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(date_received)) > 365 THEN current_weight ELSE 0 END) AS weight_more_than_1yr'),

                // Add total weight column
                DB::raw('SUM(current_weight) AS total_weight'),
                DB::raw('SUM(current_stock) AS total_stock')
            )
            ->where('current_stock', '>', 0)
            ->groupBy('client_id', 'client_name')
            ->orderBy('total_weight', 'desc')
            ->get();

        return view('admin::reports.agingAnalysis')->with(['clients' => $agingAnalysis]);
    }
    public function clientStock($id)
    {
        $agingAnalysis = DB::table('currentstock')
            ->select(
                'client_id',
                'client_name',
                'invoice_number',
                'order_number',
                'grade_name',
                'garden_name',
                DB::raw('CASE WHEN delivery_type = 1 THEN "DO ENTRY" ELSE "DIRECT DEL" END AS delivery'),
                DB::raw('SUM(current_stock) AS current_stock'), // Sum of current_stock
                DB::raw('SUM(current_weight) AS current_weight'), // Sum of current_weight
                DB::raw('DATE_FORMAT(FROM_UNIXTIME(MIN(date_received)), "%Y-%m-%d") AS min_date_received'), // Minimum date_received
                DB::raw('CASE
                    WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(MIN(date_received))) <= 30 THEN "0-30"
                    WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(MIN(date_received))) BETWEEN 31 AND 90 THEN "31-90"
                    WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(MIN(date_received))) BETWEEN 91 AND 180 THEN "91-180"
                    WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(MIN(date_received))) BETWEEN 181 AND 365 THEN "181-365"
                    WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(MIN(date_received))) > 365 THEN "365+"
                    ELSE "Unknown"
                END AS aging_period')
            )
            ->where('current_stock', '>', 0)
            ->where(['client_id' => $id])
            ->groupBy('client_id', 'client_name', 'invoice_number', 'grade_name', 'garden_name', 'delivery_type', 'order_number') // Group by necessary fields
            ->orderBy('min_date_received', 'desc') // Order by the minimum date_received
            ->get();

        return view('admin::reports.clientStock')->with(['stocks' => $agingAnalysis]);
    }
    public function downloadStockAgingReport(Request $request)
    {
        $agingAnalysis = DB::table('currentstock')
            ->select(
                'client_id',
                'client_name',
                DB::raw('SUM(CASE WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(date_received)) <= 30 THEN current_stock ELSE 0 END) AS stock_0_30'),
                DB::raw('SUM(CASE WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(date_received)) <= 30 THEN current_weight ELSE 0 END) AS weight_0_30'),

                DB::raw('SUM(CASE WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(date_received)) BETWEEN 31 AND 90 THEN current_stock ELSE 0 END) AS stock_31_90'),
                DB::raw('SUM(CASE WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(date_received)) BETWEEN 31 AND 90 THEN current_weight ELSE 0 END) AS weight_31_90'),

                DB::raw('SUM(CASE WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(date_received)) BETWEEN 91 AND 180 THEN current_stock ELSE 0 END) AS stock_91_180'),
                DB::raw('SUM(CASE WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(date_received)) BETWEEN 91 AND 180 THEN current_weight ELSE 0 END) AS weight_91_180'),

                DB::raw('SUM(CASE WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(date_received)) BETWEEN 181 AND 365 THEN current_stock ELSE 0 END) AS stock_181_365'),
                DB::raw('SUM(CASE WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(date_received)) BETWEEN 181 AND 365 THEN current_weight ELSE 0 END) AS weight_181_365'),

                DB::raw('SUM(CASE WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(date_received)) > 365 THEN current_stock ELSE 0 END) AS stock_more_than_1yr'),
                DB::raw('SUM(CASE WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(date_received)) > 365 THEN current_weight ELSE 0 END) AS weight_more_than_1yr'),

                // Add total weight column
                DB::raw('SUM(current_weight) AS total_weight'),
                DB::raw('SUM(current_stock) AS total_stock')
            )
            ->where('current_stock', '>', 0)
            ->groupBy('client_id', 'client_name')
            ->orderBy('total_weight', 'desc');

                if ($request->clientId !== null){
                        $agingAnalysis->where('client_id', $request->clientId);
                }

                $stocks = $agingAnalysis->get();

                if ($request->reportType == 2){
                    return Excel::download(new ExportAgingStock($stocks), 'AGING REPORT'.' '.time().'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
                }

        ini_set('memory_limit', '10000M');
        ini_set('max_execution_time', 30000);

        $date = date('D, d-m-Y, h:i:s');
        $printed = auth()->user()->user;
        $by = $printed->first_name.' '.$printed->surname;

        $domPdfPath = base_path('vendor/dompdf/dompdf');
        \PhpOffice\PhpWord\Settings::setPdfRendererPath($domPdfPath);
        \PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF');

        $headers = ['size' => 8, 'name' => 'New Times Roman', 'bold' => true, 'space' => ['after' => 50, 'before' => 100]];
        $text = ['size' => 7, 'name' => 'New Times Roman', 'bold' => false, 'space' => ['after' => 40, 'before' => 100]];

        $table = new Table(['unit' => \PhpOffice\PhpWord\SimpleType\TblWidth::PERCENT, 'width' => 100 * 50, 'align' => Jc::CENTER]);

        $table->addRow();
        $table->addCell(600, ['borderSize' => 1])->addText('#', $headers, ['space' => ['before' => 100]]);
        $table->addCell(2000, ['borderSize' => 1])->addText('Client Name', $headers, ['space' => ['before' => 100]]);
        $table->addCell(1000, ['borderSize' => 1])->addText('0-30 Days', $headers, ['space' => ['before' => 100]]);
        $table->addCell(1000, ['borderSize' => 1])->addText('31-90 Days', $headers, ['space' => ['before' => 100]]);
        $table->addCell(1000, ['borderSize' => 1])->addText('91-180 Days', $headers, ['space' => ['before' => 100]]);
        $table->addCell(1000, ['borderSize' => 1])->addText('181-365 Days', $headers, ['space' => ['before' => 100]]);
        $table->addCell(1000, ['borderSize' => 1])->addText('365+ Days', $headers, ['space' => ['before' => 100]]);
        $table->addCell(1500, ['borderSize' => 1])->addText('Net Weight (Package)', $headers, ['space' => ['before' => 100]]);

        foreach ($stocks as $key => $stock){
            $table->addRow();
            $table->addCell(600, ['borderSize' => 1])->addText(++$key, $text, ['space' => ['before' => 100]]);
            $table->addCell(2000, ['borderSize' => 1])->addText($stock->client_name, $text, ['setNoWrap' => true, 'space' => ['before' => 100]]);
            $table->addCell(1000, ['borderSize' => 1])->addText(number_format($stock->weight_0_30, 2), $text, ['setNoWrap' => true, 'space' => ['before' => 100]]);
            $table->addCell(1000, ['borderSize' => 1])->addText(number_format($stock->weight_31_90, 2), $text, ['setNoWrap' => true, 'space' => ['before' => 100]]);
            $table->addCell(1000, ['borderSize' => 1])->addText(number_format($stock->weight_91_180, 2), $text, ['space' => ['before' => 100]]);
            $table->addCell(1000, ['borderSize' => 1])->addText(number_format($stock->weight_181_365, 2), $text, ['space' => ['before' => 100]]);
            $table->addCell(1000, ['borderSize' => 1])->addText(number_format($stock->weight_more_than_1yr, 2), $text, ['space' => ['before' => 100]]);
            $table->addCell(1500, ['borderSize' => 1])->addText(number_format($stock->total_weight, 2).' ('.number_format($stock->total_stock, 0).')', $text, ['space' => ['before' => 100]]);
        }

        $stock = new TemplateProcessor(storage_path('client_aging_stock_template.docx'));
        $stock->setComplexBlock('{table}', $table);
        $stock->setValue('date', $date);
        $stock->setValue('by', $by);
        $docPath = 'Files/TempFiles/STOCK AGING'.time().'.docx';
        $stock->saveAs($docPath);

        $phpWord = IOFactory::load($docPath);
        $phpWord = IOFactory::load($docPath);
        $pdfPath = 'Files/TempFiles/STOCK AGING'.time(). ".pdf";
        $converter =  new OfficeConverter($docPath, 'Files/TempFiles/');
        $converter->convertTo('STOCK AGING'.time().".pdf");
        unlink($docPath);
        return response()->download($pdfPath)->deleteFileAfterSend(true);

    }
    public function downloadClientStockAgingReport(Request $request, $id)
    {
        $agingAnalysis = DB::table('currentstock')
            ->select(
                'client_id',
                'client_name',
                'invoice_number',
                'order_number',
                'grade_name',
                'garden_name',
                DB::raw('CASE WHEN delivery_type = 1 THEN "DO ENTRY" ELSE "DIRECT DEL" END AS delivery'),
                DB::raw('SUM(current_stock) AS current_stock'), // Sum of current_stock
                DB::raw('SUM(current_weight) AS current_weight'), // Sum of current_weight
                DB::raw('DATE_FORMAT(FROM_UNIXTIME(MIN(date_received)), "%Y-%m-%d") AS min_date_received'), // Minimum date_received
                DB::raw('CASE
                    WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(MIN(date_received))) < 30 THEN "0-30"
                    WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(MIN(date_received))) BETWEEN 31 AND 90 THEN "31-90"
                    WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(MIN(date_received))) BETWEEN 91 AND 180 THEN "91-180"
                    WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(MIN(date_received))) BETWEEN 181 AND 365 THEN "181-365"
                    WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(MIN(date_received))) > 365 THEN "365+"
                    ELSE "Unknown"
                END AS aging_period'),
                'loading_number'
            )
            ->where('current_stock', '>', 0)
            ->where(['client_id' => $id])
            ->groupBy('client_id', 'client_name', 'invoice_number', 'grade_name', 'garden_name', 'delivery_type', 'order_number', 'loading_number') // Group by necessary fields
            ->orderBy('min_date_received', 'desc'); // Order by the minimum date_received

        if ($request->period !== null) {
            $thirtyDay = Carbon::today()->subDays(30)->getTimestamp();
            $nintyDay = Carbon::today()->subDays(90)->getTimestamp();
            $oneEightyDay = Carbon::today()->subDays(180)->getTimestamp();
            $threeSixFiveDay = Carbon::today()->subDays(365)->getTimestamp();

            if ($request->period == 1) {
                // <30
                $agingAnalysis = $agingAnalysis->where('date_received', '>=', $thirtyDay);  // Records received within the last 30 days
            } elseif ($request->period == 2) {
                // 31-90
                $agingAnalysis = $agingAnalysis->where('date_received', '>=', $nintyDay)
                    ->where('date_received', '<', $thirtyDay); // Between 31 and 90 days ago
            } elseif ($request->period == 3) {
                // 91-180
                $agingAnalysis = $agingAnalysis->where('date_received', '>=', $oneEightyDay)
                    ->where('date_received', '<', $nintyDay); // Between 91 and 180 days ago
            } elseif ($request->period == 4) {
                // 181-365
                $agingAnalysis = $agingAnalysis->where('date_received', '>=', $threeSixFiveDay)
                    ->where('date_received', '<', $oneEightyDay); // Between 181 and 365 days ago
            } else {
                // >365
                $agingAnalysis = $agingAnalysis;  // Records older than 365 days
            }
        }

        $stocks = $agingAnalysis->get();

        if ($request->reportType == 2){
            return Excel::download(new ExportClientAgingStock($stocks), 'AGING REPORT'.' '.time().'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        }

        ini_set('memory_limit', '10000M');
        ini_set('max_execution_time', 30000);

        $date = date('D, d-m-Y, h:i:s');
        $printed = auth()->user()->user;
        $by = $printed->first_name.' '.$printed->surname;

        $domPdfPath = base_path('vendor/dompdf/dompdf');
        \PhpOffice\PhpWord\Settings::setPdfRendererPath($domPdfPath);
        \PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF');

        $headers = ['size' => 8, 'name' => 'New Times Roman', 'bold' => true, 'space' => ['after' => 50, 'before' => 100]];
        $text = ['size' => 7, 'name' => 'New Times Roman', 'bold' => false, 'space' => ['after' => 40, 'before' => 100]];

        $table = new Table(['unit' => \PhpOffice\PhpWord\SimpleType\TblWidth::PERCENT, 'width' => 100 * 50, 'align' => Jc::CENTER]);

            $table->addRow();
            $table->addCell(null, ['gridSpan' => 11])->addText('CLIENT NAME : '.$stocks[0]->client_name, $headers);
            $table->addRow();
            $table->addCell(600, ['borderSize' => 1])->addText('#', $headers, ['space' => ['before' => 100]]);
            $table->addCell(1200, ['borderSize' => 1])->addText('Invoice #', $headers, ['space' => ['before' => 100]]);
            $table->addCell(1200, ['borderSize' => 1])->addText('Delivery Type', $headers, ['space' => ['before' => 100]]);
            $table->addCell(1300, ['borderSize' => 1])->addText('Order Number', $headers, ['space' => ['before' => 100]]);
            $table->addCell(1500, ['borderSize' => 1])->addText('Garden Name', $headers, ['space' => ['before' => 100]]);
            $table->addCell(1000, ['borderSize' => 1])->addText('Grade', $headers, ['space' => ['before' => 100]]);
            $table->addCell(1000, ['borderSize' => 1])->addText('Pkgs', $headers, ['space' => ['before' => 100]]);
            $table->addCell(1200, ['borderSize' => 1])->addText('Net Weight', $headers, ['space' => ['before' => 100]]);
            $table->addCell(1000, ['borderSize' => 1])->addText('TCI #', $headers, ['space' => ['before' => 100]]);
            $table->addCell(1100, ['borderSize' => 1])->addText("Date Rec'd", $headers, ['space' => ['before' => 100]]);
            $table->addCell(1200, ['borderSize' => 1])->addText('Aging Period', $headers, ['space' => ['before' => 100]]);

            $totalPackets = 0;
            $totalWeight = 0;

            foreach ($stocks as $key => $stock){
                $table->addRow();
                $table->addCell(600, ['borderSize' => 1])->addText(++$key, $text, ['space' => ['before' => 100]]);
                $table->addCell(1200, ['borderSize' => 1])->addText($stock->invoice_number, $text, ['setNoWrap' => true, 'space' => ['before' => 100]]);
                $table->addCell(1200, ['borderSize' => 1])->addText($stock->delivery, $text, ['setNoWrap' => true, 'space' => ['before' => 100]]);
                $table->addCell(1300, ['borderSize' => 1])->addText($stock->order_number, $text, ['setNoWrap' => true, 'space' => ['before' => 100]]);
                $table->addCell(1500, ['borderSize' => 1])->addText($stock->garden_name, $text, ['space' => ['before' => 100]]);
                $table->addCell(1000, ['borderSize' => 1])->addText($stock->grade_name, $text, ['space' => ['before' => 100]]);
                $table->addCell(1000, ['borderSize' => 1])->addText(number_format($stock->current_stock, 0), $text, ['space' => ['before' => 100]]);
                $table->addCell(1200, ['borderSize' => 1])->addText(number_format($stock->current_weight, 2), $text, ['space' => ['before' => 100]]);
                $table->addCell(1000, ['borderSize' => 1])->addText($stock->loading_number, $text, ['space' => ['before' => 100]]);
                $table->addCell(1100, ['borderSize' => 1])->addText($stock->min_date_received, $text, ['space' => ['before' => 100]]);
                $table->addCell(1200, ['borderSize' => 1])->addText($stock->aging_period.' Days', $text, ['space' => ['before' => 100]]);

                $totalPackets += $stock->current_stock;
                $totalWeight += $stock->current_weight;
            }

            $table->addRow();
            $table->addCell(6550, ['gridSpan' => 6])->addText('');
            $table->addCell(1000, ['borderSize' => 1])->addText(number_format($totalPackets, 0), $headers, ['space' => ['before' => 100]]);
            $table->addCell(1200, ['borderSize' => 1])->addText(number_format($totalWeight, 2), $headers, ['space' => ['before' => 100]]);
            $table->addCell(4700, ['gridSpan' => 3])->addText('');

        $stock = new TemplateProcessor(storage_path('aging_stock_template.docx'));
        $stock->setComplexBlock('{table}', $table);
        $stock->setValue('date', $date);
        $stock->setValue('by', $by);
        $docPath = 'Files/TempFiles/ANGING REPORT'.time().'.docx';
        $stock->saveAs($docPath);

        $phpWord = IOFactory::load($docPath);
        $pdfPath = 'Files/TempFiles/ANGING REPORT'.time(). ".pdf";
        $converter =  new OfficeConverter($docPath, 'Files/TempFiles/');
        $converter->convertTo('ANGING REPORT'.time().".pdf");

        unlink($docPath);
        return response()->download($pdfPath)->deleteFileAfterSend(true);
    }
    public function stockPerWarehouse()
    {
        $data = DB::table('currentstock')
            ->join('stations', 'stations.station_id', '=', 'currentstock.station_id')
            ->select('currentstock.station_id', 'station_name',
                DB::raw('SUM(current_stock) as total_packages'),
                DB::raw('SUM(current_weight) as total_weight'))
            ->groupBy('station_id', 'station_name')
            ->where('current_stock', '>', 0)
            ->get();

// Compute the overall totals
        $overallTotals = [
            'packages' => $data->sum('total_packages'),
            'weight' => $data->sum('total_weight'),
        ];

// Calculate the percentage for each warehouse
        $results = $data->map(function ($item) use ($overallTotals) {
            $item->percentage_weight = $overallTotals['weight'] > 0
                ? number_format(($item->total_weight / $overallTotals['weight']) * 100, 2)
                : 0;
            return $item;
        });

// Sort the results by percentage_weight in descending order
        $sortedResults = $results->sortByDesc('percentage_weight');

// If needed, reindex the collection
        $sortedResults = $sortedResults->values();

        return view('admin::reports.stockPerWarehouse')->with(['warehouses' => $sortedResults]);

    }
    public function clientStockPerWarehouse($id)
    {
        $stocks = DB::table('currentstock')
            ->select('client_id', 'client_name', 'lot_number', 'invoice_number', 'current_stock', 'current_weight', 'bay_name', 'loading_number', 'date_received', 'stocked_at', 'station_id')
            ->where(['station_id' => $id])
            ->where('current_stock', '>', 0)
            ->orderBy('client_name', 'asc')
            ->orderBy('bay_name', 'asc')
            ->get();

        return view('admin::reports.clientStockPerWarehouse')->with(['stocks' => $stocks, 'stationNames' => [], 'percentageWeights' => []]);
    }

    public function getClientStockData($id)
    {
        $stocks = DB::table('currentstock')
            ->select('client_name',
                DB::raw('SUM(current_stock) as total_stock'),
                DB::raw('SUM(current_weight) as total_weight'))
            ->where('station_id', $id)
            ->where('current_stock', '>', 0)
            ->groupBy('client_name')
            ->orderBy('client_name', 'asc')
            ->get();

        return response()->json($stocks);
    }

    public function getStockDataByBay($id)
    {
        $stocks = DB::table('currentstock')
            ->select('bay_name',
                DB::raw('SUM(current_stock) as total_stock'),
                DB::raw('SUM(current_weight) as total_weight'))
            ->where('station_id', $id)
            ->where('current_stock', '>', 0)
            ->groupBy('bay_name')
            ->orderBy('bay_name', 'asc')
            ->get();

        return response()->json($stocks);
    }

    public function getClientStockPerWarehouse($id)
    {
        $stocks = DB::table('currentstock')
            ->select('stocked_at',
                DB::raw('SUM(current_stock) as total_stock'),
                DB::raw('SUM(current_weight) as total_weight'))
            ->where('client_id', $id)
            ->where('current_stock', '>', 0)
            ->groupBy('stocked_at')
            ->orderBy('stocked_at', 'asc')
            ->get();

        return response()->json($stocks);
    }

    public function getClientStockAgingReport($id)
    {
        $stocks = DB::table('currentstock')
            ->select(
                DB::raw('CASE
                WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(date_received)) <= 30 THEN "0-30"
                WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(date_received)) BETWEEN 31 AND 90 THEN "31-90"
                WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(date_received)) BETWEEN 91 AND 180 THEN "91-180"
                WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(date_received)) BETWEEN 181 AND 365 THEN "181-365"
                WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(date_received)) > 365 THEN "365+"
                ELSE "Unknown"
            END AS aging_period'),
                DB::raw('SUM(current_stock) as total_stock'),
                DB::raw('SUM(current_weight) as total_weight')
            )
            ->where('client_id', $id)
            ->where('current_stock', '>', 0)
            ->groupBy(DB::raw('CASE
            WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(date_received)) <= 30 THEN "0-30"
            WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(date_received)) BETWEEN 31 AND 90 THEN "31-90"
            WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(date_received)) BETWEEN 91 AND 180 THEN "91-180"
            WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(date_received)) BETWEEN 181 AND 365 THEN "181-365"
            WHEN DATEDIFF(CURDATE(), FROM_UNIXTIME(date_received)) > 365 THEN "365+"
            ELSE "Unknown"
        END'))
            ->orderBy(DB::raw('FIELD(aging_period, "0-30", "31-90", "91-180", "181-365", "365+")'))
            ->get();

        return response()->json($stocks);
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

        return view('admin::reports.TCI.index')->with(['tcis' => $tcis]);

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
        return view('admin::reports.TCI.locationWise')->with(['teas' => $teas]);
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

        return view('admin::shipping.rebagging')->with(['bags' => $bags]);
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

        return view('admin::shipping.prepareRebagging')->with(['teas' => $teas, 'data' => $data]);
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
        return redirect()->route('admin.viewRebaggedTeas', $id)->with('success', 'Rebagging request submitted successfully');
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

        return view('admin::shipping.viewRebagging')->with(['teas' => $teas, 'data' => $data, 'lines' => $lines]);
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
    public function viewDepartments ()
    {
        $departments = Department::join('users', 'users.user_id', '=', 'departments.user_id')
            ->select('department_id', 'department_name', 'dept_code', 'departments.status', 'username')
            ->orderBy('departments.created_at', 'DESC')->get();
        return view('admin::users.departments', compact('departments'));
    }
    public function storeDepartment(Request $request)
    {
        $request->validate([
           'department' => 'required|unique:departments,department_name',
        ]);
        Department::create([
            'department_id' => (new CustomIds())->generateId(),
            'department_name' => $request->department,
            'dept_code' => $request->code,
            'status' => 1,
            'user_id' => auth()->user()->user_id
        ]);
        $this->logger->create();
        return back()->with('success', 'Department added successfully');
    }
    public function updateDepartment(Request $request, $id)
    {
        $request->validate([
            'department' => 'required|unique:departments,department_name,'.$id.',department_id'
        ]);
        Department::where(['department_id' => $id])->update([
            'department_name' => $request->department,
            'dept_code' => $request->code,
            'status' => $request->status
        ]);
        $this->logger->create();
        return back()->with('success', 'Department update successfully');
    }
    public function deleteDepartment($id)
    {
        Department::where(['department_id' => $id])->delete();
        $this->logger->create();
        return back()->with('success', 'Department deleted successfully');
    }
    public function viewSignatories()
    {
        $signatories = Signatory::join('users', 'users.user_id', '=', 'signatories.user_id')
            ->join('user_infos', 'user_infos.user_id', '=', 'users.user_id')
            ->join('departments', 'departments.department_id', '=', 'signatories.department_id')
            ->join('users as creator', 'creator.user_id', '=', 'signatories.created_by')
            ->select('signatory_id', DB::raw("CONCAT(COALESCE(surname, ' '),' ', COALESCE(first_name, '')) as full_name"), 'department_name', 'signature', 'creator.username', 'signatories.status', 'departments.department_id', 'signatories.user_id')
            ->orderBy('signatories.created_at', 'desc')
            ->get();
        $departments = Department::all();
        $users = UserInfo::join('users', 'users.user_id', 'user_infos.user_id')->where('status', 1)->get();
        return view('admin::users.signatories', compact('departments', 'signatories', 'users'));
    }

    public function storeSignatory(Request $request)
    {
        $request->validate([
           'signatory' => 'required',
           'department' => 'required',
            'signature' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        $file = $request->file('signature');
        $fileName = Str::uuid() . '.png';

        $image = Image::read($file);

//            $image->greyscale();

        $image->trim(15, 'top-left');

        $image->resize(300, 100, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize(); // prevents enlarging small sigs
        });

        $encoded = $image->encode(new PngEncoder());

        Storage::disk('signatures')->put($fileName, (string) $encoded);

        Signatory::create([
            'signatory_id' => (new CustomIds())->generateId(),
            'user_id' => $request->signatory,
            'department_id' => $request->department,
            'created_by' => auth()->user()->user_id,
            'status' => 1,
            'signature' => $fileName
        ]);
        $this->logger->create();
        return back()->with('success', 'Success! Signatory added successfully');
    }
    public function updateSignatory(Request $request, $id)
    {
        if ($request->hasFile('signature')) {
            $file = $request->file('signature');
            $fileName = Str::uuid() . '.png';

            $image = Image::read($file);

//            $image->greyscale();

            $image->trim(15, 'top-left');

            $image->resize(300, 100, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize(); // prevents enlarging small sigs
            });

            $encoded = $image->encode(new PngEncoder());

            Storage::disk('signatures')->put($fileName, (string) $encoded);

            Signatory::where(['signatory_id' => $id])->update([
                'user_id' => $request->signatory,
                'department_id' => $request->department,
                'status' => $request->status,
                'signature' => $fileName
            ]);
        }

        Signatory::where(['signatory_id' => $id])->update([
            'user_id' => $request->signatory,
            'department_id' => $request->department,
            'status' => $request->status,
        ]);
        $this->logger->create();
        return back()->with('success', 'Success! Signatory updated successfully');
    }
    public function deleteSignatory($id)
    {
        Signatory::where('signatory_id', $id)->delete();
        $this->logger->create();
        return back()->with('success', 'Success! Signatory deleted successfully');
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
            'delivery_orders.grade_id',
            'delivery_orders.delivery_id',
            'clients.client_name',
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

    if ($request->filled('client')) {
        $query->where('delivery_orders.client_id', $request->client);
    }
    $auctions = $query->orderBy('auctions.created_at', 'desc')->get();

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

$clients = Client::join('delivery_orders', 'delivery_orders.client_id', '=', 'clients.client_id')
    ->join('stock_ins', 'stock_ins.delivery_id', '=', 'delivery_orders.delivery_id')
    ->join('auctions', 'auctions.stock_id', '=', 'stock_ins.stock_id')
    ->select('clients.client_id', 'clients.client_name')
    ->distinct()
    ->orderBy('client_name')
    ->get();

    return view('admin::auctions.auctions', compact(
        'auctions',
        'gardens',
        'grades',
        'brokers',
        'buyers',
        'warehouses',
        'sales',
        'clients'
    ));
}

/**
 * Handle export logic for Excel, CSV, PDF
 */
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
                        $row->sale,
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
        return view('admin::auctions.index', compact('auctions', 'clients'));
    }
    public function viewSale($id)
    {
        $sale = base64_decode($id);
        $teas = Auction::join('currentstock', function ($join) use ($sale) {
            $join->on('currentstock.stock_id', '=', 'auctions.stock_id')
                ->on('currentstock.delivery_id', '=', 'auctions.delivery_id');
        })
            ->join('stock_ins', function ($join){
                $join->on('currentstock.stock_id', '=', 'stock_ins.stock_id');
            })
            ->join('brokers', 'brokers.broker_id', '=', 'auctions.broker_id')
            ->leftJoin('clients', 'clients.client_id', '=', 'auctions.client_id')
            ->leftJoin('warehouses', 'warehouses.warehouse_id', '=', 'auctions.warehouse_id')
            ->select('auction_id', 'currentstock.client_name', 'warrant_number', 'stock_ins.total_pallets as current_stock', 'stock_ins.net_weight as current_weight', 'auctions.status', 'clients.client_name as buyer_name', 'brokers.broker_name', 'invoice_number', 'garden_name', 'grade_name', 'order_number', 'auctions.client_id', 'auctions.broker_id', 'sale', 'auctions.sale_date', 'auctions.prompt_date', 'auctions.warehouse_id', 'warehouses.warehouse_name', 'release_date')
            ->where('auctions.sale', $sale)
            ->orderBy('warrant_number')
            ->get();
        $clients = Client::all();
        $brokers = Broker::all();
        $warehouses = Warehouse::all();
        return view('admin::auctions.viewSale', compact('teas', 'sale', 'clients', 'brokers', 'warehouses'));
    }
    public function prepareAuctionList(Request $request)
    {
        $teas = DB::table('currentstock')
            ->leftJoin('auctions', function ($join) {
                $join->on('currentstock.delivery_id', '=', 'auctions.delivery_id')
                    ->on('currentstock.stock_id', '=', 'auctions.stock_id');
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
        return view('admin::auctions.prepareAuctionList', compact('teas', 'client', 'brokers'));
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
        return redirect()->route('admin.teaAuction')->with('success', 'Auction added successfully');
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
        return view('admin::shipping.editSI')->with(['ports' => $ports, 'stations' => $stations, 'clients' => $clients, 'si' => $si, 'siTeas' => $siTeas]);
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
        return redirect()->route('admin.addShipmentTeas', $id)->with('success', 'Success! Shipping instruction created successfully');
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
        return view('admin::shipping.editBlend')->with(['ports' => $ports, 'stations' => $stations, 'clients' => $clients, 'sheet' => $sheet, 'blendTeas' => $blendTeas]);
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
        return redirect()->route('admin.addBlendTeas', $id)->with('success', 'Success! Blend sheet updated successfully');
    }

    public function downloadSIPackingList($id)
    {
        return $this->AppClass->downloadSIPackingList($id);
    }
    public function downloadSIContinuedPackingList($id)
    {
        return $this->AppClass->downloadSIContinuedPackingList($id);
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

        return view('admin::DOS.foreign_teas')->with(['orders' => $orders]);
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

    public function userPermissions()
    {
        $users = User::join('user_infos', 'user_infos.user_id', '=', 'users.user_id')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->whereBetween('role_id', [2, 9])
            ->where(['status' => 1])
            ->orderBy('users.created_at', 'asc')
            ->get();
        return view('admin::users.userPermissions', compact('users'));
    }

    public function userPermission($id)
    {
        $user = User::join('user_infos', 'user_infos.user_id', '=', 'users.user_id')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->where(['users.user_id' => $id])
            ->first();
        $categories = Permission::orderBy('created_at')->get()->groupBy('category');
        return view('admin::users.userPermission', compact('user', 'categories'));
    }

    public function usersPermissionsToggle(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'permission_id' => 'required',
            'checked' => 'required|boolean',
        ]);

        if ($request->checked) {
            UserPermission::firstOrCreate([
                'user_id' => $request->user_id,
                'permission_id' => $request->permission_id,
            ]);
        } else {
            UserPermission::where([
                'user_id' => $request->user_id,
                'permission_id' => $request->permission_id,
            ])->delete();
        }

        return response()->json(['status' => 'success']);
    }

}
