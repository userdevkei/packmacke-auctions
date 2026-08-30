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

            ['name' => 'Edit Delivery Order', 'key' => 'do.edit', 'category' => 'delivery order'],
            ['name' => 'Delete Delivery Order', 'key' => 'do.delete', 'category' => 'delivery order'],

/*            ['name' => 'Access Inventory', 'key' => 'inventory.access', 'category' => 'inventory'],
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
            ['name' => 'Delete Suppliers', 'key' => 'supplier.delete', 'category' => 'inventory'],*/

            ['name' => 'Create Straight Line Jobs', 'key' => 'straightline.create', 'category' => 'straight line'],
            ['name' => 'Update Straight Line - Knock off tea', 'key' => 'straightline.update', 'category' => 'straight line'],
            ['name' => 'Delete Straight Line Jobs', 'key' => 'straightline.delete', 'category' => 'straight line'],
            ['name' => 'Edit Straight Line Jobs', 'key' => 'straightline.edit', 'category' => 'straight line'],
            ['name' => 'Amend Straight Line Teas', 'key' => 'straightline.amend', 'category' => 'straight line'],
            ['name' => 'Add Missing Lines to Straight Line', 'key' => 'straightline.addmissinglines', 'category' => 'straight line'],
            ['name' => 'First Approval Straight Line', 'key' => 'straightline.approve', 'category' => 'straight line'],
            ['name' => 'Final Approval', 'key' => 'straightline.finalapproval', 'category' => 'straight line'],
            ['name' => 'Amend Transport/Logistics Details', 'key' => 'straightline.amendtransportdetails', 'category' => 'straight line'],

            ['name' => 'Create Blend Sheet', 'key' => 'blend.create', 'category' => 'blend sheets'],
            ['name' => 'Add Teas To Blend Sheet', 'key' => 'blend.addblendteas', 'category' => 'blend sheets'],
            ['name' => 'Update Blend Sheet - Knock off teas', 'key' => 'blend.updateblend', 'category' => 'blend sheets'],
            ['name' => 'Delete Blend Sheet', 'key' => 'blend.delete', 'category' => 'blend sheets'],
            ['name' => 'Edit Blend Sheet', 'key' => 'blend.edit', 'category' => 'blend sheets'],
            ['name' => 'Amend Blend Teas', 'key' => 'blend.amend', 'category' => 'blend sheets'],
            ['name' => 'Amend Blend Outturn Report', 'key' => 'blend.amendOutturn', 'category' => 'blend sheets'],
            ['name' => 'First Blend Sheets Approval', 'key' => 'blend.approve', 'category' => 'blend sheets'],
            ['name' => 'Final Blend Sheet Approval', 'key' => 'blend.finalblendsheetapproval', 'category' => 'blend sheets'],
            ['name' => 'Amend Transport/Logistics Details', 'key' => 'blend.amendtransportdetails', 'category' => 'blend sheets'],
            ['name' => 'Approve/Decline Container Details' , 'key' => 'blend.container.approve', 'category' => 'blend sheets'],
            ['name' => 'Mark Blend as Shipped', 'key' => 'blend.markasshipped', 'category' => 'blend sheets'],

            ['name' => 'Create Rebag Job', 'key' => 'rebag.create', 'category' => 'rebag job'],
            ['name' => 'Add Teas To Rebag', 'key' => 'rebag.addblendteas', 'category' => 'rebag job'],
            ['name' => 'Update Rebag Job - Knock off teas', 'key' => 'rebag.updateblend', 'category' => 'rebag job'],
            ['name' => 'Delete Rebag Job', 'key' => 'rebag.delete', 'category' => 'rebag job'],
            ['name' => 'Edit Rebag Job', 'key' => 'rebag.edit', 'category' => 'rebag job'],
            ['name' => 'Amend Rebag Teas', 'key' => 'rebag.amend', 'category' => 'rebag job'],
            ['name' => 'Amend Rebag Outturn Report', 'key' => 'rebag.amendOutturn', 'category' => 'rebag job'],
            ['name' => 'First Rebag Job Approval', 'key' => 'rebag.approve', 'category' => 'rebag job'],
            ['name' => 'Final Rebag Job Approval', 'key' => 'rebag.finalblendsheetapproval', 'category' => 'rebag job'],
            ['name' => 'Amend Transport/Logistics Details', 'key' => 'rebag.amendtransportdetails', 'category' => 'rebag job'],
            ['name' => 'Mark Rebag as Shipped', 'key' => 'rebag.markasshipped', 'category' => 'rebag job'],
            ['name' => 'Approve/Decline Container Details' , 'key' => 'rebag.container.approve', 'category' => 'rebag job'],


            ['name' => 'Create Internal Transfers', 'key' => 'transfer.internal.create', 'category' => 'internal transfer'],
            ['name' => 'Receive Internal Transfers', 'key' => 'transfer.internal.receive', 'category' => 'internal transfer'],
            ['name' => 'First Approval (Operations)', 'key' => 'transfer.internal.approve', 'category' => 'internal transfer'],
            ['name' => 'Final Approval (Stock Controller)', 'key' => 'transfer.internal.approve.final', 'category' => 'internal transfer'],
            ['name' => 'Release Internal Transfers', 'key' => 'transfer.internal.release', 'category' => 'internal transfer'],


            ['name' => 'Create External Transfers', 'key' => 'transfer.external.create', 'category' => 'external transfer'],
            ['name' => 'First Approval', 'key' => 'transfer.external.approve', 'category' => 'external transfer'],
            ['name' => 'Second Transfer Approval', 'key' => 'transfer.external.approve.final', 'category' => 'external transfer'],
            ['name' => 'Release Transfer', 'key' => 'transfer.external.release', 'category' => 'external transfer'],

        ];

        foreach ($permissions as $perm) {
            Permission::updateOrCreate(['key' => $perm['key']], $perm);
        }
    }
}
