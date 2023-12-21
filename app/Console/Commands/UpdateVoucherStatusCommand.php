<?php

namespace App\Console\Commands;
use App\Models\VouchersModel;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class UpdateVoucherStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-voucher-status-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update voucher status to NOT VALID for expired vouchers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now('Asia/Manila'); // Set the timezone to Asia/Manila

        // Update vouchers where expire_at is less than the current time
        VouchersModel::where('expire_at', '<=', $now)->update(['status' => 'NOT VALID']);

        $this->info('Voucher status updated successfully.');

    }
}
