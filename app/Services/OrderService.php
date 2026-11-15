<?php

namespace App\Services;

use App\Enums\OrderStatusEnum;
use App\Exceptions\OrderException;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderDetail;
use App\Models\DiningTable;
use Illuminate\Support\Facades\Log;

class OrderService
{
    /**
     * Membuat pesanan baru beserta detailnya.
     *
     * @throws OrderException jika meja tidak ada atau sedang dipakai
     */
    public function createOrder(
        string|int|null $tableNumber,
        array $cartItems,
        $totalPrice,
        ?string $contact = null
    ) : CustomerOrder {
        $table = $this->findAvailableTable($tableNumber);

        $this->occupyTable($table);

        $order = $this->saveOrder($table, $totalPrice, $contact);

        $details = $this->saveOrderDetails($order, $cartItems);

        Log::info([$table, $order, $details]);

        return $order;
    }

    private function findAvailableTable(string|int|null $tableNumber) : DiningTable
    {
        $table = DiningTable::where('table_name', $tableNumber)->first();

        if (!$table) {
            throw new OrderException('Table does not exists. Plese enter a correct table number.');
        }

        if ($table->isOccupied) {
            throw new OrderException('Table is taken. Please enter another table number.');
        }

        return $table;
    }

    private function occupyTable(DiningTable $table) : void
    {
        $table->update(['isOccupied' => true]);
    }

    private function saveOrder(DiningTable $table, $totalPrice, ?string $contact) : CustomerOrder
    {
        return CustomerOrder::create([
            'dining_table_id' => $table->id,
            'order_total_price' => $totalPrice,
            'isPaid' => false,
            'order_status' => OrderStatusEnum::Preparing,
            'customer_contact' => $contact,
        ]);
    }

    private function saveOrderDetails(CustomerOrder $order, array $cartItems) : array
    {
        $details = [];

        foreach ($cartItems as $item) {
            $details[] = CustomerOrderDetail::create([
                'order_id' => $order->id,
                'food_id' => $item['id'],
                'quantity' => $item['quantity'],
                'total_price' => $item['eachTotalPrice'],
            ]);
        }

        return $details;
    }
}