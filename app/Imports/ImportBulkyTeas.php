<?php

namespace App\Imports;

use App\Models\Client;
use App\Models\DeliveryOrder;
use App\Models\Garden;
use App\Models\Grade;
use App\Models\StockIn;
use App\Models\Warehouse;
use App\Models\WarehouseBay;
use App\Services\CustomIds;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportBulkyTeas implements OnEachRow, WithHeadingRow, WithCalculatedFormulas
{
    use Importable;
    protected $errors = [];
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function onRow(Row $row)
    {
        // ✅ use ->getCalculatedValue() instead of ->toArray()
        $rowArray = [];
        foreach ($row->getDelegate()->getCellIterator() as $cell) {
            $rowArray[strtolower($cell->getColumn())] = $cell->getCalculatedValue();
        }

        // now map your Excel headings
        $row = array_combine(array_keys($row->toArray()), array_values($rowArray));
    

        /*DB::beginTransaction();

        try {*/
            if (
                empty(trim($row['invoice_number'] ?? '')) &&
                empty(trim($row['order_number'] ?? ''))
            ) {
                return; // skip blank row
            }

            $garden  = Garden::where('garden_name', trim($row['garden']))->first();
            $grade   = Grade::where('grade_name', trim($row['grade']))->first();
            $client  = Client::where('client_id', $this->data['client_id'])->first();
            $warehouse = Warehouse::where('warehouse_name', trim($row['producer_warehouse']))->first();
            $bay = WarehouseBay::where(['station_id' => $this->data['station_id'], 'bay_name' => trim($row['warehouse_bay'])])->first();

            $deliveryId = (new CustomIds())->generateId();
            $stockId    = (new CustomIds())->generateId();
            $deliveryType = 2;

            $package = match (trim($row['package'])) {
                'PB' => 1,
                'PS' => 2,
                default => null,
            };

            $teaType = match ($row['tea_type']) {
                'AUCTION TEAS'  => 1,
                'PRIVATE TEAS'  => 2,
                'FACTORY TEAS'  => 3,
                default         => 4,
            };

            // check for duplicates
            $existingOrder = DeliveryOrder::withoutTrashed()
                ->where([
                    'invoice_number' => $row['invoice_number'],
                    'client_id'      => $this->data['client_id'],
                    'garden_id'      => $garden?->garden_id,
                ])->first();

            if ($existingOrder) {
                $this->addError(
                    $row['invoice_number'],
                    "Duplicate invoice for client {$client->client_name}, Invoice: {$row['invoice_number']}"
                );
                return;
            }

            $do = [
                'delivery_id' => $deliveryId,
                'client_id' => $client->client_id,
                'tea_id' => $teaType,
                'garden_id' => $garden->garden_id,
                'grade_id' => $grade->grade_id,
                'order_number' => $row['order_number'],
                'invoice_number' => $row['invoice_number'],
                'package' => $package,
                'packet' => $row['total_packages'],
                'weight' => $row['net_weight'],
                'unit_weight' => $row['total_net_weight'],
                'gross_weight' => $row['gross_weight'],
                'total_weight' => $row['total_gross_weight'],
                'created_by' => auth()->user()->user_id,
                'delivery_type' => $deliveryType,
                'warehouse_id' => $warehouse->warehouse_id,
                'production_date' => $row['production_date'] != null ? Carbon::instance(ExcelDate::excelToDateTimeObject($row['production_date'])) : null,
                'expiry_date' => $row['expiry_date'] != null ? Carbon::instance(ExcelDate::excelToDateTimeObject($row['expiry_date'])) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
//            dd($do);
            DeliveryOrder::create($do);
//            DB::table('delivery_orders')->insert($do);

            $stock = [
                'stock_id' => $stockId,
                'delivery_id' => $deliveryId,
                'station_id' => $this->data['station_id'],
                'date_received' => time(),
                'delivery_number' => $row['delivery_number'],
                'warehouse_bay' => $bay ? $bay->bay_id : $this->data['bay_id'],
                'total_weight' => $row['total_gross_weight'],
                'total_pallets' => $row['total_packages'],
                'pallet_weight' => $row['pallet_weight'],
                'package_tare' => $row['package_tare'] ?? 0,
                'net_weight' => $row['total_net_weight'],
                'user_id' => auth()->user()->user_id,
                'created_at' => now(),
                'updated_at' => now(),
            ];

//            DB::table('stock_ins')->insert($stock);
            StockIn::create($stock);

        /*    DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error importing row: {$e->getMessage()}");
            $this->addError($row['invoice_number'] ?? $row['delivery_number'] ?? '?', $e->getMessage());
        }*/
    }

    protected function addError($rowIndex, $error)
    {
        $this->errors[$rowIndex] = $error;
    }

    public function getErrors()
    {
        return $this->errors;
    }

}

