<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AnalyticService
{
    public function getAnalytics(): array
    {
        return [
            [
                'total_revenue' => DB::selectOne('select sum(quantity * price) as total_revenue from orders')->total_revenue,
                'top_products_by_sales' => collect(DB::select('
                    select p.name, sum(o.quantity) as total_sales
                    from products p
                    join orders o on p.id = o.product_id
                    group by p.id
                    order by total_sales desc
                    limit 5'
                ))->map(function ($product) {
                    return [
                        'name' => $product->name,
                        'total_sales' => $product->total_sales,
                    ];
                }),
                'revenue_changes_in_last_1_minute' => DB::selectOne('
                    select sum(quantity * price) as revenue
                    from orders
                    where date >= datetime("now", "-1 minute")'
                )->revenue,
                'count_orders_in_last_1_minute' => DB::selectOne('
                    select count(*) as count_orders
                    from orders
                    where date >= datetime("now", "-1 minute")'
                )->count_orders,
            ],
        ];
    }
}
