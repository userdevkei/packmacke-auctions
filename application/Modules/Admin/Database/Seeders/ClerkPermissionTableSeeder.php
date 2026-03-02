<?php

namespace Modules\Admin\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Admin\Entities\Permission;

class ClerkPermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
//        Model::unguard();
        $permissions = [
            ['name' => 'Create Internal Transfers', 'key' => 'transfer.internal.create', 'category' => 'stocks'],
            ['name' => 'Receive Internal Transfers', 'key' => 'transfer.internal.receive', 'category' => 'stocks'],
            ['name' => 'Approve Internal Transfers', 'key' => 'transfer.internal.approve', 'category' => 'stocks'],
            ['name' => 'Create External Transfers', 'key' => 'transfer.external.create', 'category' => 'stocks'],
            ['name' => 'Approve External Transfers', 'key' => 'transfer.external.approve', 'category' => 'stocks'],
            ['name' => 'Receive Tea Collection Stocks', 'key' => 'stocks.receive', 'category' => 'stocks'],
            ['name' => 'Create Auction Sale', 'key' => 'auction.create', 'category' => 'auction'],
            ['name' => 'Export Stock In Excel', 'key' => 'export.stock-excel', 'category' => 'stocks'],
            ['name' => 'Update Direct Deliver Transport Details', 'key' => 'direct-deliver-transport-details.update', 'category' => 'stocks'],
            ['name' => 'Update Transporter Details', 'key' => 'transporter-details.update', 'category' => 'stocks'],
            ['name' => 'Issue TCI to Driver', 'key' => 'do.issueToDriver', 'category' => 'stocks'],
            ['name' => 'Mark Foreign Tea Entries Received', 'key' => 'do.entriesReceived', 'category' => 'stocks'],
            ['name' => 'Mark Foreign Tea Entries Validated', 'key' => 'do.entriesValidated', 'category' => 'stocks'],
            ['name' => 'View TCI Report', 'key' => 'tci.view', 'category' => 'stocks'],
            ['name' => 'Edit Stock Item Details', 'key' => 'stock-item.edit', 'category' => 'stocks'],
            ['name' => 'Amend TCI Teas', 'key' => 'amend.teas', 'category' => 'stocks'],
            ['name' => 'Add Direct Delivery Teas', 'key' => 'direct-deliver-teas.add', 'category' => 'stocks'],
            ['name' => 'Release Teas', 'key' => 'external.release', 'category' => 'stocks'],

            ['name' => 'Access Inventory', 'key' => 'inventory.access', 'category' => 'inventory'],
            ['name' => 'View Inventory', 'key' => 'inventory.view', 'category' => 'inventory'],
            ['name' => 'Add Inventory Item', 'key' => 'inventoryItem.add', 'category' => 'inventory'],
            ['name' => 'View Inventory Item', 'key' => 'inventoryItem.view', 'category' => 'inventory'],
            ['name' => 'Edit Inventory Item', 'key' => 'inventory.editItem', 'category' => 'inventory'],
            ['name' => 'Delete Inventory Item', 'key' => 'inventory.deleteItem', 'category' => 'inventory'],
            ['name' => 'View Inventory Item', 'key' => 'inventory.viewItem', 'category' => 'inventory'],
            ['name' => 'Add LPO', 'key' => 'inventory.addLpo', 'category' => 'inventory'],
            ['name' => 'Edit LPO', 'key' => 'inventory.editLpo', 'category' => 'inventory'],
            ['name' => 'Delete LPO', 'key' => 'inventory.deleteLpo', 'category' => 'inventory'],
            ['name' => 'View LPO', 'key' => 'inventory.viewLpo', 'category' => 'inventory'],
            ['name' => 'Approve LPO', 'key' => 'inventory.approveLpo', 'category' => 'inventory'],
            ['name' => 'Add Inventory Items Transfer', 'key' => 'inventory.addItemsTransfer', 'category' => 'inventory'],
            ['name' => 'Edit Inventory Items Transfer', 'key' => 'inventory.editItemsTransfer', 'category' => 'inventory'],
            ['name' => 'Delete Inventory Items Transfer', 'key' => 'inventory.deleteItemsTransfer', 'category' => 'inventory'],
            ['name' => 'View Inventory Items Transfer', 'key' => 'inventory.viewItemsTransfer', 'category' => 'inventory'],
            ['name' => 'Approve Inventory Items Transfer', 'key' => 'inventory.approveItemsTransfer', 'category' => 'inventory'],
            ['name' => 'Add Inventory Items Release', 'key' => 'inventory.addItemsRelease', 'category' => 'inventory'],
            ['name' => 'Edit Inventory Items Release', 'key' => 'inventory.editItemsRelease', 'category' => 'inventory'],
            ['name' => 'Delete Inventory Items Release', 'key' => 'inventory.deleteItemsRelease', 'category' => 'inventory'],
            ['name' => 'View Inventory Items Release', 'key' => 'inventory.viewItemsRelease', 'category' => 'inventory'],
            ['name' => 'Approve Inventory Items Release', 'key' => 'inventory.approveItemsRelease', 'category' => 'inventory'],
            ['name' => 'Add Requisition', 'key' => 'inventory.addRequisition', 'category' => 'inventory'],
            ['name' => 'Edit Requisition', 'key' => 'inventory.editRequisition', 'category' => 'inventory'],
            ['name' => 'Delete Requisition', 'key' => 'inventory.deleteRequisition', 'category' => 'inventory'],
            ['name' => 'View Requisition', 'key' => 'inventory.viewRequisition', 'category' => 'inventory'],
            ['name' => 'Approve Requisition', 'key' => 'inventory.approveRequisition', 'category' => 'inventory'],
            ['name' => 'View Inventory Category', 'key' => 'inventory.viewInventoryCategory', 'category' => 'inventory'],
            ['name' => 'Add Inventory Category', 'key' => 'inventory.addInventoryCategory', 'category' => 'inventory'],
            ['name' => 'Edit Inventory Category', 'key' => 'inventory.editInventoryCategory', 'category' => 'inventory'],
            ['name' => 'Delete Inventory Category', 'key' => 'inventory.deleteInventoryCategory', 'category' => 'inventory'],
            ['name' => 'View Suppliers', 'key' => 'supplier.view', 'category' => 'inventory'],
            ['name' => 'Add Suppliers', 'key' => 'supplier.add', 'category' => 'inventory'],
            ['name' => 'Edit Suppliers', 'key' => 'supplier.edit', 'category' => 'inventory'],
            ['name' => 'Delete Suppliers', 'key' => 'supplier.delete', 'category' => 'inventory'],


            ['name' => 'Create Blend Sheets Jobs', 'key' => 'blend.create', 'category' => 'shipping'],
            ['name' => 'Create Straight Line Jobs', 'key' => 'straightline.create', 'category' => 'shipping'],
            ['name' => 'Update Blend - Knock off teas', 'key' => 'blend.update', 'category' => 'shipping'],
            ['name' => 'Update Straight Line - Knock off tea', 'key' => 'straightline.update', 'category' => 'shipping'],
            ['name' => 'Delete Blend Jobs', 'key' => 'blend.delete', 'category' => 'shipping'],
            ['name' => 'Edit Blend Jobs', 'key' => 'blend.edit', 'category' => 'shipping'],
            ['name' => 'Delete Straight Line Jobs', 'key' => 'straightline.delete', 'category' => 'shipping'],
            ['name' => 'Edit Straight Line Jobs', 'key' => 'straightline.edit', 'category' => 'shipping'],
            ['name' => 'Amend Straight Line Teas', 'key' => 'straightline.amend', 'category' => 'shipping'],
            ['name' => 'Amend Blend Teas', 'key' => 'blend.amend', 'category' => 'shipping'],
            ['name' => 'Amend Blend Outturn Report', 'key' => 'blend.amendOutturn', 'category' => 'shipping'],
            
    

        ];

        foreach ($permissions as $perm) {
            Permission::updateOrCreate(['key' => $perm['key']], $perm);
        }
    }
}
