<?php

namespace NickDeKruijk\Webshop\Commands;

use Illuminate\Console\Command;
use NickDeKruijk\Webshop\Model\Order;
use NickDeKruijk\Webshop\Model\OrderLine;

class ConvertOrderProductsToOrderLines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webshop:orderlines';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Converts the Order->products array to OrderLine model entries.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        foreach (Order::all() as $order) {
            if (!$order->lines->count()) {
                $this->info("Processing order {$order->id}");
                foreach ($order->products as $index => $product) {
                    // dd($product);
                    $this->info("Processing product {$index}");
                    $orderline = new Orderline;
                    $orderline->order_id = $order->id;
                    $orderline->product_id = $product['product_id'] ?? null;
                    $orderline->title = $product['title'];
                    $orderline->quantity = $product['quantity'];
                    $orderline->weight = $product['weight'] ?? null;
                    $orderline->price = $product['price']['price'];
                    $orderline->vat_rate = $product['price']['vat_rate'];
                    $orderline->vat_included = $product['price']['vat_included'];
                    $orderline->save();
                    // dd($orderline);
                }
            }
        }
    }
}
