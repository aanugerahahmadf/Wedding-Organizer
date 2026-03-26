<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Topup;
use App\Models\Withdrawal;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\TopupStatus;
use App\Enums\WithdrawalStatus;
use Illuminate\Console\Command;
use Carbon\Carbon;

class DailySummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:daily-summary';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Displays a summary of daily transactions and statuses.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        $this->info("Transaction Summary for {$today->toDateString()}");
        $this->newLine();

        $this->sectionTitle('ORDERS (Hari Ini)');
        $this->table(['Status', 'Count', 'Total (Rp)'], [
            ['Pending', Order::whereDate('created_at', $today)->where('status', OrderStatus::PENDING)->count(), number_format(Order::whereDate('created_at', $today)->where('status', OrderStatus::PENDING)->sum('total_price'), 2)],
            ['Confirmed', Order::whereDate('created_at', $today)->where('status', OrderStatus::CONFIRMED)->count(), number_format(Order::whereDate('created_at', $today)->where('status', OrderStatus::CONFIRMED)->sum('total_price'), 2)],
            ['Completed', Order::whereDate('created_at', $today)->where('status', OrderStatus::COMPLETED)->count(), number_format(Order::whereDate('created_at', $today)->where('status', OrderStatus::COMPLETED)->sum('total_price'), 2)],
        ]);
        $this->newLine();

        $this->sectionTitle('PAYMENTS (Diterima Hari Ini)');
        $successPaymentsCount = Payment::whereDate('paid_at', $today)->where('status', PaymentStatus::SUCCESS)->count();
        $successPaymentsTotal = Payment::whereDate('paid_at', $today)->where('status', PaymentStatus::SUCCESS)->sum('total_amount');
        $this->info("Total Pembayaran Berhasil: {$successPaymentsCount} (Rp " . number_format($successPaymentsTotal, 2) . ")");
        $this->newLine();

        $this->sectionTitle('TOPUPS & WITHDRAWALS');
        $this->table(['Type', 'Count', 'Total (Rp)'], [
            ['Topup (Berhasil)', Topup::whereDate('paid_at', $today)->where('status', TopupStatus::SUCCESS)->count(), number_format(Topup::whereDate('paid_at', $today)->where('status', TopupStatus::SUCCESS)->sum('total_amount'), 2)],
            ['Withdrawal (Selesai)', Withdrawal::whereDate('updated_at', $today)->where('status', WithdrawalStatus::COMPLETED)->count(), number_format(Withdrawal::whereDate('updated_at', $today)->where('status', WithdrawalStatus::COMPLETED)->sum('amount'), 2)],
        ]);
    }

    private function sectionTitle($title)
    {
        $this->line('<fg=cyan>' . str_pad(" $title ", 60, "=", STR_PAD_BOTH) . '</>');
    }
}
